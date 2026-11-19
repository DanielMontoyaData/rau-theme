/* Decodificador de CW — RAU
 *
 * 1. Tono: espectro promediado del audio (resolución de 3 Hz entre 300 y 1200 Hz); el tono se
 *    cambia solo si otro pico es claramente más fuerte por un rato.
 * 2. Detector coherente: el audio se mezcla con el tono exacto (I/Q), se promedia en pasos de 4 ms
 *    y pasa por un pasabajos de 4 polos cuya banda se ajusta a la velocidad (más angosto a menos
 *    PPM: menos ruido). La envolvente es el módulo de I/Q.
 * 3. Niveles: ruido y marca salen de percentiles de los últimos 3 s; umbral al medio con histéresis
 *    y un antirrebote de un cuarto de punto.
 * 4. Velocidad: la unidad (punto) que mejor explica marcas de 1 y 3 unidades y silencios de 1, 3 y 7.
 * 5. Letras: se elige la secuencia válida de Morse más probable según las duraciones (escala
 *    logarítmica). Si dos letras salieron pegadas, se separan donde los silencios lo hacen más probable.
 *
 * Interfaz: crear(tasa, alTexto(ch, confianza), alInfo({ppm, tono, snr, senal})) → {procesar(Float32Array), reiniciar()}
 */
