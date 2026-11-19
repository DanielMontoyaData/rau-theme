/* Decodificador y codificador FT8/FT4 — RAU
 *
 * Traducción a JavaScript de ft8_lib de Kārlis Goba (https://github.com/kgoba/ft8_lib, licencia MIT,
 * versión de agosto de 2025), que a su vez sigue el protocolo y las tablas de WSJT-X (K1JT y equipo).
 * Se conserva el algoritmo: cascada con sobremuestreo 2×2, búsqueda de candidatos por los patrones de
 * Costas, verosimilitud por bit, decodificación LDPC(174,91) por propagación de creencias, CRC-14 y
 * desempaquetado de los 77 bits. Se agregan: estimación de SNR en 2500 Hz (como WSJT-X) a partir de
 * los tonos re-codificados, y el sintetizador GFSK para las pruebas.
 *
 * MIT License — Copyright (c) 2018 Kārlis Goba (ft8_lib); traducción © RAU, misma licencia.
 */
(function(raiz, fabrica){ if(typeof module==='object' && module.exports) module.exports=fabrica(); else raiz.RauFTX=fabrica(); })(typeof self!=='undefined'?self:this, function(){
'use strict';

/* ── Protocolos ─────────────────────────────────────────────────────────── */
var FT8={nombre:'FT8', simbolo:0.160, ranura:15, nn:79, nd:58, tonos:8, bt:2.0};
var FT4={nombre:'FT4', simbolo:0.048, ranura:7.5, nn:105, nd:87, tonos:4, bt:1.0};
var COSTAS8=[3,1,4,0,6,5,2], COSTAS4=[[0,1,3,2],[1,0,2,3],[2,3,1,0],[3,2,0,1]];
var GRAY8=[0,1,3,2,5,6,4,7], GRAY4=[0,1,3,2];
var XOR4=[0x4A,0x5E,0x89,0xB4,0xB0,0x8A,0x79,0x55,0xBE,0x28];
var N=174, K=91, M=83;

// Tablas del código LDPC(174,91), copiadas de ft8_lib (ft8/constants.c, MIT) que las toma de WSJT-X
var LDPC_GEN = [131,41,206,17,191,49,234,245,9,242,127,192,118,28,38,78,37,194,89,51,84,147,19,32,220,38,89,2,251,39,124,100,16,161,189,192,27,63,65,120,88,205,45,211,62,199,246,32,9,253,164,254,224,65,149,253,3,71,131,160,7,124,204,193,27,136,115,237,92,61,72,160,41,182,42,254,60,160,54,244,254,26,157,160,96,84,250,245,243,93,150,211,176,200,195,224,226,7,152,228,49,14,237,39,136,74,233,0,119,92,156,8,232,14,38,221,174,86,49,128,176,184,17,2,140,43,249,151,33,52,135,192,24,160,201,35,31,198,10,223,92,94,163,32,118,71,30,131,2,160,114,30,1,177,43,128,255,188,203,128,202,131,65,250,251,71,178,224,102,167,42,21,143,147,37,162,191,103,23,0,196,36,54,137,254,133,177,197,19,99,161,128,13,255,115,148,20,209,161,179,75,28,39,0,21,180,136,48,99,108,139,153,137,73,114,224,41,168,156,13,61,232,29,102,84,137,176,224,79,18,111,55,250,81,203,230,27,214,185,64,153,196,114,57,208,217,125,60,132,224,148,0,25,25,183,81,25,118,86,33,187,79,30,128,9,219,18,215,49,250,238,11,134,223,107,128,72,143,195,61,244,63,189,238,164,234,251,64,130,116,35,238,64,182,117,247,86,235,95,224,171,225,151,196,132,203,116,117,113,68,169,160,43,80,14,75,192,236,90,109,43,219,221,0,196,116,170,83,215,2,24,118,22,105,54,0,142,186,26,19,219,51,144,189,103,24,206,192,117,56,68,103,58,39,120,44,196,32,18,224,6,255,131,161,69,195,112,53,165,193,38,128,59,55,65,120,88,204,45,211,62,195,246,32,154,74,90,40,238,23,202,156,50,72,66,192,188,41,244,101,48,156,151,126,137,97,10,64,38,99,174,109,223,139,92,226,187,41,72,128,70,242,49,239,228,87,3,76,24,20,65,128,63,178,206,133,171,233,176,199,46,6,251,224,222,135,72,31,40,44,21,57,113,160,162,224,252,215,204,242,60,105,250,153,187,161,65,32,240,38,20,71,233,73,12,168,228,116,206,192,68,16,17,88,24,25,111,149,205,215,1,32,8,143,195,29,244,191,189,226,164,234,251,64,184,254,241,182,48,119,41,251,10,7,140,0,90,254,167,172,204,183,123,188,157,153,169,0,73,167,1,106,198,83,246,94,205,201,7,96,25,68,208,133,190,78,125,168,214,204,125,0,37,31,98,173,196,3,47,14,231,20,0,32,86,71,31,135,2,160,114,30,0,177,43,128,43,142,73,35,242,221,81,226,213,55,250,0,107,85,10,64,166,111,71,85,222,149,194,96,161,138,210,141,78,39,254,146,164,246,200,64,16,194,229,134,56,140,184,42,61,128,117,128,239,52,164,24,23,238,2,19,61,178,235,0,126,156,12,84,50,90,156,21,131,110,0,0,54,147,229,114,209,253,228,205,240,121,232,96,191,178,206,197,171,225,176,199,46,7,251,224,126,225,130,48,197,131,204,204,87,212,176,128,160,102,203,47,237,175,201,245,38,100,18,96,187,35,114,90,188,71,204,95,76,196,205,32,222,217,219,163,190,228,12,89,181,96,155,64,217,167,1,106,198,83,230,222,205,201,3,96,154,212,106,237,95,112,127,40,10,181,252,64,229,146,28,119,130,37,135,49,109,125,60,32,79,20,218,130,66,168,184,109,202,115,53,32,139,139,80,122,212,103,212,68,29,247,112,224,34,131,28,156,241,22,148,103,173,4,182,128,33,59,131,143,226,174,84,195,142,231,24,0,93,146,107,109,215,31,8,81,129,164,225,32,102,171,121,212,178,158,230,230,149,9,229,96,149,129,72,104,45,116,138,56,221,104,186,160,184,206,2,12,240,105,195,42,114,58,177,64,244,51,29,109,70,22,7,233,87,82,116,96,109,162,59,164,36,185,89,97,51,207,156,128,166,54,188,188,123,48,197,251,234,230,127,224,92,176,216,106,7,223,101,74,144,137,162,0,241,31,16,104,72,120,15,201,236,221,128,160,31,187,83,100,251,141,44,157,115,13,91,160,252,184,107,199,10,80,201,208,42,93,3,64,165,52,67,48,41,234,193,95,50,46,52,192,201,137,217,199,195,211,184,197,93,117,19,0,123,179,139,47,1,134,212,102,67,174,150,32,38,68,235,173,235,68,185,70,125,31,66,192,96,140,200,87,89,75,251,181,93,105,96,0];
var LDPC_NM = [4,31,59,91,92,96,153,5,32,60,93,115,146,0,6,24,61,94,122,151,0,7,33,62,95,96,143,0,8,25,63,83,93,96,148,6,32,64,97,126,138,0,5,34,65,78,98,107,154,9,35,66,99,139,146,0,10,36,67,100,107,126,0,11,37,67,87,101,139,158,12,38,68,102,105,155,0,13,39,69,103,149,162,0,8,40,70,82,104,114,145,14,41,71,88,102,123,156,15,42,59,106,123,159,0,1,33,72,106,107,157,0,16,43,73,108,141,160,0,17,37,74,81,109,131,154,11,44,75,110,121,166,0,45,55,64,111,130,161,173,8,46,71,112,119,166,0,18,36,76,89,113,114,143,19,38,77,104,116,163,0,20,47,70,92,138,165,0,2,48,74,113,128,160,0,21,45,78,83,117,121,151,22,47,58,118,127,164,0,16,39,62,112,134,158,0,23,43,79,120,131,145,0,19,35,59,73,110,125,161,20,36,63,94,136,161,0,14,31,79,98,132,164,0,3,44,80,124,127,169,0,19,46,81,117,135,167,0,7,49,58,90,100,105,168,12,50,61,118,119,144,0,13,51,64,114,118,157,0,24,52,76,129,148,149,0,25,53,69,90,101,130,156,20,46,65,80,120,140,170,21,54,77,100,140,171,0,35,82,133,142,171,174,0,14,30,83,113,125,170,0,4,29,68,120,134,173,0,1,4,52,57,86,136,152,26,51,56,91,122,137,168,52,84,110,115,145,168,0,7,50,81,99,132,173,0,23,55,67,95,172,174,0,26,41,77,109,141,148,0,2,27,41,61,62,115,133,27,40,56,124,125,126,0,18,49,55,124,141,167,0,6,33,85,108,116,156,0,28,48,70,85,105,129,158,9,54,63,131,147,155,0,22,53,68,109,121,174,0,3,13,48,78,95,123,0,31,69,133,150,155,169,0,12,43,66,89,97,135,159,5,39,75,102,136,167,0,2,54,86,101,135,164,0,15,56,87,108,119,171,0,10,44,82,91,111,144,149,23,34,71,94,127,153,0,11,49,88,92,142,157,0,29,34,87,97,147,162,0,30,50,60,86,137,142,162,10,53,66,84,112,128,165,22,57,85,93,140,159,0,28,32,72,103,132,166,0,28,29,84,88,117,143,150,1,26,45,80,128,147,0,17,27,89,103,116,153,0,51,57,98,163,165,172,0,21,37,73,138,152,169,0,16,47,76,130,137,154,0,3,24,30,72,104,139,0,9,40,90,106,134,151,0,15,58,60,74,111,150,163,18,42,79,144,146,152,0,25,38,65,99,122,160,0,17,42,75,129,170,172,0];
var LDPC_MN = [16,45,73,25,51,62,33,58,78,1,44,45,2,7,61,3,6,54,4,35,48,5,13,21,8,56,79,9,64,69,10,19,66,11,36,60,12,37,58,14,32,43,15,63,80,17,28,77,18,74,83,22,53,81,23,30,34,24,31,40,26,41,76,27,57,70,29,49,65,3,38,78,5,39,82,46,50,73,51,52,74,55,71,72,44,67,72,43,68,78,1,32,59,2,6,71,4,16,54,7,65,67,8,30,42,9,22,31,10,18,76,11,23,82,12,28,61,13,52,79,14,50,51,15,81,83,17,29,60,19,33,64,20,26,73,21,34,40,24,27,77,25,55,58,35,53,66,36,48,68,37,46,75,38,45,47,39,57,69,41,56,62,20,49,53,46,52,63,45,70,75,27,35,80,1,15,30,2,68,80,3,36,51,4,28,51,5,31,56,6,20,37,7,40,82,8,60,69,9,10,49,11,44,57,12,39,59,13,24,55,14,21,65,16,71,78,17,30,76,18,25,80,19,61,83,22,38,77,23,41,50,7,26,58,29,32,81,33,40,73,18,34,48,13,42,64,5,26,43,47,69,72,54,55,70,45,62,68,10,63,67,14,66,72,22,60,74,35,39,79,1,46,64,1,24,66,2,5,70,3,31,65,4,49,58,1,4,5,6,60,67,7,32,75,8,48,82,9,35,41,10,39,62,11,14,61,12,71,74,13,23,78,11,35,55,15,16,79,7,9,16,17,54,63,18,50,57,19,30,47,20,64,80,21,28,69,22,25,43,13,22,37,2,47,51,23,54,74,26,34,72,27,36,37,21,36,63,29,40,44,19,26,57,3,46,82,14,15,58,33,52,53,30,43,52,6,9,52,27,33,65,25,69,73,38,55,83,20,39,77,18,29,56,32,48,71,42,51,59,28,44,79,34,60,62,31,45,61,46,68,77,6,24,76,8,10,78,40,41,70,17,50,53,42,66,68,4,22,72,36,64,81,13,29,47,2,8,81,56,67,73,5,38,50,12,38,64,59,72,80,3,26,79,45,76,81,1,65,74,7,18,77,11,56,59,14,39,54,16,37,66,10,28,55,15,60,70,17,25,82,20,30,31,12,67,68,23,75,80,27,32,62,24,69,75,19,21,71,34,53,61,35,46,47,33,59,76,40,43,83,41,42,63,49,75,83,20,44,48,42,49,57];
var LDPC_FILAS = [7,6,6,6,7,6,7,6,6,7,6,6,7,7,6,6,6,7,6,7,6,7,6,6,6,7,6,6,6,7,6,6,6,6,7,6,6,6,7,7,6,6,6,6,7,7,6,6,6,6,7,6,6,6,7,6,6,6,6,7,6,6,6,7,6,6,6,7,7,6,6,7,6,6,6,6,6,6,6,7,6,6,6];

/* ── FFT de base mixta (2, 3, 4, 5): los tamaños de FT8 (3840) y FT4 (1152) no son potencias de 2 ── */
function planFFT(n){
	var f=[], m=n;
	[4,2,3,5].forEach(function(p){ while(m%p===0){ f.push(p); m/=p; } });
	if(m!==1) throw new Error('Tamaño de FFT no soportado: '+n);
	var cr=new Float64Array(n), ci=new Float64Array(n);
	for(var k=0;k<n;k++){ cr[k]=Math.cos(-2*Math.PI*k/n); ci[k]=Math.sin(-2*Math.PI*k/n); }
	return {n:n, f:f, cr:cr, ci:ci, tr:new Float64Array(5), ti:new Float64Array(5)};
}
/* Cooley-Tukey recursivo de base mixta (decimación en el tiempo), como kiss_fft */
function fftRec(P, nivel, n, xr, xi, xo, xs, yr, yi, yo){
	if(n===1){ yr[yo]=xr[xo]; yi[yo]=xi[xo]; return; }
	var p=P.f[nivel], m=n/p, q, k, u, paso=P.n/n;
	for(q=0;q<p;q++) fftRec(P, nivel+1, m, xr, xi, xo+q*xs, xs*p, yr, yi, yo+q*m);
	var tr=P.tr, ti=P.ti;
	for(k=0;k<m;k++){
		for(u=0;u<p;u++){
			var sr=0, si=0;
			for(q=0;q<p;q++){
				var e=((q*(k+u*m))%n)*paso, ar=yr[yo+q*m+k], ai=yi[yo+q*m+k], wr=P.cr[e], wi=P.ci[e];
				sr+=ar*wr-ai*wi; si+=ar*wi+ai*wr;
			}
			tr[u]=sr; ti[u]=si;
		}
		for(u=0;u<p;u++){ yr[yo+k+u*m]=tr[u]; yi[yo+k+u*m]=ti[u]; }
	}
}
function fft(P, xr, xi, yr, yi){ fftRec(P, 0, P.n, xr, xi, 0, 1, yr, yi, 0); }

/* ── Monitor: cascada de magnitudes (dB en 0,5 dB, 8 bits) sobre la ranura ── */
function Monitor(proto, tasa, opts){
	opts=opts||{};
	var fmin=opts.fmin||200, fmax=opts.fmax||3000;
	this.p=proto; this.tasa=tasa; this.osrT=2; this.osrF=2;
	this.bloque=Math.round(tasa*proto.simbolo); this.sub=this.bloque/this.osrT; this.nfft=this.bloque*this.osrF;
	var norma=2/this.nfft; this.ventana=new Float64Array(this.nfft);
	for(var i=0;i<this.nfft;i++){ var s=Math.sin(Math.PI*i/this.nfft); this.ventana[i]=norma*s*s; }
	this.marco=new Float64Array(this.nfft);
	this.plan=planFFT(this.nfft);
	this.xr=new Float64Array(this.nfft); this.xi=new Float64Array(this.nfft); this.yr=new Float64Array(this.nfft); this.yi=new Float64Array(this.nfft);
	this.maxBloques=Math.floor(proto.ranura/proto.simbolo);
	this.minBin=Math.floor(fmin*proto.simbolo); this.maxBin=Math.floor(fmax*proto.simbolo)+1;
	this.bins=this.maxBin-this.minBin;
	this.paso=this.osrT*this.osrF*this.bins;
	this.mag=new Uint8Array(this.maxBloques*this.paso);
	this.bloques=0;
}
Monitor.prototype.reiniciar=function(){ this.bloques=0; this.marco.fill(0); };
/* Procesa un bloque de muestras (una duración de símbolo) */
Monitor.prototype.procesar=function(x, desde){
	if(this.bloques>=this.maxBloques) return;
	var o=this.bloques*this.paso, pos=desde||0, nf=this.nfft, sb=this.sub, mf=this.marco;
	for(var ts=0;ts<this.osrT;ts++){
		mf.copyWithin(0, sb);
		for(var j=nf-sb;j<nf;j++) mf[j]=x[pos++]||0;
		for(var j2=0;j2<nf;j2++){ this.xr[j2]=this.ventana[j2]*mf[j2]; this.xi[j2]=0; }
		fft(this.plan, this.xr, this.xi, this.yr, this.yi);
		for(var fs=0;fs<this.osrF;fs++) for(var b=this.minBin;b<this.maxBin;b++){
			var sbin=b*this.osrF+fs, m2=this.yr[sbin]*this.yr[sbin]+this.yi[sbin]*this.yi[sbin];
			var db=10*Math.log10(1e-12+m2), v=Math.trunc(2*db+240);
			this.mag[o++]= v<0?0:v>255?255:v;
		}
	}
	this.bloques++;
};
/* Procesa una señal completa (Float32Array a la tasa del monitor) */
Monitor.prototype.cargar=function(x){ this.reiniciar(); for(var p=0;p+this.bloque<=x.length;p+=this.bloque) this.procesar(x, p); };

/* ── Búsqueda de candidatos por sincronismo (Costas) ── */
function idxCand(W, c){ return (((c.t*W.osrT)+c.ts)*W.osrF+c.fs)*W.bins+c.f; }
function puntajeFT8(W, c){
	var s=0, n=0, base=idxCand(W, c), mag=W.mag, st=W.paso;
	for(var m=0;m<3;m++) for(var k=0;k<7;k++){
		var bl=36*m+k, ba=c.t+bl; if(ba<0) continue; if(ba>=W.bloques) break;
		var p=base+bl*st, sm=COSTAS8[k], v=mag[p+sm];
		if(sm>0){ s+=v-mag[p+sm-1]; n++; }
		if(sm<7){ s+=v-mag[p+sm+1]; n++; }
		if(k>0 && ba>0){ s+=v-mag[p+sm-st]; n++; }
		if(k+1<7 && ba+1<W.bloques){ s+=v-mag[p+sm+st]; n++; }
	}
	return n>0 ? Math.trunc(s/n) : 0;
}
function puntajeFT4(W, c){
	var s=0, n=0, base=idxCand(W, c), mag=W.mag, st=W.paso;
	for(var m=0;m<4;m++) for(var k=0;k<4;k++){
		var bl=1+33*m+k, ba=c.t+bl; if(ba<0) continue; if(ba>=W.bloques) break;
		var p=base+bl*st, sm=COSTAS4[m][k], v=mag[p+sm];
		if(sm>0){ s+=v-mag[p+sm-1]; n++; }
		if(sm<3){ s+=v-mag[p+sm+1]; n++; }
		if(k>0 && ba>0){ s+=v-mag[p+sm-st]; n++; }
		if(k+1<4 && ba+1<W.bloques){ s+=v-mag[p+sm+st]; n++; }
	}
	return n>0 ? Math.trunc(s/n) : 0;
}
/* Los mejores candidatos (montículo de mínimos como en ft8_lib), ordenados de mayor a menor puntaje */
function buscarCandidatos(W, max, minimo){
	var fun=W.p===FT4?puntajeFT4:puntajeFT8, tonos=W.p.tonos, heap=[], c={t:0, f:0, ts:0, fs:0, s:0};
	function abajo(h, n){ var i=0; for(;;){ var l=2*i+1, r=l+1, m=i; if(l<n && h[l].s<h[m].s) m=l; if(r<n && h[r].s<h[m].s) m=r; if(m===i) break; var t=h[m]; h[m]=h[i]; h[i]=t; i=m; } }
	function arriba(h, n){ var i=n-1; while(i>0){ var pa=(i-1)>>1; if(!(h[i].s<h[pa].s)) break; var t=h[pa]; h[pa]=h[i]; h[i]=t; i=pa; } }
	for(c.ts=0;c.ts<W.osrT;c.ts++) for(c.fs=0;c.fs<W.osrF;c.fs++) for(c.t=-10;c.t<20;c.t++) for(c.f=0;c.f+tonos-1<W.bins;c.f++){
		c.s=fun(W, c); if(c.s<minimo) continue;
		if(heap.length===max && c.s>heap[0].s){ heap[0]=heap[heap.length-1]; heap.pop(); abajo(heap, heap.length); }
		if(heap.length<max){ heap.push({t:c.t, f:c.f, ts:c.ts, fs:c.fs, s:c.s}); arriba(heap, heap.length); }
	}
	var n=heap.length;
	while(n>1){ var t=heap[n-1]; heap[n-1]=heap[0]; heap[0]=t; n--; abajo(heap, n); }
	return heap;
}

/* ── Verosimilitud de cada bit (log p(1)/p(0)) ── */
function magDb(v){ return v*0.5-120; }
function verosimilitud(W, c){
	var L=new Float64Array(N), base=idxCand(W, c), st=W.paso, mag=W.mag, k, j;
	if(W.p===FT4){
		for(k=0;k<87;k++){
			var si=k+(k<29?5:k<58?9:13), bi=2*k, bl=c.t+si;
			if(bl<0 || bl>=W.bloques){ L[bi]=L[bi+1]=0; continue; }
			var p=base+si*st, s=[]; for(j=0;j<4;j++) s[j]=magDb(mag[p+GRAY4[j]]);
			L[bi]=Math.max(s[2],s[3])-Math.max(s[0],s[1]);
			L[bi+1]=Math.max(s[1],s[3])-Math.max(s[0],s[2]);
		}
	} else {
		for(k=0;k<58;k++){
			var si8=k+(k<29?7:14), b8=3*k, bl8=c.t+si8;
			if(bl8<0 || bl8>=W.bloques){ L[b8]=L[b8+1]=L[b8+2]=0; continue; }
			var p8=base+si8*st, s8=[]; for(j=0;j<8;j++) s8[j]=magDb(mag[p8+GRAY8[j]]);
			L[b8]=Math.max(s8[4],s8[5],s8[6],s8[7])-Math.max(s8[0],s8[1],s8[2],s8[3]);
			L[b8+1]=Math.max(s8[2],s8[3],s8[6],s8[7])-Math.max(s8[0],s8[1],s8[4],s8[5]);
			L[b8+2]=Math.max(s8[1],s8[3],s8[5],s8[7])-Math.max(s8[0],s8[2],s8[4],s8[6]);
		}
	}
	// Normalización de la varianza con el coeficiente experimental de ft8_lib
	var sum=0, sum2=0; for(var i=0;i<N;i++){ sum+=L[i]; sum2+=L[i]*L[i]; }
	var varianza=(sum2-sum*sum/N)/N, f=Math.sqrt(24/varianza);
	for(var i2=0;i2<N;i2++) L[i2]*=f;
	return L;
}

/* ── LDPC(174,91): propagación de creencias (bp_decode de ft8_lib) ── */
function tanhR(x){ if(x<-4.97) return -1; if(x>4.97) return 1; var x2=x*x; return x*(945+x2*(105+x2))/(945+x2*(420+x2*15)); }
function atanhR(x){ var x2=x*x; return x*(945+x2*(-735+x2*64))/(945+x2*(-1050+x2*225)); }
function erroresParidad(b){ var e=0; for(var m=0;m<M;m++){ var x=0; for(var i=0;i<LDPC_FILAS[m];i++) x^=b[LDPC_NM[m*7+i]-1]; if(x) e++; } return e; }
function ldpc(L, iters){
	var tov=new Float64Array(N*3), toc=new Float64Array(M*7), plano=new Uint8Array(N), mejor=new Uint8Array(N), minErr=M;
	for(var it=0;it<iters;it++){
		var suma=0;
		for(var n=0;n<N;n++){ plano[n]=(L[n]+tov[n*3]+tov[n*3+1]+tov[n*3+2])>0?1:0; suma+=plano[n]; }
		if(!suma) break; // todo ceros: prohibido
		var e=erroresParidad(plano);
		if(e<minErr){ minErr=e; mejor.set(plano); if(e===0) break; }
		for(var m=0;m<M;m++) for(var ni=0;ni<LDPC_FILAS[m];ni++){
			var nn=LDPC_NM[m*7+ni]-1, T=L[nn];
			for(var mi=0;mi<3;mi++) if(LDPC_MN[nn*3+mi]-1!==m) T+=tov[nn*3+mi];
			toc[m*7+ni]=tanhR(-T/2);
		}
		for(var n2=0;n2<N;n2++) for(var mi2=0;mi2<3;mi2++){
			var mm=LDPC_MN[n2*3+mi2]-1, Tm=1;
			for(var ni2=0;ni2<LDPC_FILAS[mm];ni2++) if(LDPC_NM[mm*7+ni2]-1!==n2) Tm*=toc[mm*7+ni2];
			tov[n2*3+mi2]=-2*atanhR(Tm);
		}
	}
	// ft8_lib devuelve el último plano evaluado; con 0 errores coincide con el mejor
	return {bits:minErr===0?mejor:plano, errores:minErr};
}

/* ── CRC-14 ── */
function crc14(bytes, nbits){
	var r=0, ib=0;
	for(var i=0;i<nbits;i++){
		if(i%8===0){ r^=bytes[ib++]<<6; }
		r = (r&0x2000) ? ((r<<1)^0x2757) : (r<<1);
		r&=0xFFFF;
	}
	return r&0x3FFF;
}
function extraerCrc(a){ return ((a[9]&7)<<11)|(a[10]<<3)|(a[11]>>5); }
function agregarCrc(payload){
	var a=new Uint8Array(12); for(var i=0;i<10;i++) a[i]=payload[i];
	a[9]&=0xF8; a[10]=0; var c=crc14(a, 82);
	a[9]|=(c>>11)&0xFF; a[10]=(c>>3)&0xFF; a[11]=(c<<5)&0xFF;
	return a;
}
function empacarBits(b, n){ var o=new Uint8Array(Math.ceil(n/8)); for(var i=0;i<n;i++) if(b[i]) o[i>>3]|=0x80>>(i&7); return o; }

/* Decodifica un candidato: devuelve los 10 bytes de carga útil o null */
function decodificarCandidato(W, c, iters){
	var L=verosimilitud(W, c), r=ldpc(L, iters||25);
	if(r.errores>0) return null;
	var a=empacarBits(r.bits, K), crc=extraerCrc(a);
	a[9]&=0xF8; a[10]=0;
	if(crc!==crc14(a, 82)) return null;
	var pl=new Uint8Array(10);
	for(var i=0;i<10;i++) pl[i]=W.p===FT4 ? a[i]^XOR4[i] : a[i];
	return {payload:pl, hash:crc};
}

/* ── Segunda etapa, como WSJT-X (ft8b.f90, sync8d.f90, ft8_downsample.f90, subtractft8.f90) ──────────
   Cada candidato se baja a banda base a 32 muestras por símbolo, se busca el tiempo y la frecuencia
   finos con los patrones de Costas, se calculan las métricas de bit sobre 1, 2 y 3 símbolos coherentes
   y se intentan las cuatro variantes en el LDPC. Lo decodificado se resta del audio y se repite. */
var DATOS8=[[7,29],[43,29]], DATOS4=[[5,29],[38,29],[71,29]];
var SYNC8=[[0,COSTAS8],[36,COSTAS8],[72,COSTAS8]], SYNC4=[[1,COSTAS4[0]],[34,COSTAS4[1]],[67,COSTAS4[2]],[100,COSTAS4[3]]];
function Etapa2(x, proto){
	var p=this.p=proto;
	this.nsps=Math.round(12000*p.simbolo); this.fac=this.nsps/32;
	this.N1=p===FT8?192000:92160; this.N2=this.N1/this.fac; this.fs2=12000/this.fac; this.df=12000/this.N1; this.baud=1/p.simbolo;
	this.bits=p===FT8?3:2; this.gray=p===FT8?GRAY8:GRAY4; this.datos=p===FT8?DATOS8:DATOS4; this.sync=p===FT8?SYNC8:SYNC4;
	this.plan1=planFFT(this.N1); this.plan2=planFFT(this.N2);
	this.cargar(x);
	var t=this.taper=new Float64Array(101); for(var i=0;i<=100;i++) t[i]=0.5*(1+Math.cos(i*Math.PI/100));
	// Tabla de la DFT de 32 puntos para los primeros tonos
	var nt=p.tonos, cr=this.dftR=new Float64Array(nt*32), ci=this.dftI=new Float64Array(nt*32);
	for(var k=0;k<nt;k++) for(var n=0;n<32;n++){ cr[k*32+n]=Math.cos(2*Math.PI*k*n/32); ci[k*32+n]=-Math.sin(2*Math.PI*k*n/32); }
}
Etapa2.prototype.cargar=function(x){
	var r=new Float64Array(this.N1), i=new Float64Array(this.N1), n=Math.min(x.length, this.N1);
	for(var k=0;k<n;k++) r[k]=x[k];
	this.XR=new Float64Array(this.N1); this.XI=new Float64Array(this.N1);
	fft(this.plan1, r, i, this.XR, this.XI);
	// Piso de ruido: potencia por bin entre 200 y 3000 Hz (muestreada) del audio de esta pasada
	var b0=Math.round(200/this.df), b1=Math.round(3000/this.df), m=[];
	for(var k2=b0;k2<b1;k2+=7) m.push(this.XR[k2]*this.XR[k2]+this.XI[k2]*this.XI[k2]);
	// Percentil 10 (para ruido exponencial, 0,105 de la media): en una banda llena, los huecos entre señales
	m.sort(function(a,b){ return a-b; }); this.ruido=m[Math.floor(m.length*0.1)]/0.10536;
};
/* Banda base: f1 queda en 0 Hz, el tono k en k·baud; 12000/fac muestras por segundo */
Etapa2.prototype.bajar=function(f1){
	var N2=this.N2, i0=Math.round(f1/this.df), it=Math.min(Math.round((f1+(this.p.tonos+0.5)*this.baud)/this.df), this.N1/2), ib=Math.max(1, Math.round((f1-1.5*this.baud)/this.df));
	var k=it-ib+1, ar=new Float64Array(N2), ai=new Float64Array(N2);
	for(var j=0;j<k && j<N2;j++){ ar[j]=this.XR[ib+j]; ai[j]=this.XI[ib+j]; }
	for(var t=0;t<=100;t++){ var w=this.taper[100-t]; ar[t]*=w; ai[t]*=w; var q=k-1-100+t; if(q>=0){ var w2=this.taper[t]; ar[q]*=w2; ai[q]*=w2; } }
	// Desplazamiento circular para que f1 quede en el bin 0, y transformada inversa (conjugando)
	var s=i0-ib, br=new Float64Array(N2), bi=new Float64Array(N2);
	for(var u=0;u<N2;u++){ var v=((u+s)%N2+N2)%N2; br[u]=ar[v]; bi[u]=-ai[v]; }
	var yr=new Float64Array(N2), yi=new Float64Array(N2); fft(this.plan2, br, bi, yr, yi);
	var f=1/Math.sqrt(this.N1*this.N2);
	for(var w3=0;w3<N2;w3++){ yr[w3]*=f; yi[w3]=-yi[w3]*f; }
	return {r:yr, i:yi};
};
/* Correlación con los patrones de Costas en la posición i0 (muestras de banda base), con corrimiento delf Hz */
Etapa2.prototype.sincronia=function(cd, i0, delf){
	var s=0, N2=this.N2, dph=2*Math.PI*(delf||0)/this.fs2;
	for(var g=0;g<this.sync.length;g++){ var ini=this.sync[g][0], pat=this.sync[g][1];
		for(var k=0;k<pat.length;k++){
			var o=i0+(ini+k)*32; if(o<0 || o+31>=N2) continue;
			var zr=0, zi=0, tono=pat[k];
			for(var n=0;n<32;n++){ var a=2*Math.PI*tono*n/32+dph*n, c=Math.cos(a), sn=Math.sin(a), xr=cd.r[o+n], xi=cd.i[o+n]; zr+=xr*c+xi*sn; zi+=xi*c-xr*sn; }
			s+=zr*zr+zi*zi;
		}
	}
	return s;
};
/* Tiempo y frecuencia finos a partir de un candidato (f1 Hz del tono 0, t0 s del símbolo 0) */
Etapa2.prototype.afinar=function(f1, t0){
	var cd=this.bajar(f1), i0=Math.round(t0*this.fs2), mejor=-1, ib=i0, k;
	for(k=i0-20;k<=i0+20;k++){ var s=this.sincronia(cd, k, 0); if(s>mejor){ mejor=s; ib=k; } }
	var dfm=0;
	// ±medio bin de la cascada: ±1,6 Hz en FT8 (se busca ±2,5 como WSJT-X), ±5,2 Hz en FT4
	var nf=Math.max(5, Math.ceil(this.baud/4/0.5));
	for(var fr=-nf;fr<=nf;fr++){ var s2=this.sincronia(cd, ib, fr*0.5); if(s2>mejor){ mejor=s2; dfm=fr*0.5; } }
	f1+=dfm; cd=this.bajar(f1);
	var mb=-1, ib2=ib; for(k=-4;k<=4;k++){ var s3=this.sincronia(cd, ib+k, 0); if(s3>mb){ mb=s3; ib2=ib+k; } }
	return {f1:f1, i0:ib2, cd:cd, t:ib2/this.fs2};
};
/* Espectro complejo de cada símbolo en los tonos 0..T-1 */
Etapa2.prototype.simbolos=function(a){
	var nn=this.p.nn, T=this.p.tonos, csr=new Float64Array(nn*T), csi=new Float64Array(nn*T), cd=a.cd, N2=this.N2;
	for(var s=0;s<nn;s++){ var o=a.i0+s*32; if(o<0 || o+31>N2-1) continue;
		for(var t=0;t<T;t++){ var zr=0, zi=0; for(var n=0;n<32;n++){ var c=this.dftR[t*32+n], si=this.dftI[t*32+n], xr=cd.r[o+n], xi=cd.i[o+n]; zr+=xr*c-xi*si; zi+=xr*si+xi*c; } csr[s*T+t]=zr; csi[s*T+t]=zi; }
	}
	return {r:csr, i:csi, T:T};
};
function normalizar(b){ var n=b.length, s=0, s2=0; for(var i=0;i<n;i++){ s+=b[i]; s2+=b[i]*b[i]; } var m=s/n, v=s2/n-m*m, sg=v>0?Math.sqrt(v):Math.sqrt(s2/n); if(sg>0) for(var j=0;j<n;j++) b[j]/=sg; return b; }
/* Métricas de bit a, b, c (1, 2 y 3 símbolos coherentes) y d (normalizada), como ft8b */
Etapa2.prototype.metricas=function(cs){
	var T=cs.T, bps=this.bits, gray=this.gray, out=[new Float64Array(N), new Float64Array(N), new Float64Array(N), new Float64Array(N)];
	for(var ns=1;ns<=3;ns++){
		var nb=bps*ns, nt=1<<nb, s2=new Float64Array(nt);
		for(var h=0;h<this.datos.length;h++){ var ini=this.datos[h][0], cnt=this.datos[h][1];
			for(var k=0;k<cnt;k+=ns){
				var ks=ini+k;
				for(var i=0;i<nt;i++){
					var zr=0, zi=0;
					for(var q=0;q<ns;q++){ var tono=gray[(i>>(bps*(ns-1-q)))&(T-1)], sym=ks+q; if(sym>=this.p.nn) continue; zr+=cs.r[sym*T+tono]; zi+=cs.i[sym*T+tono]; }
					s2[i]=Math.sqrt(zr*zr+zi*zi);
				}
				var base=h*cnt*bps+k*bps;
				for(var b=0;b<nb;b++){
					var idx=base+b; if(idx>=N) continue;
					var bit=nb-1-b, m1=-1, m0=-1;
					for(var i2=0;i2<nt;i2++){ if((i2>>bit)&1){ if(s2[i2]>m1) m1=s2[i2]; } else if(s2[i2]>m0) m0=s2[i2]; }
					if(ns===1){ out[0][idx]=m1-m0; var den=Math.max(m1, m0); out[3][idx]=den>0?(m1-m0)/den:0; }
					else if(ns===2) out[1][idx]=m1-m0; else out[2][idx]=m1-m0;
				}
			}
		}
	}
	return out.map(function(b){ normalizar(b); for(var i=0;i<N;i++) b[i]*=2.83; return b; });
};
/* Cuántos símbolos de Costas caen en el tono esperado (descarta sincronías falsas) */
Etapa2.prototype.sincroDura=function(cs){
	var T=cs.T, n=0;
	for(var g=0;g<this.sync.length;g++){ var ini=this.sync[g][0], pat=this.sync[g][1];
		for(var k=0;k<pat.length;k++){ var s=ini+k, mx=-1, im=0; for(var t=0;t<T;t++){ var v=cs.r[s*T+t]*cs.r[s*T+t]+cs.i[s*T+t]*cs.i[s*T+t]; if(v>mx){ mx=v; im=t; } } if(im===pat[k]) n++; }
	}
	return n;
};
function verificarLdpc(L, iters, proto){
	var r=ldpc(L, iters); if(r.errores>0) return null;
	var a=empacarBits(r.bits, K), crc=extraerCrc(a); a[9]&=0xF8; a[10]=0;
	if(crc!==crc14(a, 82)) return null;
	var pl=new Uint8Array(10); for(var i=0;i<10;i++) pl[i]=proto===FT4 ? a[i]^XOR4[i] : a[i];
	// Una carga útil toda en cero no es un mensaje real
	if(!pl.some(function(v){ return v; })) return null;
	return pl;
}
/* ── OSD: decodificación por estadísticas ordenadas (osd174_91 de WSJT-X), cuando falla la propagación de
   creencias. Se toman los 91 bits más confiables como base (eliminación gaussiana sobre la matriz
   generadora con las columnas ordenadas por |LLR|), se reconstruye la palabra y se prueban los cambios de
   uno y de dos de los bits menos confiables de esa base; gana la palabra más cercana a lo recibido. */
var GFULL=null, PAL=6;
function generadora(){
	if(GFULL) return GFULL; GFULL=[];
	for(var j=0;j<K;j++){ var r=new Uint32Array(PAL); r[j>>5]|=1<<(j&31);
		for(var i=0;i<M;i++) if((LDPC_GEN[i*12+(j>>3)]>>(7-(j&7)))&1){ var n=K+i; r[n>>5]|=1<<(n&31); }
		GFULL.push(r); }
	return GFULL;
}
function bitDe(c, n){ return (c[n>>5]>>>(n&31))&1; }
function osd(L, pares){
	var G=generadora(), perm=new Array(N), n, r, k;
	for(n=0;n<N;n++) perm[n]=n;
	perm.sort(function(a,b){ return Math.abs(L[b])-Math.abs(L[a]); });
	var filas=G.map(function(x){ return x.slice(); }), piv=[], r0=0;
	for(var pi=0;pi<N && r0<K;pi++){
		var col=perm[pi], w=col>>5, b=1<<(col&31), p=-1;
		for(r=r0;r<K;r++) if(filas[r][w]&b){ p=r; break; }
		if(p<0) continue;
		var t=filas[p]; filas[p]=filas[r0]; filas[r0]=t;
		for(r=0;r<K;r++) if(r!==r0 && (filas[r][w]&b)) for(k=0;k<PAL;k++) filas[r][k]^=filas[r0][k];
		piv.push(col); r0++;
	}
	if(r0<K) return null;
	var c0=new Uint32Array(PAL);
	for(k=0;k<K;k++) if(L[piv[k]]>0) for(var q=0;q<PAL;q++) c0[q]^=filas[k][q];
	// Aporte de cambiar cada bit: + |L| si hoy coincide con lo recibido, − |L| si no
	var d0=0, sg=new Float64Array(N);
	for(n=0;n<N;n++){ var dur=L[n]>0?1:0, ok=bitDe(c0, n)===dur; if(!ok) d0+=Math.abs(L[n]); sg[n]=ok?Math.abs(L[n]):-Math.abs(L[n]); }
	function delta(x){ var d=0; for(var q2=0;q2<PAL;q2++){ var v=x[q2]; while(v){ var lb=v&-v, pos=31-Math.clz32(lb); d+=sg[(q2<<5)+pos]; v^=lb; } } return d; }
	var mejor=c0, md=d0, tmp=new Uint32Array(PAL);
	for(k=0;k<K;k++){ var dk=d0+delta(filas[k]); if(dk<md){ md=dk; mejor=new Uint32Array(PAL); for(var q3=0;q3<PAL;q3++) mejor[q3]=c0[q3]^filas[k][q3]; } }
	var T=Math.min(K, pares||0);
	for(var a1=K-T;a1<K;a1++) for(var a2=a1+1;a2<K;a2++){
		for(var q4=0;q4<PAL;q4++) tmp[q4]=filas[a1][q4]^filas[a2][q4];
		var d2=d0+delta(tmp); if(d2<md){ md=d2; mejor=new Uint32Array(PAL); for(var q5=0;q5<PAL;q5++) mejor[q5]=c0[q5]^tmp[q5]; }
	}
	var bits=new Uint8Array(N), duros=0;
	for(n=0;n<N;n++){ bits[n]=bitDe(mejor, n); if(bits[n]!==(L[n]>0?1:0)) duros++; }
	if(erroresParidad(bits)) return null; // no debería pasar: toda combinación de filas es palabra de código
	return {bits:bits, dist:md, duros:duros};
}
var OSD_DUROS=99, OSD_PARES=24;
function verificarOsd(L, proto){
	var r=osd(L, OSD_PARES); if(!r || r.duros>OSD_DUROS) return null;
	var a=empacarBits(r.bits, K), crc=extraerCrc(a); a[9]&=0xF8; a[10]=0;
	if(crc!==crc14(a, 82)) return null;
	var pl=new Uint8Array(10); for(var i=0;i<10;i++) pl[i]=proto===FT4 ? a[i]^XOR4[i] : a[i];
	if(!pl.some(function(v){ return v; })) return null;
	return {payload:pl, duros:r.duros, dist:r.dist};
}

/* Intenta decodificar en la posición fina; devuelve {payload, f1, t, cs} o null */
Etapa2.prototype.decodificar=function(f1, t0){
	var a=this.afinar(f1, t0), cs=this.simbolos(a);
	if(this.sincroDura(cs)<=(this.p===FT8?6:4)) return null;
	var Ls=this.metricas(cs);
	for(var k=0;k<4;k++){ var pl=verificarLdpc(Ls[k], 30, this.p); if(pl) return {payload:pl, f1:a.f1, t:a.t, cs:cs}; }
	// Segunda oportunidad con OSD sobre las métricas de 1 y de 3 símbolos
	if(this.osd) for(var q=0;q<3;q+=2){ var o=verificarOsd(Ls[q], this.p); if(o) return {payload:o.payload, f1:a.f1, t:a.t, cs:cs, osd:o}; }
	return null;
};
/* SNR en 2500 Hz, el mayor de dos métodos que fallan en casos opuestos:
   · contra el piso de toda la banda (percentil 10 del espectro de 200 a 3000 Hz): exacto con ruido
     blanco (calibrado con señales de SNR conocida), pero da de menos en una banda llena;
   · contra los tonos alejados del transmitido en el mismo símbolo (calibrado con lo que informa WSJT-X
     en las 22 grabaciones de ft8_lib): coincide en bandas llenas, pero se satura con señales fuertes. */
Etapa2.prototype.snr=function(cs, ts){
	var T=cs.T, xs=0, lejos=[];
	for(var s=0;s<ts.length;s++){ var t=ts[s];
		xs+=cs.r[s*T+t]*cs.r[s*T+t]+cs.i[s*T+t]*cs.i[s*T+t];
		for(var o=0;o<T;o++) if(Math.abs(o-t)>=(T===8?3:2)) lejos.push(cs.r[s*T+o]*cs.r[s*T+o]+cs.i[s*T+o]*cs.i[s*T+o]);
	}
	xs/=ts.length; lejos.sort(function(a,b){ return a-b; });
	var piso=10*Math.log10(Math.max(xs/this.ruido, 1e-6))+(T===8?-3:3);
	var xn=lejos[lejos.length>>1]/Math.LN2, tonos=0.83*(10*Math.log10(Math.max(xs/xn-1, 0.001))+(T===8?-26:-21))-4.15;
	return Math.max(-28, Math.round(Math.max(piso, tonos)));
};

/* ── Resta de una señal decodificada (subtractft8): amplitud compleja por filtro pasabajos ── */
var PULSOS={};
function pulsoGFSK(proto, nsps){
	var k=proto.nombre+nsps; if(PULSOS[k]) return PULSOS[k];
	var p=new Float64Array(3*nsps), K5=5.336446;
	for(var i=0;i<3*nsps;i++){ var t=i/nsps-1.5; p[i]=(erf(K5*proto.bt*(t+0.5))-erf(K5*proto.bt*(t-0.5)))/2; }
	return (PULSOS[k]=p);
}
function referenciaGFSK(ts, f0, proto, nsps){
	var n=ts.length, nw=n*nsps, dpk=2*Math.PI/nsps, dphi=new Float64Array(nw+2*nsps), pulso=pulsoGFSK(proto, nsps);
	for(var i=0;i<dphi.length;i++) dphi[i]=2*Math.PI*f0/12000;
	for(var s=0;s<n;s++){ var ib=s*nsps; for(var j=0;j<3*nsps;j++) dphi[j+ib]+=dpk*ts[s]*pulso[j]; }
	for(var j2=0;j2<2*nsps;j2++){ dphi[j2]+=dpk*pulso[j2+nsps]*ts[0]; dphi[j2+n*nsps]+=dpk*pulso[j2]*ts[n-1]; }
	var cr=new Float64Array(nw), ci=new Float64Array(nw), phi=0;
	for(var k=0;k<nw;k++){ cr[k]=Math.cos(phi); ci[k]=Math.sin(phi); phi=(phi+dphi[k+nsps])%(2*Math.PI); }
	return {r:cr, i:ci};
}
/* Media móvil centrada, normalizada por las muestras disponibles (corrige los bordes) */
function promedioMovil(a, L){
	var n=a.length, o=new Float64Array(n), h=L>>1, s=0, c=0, i;
	for(i=0;i<Math.min(n, h);i++){ s+=a[i]; c++; }
	for(i=0;i<n;i++){ var ent=i+h, sal=i-h-1; if(ent<n){ s+=a[ent]; c++; } if(sal>=0){ s-=a[sal]; c--; } o[i]=s/c; }
	return o;
}
function restar(x, ref, t0, proto, aplicar){
	var nsps=Math.round(12000*proto.simbolo), n0=Math.round(t0*12000), nf=ref.r.length;
	var ar=new Float64Array(nf), ai=new Float64Array(nf);
	for(var i=0;i<nf;i++){ var j=n0+i; if(j<0 || j>=x.length) continue; ar[i]=x[j]*ref.r[i]; ai[i]=-x[j]*ref.i[i]; }
	// Dos medias móviles en cascada ≈ ventana triangular de un tercio de segundo (FT8) como el filtro de WSJT-X
	var L=Math.round(nsps*1.04), fr=promedioMovil(promedioMovil(ar, L), L), fi=promedioMovil(promedioMovil(ai, L), L), e0=0, e1=0;
	for(var k=0;k<nf;k++){ var q=n0+k; if(q<0 || q>=x.length) continue; var v=x[q]-2*(fr[k]*ref.r[k]-fi[k]*ref.i[k]); e0+=x[q]*x[q]; e1+=v*v; if(aplicar) x[q]=v; }
	return e1/(e0||1);
}
/* Busca el corrimiento de tiempo que deja menos residuo y resta */
function restarFino(x, ts, f0, t0, proto){
	// La forma de onda no depende del instante: se genera una vez y se prueba corrida en el tiempo
	var nsps=Math.round(12000*proto.simbolo), ref=referenciaGFSK(ts, f0, proto, nsps), paso=nsps/32/2/12000, mejor=Infinity, tb=t0;
	for(var k=-2;k<=2;k++){ var r=restar(x, ref, t0+k*paso, proto, false); if(r<mejor){ mejor=r; tb=t0+k*paso; } }
	restar(x, ref, tb, proto, true);
	return mejor;
}

/* ── Tablas de caracteres (text.c) ── */
var T_FULL=0, T_ANS=1, T_AN_SP=2, T_LET_SP=3, T_AN=4, T_NUM=5;
function charn(c, t){
	if(t!==T_AN && t!==T_NUM){ if(c===0) return ' '; c-=1; }
	if(t!==T_LET_SP){ if(c<10) return String.fromCharCode(48+c); c-=10; }
	if(t!==T_NUM){ if(c<26) return String.fromCharCode(65+c); c-=26; }
	if(t===T_FULL){ if(c<5) return '+-./?'[c]; }
	else if(t===T_ANS){ if(c===0) return '/'; }
	return '_';
}
function nchar(ch, t){
	var n=0;
	if(t!==T_AN && t!==T_NUM){ if(ch===' ') return n; n+=1; }
	if(t!==T_LET_SP){ if(ch>='0' && ch<='9') return n+ch.charCodeAt(0)-48; n+=10; }
	if(t!==T_NUM){ if(ch>='A' && ch<='Z') return n+ch.charCodeAt(0)-65; n+=26; }
	if(t===T_FULL){ var i='+-./?'.indexOf(ch); if(i>=0) return n+i; }
	else if(t===T_ANS){ if(ch==='/') return n; }
	return -1;
}
function dd(v, ancho, signo){ var s=v<0?'-':signo?'+':''; v=Math.abs(v); var t=String(v); while(t.length<ancho) t='0'+t; return s+t; }
function esLetra(c){ return c>='A' && c<='Z'; } function esDigito(c){ return c>='0' && c<='9'; }

/* ── Indicativos con hash: los que se transmiten abreviados se resuelven con los que ya se oyeron ── */
var NTOKENS=2063592, MAX22=4194304, MAXGRID4=32400;
function Hashes(){ this.m=new Map(); }
Hashes.prototype.n22=function(call){
	var n58=0n, i=0;
	for(;i<call.length && i<11;i++){ var j=nchar(call[i], T_ANS); if(j<0) return -1; n58=38n*n58+BigInt(j); }
	for(;i<11;i++) n58=38n*n58;
	return Number(((47055833459n*n58)&0xFFFFFFFFFFFFFFFFn)>>42n & 0x3FFFFFn);
};
Hashes.prototype.guardar=function(call){ var h=this.n22(call); if(h>=0){ this.m.set(h, call); if(this.m.size>2000) this.m.delete(this.m.keys().next().value); } return h; };
Hashes.prototype.buscar=function(bits, h){
	var s=bits===22?0:bits===12?10:12;
	if(s===0){ var c=this.m.get(h); return c?'<'+c+'>':'<...>'; }
	for(var e of this.m) if((e[0]>>s)===h) return '<'+e[1]+'>';
	return '<...>';
};

function unpack28(n28, ip, i3, H){
	if(n28<NTOKENS){
		if(n28===0) return 'DE'; if(n28===1) return 'QRZ'; if(n28===2) return 'CQ';
		if(n28<=1002) return 'CQ '+dd(n28-3, 3, false);
		if(n28<=532443){ var n=n28-1003, a=''; for(var i=0;i<4;i++){ a=charn(n%27, T_LET_SP)+a; n=Math.floor(n/27); } return 'CQ '+a.replace(/^ +/,''); }
		return null;
	}
	n28-=NTOKENS;
	if(n28<MAX22) return H.buscar(22, n28);
	var m=n28-MAX22, c=[];
	c[5]=charn(m%27, T_LET_SP); m=Math.floor(m/27);
	c[4]=charn(m%27, T_LET_SP); m=Math.floor(m/27);
	c[3]=charn(m%27, T_LET_SP); m=Math.floor(m/27);
	c[2]=charn(m%10, T_NUM); m=Math.floor(m/10);
	c[1]=charn(m%36, T_AN); m=Math.floor(m/36);
	c[0]=charn(m%37, T_AN_SP);
	var cs=c.join(''), r;
	if(cs.indexOf('3D0')===0 && cs[3]!==' ') r='3DA0'+cs.slice(3).trim();
	else if(cs[0]==='Q' && esLetra(cs[1])) r='3X'+cs.slice(1).trim();
	else r=cs.trim();
	if(r.length<3) return null;
	if(ip){ if(i3===1) r+='/R'; else if(i3===2) r+='/P'; else return null; }
	H.guardar(r);
	return r;
}
function unpackgrid(g, ir){
	if(g<=MAXGRID4){
		var n=g, d3=n%10; n=Math.floor(n/10); var d2=n%10; n=Math.floor(n/10); var l1=n%18; n=Math.floor(n/18); var l0=n%18;
		return (ir?'R ':'')+String.fromCharCode(65+l0, 65+l1)+d2+d3;
	}
	var r=g-MAXGRID4;
	if(r===1) return ''; if(r===2) return 'RRR'; if(r===3) return 'RR73'; if(r===4) return '73';
	return (ir?'R':'')+dd(r-35, 2, true);
}
function bitsTelemetria(p){ var t=new Uint8Array(9), c=0; for(var i=0;i<9;i++){ t[i]=((c<<7)|(p[i]>>1))&0xFF; c=p[i]&1; } return t; }
/* Convierte 10 bytes de carga útil en texto; {texto, tipo} o {error} */
function desempacar(p, H){
	H=H||new Hashes();
	var i3=(p[9]>>3)&7, n3=((p[8]<<2)&4)|((p[9]>>6)&3);
	if(i3===1 || i3===2){
		var a=((p[0]<<21)|(p[1]<<13)|(p[2]<<5)|(p[3]>>3))>>>0;
		var b=(((p[3]&7)<<26)|(p[4]<<18)|(p[5]<<10)|(p[6]<<2)|(p[7]>>6))>>>0;
		var ir=(p[7]&0x20)>>5, g=((p[7]&0x1F)<<10)|(p[8]<<2)|(p[9]>>6);
		var c1=unpack28(a>>>1, a&1, i3, H), c2=unpack28(b>>>1, b&1, i3, H);
		if(c1===null || c2===null) return {error:'indicativo inválido'};
		var ex=unpackgrid(g, ir);
		return {texto:[c1, c2, ex].filter(Boolean).join(' '), tipo:'estandar', campos:[c1, c2, ex]};
	}
	if(i3===4){
		var n12=(p[0]<<4)|(p[1]>>4), n58=0n;
		n58=(BigInt(p[1]&0x0F)<<54n)|(BigInt(p[2])<<46n)|(BigInt(p[3])<<38n)|(BigInt(p[4])<<30n)|(BigInt(p[5])<<22n)|(BigInt(p[6])<<14n)|(BigInt(p[7])<<6n)|(BigInt(p[8])>>2n);
		var iflip=(p[8]>>1)&1, nrpt=((p[8]&1)<<1)|(p[9]>>7), icq=(p[9]>>6)&1, s='';
		for(var i=0;i<11;i++){ s=charn(Number(n58%38n), T_ANS)+s; n58/=38n; }
		var dec=s.trim(); if(dec.length>=3) H.guardar(dec);
		var h=H.buscar(12, n12), x1=iflip?dec:h, x2=iflip?h:dec;
		if(icq) return {texto:'CQ '+x2, tipo:'no_estandar', campos:['CQ', x2, '']};
		var rp=['', 'RRR', 'RR73', '73'][nrpt];
		return {texto:[x1, x2, rp].filter(Boolean).join(' '), tipo:'no_estandar', campos:[x1, x2, rp]};
	}
	if(i3===0 && n3===0){
		var b71=bitsTelemetria(p), c14='';
		for(var k=12;k>=0;k--){ var rem=0; for(var j=0;j<9;j++){ rem=(rem<<8)|b71[j]; b71[j]=Math.floor(rem/42); rem=rem%42; } c14=charn(rem, T_FULL)+c14; }
		return {texto:c14.trim(), tipo:'texto'};
	}
	if(i3===0 && n3===5){ var t=bitsTelemetria(p), hx=''; for(var q=0;q<9;q++) hx+=(t[q]<16?'0':'')+t[q].toString(16).toUpperCase(); return {texto:hx, tipo:'telemetria'}; }
	return {error:'tipo de mensaje no soportado (i3='+i3+', n3='+n3+'): concursos y DXpedición', tipo:'otro'};
}

/* ── Empaquetado (para el codificador de pruebas) ── */
function packBasecall(c){
	var L=c.length; if(L<=2) return -1;
	var c6=[' ',' ',' ',' ',' ',' '];
	if(c.indexOf('3DA0')===0 && L>4 && L<=7){ c6=('3D0'+c.slice(4)+'      ').slice(0,6).split(''); }
	else if(c.indexOf('3X')===0 && esLetra(c[2]) && L<=7){ c6=('Q'+c.slice(2)+'      ').slice(0,6).split(''); }
	else if(esDigito(c[2]) && L<=6){ c6=(c+'      ').slice(0,6).split(''); }
	else if(esDigito(c[1]) && L<=5){ c6=(' '+c+'      ').slice(0,6).split(''); }
	var i0=nchar(c6[0],T_AN_SP), i1=nchar(c6[1],T_AN), i2=nchar(c6[2],T_NUM), i3=nchar(c6[3],T_LET_SP), i4=nchar(c6[4],T_LET_SP), i5=nchar(c6[5],T_LET_SP);
	if(i0<0||i1<0||i2<0||i3<0||i4<0||i5<0) return -1;
	return ((((i0*36+i1)*10+i2)*27+i3)*27+i4)*27+i5;
}
function cqMod(s){
	var nnum=0, nlet=0, m=0;
	for(var i=3;i<8;i++){ var ch=s[i]; if(!ch || ch===' ') break; if(esDigito(ch)) nnum++; else if(esLetra(ch)){ nlet++; m=27*m+(ch.charCodeAt(0)-64); } else return -1; }
	if(nnum===3 && nlet===0) return parseInt(s.slice(3), 10);
	if(nnum===0 && nlet<=4) return 1000+m;
	return -1;
}
function pack28(c, H){
	if(c==='DE') return {n:0, ip:0}; if(c==='QRZ') return {n:1, ip:0}; if(c==='CQ') return {n:2, ip:0};
	if(c.indexOf('CQ ')===0 && c.length<8){ var v=cqMod(c); return v<0?null:{n:3+v, ip:0}; }
	var ip=0, base=c;
	if(/\/[PR]$/.test(c)){ ip=1; base=c.slice(0,-2); }
	var n=packBasecall(base);
	if(n>=0){ H.guardar(c); return {n:NTOKENS+MAX22+n, ip:ip}; }
	if(c.length>=3 && c.length<=11){ var h=H.guardar(c); if(h<0) return null; return {n:NTOKENS+h, ip:0}; }
	return null;
}
function packgrid(g){
	if(!g) return MAXGRID4+1; if(g==='RRR') return MAXGRID4+2; if(g==='RR73') return MAXGRID4+3; if(g==='73') return MAXGRID4+4;
	if(/^[A-R][A-R][0-9][0-9]$/.test(g)) return (((g.charCodeAt(0)-65)*18+(g.charCodeAt(1)-65))*10+(+g[2]))*10+(+g[3]);
	if(g[0]==='R') return (MAXGRID4+35+parseInt(g.slice(1),10))|0x8000;
	return MAXGRID4+35+parseInt(g,10);
}
/* Texto → 10 bytes: estándar, no estándar o texto libre (como ftx_message_encode) */
function empacar(texto, H){
	H=H||new Hashes();
	var t=String(texto).toUpperCase().replace(/\s+/g,' ').trim(), w=t.split(' '), to, de, ex, resto;
	// Como ftx_message_encode: «CQ nnn» o «CQ abcd» (DX, POTA…) viajan juntos como destinatario
	if(w[0]==='CQ' && cqMod(t)>=0){ to='CQ '+w[1]; de=w[2]; ex=w[3]||''; resto=w.slice(4); }
	else if(w[0]==='CQ'){ to='CQ'; de=w[1]; ex=w[2]||''; resto=w.slice(3); }
	else { to=w[0]; de=w[1]; ex=w[2]||''; resto=w.slice(3); }
	// Solo se arma un mensaje estándar si los indicativos parecen indicativos (letras y números); si no, va como texto libre
	function indic(c){ return /^<.+>$/.test(c) || /^CQ( |$)|^DE$|^QRZ$/.test(c) || (/[A-Z]/.test(c) && /[0-9]/.test(c)); }
	if(!resto.length && de && indic(to) && indic(de)){
		var p=std(to, de, ex, H); if(p) return p;
		p=nonstd(to, de, ex, H); if(p) return p;
	}
	return libre(t);
}
function std(to, de, ex, H){
	var a=pack28(to, H), b=pack28(de, H); if(!a || !b) return null;
	var i3=1;
	if(/\/P$/.test(to) || /\/P$/.test(de)){ i3=2; if(/\/R$/.test(to) || /\/R$/.test(de)) return null; }
	var sl=de.indexOf('/'), icq=to==='CQ' || to.indexOf('CQ ')===0;
	if(sl>=2 && icq && !/\/[PR]$/.test(de)) return null;
	if(ex && !/^(RRR|RR73|73|[A-R]{2}\d\d|R?[+-]\d\d)$/.test(ex)) return null;
	var g=packgrid(ex), A=(a.n*2+a.ip)>>>0, B=(b.n*2+b.ip)>>>0;
	if(/\/R$/.test(to)) A|=1; else if(/\/P$/.test(to)){ A|=1; i3=2; }
	var p=new Uint8Array(10);
	p[0]=(A>>>21)&0xFF; p[1]=(A>>>13)&0xFF; p[2]=(A>>>5)&0xFF; p[3]=((A<<3)|(B>>>26))&0xFF;
	p[4]=(B>>>18)&0xFF; p[5]=(B>>>10)&0xFF; p[6]=(B>>>2)&0xFF; p[7]=((B<<6)|(g>>10))&0xFF;
	p[8]=(g>>2)&0xFF; p[9]=((g<<6)|(i3<<3))&0xFF;
	return p;
}
function nonstd(to, de, ex, H){
	var icq=to==='CQ' || to.indexOf('CQ ')===0;
	if(!icq && to.length<3) return null; if(de.length<3) return null;
	var iflip=0, n12=0, c58=de;
	if(!icq){ var c12=to; n12=H.guardar(c12)>>10; c58=de; }
	var n58=0n, s=c58.replace(/^</,''), i;
	for(i=0;i<s.length && i<11 && s[i]!=='<';i++){ var j=nchar(s[i], T_ANS); if(j<0) return null; n58=n58*38n+BigInt(j); }
	H.guardar(s.slice(0,i));
	var nrpt=icq?0:ex==='RRR'?1:ex==='RR73'?2:ex==='73'?3:0, p=new Uint8Array(10);
	function by(sh){ return Number((n58>>BigInt(sh))&0xFFn); }
	p[0]=(n12>>4)&0xFF; p[1]=((n12<<4)|Number((n58>>54n)&0xFn))&0xFF; p[2]=by(46); p[3]=by(38); p[4]=by(30); p[5]=by(22); p[6]=by(14); p[7]=by(6);
	p[8]=((Number(n58&0x3Fn)<<2)|(iflip<<1)|(nrpt>>1))&0xFF; p[9]=((nrpt<<7)|((icq?1:0)<<6)|(4<<3))&0xFF;
	return p;
}
function libre(t){
	if(t.length>13) t=t.slice(0,13);
	var b=new Uint8Array(9);
	for(var i=0;i<13;i++){
		var c=nchar(i<t.length?t[i]:' ', T_FULL); if(c<0) c=0;
		var rem=c; for(var k=8;k>=0;k--){ rem+=b[k]*42; b[k]=rem&0xFF; rem>>=8; }
	}
	var p=new Uint8Array(10), carry=0;
	for(var q=8;q>=0;q--){ p[q]=((b[q]<<1)|(carry>>7))&0xFF; carry=b[q]&0x80; }
	p[9]=0;
	return p;
}

/* ── Codificador: carga útil → tonos (para re-codificar al estimar SNR y para pruebas) ── */
function paridad8(x){ x^=x>>4; x^=x>>2; x^=x>>1; return x&1; }
function codificar174(a){
	var cw=new Uint8Array(22); for(var j=0;j<22;j++) cw[j]=j<12?a[j]:0;
	var mask=0x80>>(K%8), col=11;
	for(var i=0;i<M;i++){
		var s=0; for(var jj=0;jj<12;jj++) s^=paridad8(a[jj]&LDPC_GEN[i*12+jj]);
		if(s) cw[col]|=mask;
		mask>>=1; if(!mask){ mask=0x80; col++; }
	}
	return cw;
}
function tonos(payload, proto){
	var pl=payload;
	if(proto===FT4){ pl=new Uint8Array(10); for(var i=0;i<10;i++) pl[i]=payload[i]^XOR4[i]; }
	var cw=codificar174(agregarCrc(pl)), bit=0, t=[];
	function lee(){ var v=(cw[bit>>3]>>(7-(bit&7)))&1; bit++; return v; }
	if(proto===FT8){
		for(var k=0;k<79;k++){
			if(k<7) t.push(COSTAS8[k]); else if(k>=36 && k<43) t.push(COSTAS8[k-36]); else if(k>=72) t.push(COSTAS8[k-72]);
			else t.push(GRAY8[(lee()<<2)|(lee()<<1)|lee()]);
		}
	} else {
		for(var k4=0;k4<105;k4++){
			if(k4===0 || k4===104) t.push(0);
			else if(k4<5) t.push(COSTAS4[0][k4-1]); else if(k4>=34 && k4<38) t.push(COSTAS4[1][k4-34]);
			else if(k4>=67 && k4<71) t.push(COSTAS4[2][k4-67]); else if(k4>=100 && k4<104) t.push(COSTAS4[3][k4-100]);
			else t.push(GRAY4[(lee()<<1)|lee()]);
		}
	}
	return t;
}
function erf(x){ var s=x<0?-1:1; x=Math.abs(x); var t=1/(1+0.3275911*x), y=1-(((((1.061405429*t-1.453152027)*t)+1.421413741)*t-0.284496736)*t+0.254829592)*t*Math.exp(-x*x); return s*y; }
/* GFSK como gen_ft8.c: devuelve Float32Array con la señal (sin silencio alrededor) */
function sintetizar(ts, f0, proto, tasa){
	tasa=tasa||12000;
	var nsp=Math.round(tasa*proto.simbolo), n=ts.length, nw=n*nsp, dpk=2*Math.PI/nsp, dphi=new Float64Array(nw+2*nsp), pulso=new Float64Array(3*nsp), K5=5.336446;
	for(var i=0;i<dphi.length;i++) dphi[i]=2*Math.PI*f0/tasa;
	for(var i2=0;i2<3*nsp;i2++){ var t=i2/nsp-1.5; pulso[i2]=(erf(K5*proto.bt*(t+0.5))-erf(K5*proto.bt*(t-0.5)))/2; }
	for(var s=0;s<n;s++){ var ib=s*nsp; for(var j=0;j<3*nsp;j++) dphi[j+ib]+=dpk*ts[s]*pulso[j]; }
	for(var j2=0;j2<2*nsp;j2++){ dphi[j2]+=dpk*pulso[j2+nsp]*ts[0]; dphi[j2+n*nsp]+=dpk*pulso[j2]*ts[n-1]; }
	var out=new Float32Array(nw), phi=0;
	for(var k=0;k<nw;k++){ out[k]=Math.sin(phi); phi=(phi+dphi[k+nsp])%(2*Math.PI); }
	var nr=Math.floor(nsp/8); for(var r=0;r<nr;r++){ var env=(1-Math.cos(2*Math.PI*r/(2*nr)))/2; out[r]*=env; out[nw-1-r]*=env; }
	return out;
}

/* ── Remuestreo a 12 kHz (el receptor puede entregar 12 000 o 20 250 Hz) ── */
function remuestrear(x, de, a){
	if(de===a) return x;
	var n=Math.floor(x.length*a/de), y=new Float32Array(n), r=de/a, fc=Math.min(1, a/de), W=16;
	for(var i=0;i<n;i++){
		var c=i*r, i0=Math.floor(c), s=0, ws=0;
		for(var k=i0-W+1;k<=i0+W;k++){
			if(k<0 || k>=x.length) continue;
			var d=c-k, sinc=d===0?1:Math.sin(Math.PI*fc*d)/(Math.PI*fc*d), win=0.5+0.5*Math.cos(Math.PI*d/W), w=fc*sinc*win;
			s+=x[k]*w; ws+=w;
		}
		y[i]=ws?s/ws:0;
	}
	return y;
}

/* ── Decodificación de una ranura completa ── */
function percentil(a, p){ var b=Array.prototype.slice.call(a).sort(function(x,y){ return x-y; }); return b[Math.min(b.length-1, Math.floor(b.length*p))]; }
/* SNR en 2500 Hz como la informa WSJT-X: potencia de los tonos transmitidos frente al piso de ruido por bin */
function estimarSNR(W, c, payload, ruidoDb){
	var ts=tonos(payload, W.p), base=idxCand(W, c), st=W.paso, sum=0, n=0;
	for(var k=0;k<ts.length;k++){ var bl=c.t+k; if(bl<0 || bl>=W.bloques) continue; sum+=Math.pow(10, magDb(W.mag[base+k*st+ts[k]])/10); n++; }
	if(!n) return -30;
	var ruido=Math.pow(10, ruidoDb/10), s=sum/n-ruido, enbw=1.5/(W.p.simbolo*W.osrF);
	return s<=0 ? -30 : Math.round(10*Math.log10(s/ruido)+10*Math.log10(enbw/2500));
}
/* Lo que sale por OSD tiene que parecer un QSO real: mensaje estándar con indicativos bien formados
   (prefijo que no empieza en 0 ni 1, un número, sufijo de letras; sin /P), o CQ, DE, QRZ. */
var RX_CALL=/^(<[A-Z0-9\/]+>|[A-Z2-9][A-Z0-9]?[0-9][A-Z]{1,4}(\/R)?|[A-Z][0-9][0-9][A-Z]{1,4})$/;
function plausibleOsd(m){
	if(m.error || m.tipo!=='estandar' || /<\.\.\.>/.test(m.texto)) return false;
	var c1=m.campos[0], c2=m.campos[1];
	return (/^(CQ( [A-Z0-9]+)?|DE|QRZ)$/.test(c1) || RX_CALL.test(c1)) && RX_CALL.test(c2);
}
/* muestras: Float32Array de una ranura (15 s FT8, 7,5 s FT4), con el inicio de la ranura en `inicio` segundos.
   Devuelve [{texto, snr, dt, freq, tipo, pasada}], sin repetidos, como la lista de WSJT-X.
   opts.profundo=false usa solo la etapa de ft8_lib (más rápida, decodifica menos). */
function decodificar(muestras, tasa, opts){
	opts=opts||{};
	var proto=opts.modo==='FT4'?FT4:FT8, H=opts.hashes||new Hashes(), inicio=opts.inicio||0, profundo=opts.profundo!==false;
	var x=Float32Array.from(remuestrear(muestras, tasa||12000, 12000)), pasadas=profundo?(opts.pasadas||3):1, vistos={}, out=[];
	for(var pasada=1;pasada<=pasadas;pasada++){
		var W=new Monitor(proto, 12000, opts); W.cargar(x);
		var cands=buscarCandidatos(W, opts.candidatos||140, opts.minimo===undefined?10:opts.minimo), E=profundo?new Etapa2(x, proto):null, nuevos=[];
		if(E) E.osd=opts.osd!==false;
		var ruido=magDb(percentil(W.mag.subarray(0, W.bloques*W.paso), 0.3));
		for(var i=0;i<cands.length;i++){
			var c=cands[i], f=(W.minBin+c.f+c.fs/W.osrF)/proto.simbolo, t0=(c.t+c.ts/W.osrT-1)*proto.simbolo;
			var r=decodificarCandidato(W, c, opts.iteraciones||25), pl=r?r.payload:null, fino=null;
			if(!pl && E){ fino=E.decodificar(f, t0); if(fino) pl=fino.payload; }
			if(!pl) continue;
			var clave=Array.prototype.join.call(pl, ','); if(vistos[clave]) continue; vistos[clave]=1;
			if(E && !fino){ var a=E.afinar(f, t0); fino={f1:a.f1, t:a.t, cs:E.simbolos(a)}; }
			var ts=tonos(pl, proto), m=desempacar(pl, H);
			// Lo que sale por OSD tiene que ser un mensaje con indicativos conocidos: el texto libre o un hash sin resolver se descartan
			if(fino && fino.osd && !plausibleOsd(m)){ delete vistos[clave]; continue; }
			if(opts.diag && fino && fino.osd) opts.diag.push({texto:m.texto, duros:fino.osd.duros, dist:fino.osd.dist});
			out.push({texto:m.texto||'', error:m.error||'', tipo:m.tipo||'', campos:m.campos||null, freq:Math.round(fino?fino.f1:f), dt:Math.round(((fino?fino.t:t0)-inicio-0.5)*10)/10,
				snr:fino?E.snr(fino.cs, ts):estimarSNR(W, c, pl, ruido), pasada:pasada, osd:!!(fino && fino.osd)});
			if(fino) nuevos.push([ts, fino.f1, fino.t]);
		}
		if(!nuevos.length || pasada===pasadas) break;
		nuevos.forEach(function(n){ restarFino(x, n[0], n[1], n[2], proto); });
	}
	return out.sort(function(a,b){ return a.freq-b.freq; });
}

return { FT8:FT8, FT4:FT4, Monitor:Monitor, Hashes:Hashes, buscarCandidatos:buscarCandidatos, decodificarCandidato:decodificarCandidato, decodificar:decodificar,
	desempacar:desempacar, empacar:empacar, tonos:tonos, sintetizar:sintetizar, remuestrear:remuestrear, crc14:crc14, planFFT:planFFT, fft:fft };
});
