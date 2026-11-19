/*!
 * Decodificadores RTTY, PSK31 y SSTV para el receptor del sitio (audio de un canal, en tiempo real).
 *
 *   RauDigi.rtty(tasa, alTexto, alInfo)       RTTY 45,45 baudios, 170 Hz, Baudot (ITA2/US-TTY), 1,5 de parada.
 *                                              Encuentra solo el par de tonos y la polaridad.
 *   RauDigi.psk31(tasa, alTexto, alInfo)      BPSK31 con varicode; se engancha a la portadora más fuerte y
 *                                              la sigue (AFC).
 *   RauDigi.sstv(tasa, alImagen, alInfo)      SSTV con encabezado VIS: Martin 1 y 2, Scottie 1 y 2, Robot 36.
 *                                              Corrige la inclinación con los pulsos de sincronismo.
 * Cada uno devuelve {procesar(Float32Array), reiniciar()}. Para pruebas: RauDigi.sintetizar.{rtty,psk31,sstv}.
 *
 * Referencias: ITA2 (UIT-T S.2) y la variante US-TTY; varicode de PSK31 (Peter Martinez G3PLX);
 * tiempos SSTV de «Image Communication on Short Waves» (JL Barber N7CXI) y del código de QSSTV.
 * Licencia GPL v2 o posterior, como el tema.
 */