(function(raiz, fabrica){ if(typeof module==='object' && module.exports) module.exports=fabrica(); else raiz.RauCW=fabrica(); })(typeof self!=='undefined'?self:this, function(){
'use strict';
var MORSE={'.-':'A','-...':'B','-.-.':'C','-..':'D','.':'E','..-.':'F','--.':'G','....':'H','..':'I','.---':'J','-.-':'K','.-..':'L','--':'M','-.':'N','---':'O','.--.':'P','--.-':'Q','.-.':'R','...':'S','-':'T','..-':'U','...-':'V','.--':'W','-..-':'X','-.--':'Y','--..':'Z',
	'-----':'0','.----':'1','..---':'2','...--':'3','....-':'4','.....':'5','-....':'6','--...':'7','---..':'8','----.':'9','.-.-.-':'.','--..--':',','..--..':'?','-..-.':'/','-...-':'=','.-.-.':'+','-....-':'-','.--.-.':'@','...-.-':'<SK>','-.--.':'(','.-...':'<AS>','-.-.-':'<KA>','-.--.-':')'};
var POR_LARGO={}; Object.keys(MORSE).forEach(function(c){ (POR_LARGO[c.length]=POR_LARGO[c.length]||[]).push(c); });
var SIG=0.28; // dispersión de las duraciones en escala logarítmica (mano humana)

/* FFT de base 2 para buscar el tono */
function fft2(re, im){
	var n=re.length, i, j=0, k;
	for(i=1;i<n;i++){ var bit=n>>1; for(;j&bit;bit>>=1) j^=bit; j^=bit; if(i<j){ var t=re[i]; re[i]=re[j]; re[j]=t; t=im[i]; im[i]=im[j]; im[j]=t; } }
	for(var len=2;len<=n;len<<=1){ var ang=-2*Math.PI/len, wr=Math.cos(ang), wi=Math.sin(ang);
		for(i=0;i<n;i+=len){ var cr=1, ci=0; for(k=0;k<len/2;k++){ var a=i+k, b=a+len/2, xr=re[b]*cr-im[b]*ci, xi=re[b]*ci+im[b]*cr; re[b]=re[a]-xr; im[b]=im[a]-xi; re[a]+=xr; im[a]+=xi; var t2=cr*wr-ci*wi; ci=cr*wi+ci*wr; cr=t2; } }
	}
}
/* Pasabajos Butterworth de 2.º orden (dos en cascada = 4 polos) */
function biquad(fc, fs){
	var w=Math.tan(Math.PI*fc/fs), w2=w*w, r=Math.SQRT2*w, n=1/(1+r+w2);
	return {b0:w2*n, b1:2*w2*n, b2:w2*n, a1:2*(w2-1)*n, a2:(1-r+w2)*n, x1:0, x2:0, y1:0, y2:0};
}
function filtrar(f, x){ var y=f.b0*x+f.b1*f.x1+f.b2*f.x2-f.a1*f.y1-f.a2*f.y2; f.x2=f.x1; f.x1=x; f.y2=f.y1; f.y1=y; return y; }
function percentil(a, n, p){ var b=Array.prototype.slice.call(a, 0, n).sort(function(x,y){ return x-y; }); return b[Math.min(n-1, Math.floor(n*p))]; }

function crear(tasa, alTexto, alInfo){
	var HOP=Math.max(1, Math.round(tasa*0.004)), fsE=tasa/HOP, pasoMs=1000/fsE;
	var NF=4096; while(NF/tasa>0.4 && NF>1024) NF>>=1;  // ~0,34 s de audio por espectro
	var st={};
	function reiniciar(){
		st.tono=0; st.fase=0; st.ai=0; st.aq=0; st.cnt=0; st.espec=null; st.cand=0; st.candT=0;
		st.bufE=new Float32Array(Math.round(3*fsE)); st.nE=0; st.iE=0;
		st.ruido=0; st.marca=0; st.abajo=false; st.dur=0; st.pend=null; st.pendT=0;
		st.marcas=[]; st.silencios=[]; st.u=40; st.envIni=[]; st.letra=[]; st.huecos=[]; st.espacio=true; st.silencio=0; st.calibrado=false; st.crudo=[];
		st.fft=new Float32Array(NF); st.nfft=0; st.inicio=0;
		st.hist=new Float32Array(Math.round(3*tasa)); st.iH=0; st.nH=0;
		ajustarFiltro();
	}
	function ajustarFiltro(){
		// Banda ≈ 0,8/punto: 13 Hz a 20 PPM, 27 Hz a 40 PPM
		var fc=Math.max(6, Math.min(fsE*0.4, 800/st.u));
		st.fi=[biquad(fc, fsE), biquad(fc, fsE)]; st.fq=[biquad(fc, fsE), biquad(fc, fsE)]; st.fc=fc;
	}
	/* Busca el tono: el pico del espectro promediado entre 300 y 1200 Hz */
	function espectro(){
		var re=new Float64Array(NF), im=new Float64Array(NF);
		for(var i=0;i<NF;i++){ var w=0.5-0.5*Math.cos(2*Math.PI*i/NF); re[i]=st.fft[i]*w; }
		fft2(re, im);
		var b0=Math.floor(300*NF/tasa), b1=Math.ceil(1200*NF/tasa);
		if(!st.espec) st.espec=new Float64Array(b1-b0+1);
		for(var b=b0;b<=b1;b++){ var p=re[b]*re[b]+im[b]*im[b]; st.espec[b-b0]=st.espec[b-b0]*0.7+p*0.3; }
		var mejor=0; for(var k=1;k<st.espec.length;k++) if(st.espec[k]>st.espec[mejor]) mejor=k;
		var med=percentil(st.espec, st.espec.length, 0.5), f=(b0+mejor)*tasa/NF;
		// Interpolación parabólica del pico
		if(mejor>0 && mejor<st.espec.length-1){ var a=st.espec[mejor-1], c=st.espec[mejor], d=st.espec[mejor+1], den=a-2*c+d; if(den) f+=0.5*(a-d)/den*tasa/NF; }
		var fuerte=st.espec[mejor]>med*8;
		if(st.tono && Math.abs(f-st.tono)<15){ st.tono+=0.2*(f-st.tono); st.candT=0; return; }
		// Un pico nuevo vale si se repite en dos espectros seguidos (el ruido no repite el mismo bin);
		// si el tono actual ya no se oye, basta con eso para cambiar; si se sigue oyendo, se piden cuatro
		if(!fuerte){ st.candT=0; return; }
		if(Math.abs(f-st.cand)<15) st.candT++; else { st.cand=f; st.candT=1; }
		var actual=st.tono ? st.espec[Math.round(st.tono*NF/tasa)-b0]||0 : 0, vivo=actual>med*4;
		if(st.candT>=(st.tono && vivo ? 4 : 2)){ st.tono=f; st.candT=0; st.enganche=true; }
	}
	/* Unidad de tiempo que mejor explica marcas (1, 3) y silencios (1, 3, 7) en escala logarítmica */
	function estimarUnidad(){
		if(st.marcas.length<4) return;
		var mejorU=st.u, mejorE=Infinity;
		for(var u=18;u<=260;u*=1.02){
			var e=0;
			st.marcas.forEach(function(d){ var a=Math.log(d/u), b=Math.log(d/(3*u)); e+=Math.min(a*a, b*b); });
			st.silencios.forEach(function(d){ var a=Math.log(d/u), b=Math.log(d/(3*u)), c=Math.log(d/(7*u)); e+=0.5*Math.min(a*a, b*b, c*c); });
			// Sin rayas o sin puntos la unidad queda indefinida: se penaliza que todo caiga en un solo grupo
			var p=st.marcas.filter(function(d){ return d<2*u; }).length; if(p===0 || p===st.marcas.length) e+=2;
			if(e<mejorE){ mejorE=e; mejorU=u; }
		}
		// Mientras se calibra, la estimación entra directa; después se suaviza para seguir cambios de mano
		var prev=st.u; st.u=st.calibrado ? prev*0.6+mejorU*0.4 : mejorU;
		if(Math.abs(st.u-prev)/prev>0.08) ajustarFiltro();
	}
	function lp(d, unidades){ var a=Math.log(d/(unidades*st.u))/SIG; return -0.5*a*a; }
	/* La mejor lectura de una letra (o de varias pegadas) por programación dinámica */
	function leer(marcas, huecos){
		var n=marcas.length, best=[0], prev=[0], cod=[''];
		for(var i=1;i<=n;i++){
			best[i]=-Infinity;
			for(var j=Math.max(0, i-7);j<i;j++){
				if(best[j]===-Infinity) continue;
				var lista=POR_LARGO[i-j]; if(!lista) continue;
				var mejorC='', mejorS=-Infinity;
				for(var q=0;q<lista.length;q++){ var c=lista[q], s=0; for(var k=0;k<c.length;k++) s+=lp(marcas[j+k], c[k]==='.'?1:3); if(s>mejorS){ mejorS=s; mejorC=c; } }
				var sp=0; for(var h=j;h<i-1;h++) sp+=lp(huecos[h], 1);
				if(j>0) sp+=lp(huecos[j-1], 3)-1.5; // separar cuesta: solo si explica mucho mejor
				var tot=best[j]+mejorS+sp;
				if(tot>best[i]){ best[i]=tot; prev[i]=j; cod[i]=mejorC; }
			}
		}
		if(best[n]===-Infinity) return [];
		var out=[], i2=n; while(i2>0){ out.unshift({c:MORSE[cod[i2]], conf:Math.exp((best[i2]-best[prev[i2]])/Math.max(1, i2-prev[i2]))}); i2=prev[i2]; }
		return out;
	}
	function soltarLetra(){
		if(!st.letra.length) return;
		leer(st.letra, st.huecos).forEach(function(r){ alTexto(r.c, r.conf); });
		st.letra=[]; st.huecos=[]; st.espacio=false;
	}
	/* Arma letras con los elementos: m = marca, s = silencio */
	function elemento(tipo, dur){
		if(tipo==='s'){
			if(!st.letra.length) return;
			if(dur>2*st.u){ soltarLetra(); if(dur>5*st.u && !st.espacio){ alTexto(' ', 1); st.espacio=true; } }
			else st.huecos.push(dur);
		} else { st.letra.push(dur); if(st.letra.length>14) soltarLetra(); }
	}
	function cambio(abajo, dur){
		var tipo=abajo?'s':'m';
		if(tipo==='s'){ if(dur<12*st.u){ st.silencios.push(dur); if(st.silencios.length>30) st.silencios.shift(); } }
		else {
			if(dur>2000){ st.letra=[]; st.huecos=[]; return; } // portadora o ruido largo
			st.marcas.push(dur); if(st.marcas.length>20) st.marcas.shift();
			estimarUnidad();
		}
		// Hasta conocer la velocidad (6 marcas) los elementos se guardan y después se leen con ella
		if(!st.calibrado){
			st.crudo.push([tipo, dur]);
			if(st.marcas.length>=6){ st.calibrado=true; st.crudo=[]; rederivar(); }
			return;
		}
		elemento(tipo, dur);
	}
	/* Con la velocidad ya conocida, se vuelve a leer la envolvente del arranque con niveles de todo ese
	   tramo: las primeras letras quedan como las demás */
	function rederivar(){
		var a=st.envIni, n=a.length; st.envIni=[];
		if(n<10) return;
		var ru=percentil(a, n, 0.2), ma=percentil(a, n, 0.95), sp=ma-ru, ab=false, d=0, pendT=0, pend=null, rebote=Math.min(0.25*st.u, 15), dur=[];
		// Se empieza en la primera muestra que es claramente señal: lo anterior es ruido y daría «T» y «E» falsas
		var i=0; while(i<n && a[i]<ru+0.7*sp) i++;
		for(;i<n;i++){
			var ahora=a[i]>ru+sp*(ab?0.42:0.58);
			if(ahora!==ab){ if(pend!==ahora){ pend=ahora; pendT=0; } pendT+=pasoMs; if(pendT>=rebote){ dur.push([ab?'m':'s', d]); ab=ahora; d=pendT; pend=null; pendT=0; } }
			else { if(pend!==null){ d+=pendT; pend=null; pendT=0; } d+=pasoMs; }
		}
		// Se descarta lo que hubo antes de la primera marca; el último tramo sigue en curso y lo cierra el detector en vivo
		while(dur.length && dur[0][0]==='s') dur.shift();
		// La velocidad se vuelve a estimar con estos elementos limpios: las marcas del detector en vivo durante el arranque eran malas
		var m=dur.filter(function(e){ return e[0]==='m' && e[1]<2000; }).map(function(e){ return e[1]; });
		if(m.length>=4){ st.marcas=m.slice(-20); st.silencios=dur.filter(function(e){ return e[0]==='s' && e[1]<1500; }).map(function(e){ return e[1]; }).slice(-30); st.calibrado=false; estimarUnidad(); st.calibrado=true; ajustarFiltro(); }
		dur.forEach(function(e){ elemento(e[0], e[1]); });
		st.abajo=ab; st.dur=d;
	}
	function muestraEnvolvente(e0){
		var b=st.bufE; b[st.iE]=e0; st.iE=(st.iE+1)%b.length; if(st.nE<b.length) st.nE++;
		st.inicio++;
		if(st.nE<fsE*0.6) return;
		var e=e0;
		if(!st.calibrado) st.envIni.push(e);
		// Ruido: percentil 20 de los últimos 3 s
		if(st.inicio%10===0){
			st.ruido=percentil(b, st.nE, 0.2);
			// La marca sigue al desvanecimiento (QSB): se mide en el último 1,2 s, como un control automático de ganancia
			var n5=Math.min(st.nE, Math.round(1.2*fsE)), rec=new Float32Array(n5); for(var k=0;k<n5;k++) rec[k]=b[(st.iE-1-k+b.length)%b.length];
			st.marca=percentil(rec, n5, 0.92);
		}
		// Silenciador: con solo ruido (Rayleigh) el percentil 95 queda ~3,6 veces sobre el 20; con CW, mucho más
		var span=st.marca-st.ruido, hay=st.marca>st.ruido*5, umbral=st.ruido+span*(st.abajo?0.42:0.58);
		if(!hay && st.sinSenal!==true){ st.sinSenal=true; if(st.abajo){ st.abajo=false; st.dur=0; } soltarLetra(); }
		if(hay) st.sinSenal=false;
		var ahora=hay && e>umbral;
		// Antirrebote: un cambio vale si dura un cuarto de punto
		if(ahora!==st.abajo){
			if(st.pend!==ahora){ st.pend=ahora; st.pendT=0; }
			st.pendT+=pasoMs;
			if(st.pendT>=Math.min(0.25*st.u, 15)){ cambio(ahora, st.dur); st.abajo=ahora; st.dur=st.pendT; st.pend=null; st.pendT=0; }
		} else {
			// Un cambio que no se sostuvo era un rebote: ese tiempo sigue siendo del estado actual
			if(st.pend!==null){ st.dur+=st.pendT; st.pend=null; st.pendT=0; }
			st.dur+=pasoMs;
		}
		if(!st.abajo && st.calibrado){
			if(st.letra.length && st.dur>2*st.u) soltarLetra();
			if(!st.espacio && st.dur>5*st.u){ alTexto(' ', 1); st.espacio=true; }
		}
		if(alInfo && st.inicio%50===0) alInfo({ppm:Math.round(1200/st.u), tono:Math.round(st.tono), snr:Math.round(20*Math.log10((st.marca+1e-12)/(st.ruido+1e-12))), senal:hay && st.marcas.length>=4, banda:Math.round(st.fc)});
	}
	function mezclar(x){
			st.fase+=2*Math.PI*st.tono/tasa; if(st.fase>2*Math.PI) st.fase-=2*Math.PI;
			st.ai+=x*Math.cos(st.fase); st.aq-=x*Math.sin(st.fase);
			if(++st.cnt===HOP){
				var i=filtrar(st.fi[1], filtrar(st.fi[0], st.ai/HOP)), q=filtrar(st.fq[1], filtrar(st.fq[0], st.aq/HOP));
				st.ai=st.aq=0; st.cnt=0;
				muestraEnvolvente(Math.sqrt(i*i+q*q));
			}
	}
	function procesar(m){
		for(var k=0;k<m.length;k++){
			var x=m[k];
			// Últimos 3 s de audio: al encontrar el tono se vuelve a pasar, para no perder la primera letra
			st.hist[st.iH]=x; st.iH=(st.iH+1)%st.hist.length; if(st.nH<st.hist.length) st.nH++;
			st.fft[st.nfft++]=x;
			if(st.nfft===NF){
				st.enganche=false; espectro(); st.nfft=0;
				// Tono nuevo (otra estación): se recalibra y se vuelven a pasar los últimos 3 s
				if(st.enganche){
					soltarLetra(); st.calibrado=false; st.crudo=[]; st.envIni=[]; st.marcas=[]; st.silencios=[]; st.nE=0; st.iE=0; st.abajo=false; st.dur=0; st.pend=null; st.pendT=0;
					var h=st.hist, n=st.nH, i0=(st.iH-n+h.length)%h.length; for(var r=0;r<n-1;r++) mezclar(h[(i0+r)%h.length]);
				}
			}
			if(st.tono) mezclar(x);
		}
	}
	reiniciar();
	return {procesar:procesar, reiniciar:reiniciar, estado:function(){ return {tono:st.tono, ppm:1200/st.u, banda:st.fc}; }};
}
return {crear:crear, MORSE:MORSE};
});
