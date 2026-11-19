/*!
 * Módem de audio RAU: pasa bytes de un dispositivo a otro por sonido (radio FM o SSB, cable, o altavoz y micrófono).
 *
 * Cada trama lleva 8 bytes y su número de secuencia en 77 bits, protegidos con el mismo código que FT8:
 * LDPC (174,91) y CRC-14 (de ftx.js). Se transmiten en 8 tonos (8-FSK de fase continua) con tres bloques
 * de sincronía Costas 7×7, como FT8, pero mucho más rápido:
 *   robusto: 50 baudios, 1100–1450 Hz, 1,58 s por trama (≈ 5 bytes/s) · para altavoz y micrófono o señal débil
 *   rápido: 100 baudios, 900–1600 Hz, 0,79 s por trama (≈ 10 bytes/s) · por cable o radio FM con buena señal
 * El receptor escucha los dos modos a la vez, corrige hasta ±150 Hz de desfase (SSB mal sintonizada) y
 * avisa qué tramas faltan para pedir solo esas. El mensaje completo lleva su propio CRC-16.
 * Hasta 512 tramas por mensaje (4 KB).
 *
 * Licencia GPL v2 o posterior, como el tema.
 */
(function(raiz, fabrica){ if(typeof module==='object' && module.exports) module.exports=fabrica(require('./ftx.js')); else raiz.RauModem=fabrica(raiz.RauFTX); })(typeof self!=='undefined'?self:this, function(F){
'use strict';
var FS=8000, MARGEN=150, MAX_TRAMAS=512;
var MODOS={ robusto:{nombre:'robusto', baud:50, f0:1100}, rapido:{nombre:'rapido', baud:100, f0:900} };
// Blanqueo: evita patrones repetidos (muchos ceros) y que una trama se confunda con FT8
var BLANQUEO=[0x5A,0x3C,0x96,0xE1,0x2D,0x78,0xB4,0x1F,0xC3,0x80];
var COSTAS=F.COSTAS8, GRAY=F.GRAY8;

function crc16(b, n){ var c=0xFFFF; for(var i=0;i<n;i++){ c^=b[i]<<8; for(var k=0;k<8;k++) c=c&0x8000?((c<<1)^0x1021)&0xFFFF:(c<<1)&0xFFFF; } return c; }

/* Trama: 9 bits de secuencia, 4 de largo (0–7: última con ese número de bytes; 8: llena; 9: llena y última), 64 de datos */
function empacarTrama(seq, L, datos){
	var pl=new Uint8Array(10), bit=0;
	function pon(v, n){ for(var i=n-1;i>=0;i--){ if((v>>i)&1) pl[bit>>3]|=0x80>>(bit&7); bit++; } }
	pon(seq, 9); pon(L, 4); for(var i=0;i<8;i++) pon(datos[i]||0, 8);
	for(var j=0;j<10;j++) pl[j]^=BLANQUEO[j];
	pl[9]&=0xF8;
	return pl;
}
function desempacarTrama(a){
	var pl=new Uint8Array(10), bit=0; for(var j=0;j<10;j++) pl[j]=a[j]^BLANQUEO[j];
	function lee(n){ var v=0; for(var i=0;i<n;i++){ v=(v<<1)|((pl[bit>>3]>>(7-(bit&7)))&1); bit++; } return v; }
	var seq=lee(9), L=lee(4); if(L>9 && L<14) return null;
	var d=new Uint8Array(8); for(var i=0;i<8;i++) d[i]=lee(8);
	if(L===14) return {seq:seq, tipo:'cabecera', bytes:(d[0]<<8)|d[1], R:d[2], B:d[3], crc:(d[4]<<8)|d[5], datos:d};
	if(L===15) return {seq:seq, tipo:'paridad', datos:d};
	var n=L<8?L:8, ultima=L!==8;
	return {seq:seq, tipo:'datos', datos:d.subarray(0, n), ultima:ultima};
}
/* Bytes → tramas (cargas de 77 bits). Se agrega el CRC-16 del mensaje al final. */
function tramas(bytes){
	var n=bytes.length+2; if(Math.ceil(n/8)>MAX_TRAMAS) throw new Error('El mensaje supera '+(MAX_TRAMAS*8-2)+' bytes');
	var b=new Uint8Array(n); b.set(bytes); var c=crc16(bytes, bytes.length); b[n-2]=c>>8; b[n-1]=c&0xFF;
	var out=[], total=Math.ceil(n/8);
	for(var s=0;s<total;s++){
		var d=b.subarray(s*8, s*8+8), ult=s===total-1;
		out.push(empacarTrama(s, ult ? (d.length===8?9:d.length) : 8, d));
	}
	return out;
}
/* ── Protección entre tramas: Reed-Solomon por borrado sobre GF(256) con matriz de Cauchy ──
   Las tramas de datos van en bloques de B=16; cada bloque lleva R tramas de paridad (2, 4 u 8). Con
   cualesquiera 16 de las 16+R que lleguen se reconstruye el bloque, sin pedir reenvío. Una trama de
   cabecera, al principio y al final, dice el largo del mensaje y la protección usada. */
var GF_EXP=new Uint8Array(512), GF_LOG=new Uint8Array(256);
(function(){ var x=1; for(var i=0;i<255;i++){ GF_EXP[i]=x; GF_LOG[x]=i; x<<=1; if(x&256) x^=0x11D; } for(var j=255;j<512;j++) GF_EXP[j]=GF_EXP[j-255]; })();
function gmul(a, b){ return a&&b ? GF_EXP[GF_LOG[a]+GF_LOG[b]] : 0; }
function ginv(a){ return GF_EXP[255-GF_LOG[a]]; }
function cauchy(j, i){ return ginv((16+j)^i); }       // x_j=16+j, y_i=i: nunca iguales
var BLOQUE=16, PROTECCION={ninguna:0, baja:2, media:4, alta:8};
/* Secuencia completa a transmitir: [cabecera] bloque₀ paridad₀ bloque₁ paridad₁ … [cabecera] */
function plan(bytes, R){
	var datos=tramas(bytes); if(!R) return datos;
	var n=bytes.length+2, b=new Uint8Array(Math.ceil(n/8)*8); b.set(bytes); var c=crc16(bytes, bytes.length); b[n-2]=c>>8; b[n-1]=c&0xFF;
	var cab=empacarTrama(0, 14, [n>>8, n&255, R, BLOQUE, c>>8, c&255, 0, 0]), out=[cab], k=datos.length, p=0;
	for(var bl=0;bl*BLOQUE<k;bl++){
		var ini=bl*BLOQUE, fin=Math.min(k, ini+BLOQUE);
		for(var i=ini;i<fin;i++) out.push(datos[i]);
		for(var j=0;j<R;j++){
			var par=[0,0,0,0,0,0,0,0];
			for(var i2=ini;i2<fin;i2++) for(var q=0;q<8;q++) par[q]^=gmul(cauchy(j, i2-ini), b[i2*8+q]);
			out.push(empacarTrama(p++, 15, par));
		}
	}
	out.push(cab);
	return out;
}
/* Resuelve los datos que faltan de un bloque: filas = ecuaciones de paridad recibidas */
function recuperar(bloque, faltan, paridades){
	var m=faltan.length, A=[], Y=[], j, i, q;
	for(var e=0;e<m;e++){
		var pj=paridades[e], fila=[], y=pj.datos.slice(0, 8);
		for(i=0;i<bloque.k;i++){ var cf=cauchy(pj.j, i); if(faltan.indexOf(i)>=0) fila.push(cf); else for(q=0;q<8;q++) y[q]^=gmul(cf, bloque.d[i][q]); }
		A.push(fila); Y.push(y);
	}
	// Gauss-Jordan en GF(256)
	for(var col=0;col<m;col++){
		var piv=-1; for(var r=col;r<m;r++) if(A[r][col]){ piv=r; break; } if(piv<0) return null;
		var t=A[col]; A[col]=A[piv]; A[piv]=t; t=Y[col]; Y[col]=Y[piv]; Y[piv]=t;
		var iv=ginv(A[col][col]); for(j=0;j<m;j++) A[col][j]=gmul(A[col][j], iv); for(q=0;q<8;q++) Y[col][q]=gmul(Y[col][q], iv);
		for(r=0;r<m;r++) if(r!==col && A[r][col]){ var f=A[r][col]; for(j=0;j<m;j++) A[r][j]^=gmul(f, A[col][j]); for(q=0;q<8;q++) Y[r][q]^=gmul(f, Y[col][q]); }
	}
	return Y;
}
function tonosTrama(pl){
	var cw=F.codificar174(F.agregarCrc(pl)), bit=0, t=[];
	function lee(){ var v=(cw[bit>>3]>>(7-(bit&7)))&1; bit++; return v; }
	for(var k=0;k<79;k++){
		if(k<7) t.push(COSTAS[k]); else if(k>=36 && k<43) t.push(COSTAS[k-36]); else if(k>=72) t.push(COSTAS[k-72]);
		else t.push(GRAY[(lee()<<2)|(lee()<<1)|lee()]);
	}
	return t;
}
function duracion(nTramas, modo){ var m=MODOS[modo]||MODOS.robusto; return 0.5+nTramas*79/m.baud+0.15; }
function cuantasTramas(bytes, proteccion){ return plan(bytes, PROTECCION[proteccion]||0).length; }
/* Audio listo para reproducir. opts: {modo, proteccion:'ninguna'|'baja'|'media'|'alta', solo:[tramas de datos a
   reenviar], desfase:Hz (para pruebas), volumen, perder:[índices del plan que no se transmiten (pruebas)]} */
function sintetizar(bytes, tasa, opts){
	opts=opts||{}; var m=MODOS[opts.modo]||MODOS.robusto, R=PROTECCION[opts.proteccion]||0, ts;
	if(opts.solo && opts.solo.length){ var d=tramas(bytes); ts=d.filter(function(_, i){ return opts.solo.indexOf(i)>=0; }); if(R) ts.unshift(plan(bytes, R)[0]); }
	else ts=plan(bytes, R);
	if(opts.perder) ts=ts.filter(function(_, i){ return opts.perder.indexOf(i)<0; });
	var nsim=tasa/m.baud, df=opts.desfase||0, amp=opts.volumen||0.6, pre=Math.round(0.5*tasa), n=Math.ceil(pre+ts.length*79*nsim+0.15*tasa);
	var y=new Float32Array(n), fase=0, i=0, rampa=Math.round(0.005*tasa);
	// Tono inicial de medio segundo: abre el VOX de la radio y le da tiempo al control de volumen
	var fpre=m.f0+3.5*m.baud+df;
	for(;i<pre;i++){ fase+=2*Math.PI*fpre/tasa; y[i]=amp*Math.sin(fase)*Math.min(1, i/rampa); }
	for(var t=0;t<ts.length;t++){
		var tn=tonosTrama(ts[t]);
		for(var k=0;k<79;k++){
			var f=m.f0+tn[k]*m.baud+df, fin=Math.round(pre+(t*79+k+1)*nsim);
			for(;i<fin;i++){ fase+=2*Math.PI*f/tasa; y[i]=amp*Math.sin(fase); }
		}
	}
	for(var r=0;r<rampa && i-1-r>=0;r++) y[i-1-r]*=r/rampa;
	return y;
}

/* ── Recepción ── */
/* Remuestreo continuo a 8 kHz (ventana sinc), con estado entre bloques */
function Remuestreo(de){
	this.r=de/FS; this.fc=Math.min(1, FS/de); this.W=Math.ceil(12/this.fc); this.buf=new Float32Array(0); this.t=0;
}
Remuestreo.prototype.pasar=function(x){
	var b=new Float32Array(this.buf.length+x.length); b.set(this.buf); b.set(x, this.buf.length);
	var out=[], W=this.W, fc=this.fc;
	while(this.t+W<b.length){
		var c=this.t, i0=Math.floor(c), s=0, ws=0;
		for(var k=i0-W+1;k<=i0+W;k++){ if(k<0) continue; var d=c-k, sn=d===0?1:Math.sin(Math.PI*fc*d)/(Math.PI*fc*d), w=fc*sn*(0.5+0.5*Math.cos(Math.PI*d/W)); s+=b[k]*w; ws+=w; }
		out.push(ws?s/ws:0); this.t+=this.r;
	}
	var corte=Math.max(0, Math.floor(this.t)-W); this.buf=b.slice(corte); this.t-=corte;
	return Float32Array.from(out);
};
/* Un detector por modo: potencias por cuarto de símbolo en cuartos de la separación de tonos */
function Detector(m, alTrama){
	this.m=m; this.alTrama=alTrama; this.nsim=FS/m.baud; this.salto=this.nsim/4;
	var fmin=m.f0-MARGEN, paso=m.baud/4; this.nb=Math.round((7*m.baud+2*MARGEN)/paso)+1; this.fmin=fmin; this.paso=paso;
	this.cos=[]; this.sin=[];
	for(var k=0;k<this.nb;k++){ var c=new Float32Array(this.nsim), s=new Float32Array(this.nsim), w=2*Math.PI*(fmin+k*paso)/FS; for(var n=0;n<this.nsim;n++){ c[n]=Math.cos(w*n); s[n]=Math.sin(w*n); } this.cos.push(c); this.sin.push(s); }
	this.x=new Float32Array(0); this.x0=0;           // muestras pendientes y el índice absoluto de la primera
	this.filas=[]; this.fila0=0;                      // potencias por salto (anillo) y el número de la primera
	this.prox=0; this.hechas=[];                      // próxima fila a evaluar como inicio de trama; decodificadas recientes
	this.LARGO=79*4;
}
Detector.prototype.alimentar=function(x){
	var b=new Float32Array(this.x.length+x.length); b.set(this.x); b.set(x, this.x.length); this.x=b;
	var nsim=this.nsim, salto=this.salto, nb=this.nb;
	while(this.x.length>=nsim){
		var fila=new Float32Array(nb), xx=this.x;
		for(var k=0;k<nb;k++){ var c=this.cos[k], s=this.sin[k], re=0, im=0; for(var n=0;n<nsim;n++){ re+=xx[n]*c[n]; im+=xx[n]*s[n]; } fila[k]=re*re+im*im; }
		this.filas.push(fila); this.x=this.x.subarray(salto); this.x0+=salto;
		// Se evalúa a medida que llegan filas (aunque entre un bloque largo de audio) y después se descarta lo viejo
		if(this.filas.length>=this.LARGO+80){ this.evaluar(); var q=this.filas.length-this.LARGO-40; this.filas.splice(0, q); this.fila0+=q; }
	}
	this.x=this.x.slice(0);
	this.evaluar();
};
Detector.prototype.p=function(fila, k){ return this.filas[fila-this.fila0][k]; };
Detector.prototype.evaluar=function(){
	var ultima=this.fila0+this.filas.length-this.LARGO; // última fila que puede iniciar una trama completa
	if(this.prox<this.fila0) this.prox=this.fila0;
	if(ultima-this.prox<8) return;                     // se evalúa de a dos símbolos
	var cands=[], kmax=this.nb-28;
	for(var t=this.prox;t<ultima;t++){
		for(var k=0;k<kmax;k++){
			var s=0;
			for(var b=0;b<3;b++) for(var j=0;j<7;j++){
				var f=this.filas[t+4*(36*b+j)-this.fila0], tot=0; for(var q=0;q<8;q++) tot+=f[k+4*q];
				s+=tot>0 ? f[k+4*COSTAS[j]]*8/tot : 0;
			}
			s/=21; if(s>2.2) cands.push({t:t, k:k, s:s});
		}
	}
	this.prox=ultima;
	cands.sort(function(a,b){ return b.s-a.s; });
	var intentos=0;
	for(var i=0;i<cands.length && intentos<8;i++){
		var c=cands[i], rep=false;
		for(var h=0;h<this.hechas.length;h++) if(Math.abs(this.hechas[h].t-c.t)<8 && Math.abs(this.hechas[h].k-c.k)<6){ rep=true; break; }
		if(rep) continue;
		intentos++;
		var r=this.decodificar(c); if(!r) continue;
		this.hechas.push({t:c.t, k:c.k}); if(this.hechas.length>40) this.hechas.shift();
		r.modo=this.m.nombre; r.desfase=Math.round(this.fmin+c.k*this.paso-this.m.f0); r.sincronia=Math.round(c.s*10)/10;
		this.alTrama(r);
	}
};
Detector.prototype.decodificar=function(c){
	var L=new Float64Array(174), sp=0, sn=0;
	for(var i=0;i<58;i++){
		var fila=this.filas[c.t+4*(i+(i<29?7:14))-this.fila0], s=[], mx=0, tot=0;
		for(var j=0;j<8;j++){ var v=fila[c.k+4*GRAY[j]]; s[j]=10*Math.log10(1e-12+v); tot+=v; if(v>mx) mx=v; }
		sp+=mx; sn+=(tot-mx)/7;
		L[3*i]=Math.max(s[4],s[5],s[6],s[7])-Math.max(s[0],s[1],s[2],s[3]);
		L[3*i+1]=Math.max(s[2],s[3],s[6],s[7])-Math.max(s[0],s[1],s[4],s[5]);
		L[3*i+2]=Math.max(s[1],s[3],s[5],s[7])-Math.max(s[0],s[2],s[4],s[6]);
	}
	var sum=0, sum2=0; for(var a=0;a<174;a++){ sum+=L[a]; sum2+=L[a]*L[a]; }
	var v=(sum2-sum*sum/174)/174, f=v>0?Math.sqrt(24/v):1; for(var b=0;b<174;b++) L[b]*=f;
	var r=F.ldpc(L, 30); if(r.errores>0) return null;
	var a91=F.empacarBits(r.bits, 91), pl=new Uint8Array(10); for(var q=0;q<10;q++) pl[q]=a91[q]; pl[9]&=0xF8;
	var chk=F.agregarCrc(pl); if(chk[9]!==a91[9] || chk[10]!==a91[10] || chk[11]!==a91[11]) return null;
	var tr=desempacarTrama(pl); if(!tr) return null;
	// SNR aproximada en 2500 Hz: tono contra el promedio de los otros siete (cada uno ve el ruido de un ancho de baudio)
	var rel=sp/Math.max(1e-12, sn)-1; tr.snr=Math.round(10*Math.log10(Math.max(1e-3, rel))-10*Math.log10(2500/this.m.baud));
	return tr;
};

/* Receptor: junta las tramas de un mensaje, reconstruye con la paridad lo que se pueda, avisa qué falta y
 * entrega los bytes cuando está completo y el CRC cuadra.
 * alEstado({recibidas, total, faltan, recuperadas, ultima:{seq, snr, modo, desfase}}) · alMensaje(Uint8Array) · alError(texto) */
function crearReceptor(tasa, alEstado, alMensaje, alError){
	var rem=tasa===FS?null:new Remuestreo(tasa), partes, paridad, cab, total, recibidas, recuperadas, snrs;
	function reiniciar(){ partes={}; paridad={}; cab=null; total=0; recibidas=0; recuperadas=0; snrs=[]; }
	reiniciar();
	function totalDatos(){ return cab ? Math.ceil(cab.bytes/8) : total; }
	function faltan(){
		var k=totalDatos(), hasta=k||(Math.max.apply(null, Object.keys(partes).map(Number).concat([-1]))+1), f=[];
		for(var i=0;i<hasta;i++) if(!partes[i]) f.push(i);
		if(!cab || !cab.R) return f;
		// Con paridad, solo falta lo que no alcanza a reconstruirse en su bloque
		return f.filter(function(i){ var bl=Math.floor(i/BLOQUE), n=f.filter(function(x){ return Math.floor(x/BLOQUE)===bl; }).length, p=0;
			for(var j=0;j<cab.R;j++) if(paridad[bl*cab.R+j]) p++; return n>p; });
	}
	function intentarRecuperar(){
		if(!cab || !cab.R) return;
		var k=totalDatos(), n=cab.bytes, B=cab.B||BLOQUE;
		for(var bl=0;bl*B<k;bl++){
			var ini=bl*B, kb=Math.min(k, ini+B)-ini, falt=[], d=[], i;
			for(i=0;i<kb;i++){ var x=partes[ini+i]; if(!x) falt.push(i); var r=new Uint8Array(8); if(x) r.set(x); d.push(r); }
			if(!falt.length) continue;
			var ps=[]; for(var j=0;j<cab.R && ps.length<falt.length;j++){ var pq=paridad[bl*cab.R+j]; if(pq) ps.push({j:j, datos:pq}); }
			if(ps.length<falt.length) continue;
			var Y=recuperar({k:kb, d:d}, falt, ps); if(!Y) continue;
			falt.forEach(function(fi, e){ var g=ini+fi, largo=Math.min(8, n-g*8); partes[g]=new Uint8Array(Y[e]).slice(0, largo); recuperadas++; });
		}
	}
	function alTrama(t){
		snrs.push(t.snr); if(snrs.length>20) snrs.shift();
		if(t.tipo==='cabecera'){ if(cab && (cab.bytes!==t.bytes || cab.crc!==t.crc)) reiniciar(); cab={bytes:t.bytes, R:t.R, B:t.B, crc:t.crc}; }
		else if(t.tipo==='paridad'){ if(paridad[t.seq]) return; paridad[t.seq]=t.datos; recibidas++; }
		else { if(partes[t.seq]) return; partes[t.seq]=new Uint8Array(t.datos); recibidas++; if(t.ultima) total=t.seq+1; }
		intentarRecuperar();
		var fal=faltan(), k=totalDatos(), completo=k && Object.keys(partes).filter(function(x){ return +x<k; }).length===k;
		if(alEstado) alEstado({recibidas:recibidas, total:k, faltan:fal, recuperadas:recuperadas, proteccion:cab?cab.R:null, sugerencia:sugerir(t.modo), ultima:{seq:t.seq, tipo:t.tipo||'datos', snr:t.snr, modo:t.modo, desfase:t.desfase}});
		if(completo){
			var n=0, i; for(i=0;i<k;i++) n+=partes[i].length;
			var b=new Uint8Array(n), o=0; for(i=0;i<k;i++){ b.set(partes[i], o); o+=partes[i].length; }
			reiniciar();
			if(n<2 || crc16(b, n-2)!==((b[n-2]<<8)|b[n-1])){ if(alError) alError('El mensaje llegó completo pero no cuadra la suma de verificación (¿se mezclaron dos envíos?). Pide que lo repitan.'); return; }
			alMensaje(b.slice(0, n-2));
		}
	}
	/* Sugerencia para quien transmite, con la SNR mediana de lo último recibido */
	function sugerir(modo){
		if(snrs.length<3) return null;
		var m=snrs.slice().sort(function(a,b){ return a-b; })[snrs.length>>1];
		if(modo==='robusto' && m>=0) return {modo:'rapido', snr:m, texto:'La señal llega bien (SNR '+m+' dB): se puede usar la velocidad rápida.'};
		if(modo==='rapido' && m<-3) return {modo:'robusto', snr:m, texto:'La señal llega justa (SNR '+m+' dB): conviene la velocidad robusta.'};
		if(m<-7) return {modo:'robusto', proteccion:'alta', snr:m, texto:'Señal débil (SNR '+m+' dB): velocidad robusta y protección alta.'};
		return {modo:modo, snr:m, texto:'SNR '+m+' dB: la velocidad actual está bien.'};
	}
	var dets=[new Detector(MODOS.robusto, alTrama), new Detector(MODOS.rapido, alTrama)];
	return {
		alimentar:function(x){ var y=rem?rem.pasar(x):x; if(y.length) dets.forEach(function(d){ d.alimentar(y); }); },
		reiniciar:reiniciar, faltan:faltan
	};
}

return { MODOS:MODOS, PROTECCION:PROTECCION, tramas:tramas, plan:plan, cuantasTramas:cuantasTramas, sintetizar:sintetizar, duracion:duracion, crearReceptor:crearReceptor, crc16:crc16, MAX_BYTES:MAX_TRAMAS*8-2 };
});