(function(raiz, fabrica){ if(typeof module==='object' && module.exports) module.exports=fabrica(); else raiz.RauDigi=fabrica(); })(typeof self!=='undefined'?self:this, function(){
'use strict';

/* ── Utilidades ── */
function fft(re, im){ // radix 2 in situ
	var n=re.length, i, j=0, k;
	for(i=1;i<n;i++){ var b=n>>1; for(;j&b;b>>=1) j^=b; j^=b; if(i<j){ var t=re[i]; re[i]=re[j]; re[j]=t; t=im[i]; im[i]=im[j]; im[j]=t; } }
	for(var len=2;len<=n;len<<=1){ var a=-2*Math.PI/len, wr=Math.cos(a), wi=Math.sin(a);
		for(i=0;i<n;i+=len){ var cr=1, ci=0; for(k=0;k<len/2;k++){ var ur=re[i+k], ui=im[i+k], vr=re[i+k+len/2]*cr-im[i+k+len/2]*ci, vi=re[i+k+len/2]*ci+im[i+k+len/2]*cr;
			re[i+k]=ur+vr; im[i+k]=ui+vi; re[i+k+len/2]=ur-vr; im[i+k+len/2]=ui-vi; var t2=cr*wr-ci*wi; ci=cr*wi+ci*wr; cr=t2; } } }
}
/* Espectro de potencia (ventana de Hann) de las últimas n muestras de un anillo */
function espectro(anillo, pos, n){
	var re=new Float64Array(n), im=new Float64Array(n), L=anillo.length;
	for(var i=0;i<n;i++){ var w=0.5-0.5*Math.cos(2*Math.PI*i/n); re[i]=w*anillo[(pos-n+i+L)%L]; }
	fft(re, im); var p=new Float64Array(n/2); for(var k=0;k<n/2;k++) p[k]=re[k]*re[k]+im[k]*im[k];
	return p;
}
function mediana(a){ var b=Array.prototype.slice.call(a).sort(function(x,y){ return x-y; }); return b[b.length>>1]||1e-12; }
/* Media móvil compleja de largo fijo */
function Promedio(n){ this.n=Math.max(1, Math.round(n)); this.r=new Float64Array(this.n); this.i=new Float64Array(this.n); this.k=0; this.sr=0; this.si=0; }
Promedio.prototype.paso=function(xr, xi){ this.sr+=xr-this.r[this.k]; this.si+=xi-this.i[this.k]; this.r[this.k]=xr; this.i[this.k]=xi; this.k=(this.k+1)%this.n; return [this.sr/this.n, this.si/this.n]; };

/* ── RTTY ── */
var LTRS=['\0','E','\n','A',' ','S','I','U','\r','D','R','J','N','F','C','K','T','Z','L','W','H','Y','P','Q','O','B','G','','M','X','V',''];
var FIGS=['\0','3','\n','-',' ','\'','8','7','\r','$','4','\x07',',','!',':','(','5','"',')','2','#','6','0','1','9','?','&','','.','/',';',''];
function Uart(tasa, invertir){ this.sps=tasa/45.45; this.inv=invertir; this.estado=0; this.t=0; this.prev=1; this.bits=0; this.n=0; this.figs=false; this.texto=''; this.puntos=0; this.recientes=[]; }
Uart.prototype.bit=function(d){ var b=d>0?1:0; return this.inv?1-b:b; };
Uart.prototype.muestra=function(d){
	var b=this.bit(d);
	if(this.estado===0){ if(this.prev===1 && b===0){ this.estado=1; this.t=0; } this.prev=b; return; }
	this.t++;
	var sps=this.sps, k=this.estado;
	// k: 1 = esperando el centro del bit de arranque, 2..6 = bits de datos, 7 = parada
	if(this.t>=(k-0.5)*sps){
		if(k===1){ if(b!==0){ this.estado=0; this.prev=b; return; } this.bits=0; this.n=0; this.estado=2; return; }
		if(k<7){ this.bits|=b<<this.n; this.n++; this.estado++; return; }
		// Parada: tiene que ser marca
		this.estado=0; this.prev=b;
		if(b!==1){ this.punto(-2); return; }
		var c=this.bits;
		if(c===31){ this.figs=false; this.punto(1); return; }
		if(c===27){ this.figs=true; this.punto(1); return; }
		var ch=(this.figs?FIGS:LTRS)[c];
		if(ch===' ') this.figs=false; // «unshift on space», como la mayoría de los equipos
		if(ch==='\r' || ch==='\0' || ch==='\x07') { this.punto(1); return; }
		this.texto+=ch; this.punto(/[A-Z0-9 \n]/.test(ch)?1:0);
		if(this.texto.length>4000) this.texto=this.texto.slice(-3000);
	}
};
Uart.prototype.punto=function(p){ this.recientes.push(p); if(this.recientes.length>40) this.recientes.shift(); this.puntos=this.recientes.reduce(function(a,b){ return a+b; }, 0); };
function rtty(tasa, alTexto, alInfo){
	// Historia de 4 s: al engancharse se repasan los últimos 3 s para no perder el comienzo del mensaje
	var N=4096, H=Math.round(tasa*4), hist=new Float32Array(H), pos=0, total=0, cuenta=0, nivel=0, nivelRuido=0, soloNivel=false, tonos=null, previo=null, fase=0, mk=null, sp=null, u=null, ganador=0, ultTexto='';
	function paso(v){
		fase+=1/tasa;
		// Filtro adaptado de cada tono: mezcla a banda base y promedio de un bit
		var a=2*Math.PI*tonos[1]*fase, b=2*Math.PI*tonos[0]*fase;
		var m1=mk.paso(v*Math.cos(a), -v*Math.sin(a)), s1=sp.paso(v*Math.cos(b), -v*Math.sin(b));
		var em=m1[0]*m1[0]+m1[1]*m1[1], es=s1[0]*s1[0]+s1[1]*s1[1], d=(em-es)/(em+es+1e-12);
		// Silenciador: sin señal (energía muy por debajo de la de los tonos) la línea queda en reposo (marca)
		var en=em+es; nivel=Math.max(en, nivel*0.99998);
		if(soloNivel) return;
		var calla=en<0.12*nivel;
		u[0].muestra(calla?1:d); u[1].muestra(calla?-1:d);   // en reposo: marca para cada polaridad
	}
	function sintonizar(){
		var p=espectro(hist, pos, N), df=tasa/N, med=mediana(p), mejor=0, f1=0, d=Math.round(170/df);
		for(var k=Math.round(300/df);k<Math.round(2900/df)-d;k++){ var a=Math.max(p[k-1],p[k],p[k+1]), b=Math.max(p[k+d-1],p[k+d],p[k+d+1]), m=Math.min(a,b); if(m>mejor){ mejor=m; f1=k*df; } }
		if(mejor<12*med){ if(alInfo && !tonos) alInfo('Buscando una señal RTTY (dos tonos separados 170 Hz)…'); previo=null; return; }
		// Afinado: centroide de cada tono (±4 bins)
		function centro(f){ var k0=Math.round(f/df), a=0, b=0; for(var k=k0-4;k<=k0+4;k++){ a+=p[k]; b+=p[k]*k; } return b/a*df; }
		var fs=centro(f1), fm=centro(f1+170); f1=(fs+fm-170)/2; nivelRuido=med;
		if(previo!==null && Math.abs(previo-f1)<12 && (!tonos || Math.abs(tonos[0]-f1)>30)){
			tonos=[f1, f1+170]; var sps=tasa/45.45, n=Math.min(total, Math.round(tasa*3)), r;
			mk=new Promedio(sps); sp=new Promedio(sps); u=[new Uart(tasa,false), new Uart(tasa,true)]; nivel=0;
			soloNivel=true; for(r=n;r>0;r--) paso(hist[(pos-r+H)%H]); soloNivel=false;
			mk=new Promedio(sps); sp=new Promedio(sps); fase=0;
			for(r=n;r>0;r--) paso(hist[(pos-r+H)%H]);
		}
		previo=f1;
	}
	return {
		procesar:function(x){
			for(var i=0;i<x.length;i++){
				hist[pos]=x[i]; pos=(pos+1)%H; total++;
				if(tonos) paso(x[i]);
				if(++cuenta>=tasa/2){ cuenta=0; sintonizar(); if(tonos && alInfo) alInfo('RTTY 45,45 Bd · marca '+Math.round(tonos[1])+' Hz · espacio '+Math.round(tonos[0])+' Hz · '+(ganador?'invertido':'normal')); }
			}
			if(!u) return;
			ganador=u[1].puntos>u[0].puntos+4 ? 1 : u[0].puntos>u[1].puntos+4 ? 0 : ganador;
			var t=u[ganador].texto; if(t!==ultTexto){ ultTexto=t; alTexto(t); }
		},
		reiniciar:function(){ tonos=null; previo=null; u=null; ultTexto=''; alTexto(''); }
	};
}

/* ── PSK31 ── */
var VARICODE=['1010101011','1011011011','1011101101','1101110111','1011101011','1101011111','1011101111','1011111101','1011111111','11101111','11101','1101101111','1011011101','11111','1101110101','1110101011',
	'1011110111','1011110101','1110101101','1110101111','1101011011','1101101011','1101101101','1101010111','1101111011','1101111101','1110110111','1101010101','1101011101','1110111011','1011111011','1101111111',
	'1','111111111','101011111','111110101','111011011','1011010101','1010111011','101111111','11111011','11110111','101101111','111011111','1110101','110101','1010111','110101111',
	'10110111','10111101','11101101','11111111','101110111','101011011','101101011','110101101','110101011','110110111','11110101','110111101','111101101','1010101','111010111','1010101111',
	'1010111101','1111101','11101011','10101101','10110101','1110111','11011011','11111101','101010101','1111111','111111101','101111101','11010111','10111011','11011101','10101011',
	'11010101','111011101','10101111','1101111','1101101','101010111','110110101','101011101','101110101','101111011','1010101101','111110111','111101111','111111011','1010111111','101101101',
	'1011011111','1011','1011111','101111','101101','11','111101','1011011','101011','1101','111101011','10111111','11011','111011','1111','111',
	'111111','110111111','10101','10111','101','110111','1111011','1101011','11011111','1011101','111010101','1010110111','110111011','1010110101','1011010111','1110110101'];
var VARI_INV={}; VARICODE.forEach(function(c,i){ VARI_INV[c]=String.fromCharCode(i); });
function psk31(tasa, alTexto, alInfo){
	var N=8192, H=Math.round(tasa*4), hist=new Float32Array(H), pos=0, total=0, cuenta=0, fc=null, previo=null, fase=0, lp=null, sps=tasa/31.25, texto='', bits='', zAnt=null;
	var NF=16, energia=new Float64Array(NF), contador=0, desde=0, prox=0, tau=0, ultTexto='', calidad=0, nivel=0, soloNivel=false;
	function paso(v){
		fase+=2*Math.PI*fc/tasa; if(fase>2*Math.PI) fase-=2*Math.PI;
		var z=lp.paso(v*Math.cos(fase), -v*Math.sin(fase));
		// Reloj de símbolo: la fase de muestreo es la media circular de la energía (de 16 tramos por símbolo);
		// así no salta entre el último tramo y el primero cuando el máximo cae en el borde
		var ph=Math.min(NF-1, Math.floor(contador*NF/sps)), e=z[0]*z[0]+z[1]*z[1];
		nivel=Math.max(e, nivel*0.99998);
		if(soloNivel) return;
		energia[ph]=energia[ph]*0.99+e; contador++; desde++;
		if(contador>=sps){ contador-=sps; var c=0, sn=0; for(var q=0;q<NF;q++){ c+=energia[q]*Math.cos(2*Math.PI*(q+0.5)/NF); sn+=energia[q]*Math.sin(2*Math.PI*(q+0.5)/NF); } tau=((Math.atan2(sn, c)/(2*Math.PI)+1)%1)*sps; }
		// Reloj continuo: el próximo instante de decisión es el anterior más un símbolo, corregido hacia tau con el
		// error envuelto a ±medio símbolo (si no, cuando tau oscila alrededor del borde se salta un símbolo)
		if(desde<prox) return;
		var err=((tau-contador)%sps+1.5*sps)%sps-sps/2;
		prox=sps+Math.max(-sps/8, Math.min(sps/8, err*0.5)); desde=0;
		// Sin señal (muy por debajo del nivel que tuvo) no se decide ni se corrige la frecuencia
		if(e<0.08*nivel){ zAnt=null; bits=''; return; }
		if(zAnt){
			var re=z[0]*zAnt[0]+z[1]*zAnt[1], im=z[1]*zAnt[0]-z[0]*zAnt[1];
			// AFC: el error de fase entre símbolos (módulo 180°) corrige la frecuencia
			var ang=Math.atan2(im, re); if(ang>Math.PI/2) ang-=Math.PI; else if(ang<-Math.PI/2) ang+=Math.PI;
			fc+=ang/(2*Math.PI)*31.25*0.05;
			bits+=re>0?'1':'0';
			if(bits.slice(-2)==='00'){ var c=bits.slice(0,-2); bits=''; if(c && VARI_INV[c]!==undefined){ var ch=VARI_INV[c]; if(ch>=' ' || ch==='\n'){ texto+=ch; if(texto.length>4000) texto=texto.slice(-3000); } } }
			else if(bits.length>14) bits='';
		}
		zAnt=z;
	}
	function sintonizar(){
		// El centro de la señal: la ventana de ±20 Hz con más energía (las inversiones ponen picos a ±15,6 Hz)
		var p=espectro(hist, pos, N), df=tasa/N, med=mediana(p), w=Math.round(20/df), mx=0, k0=0, acum=0, k;
		for(k=Math.round(300/df)-w;k<Math.round(300/df)+w;k++) acum+=p[k];
		for(k=Math.round(300/df);k<Math.round(3000/df);k++){ acum+=p[k+w]-p[k-w]; if(acum>mx){ mx=acum; k0=k; } }
		// Centro de masa para afinar
		var sp2=0, sk=0; for(k=k0-w;k<=k0+w;k++){ sp2+=p[k]; sk+=p[k]*k; } var f=sk/sp2*df;
		calidad=mx/((2*w+1)*med);
		if(calidad<8){ if(alInfo && fc===null) alInfo('Buscando una señal PSK31…'); previo=null; if(fc!==null && calidad<3) fc=null; return; }
		if(previo!==null && Math.abs(previo-f)<4 && (fc===null || Math.abs(fc-f)>12)){
			var n=Math.min(total, Math.round(tasa*3)), r;
			// Primero se mide el nivel de la señal en lo guardado y después se decodifica desde el principio
			fc=f; lp=new Promedio(sps); nivel=0; soloNivel=true; for(r=n;r>0;r--) paso(hist[(pos-r+H)%H]); soloNivel=false;
			fc=f; lp=new Promedio(sps); zAnt=null; energia.fill(0); contador=0; desde=0; prox=0; tau=0; bits='';
			for(r=n;r>0;r--) paso(hist[(pos-r+H)%H]);
		}
		previo=f;
	}
	return {
		procesar:function(x){
			for(var i=0;i<x.length;i++){
				hist[pos]=x[i]; pos=(pos+1)%H; total++;
				if(fc!==null) paso(x[i]);
				if(++cuenta>=tasa/2){ cuenta=0; sintonizar(); if(fc!==null && alInfo) alInfo('PSK31 · '+fc.toFixed(1)+' Hz · señal '+Math.round(10*Math.log10(calidad))+' dB sobre el ruido'); }
			}
			if(texto!==ultTexto){ ultTexto=texto; alTexto(texto); }
		},
		reiniciar:function(){ fc=null; previo=null; texto=''; bits=''; zAnt=null; ultTexto=''; alTexto(''); }
	};
}

/* ── SSTV ── */
// ms: canal de color, línea completa; orden de los canales y dónde está el sincronismo
var SSTV={
	44:{n:'Martin 1', w:320, h:256, tipo:'martin', sync:4.862, porch:0.572, sep:0.572, canal:146.432},
	40:{n:'Martin 2', w:320, h:256, tipo:'martin', sync:4.862, porch:0.572, sep:0.572, canal:73.216},
	60:{n:'Scottie 1', w:320, h:256, tipo:'scottie', sync:9, porch:1.5, sep:1.5, canal:138.240},
	56:{n:'Scottie 2', w:320, h:256, tipo:'scottie', sync:9, porch:1.5, sep:1.5, canal:88.064},
	8: {n:'Robot 36', w:320, h:240, tipo:'robot36', sync:9, porch:3, sep:4.5, canal:88, croma:44, porch2:1.5}
};
function largoLinea(m){
	if(m.tipo==='martin') return m.sync+m.porch+3*m.canal+3*m.sep;
	if(m.tipo==='scottie') return 2*m.sep+2*m.canal+m.sync+m.porch+m.canal;
	return m.sync+m.porch+m.canal+m.sep+m.porch2+m.croma;
}
/* Inicio de cada canal dentro de la línea (ms desde el comienzo de la línea) y posición del sincronismo */
function tramos(m){
	if(m.tipo==='martin'){ var a=m.sync+m.porch; return {sync:0, canales:[[a,m.canal,1],[a+m.canal+m.sep,m.canal,2],[a+2*(m.canal+m.sep),m.canal,0]]}; } // G, B, R
	if(m.tipo==='scottie'){ var b=m.sep; return {sync:2*m.sep+2*m.canal, canales:[[b,m.canal,1],[2*b+m.canal,m.canal,2],[2*m.sep+2*m.canal+m.sync+m.porch,m.canal,0]]}; }
	return {sync:0, canales:[[m.sync+m.porch,m.canal,'Y'],[m.sync+m.porch+m.canal+m.sep+m.porch2,m.croma,'C']]};
}
function valor(f){ return Math.max(0, Math.min(255, (f-1500)/800*255)); }
function sstv(tasa, alImagen, alInfo){
	var H=Math.round(tasa*1.2), fr=new Float32Array(H*8), n=0, fase=0, lp1=new Promedio(tasa/1800), lp2=new Promedio(tasa/1800), zAnt=[1,0];
	var est=null, ultVis=0, msMuestra=1000/tasa;
	function f(i){ return fr[((i%fr.length)+fr.length)%fr.length]; }
	function media(a, b){ var s=0, c=0; for(var i=Math.round(a);i<Math.round(b);i++){ s+=f(i); c++; } return c?s/c:0; }
	var paso=Math.round(tasa*0.005);
	function buscarVis(){
		// Termina en el bit de parada: [guía 1900 ≥ 200 ms][arranque 1200, 30 ms][8 bits de 30 ms][parada 1200, 30 ms]
		var b=tasa*0.03, fin=n, ini=fin-10*b;
		if(Math.abs(media(fin-b, fin)-1200)>90 || Math.abs(media(ini, ini+b)-1200)>90 || Math.abs(media(ini-tasa*0.2, ini)-1900)>70) return null;
		var codigo=0, unos=0;
		for(var k=0;k<8;k++){ var v=media(ini+(k+1)*b+b*0.15, ini+(k+2)*b-b*0.15); if(Math.abs(v-1100)<90){ if(k<7) codigo|=1<<k; unos++; } else if(Math.abs(v-1300)>90) return null; }
		if(unos%2) return null; // paridad par
		return SSTV[codigo] ? codigo : -codigo-1;
	}
	function iniciar(codigo){
		var m=SSTV[codigo], img=new Uint8ClampedArray(m.w*m.h*4); for(var i=3;i<img.length;i+=4) img[i]=255;
		est={m:m, vis:codigo, t0:n, linea:0, periodo:largoLinea(m)*tasa/1000, img:img, cr:null, corr:0};
		if(alInfo) alInfo('SSTV '+m.n+' (VIS '+codigo+') · recibiendo…');
	}
	function lineaLista(){
		var m=est.m, L=est.linea, base=est.t0+L*est.periodo+est.corr, T=tramos(m), k=tasa/1000;
		// Sincronismo: se busca el pulso de 1200 Hz cerca de donde debe estar y se corrige la inclinación
		var sEsp=base+T.sync*k, ancho=m.sync*k, mejor=1e9, at=0;
		for(var d=-Math.round(3*k);d<=Math.round(3*k);d+=Math.max(1,Math.round(k/4))){ var v=media(sEsp+d, sEsp+d+ancho); if(v<mejor){ mejor=v; at=d; } }
		if(mejor<1350 && L>0) { est.corr+=at*0.25; base+=at*0.25; }
		for(var c=0;c<T.canales.length;c++){
			var t=T.canales[c], a=base+t[0]*k, dur=t[1]*k, px=m.w;
			for(var x=0;x<px;x++){
				var v=valor(media(a+x*dur/px, a+(x+1)*dur/px)), o=(L*m.w+x)*4;
				if(t[2]==='Y'){ est.img[o]=est.img[o+1]=est.img[o+2]=v; est.y=est.y||new Float32Array(m.w); est.y[x]=v; }
				else if(t[2]==='C'){ var cr=est.cr||(est.cr=new Float32Array(m.w*2)), par=(L%2===0); cr[(par?0:m.w)+x]=v; }
				else est.img[o+t[2]]=v;
			}
		}
		if(m.tipo==='robot36' && L%2===1){ // color con el par de líneas: R−Y en la par, B−Y en la impar
			for(var x2=0;x2<m.w;x2++){ var V=est.cr[x2]-128, U=est.cr[m.w+x2]-128;
				for(var q=L-1;q<=L;q++){ var o2=(q*m.w+x2)*4, Y=est.img[o2]; est.img[o2]=Y+1.402*V; est.img[o2+1]=Y-0.344*U-0.714*V; est.img[o2+2]=Y+1.772*U; } }
		}
		est.linea++;
		alImagen({modo:m.n, vis:est.vis, ancho:m.w, alto:m.h, linea:est.linea, img:est.img});
		if(est.linea>=m.h){ if(alInfo) alInfo('SSTV '+m.n+' · imagen completa'); est=null; }
	}
	return {
		procesar:function(x){
			for(var i=0;i<x.length;i++){
				// Demodulador FM: banda base alrededor de 1900 Hz y diferencia de fase entre muestras
				fase+=2*Math.PI*1900/tasa; if(fase>2*Math.PI) fase-=2*Math.PI;
				var z=lp1.paso(x[i]*Math.cos(fase), -x[i]*Math.sin(fase)); z=lp2.paso(z[0], z[1]);
				var re=z[0]*zAnt[0]+z[1]*zAnt[1], im=z[1]*zAnt[0]-z[0]*zAnt[1]; zAnt=z;
				fr[n%fr.length]=1900+Math.atan2(im, re)*tasa/(2*Math.PI); n++;
				if(est){ if(n>=est.t0+(est.linea+1)*est.periodo+est.corr+tasa*0.01) lineaLista(); continue; }
				if(n%paso===0 && n-ultVis>tasa){ var v=buscarVis(); if(v!==null){ ultVis=n; if(v>=0) iniciar(v); else if(alInfo) alInfo('SSTV: modo con VIS '+(-v-1)+' no soportado (solo Martin, Scottie y Robot 36).'); } }
			}
		},
		reiniciar:function(){ est=null; },
		modos:SSTV
	};
}

/* ── Codificadores para pruebas (y para quien quiera transmitir desde el navegador) ── */
function tonoFSK(out, f, dur, tasa, st){ var n=Math.round(dur*tasa+st.r), k=n; st.r=dur*tasa+st.r-n; for(var i=0;i<k;i++){ st.ph+=2*Math.PI*f/tasa; out.push(Math.sin(st.ph)); } }
var sintetizar={
	rtty:function(texto, tasa, fMarca, invertir){
		var out=[], st={ph:0, r:0}, b=1/45.45, fm=fMarca||2125, fs=fm-170, figs=false;
		function bit(v){ if(invertir) v=1-v; tonoFSK(out, v?fm:fs, b, tasa, st); }
		function car(c){ bit(0); for(var i=0;i<5;i++) bit((c>>i)&1); bit(1); tonoFSK(out, invertir?fs:fm, b/2, tasa, st); }
		tonoFSK(out, invertir?fs:fm, 0.5, tasa, st); car(31);
		texto.toUpperCase().split('').forEach(function(ch){
			var l=LTRS.indexOf(ch), f=FIGS.indexOf(ch);
			if(ch===' '){ car(4); figs=false; return; }
			if(l>0 && ch!=='\0'){ if(figs){ car(31); figs=false; } car(l); return; }
			if(f>0){ if(!figs){ car(27); figs=true; } car(f); }
		});
		tonoFSK(out, invertir?fs:fm, 0.3, tasa, st);
		return Float32Array.from(out);
	},
	psk31:function(texto, tasa, fc){
		var sps=tasa/31.25, bits='0000000000000000';
		texto.split('').forEach(function(ch){ bits+=VARICODE[ch.charCodeAt(0)&127]+'00'; }); bits+='0000000000000000';
		var n=Math.round(bits.length*sps), y=new Float32Array(n), sig=1;
		for(var s=0;s<bits.length;s++){
			var cambio=bits[s]==='0', ant=sig; if(cambio) sig=-sig;
			for(var i=0;i<sps;i++){ var idx=Math.round(s*sps)+i; if(idx>=n) break;
				var a=cambio ? ant*Math.cos(Math.PI*i/sps) : sig; // inversión con envolvente de coseno
				y[idx]=a*Math.sin(2*Math.PI*fc*idx/tasa); }
		}
		return y;
	},
	/* img: función (x, y) → [r, g, b] */
	sstv:function(vis, img, tasa){
		var m=SSTV[vis], out=[], st={ph:0, r:0}, T=tramos(m), L=largoLinea(m);
		tonoFSK(out, 1900, 0.3, tasa, st); tonoFSK(out, 1200, 0.01, tasa, st); tonoFSK(out, 1900, 0.3, tasa, st); tonoFSK(out, 1200, 0.03, tasa, st);
		var unos=0; for(var k=0;k<7;k++){ var b=(vis>>k)&1; unos+=b; tonoFSK(out, b?1100:1300, 0.03, tasa, st); }
		tonoFSK(out, unos%2?1100:1300, 0.03, tasa, st); tonoFSK(out, 1200, 0.03, tasa, st);
		function fdev(v){ return 1500+v/255*800; }
		for(var y=0;y<m.h;y++){
			var seg=[]; // [inicio ms, dur ms, frecuencia o función]
			if(m.tipo==='martin'){ seg.push([0,m.sync,1200],[m.sync,m.porch,1500]); }
			else if(m.tipo==='scottie'){ seg.push([0,m.sep,1500],[m.sep+m.canal,m.sep,1500],[T.sync,m.sync,1200],[T.sync+m.sync,m.porch,1500]); }
			else seg.push([0,m.sync,1200],[m.sync,m.porch,1500],[m.sync+m.porch+m.canal,m.sep,y%2?2300:1500],[m.sync+m.porch+m.canal+m.sep,m.porch2,1900]);
			T.canales.forEach(function(c, ci){ seg.push([c[0], c[1], ci]); });
			if(m.tipo==='martin') T.canales.forEach(function(c){ seg.push([c[0]+c[1], m.sep, 1500]); });
			seg.sort(function(a,b){ return a[0]-b[0]; });
			seg.forEach(function(s){
				if(typeof s[2]==='number' && s[2]>=1000){ tonoFSK(out, s[2], s[1]/1000, tasa, st); return; }
				var c=T.canales[s[2]];
				for(var x=0;x<m.w;x++){
					var p=img(x, y), v;
					if(c[2]==='Y') v=0.299*p[0]+0.587*p[1]+0.114*p[2];
					else if(c[2]==='C'){ var Y=0.299*p[0]+0.587*p[1]+0.114*p[2]; v=y%2===0 ? 128+(p[0]-Y)/1.402 : 128+(p[2]-Y)/1.772; }
					else v=p[c[2]];
					tonoFSK(out, fdev(Math.max(0,Math.min(255,v))), c[1]/1000/m.w, tasa, st);
				}
			});
		}
		tonoFSK(out, 1500, 0.2, tasa, st);
		return Float32Array.from(out);
	}
};

return { rtty:rtty, psk31:psk31, sstv:sstv, sintetizar:sintetizar, SSTV:SSTV, VARICODE:VARICODE };
});
