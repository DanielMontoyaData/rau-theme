/*!
 * Decodificador JS8 (JS8Call, submodo Normal) para el receptor del sitio.
 *
 * Portado del código de JS8Call (https://github.com/js8call/js8call), © Jordan Sherer KN4CRD y colaboradores,
 * con licencia GNU GPL v3: este archivo se distribuye también bajo GPL v3 (compatible con la GPL v2 o posterior
 * del tema). Tablas del código LDPC (174,87) y de paridad, CRC-12, Costas, varicode, Huffman y la compresión
 * JSC tomados de JS8.cpp, varicode.cpp, jsc.cpp y jsc_map.cpp. La sincronía, la búsqueda de candidatos y la
 * etapa fina son las de ftx.js (port de ft8_lib), que JS8 comparte con FT8: 79 símbolos de 0,16 s en 8 tonos
 * de 6,25 Hz, ranuras de 15 s.
 *
 * El diccionario JSC (262 144 palabras, assets/radio/js8-jsc.json.gz) se carga aparte con usarDiccionario().
 */
(function(raiz, fabrica){ if(typeof module==='object' && module.exports) module.exports=fabrica(require('./ftx.js')); else raiz.RauJS8=fabrica(raiz.RauFTX); })(typeof self!=='undefined'?self:this, function(F){
'use strict';
var MN=[0,24,68,1,4,72,2,31,67,3,50,60,5,62,69,6,32,78,7,49,85,8,36,42,9,40,64,10,13,63,11,74,76,12,22,80,14,15,81,16,55,65,17,52,59,18,30,51,19,66,83,20,28,71,21,23,43,25,34,75,26,35,37,27,39,41,29,53,54,33,48,86,38,56,57,44,73,82,45,61,79,46,47,84,58,70,77,0,49,52,1,46,83,2,24,78,3,5,13,4,6,79,7,33,54,8,35,68,9,42,82,10,22,73,11,16,43,12,56,75,14,26,55,15,27,28,17,18,58,19,39,62,20,34,51,21,53,63,23,61,77,25,31,76,29,71,84,30,64,86,32,38,50,36,47,74,37,69,70,40,41,67,44,66,85,45,80,81,48,65,72,57,59,65,60,64,84,0,13,20,1,12,58,2,66,81,3,31,72,4,35,53,5,42,45,6,27,74,7,32,70,8,48,75,9,57,63,10,47,67,11,18,44,14,49,60,15,21,25,16,71,79,17,39,54,19,34,50,22,24,33,23,62,86,26,38,73,28,77,82,29,69,76,30,68,83,21,36,85,37,40,80,41,43,56,46,52,61,51,55,78,59,74,80,0,38,76,1,15,40,2,30,53,3,35,77,4,44,64,5,56,84,6,13,48,7,20,45,8,14,71,9,19,61,10,16,70,11,33,46,12,67,85,17,22,42,18,63,72,23,47,78,24,69,82,25,79,86,26,31,39,27,55,68,28,62,65,29,41,49,32,36,81,34,59,73,37,54,83,43,51,60,50,52,71,57,58,66,46,55,75,0,18,36,1,60,74,2,7,65,3,59,83,4,33,38,5,25,52,6,31,56,8,51,66,9,11,14,10,50,68,12,13,64,15,30,42,16,19,35,17,79,85,20,47,58,21,39,45,22,32,61,23,29,73,24,41,63,26,48,84,27,37,72,28,43,80,34,67,69,40,62,75,44,48,70,49,57,86,47,53,82,12,54,78,76,77,81,0,1,23,2,5,74,3,55,86,4,43,52,6,49,82,7,9,27,8,54,61,10,28,66,11,32,39,13,15,19,14,34,72,16,30,38,17,35,56,18,45,75,20,41,83,21,33,58,22,25,60,24,59,64,26,63,79,29,36,65,31,44,71,37,50,85,40,76,78,42,55,67,46,73,81,39,51,77,53,60,70,45,57,68];
var NM_N=[6,6,6,6,6,6,6,6,6,6,6,6,6,6,6,6,6,6,6,6,6,6,6,6,6,6,6,6,6,6,6,6,6,6,6,6,6,6,6,7,6,6,6,6,6,7,6,6,6,6,6,6,6,6,6,7,6,6,6,6,7,6,5,6,6,6,6,6,6,5,6,6,6,6,6,6,6,6,6,6,5,6,6,6,5,6,6];
var NM=[0,29,59,88,117,146,-1,1,30,60,89,118,146,-1,2,31,61,90,119,147,-1,3,32,62,91,120,148,-1,1,33,63,92,121,149,-1,4,32,64,93,122,147,-1,5,33,65,94,123,150,-1,6,34,66,95,119,151,-1,7,35,67,96,124,152,-1,8,36,68,97,125,151,-1,9,37,69,98,126,153,-1,10,38,70,99,125,154,-1,11,39,60,100,127,144,-1,9,32,59,94,127,155,-1,12,40,71,96,125,156,-1,12,41,72,89,128,155,-1,13,38,73,98,129,157,-1,14,42,74,101,130,158,-1,15,42,70,102,117,159,-1,16,43,75,97,129,155,-1,17,44,59,95,131,160,-1,18,45,72,82,132,161,-1,11,37,76,101,133,162,-1,18,46,77,103,134,146,-1,0,31,76,104,135,163,-1,19,47,72,105,122,162,-1,20,40,78,106,136,164,-1,21,41,65,107,137,151,-1,17,41,79,108,138,153,-1,22,48,80,109,134,165,-1,15,49,81,90,128,157,-1,2,47,62,106,123,166,-1,5,50,66,110,133,154,-1,23,34,76,99,121,161,-1,19,44,75,111,139,156,-1,20,35,63,91,129,158,-1,7,51,82,110,117,165,-1,20,52,83,112,137,167,-1,24,50,78,88,121,157,-1,21,43,74,106,132,154,171,8,53,83,89,140,168,-1,21,53,84,109,135,160,-1,7,36,64,101,128,169,-1,18,38,84,113,138,149,-1,25,54,70,92,141,166,-1,26,55,64,95,132,159,173,27,30,85,99,116,170,-1,27,51,69,103,131,143,-1,23,56,67,94,136,141,-1,6,29,71,109,142,150,-1,3,50,75,114,126,167,-1,15,44,86,113,124,171,-1,14,29,85,114,122,149,-1,22,45,63,90,143,172,-1,22,34,74,112,144,152,-1,13,40,86,107,116,148,169,24,39,84,93,123,158,-1,24,57,68,115,142,173,-1,28,42,60,115,131,161,-1,14,57,87,111,120,163,-1,3,58,71,113,118,162,172,26,46,85,97,133,152,-1,4,43,77,108,140,-1,-1,9,45,68,102,135,164,-1,8,49,58,92,127,163,-1,13,56,57,108,119,165,-1,16,54,61,115,124,153,-1,2,53,69,100,139,169,-1,0,35,81,107,126,173,-1,4,52,80,104,139,-1,-1,28,52,66,98,141,172,-1,17,48,73,96,114,166,-1,1,56,62,102,137,156,-1,25,37,78,111,134,170,-1,10,51,65,87,118,147,-1,19,39,67,116,140,159,-1,10,47,80,88,145,168,-1,28,46,79,91,145,171,-1,5,31,86,103,144,168,-1,26,33,73,105,130,164,-1,11,55,83,87,138,-1,-1,12,55,61,110,145,170,-1,25,36,79,104,143,150,-1,16,30,81,112,120,160,-1,27,48,58,93,136,-1,-1,6,54,82,100,130,167,-1,23,49,77,105,142,148,-1];
var PARIDAD='23bba830e23b6b6f50982e1f8e55da218c5df3309052ca7b3217cd92bd59a5ae2056f78313537d0f4382964e6be396b5e2e819e373340c293548a138858328af4210cb6c6afcdc28bb3f7c6e863f2a86f5c5bd225c961150849dd2d63673481860f62c56cdaec6e7ae14b43feeee04ef5cfa3766ba778f45a4c525ae4bd4f627320a397441fd9520b2e4abeb2f989c7fb36c24085a34d8c1dbc440fc3e44bb7d2bb2756e44d38ab0a1d2e52a8ec3bc763d0f929ef3949bd84d473445d3814f504064f80549aef14dbf263825d0bd04b05edb714f8f64e8ac7af1a76e8d0274de71e7c1a8055eb051f81573dd4049b082de14d8f937f31822e57c562370b6537f417e61d1a7085336ecbd7c73b9cd34c3720c8a3d188ea477f6fa41317a4e1ac4672b549cd6dba79bcca377253773ea678367c3f60dbd816fba1543f721dc72ca4186dd44c3121565cf5c29c29dba9c545e267762fe1616d78018d0b4745ca0f2fe37802941d66dde02b99ca9fa8e50bcb032c85e330483f640f1a48a8ebc0443ea3776af54ccfbae916afde6a8fc906976c35669e79ce0f08a91fb2e1f78290619a8cc9da55fe046d0cb3a770cd36d662a69ae24b74dcbd840907b01280f03c0323946d037db825175d851f3af001bf1490607c54032660ede0af7723161ec223080be86eca9afa0f6b01d92305edc7a8dec79a51e8ac53880229059dfa2bb20ef7ef73ad46abb212d9739dfc02580f2f6ad4824b87c80ebfce466d747bfc5fd65ef70fbd9bc612f63acc025b6ab476f7c05209a0abb530b9e7e34b045b7ab6242b77474d9f11a6c280d2a0523d9c4bc5946f1627701a2d692fd9449e68d9071b7e7a6a2eed6965ebf4f56e073271f6ab4bf80c0fc3ec4fb7d2bb275664457da6d13cb96a7689b2790a9fa2eefa6f8796a355772164cc861bdd803c547f2accc6de59755420925f90ed2a0c0033a52ab6299802fd2b274db8abd3c6f396ea35697d4169cb33e7435718d9081cfc6f18c35b1e1f17114481a2a0df8a23583f82d6c081c29a10d468ccdbcecb62c4142bf42b01e71076acca6573f3dc8b16c9d19f746c87af9a5d5206abca532a8012dee2198eba82b19a1dab1ca4ea2e3d173bad4379cb33ec97be83ce413f9acc85b0f7742bca86b8012609a37d8e0af9258b9e8c5f9b235ad3fb0faeb5f1b0c30dc6114e08483043fd3f38a8acd921fdf59e882683763f695e45ecd0135aca9d6e6ae2e547dd7a05f6597aac51614cd0f642fc0c5fe3a65ca3a0a1dfd7eee29c2e827e0c8b5dffc335095dcdcaf2a3dd01a59d86310743ec7528abdb889efbe39a510a1183f231f212055371cf3e2a2';

var N=174, K=87, M=87;
var COSTAS=[4,2,5,6,1,3,0];                      // modo Normal (JS8A): la misma matriz en los tres bloques
var JS8={nombre:'JS8', simbolo:0.160, ranura:15, nn:79, nd:58, tonos:8, bt:2.0, costas:COSTAS, gray:[0,1,2,3,4,5,6,7]};

/* ── Código ── */
function paridad(i, j){ var h=parseInt(PARIDAD[i*22+(j>>2)], 16); return (h>>(3-(j&3)))&1; }
/* CRC-12 «aumentado» de boost (polinomio 0xC06) sobre 11 bytes, XOR 42 */
function crc12(b){
	var r=0;
	for(var i=0;i<b.length;i++) for(var k=7;k>=0;k--){ var top=(r>>11)&1; r=((r<<1)|((b[i]>>k)&1))&0xFFF; if(top) r^=0xC06; }
	return r^42;
}
/* 72 bits de mensaje + 3 de tipo → 79 tonos */
function codificar(bits72, tipo){
	var b=new Uint8Array(11), i;
	for(i=0;i<72;i++) if(bits72[i]) b[i>>3]|=0x80>>(i&7);
	b[9]|=(tipo&7)<<5;
	var c=crc12(b); b[9]|=(c>>7)&0x1F; b[10]=(c&0x7F)<<1;
	var msg=[]; for(i=0;i<87;i++) msg.push((b[i>>3]>>(7-(i&7)))&1);
	var par=[]; for(i=0;i<87;i++){ var s=0; for(var j=0;j<87;j++) if(msg[j] && paridad(i,j)) s^=1; par.push(s); }
	var t=[];
	for(var k=0;k<79;k++){
		if(k<7 || (k>=36 && k<43) || k>=72){ t.push(COSTAS[k<7?k:k<43?k-36:k-72]); continue; }
		var src=k<36?par:msg, o=(k<36?k-7:k-43)*3; t.push((src[o]<<2)|(src[o+1]<<1)|src[o+2]);
	}
	return t;
}
/* Propagación de creencias (bpdecode174 de JS8Call); la palabra lleva primero la paridad y después el mensaje */
function atanhR(x){ if(x>0.9999) x=0.9999; if(x<-0.9999) x=-0.9999; return 0.5*Math.log((1+x)/(1-x)); }
function bp(llr){
	var tov=new Float64Array(N*3), toc=new Float64Array(M*7), th=new Float64Array(M*7), zn=new Float64Array(N), cw=new Uint8Array(N), i, j, k, ncnt=0, nclast=0;
	for(i=0;i<M;i++) for(j=0;j<NM_N[i];j++) toc[i*7+j]=llr[NM[i*7+j]];
	for(var it=0;it<=30;it++){
		for(i=0;i<N;i++){ zn[i]=llr[i]+tov[i*3]+tov[i*3+1]+tov[i*3+2]; cw[i]=zn[i]>0?1:0; }
		var ncheck=0;
		for(i=0;i<M;i++){ var s=0; for(j=0;j<NM_N[i];j++) s+=cw[NM[i*7+j]]; if(s&1) ncheck++; }
		if(ncheck===0){
			var nerr=0; for(i=0;i<N;i++) if((2*cw[i]-1)*llr[i]<0) nerr++;
			return {cw:cw, errores:nerr};
		}
		if(it>0){ var nd=ncheck-nclast; ncnt=nd<0?0:ncnt+1; if(ncnt>=5 && it>=10 && ncheck>15) return null; }
		nclast=ncheck;
		// Mensajes de bits a verificaciones
		for(i=0;i<M;i++) for(j=0;j<NM_N[i];j++){
			var ib=NM[i*7+j], v=zn[ib];
			for(k=0;k<3;k++) if(MN[ib*3+k]===i) v-=tov[ib*3+k];
			toc[i*7+j]=v; th[i*7+j]=Math.tanh(-v/2);
		}
		// Mensajes de verificaciones a bits
		for(i=0;i<N;i++) for(j=0;j<3;j++){
			var ch=MN[i*3+j], p=1;
			for(k=0;k<NM_N[ch];k++) if(NM[ch*7+k]!==i) p*=th[ch*7+k];
			tov[i*3+j]=2*atanhR(-p);
		}
	}
	return null;
}
/* Los 87 bits decodificados → 72 de mensaje y 3 de tipo, si el CRC cuadra */
function verificar(cw){
	var b=new Uint8Array(11), i;
	for(i=0;i<87;i++) if(cw[87+i]) b[i>>3]|=0x80>>(i&7);
	var crc=((b[9]&0x1F)<<7)|(b[10]>>1); b[9]&=0xE0; b[10]=0;
	if(crc!==crc12(b)) return null;
	var bits=[]; for(i=0;i<72;i++) bits.push(cw[87+i]);
	return {bits:bits, tipo:(cw[87+72]<<2)|(cw[87+73]<<1)|cw[87+74]};
}
function intentar(L){
	var r=bp(L); if(!r || r.errores>=60) return null;
	var todo0=true; for(var i=0;i<N;i++) if(r.cw[i]){ todo0=false; break; }
	if(todo0) return null;
	return verificar(r.cw);
}
function normalizar(L){ var s=0, s2=0, n=L.length; for(var i=0;i<n;i++){ s+=L[i]; s2+=L[i]*L[i]; } var v=s2/n-(s/n)*(s/n), sg=Math.sqrt(v>0?v:s2/n); for(var j=0;j<n;j++) L[j]=L[j]/sg*2.83; return L; }

/* ── Mensajes (varicode.cpp y decodedtext.cpp de JS8Call) ── */
var ALFANUM='0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ /@';
var NBASECALL=37*36*10*27*27*27, NBASEGRID=180*180, NUSERGRID=NBASEGRID+10, NMAXGRID=(1<<15)-1;
var GRUPOS=['<....>','@ALLCALL','@JS8NET','@DX/NA','@DX/SA','@DX/EU','@DX/AS','@DX/AF','@DX/OC','@DX/AN','@REGION/1','@REGION/2','@REGION/3',
	'@GROUP/0','@GROUP/1','@GROUP/2','@GROUP/3','@GROUP/4','@GROUP/5','@GROUP/6','@GROUP/7','@GROUP/8','@GROUP/9','@COMMAND','@CONTROL','@NET','@NTS',
	'@RESERVE/0','@RESERVE/1','@RESERVE/2','@RESERVE/3','@RESERVE/4','@APRSIS','@RAGCHEW','@JS8','@EMCOMM','@ARES','@MARS','@AMRRON','@RACES','@RAYNET',
	'@RADAR','@SKYWARN','@CQ','@HB','@QSO','@QSOPARTY','@CONTEST','@FIELDDAY','@SOTA','@IOTA','@POTA','@QRP','@QRO'];
// Comandos dirigidos: el texto que JS8Call muestra para cada número (el primero en orden de clave, como QMap::key)
var CMDS={'-1':' HB',0:' SNR?',1:' DIT DIT',2:' NACK',3:' HEARING?',4:' GRID?',5:'>',6:' STATUS?',7:' STATUS',8:' HEARING',9:' MSG',10:' MSG TO:',11:' QUERY',
	12:' QUERY MSGS',13:' QUERY CALL',14:' ACK',15:' GRID',16:' INFO?',17:' INFO',18:' FB',19:' HW CPY?',20:' SK',21:' RR',22:' QSL?',23:' QSL',24:' CMD',
	25:' SNR',26:' NO',27:' YES',28:' 73',29:' HEARTBEAT SNR',30:' AGN?',31:' '};
var CQS=['CQ CQ CQ','CQ DX','CQ QRP','CQ CONTEST','CQ FIELD','CQ FD','CQ CQ','CQ'];
var HUFF={' ':'01','E':'100','T':'1101','A':'0011','O':'11111','I':'11100','N':'10111','S':'10100','H':'00011','R':'00000','D':'111011','L':'110011','C':'110001',
	'U':'101101','M':'101011','W':'001011','F':'001001','G':'000101','Y':'000011','P':'1111011','B':'1111001','.':'1110100','V':'1100101','K':'1100100','-':'1100001',
	'+':'1100000','?':'1011001','!':'1011000','"':'1010101','X':'1010100','0':'0010101','J':'0010100','1':'0010001','Q':'0010000','2':'0001001','Z':'0001000',
	'3':'0000101','5':'0000100','4':'11110101','9':'11110100','8':'11110001','6':'11110000','7':'11101011','/':'11101010'};
var HUFF_INV={}; Object.keys(HUFF).forEach(function(k){ HUFF_INV[HUFF[k]]=k; });
function num(bits, a, n){ var v=0; for(var i=0;i<n;i++) v=v*2+bits[a+i]; return v; }
function snrTxt(s){ if(s<-60 || s>60) return ''; var a=Math.abs(s); return (s>=0?'+':'-')+(a<10?'0':'')+a; }
function indicativo(v, portatil){
	if(v>NBASECALL && v<=NBASECALL+GRUPOS.length) return GRUPOS[v-NBASECALL-1];
	var w=[];
	w[5]=ALFANUM[v%27+10]; v=Math.floor(v/27); w[4]=ALFANUM[v%27+10]; v=Math.floor(v/27); w[3]=ALFANUM[v%27+10]; v=Math.floor(v/27);
	w[2]=ALFANUM[v%10]; v=Math.floor(v/10); w[1]=ALFANUM[v%36]; v=Math.floor(v/36); w[0]=ALFANUM[v]||' ';
	var c=w.join('');
	if(c.indexOf('3D0')===0) c='3DA0'+c.slice(3);
	if(c[0]==='Q' && c[1]>='A' && c[1]<='Z') c='3X'+c.slice(1);
	c=c.trim(); return portatil ? c+'/P' : c;
}
function alfanum50(bits, a){
	// 50 bits no caben con exactitud en un double si se multiplica; se lee como alto*2^25+bajo
	var v=num(bits, a, 25)*33554432+num(bits, a+25, 25), w=[];
	function sac(m){ var r=v%m; v=Math.floor(v/m); return r; }
	w[10]=ALFANUM[sac(38)]; w[9]=ALFANUM[sac(38)]; w[8]=ALFANUM[sac(38)]; w[7]=sac(2)?'/':' ';
	w[6]=ALFANUM[sac(38)]; w[5]=ALFANUM[sac(38)]; w[4]=ALFANUM[sac(38)]; w[3]=sac(2)?'/':' ';
	w[2]=ALFANUM[sac(38)]; w[1]=ALFANUM[sac(38)]; w[0]=ALFANUM[sac(39)]||'';
	return w.join('').replace(/ /g, '');
}
function cuadricula(v){
	if(v>NBASEGRID) return '';
	var dlat=v%180-90, dlong=Math.floor(v/180)*2-180+2;
	var nlong=Math.trunc(60*(180-dlong)/5), nlat=Math.trunc(60*(dlat+90)/2.5);
	return String.fromCharCode(65+Math.trunc(nlong/240))+String.fromCharCode(65+Math.trunc(nlat/240))+Math.trunc((nlong%240)/24)+Math.trunc((nlat%240)/24);
}
function huff(bits){
	var t='', cod='';
	for(var i=0;i<bits.length;i++){ cod+=bits[i]; if(HUFF_INV[cod]!==undefined){ t+=HUFF_INV[cod]; cod=''; } else if(cod.length>8) break; }
	return t;
}
/* Diccionario de compresión JSC (262 144 palabras): se carga aparte y solo si hace falta */
var JSC=null;
function usarDiccionario(lista){ JSC=lista; }
function jsc(bits){
	if(!JSC) return null;
	var s=7, c=9, base=[0, s, s+s*c, s+s*c+s*c*c, s+s*c+s*c*c+s*c*c*c, s+s*c+s*c*c+s*c*c*c+s*c*c*c*c, 0, 0];
	base[6]=base[5]+s*Math.pow(c,5); base[7]=base[6]+s*Math.pow(c,6);
	var bytes=[], sep=[], i=0, out='';
	while(i+4<=bits.length){ var b=num(bits, i, 4); bytes.push(b); i+=4; if(b<s){ if(bits.length-i>0 && bits[i]) sep.push(bytes.length-1); i+=1; } }
	var ini=0;
	while(ini<bytes.length){
		var k=0, j=0;
		while(ini+k<bytes.length && bytes[ini+k]>=s){ j=j*c+(bytes[ini+k]-s); k++; }
		if(j>=262144 || ini+k>=bytes.length) break;
		j=j*s+bytes[ini+k]+base[k]; if(j>=262144) break;
		out+=JSC[j];
		if(sep.length && sep[0]===ini+k){ out+=' '; sep.shift(); }
		ini+=k+1;
	}
	return out;
}
function recortar(bits, desde){ var n=bits.lastIndexOf(0); return n>desde ? bits.slice(desde, n) : []; }
/* Texto de un marco, en el orden de estrategias de JS8Call: datos rápidos, datos, latido, compuesto, dirigido */
function interpretar(bits, tipo){
	var r={tipo:'', texto:'', grupo:'', de:'', para:''};
	if(tipo&4){ // datos rápidos (submodos más veloces): todo comprimido
		var t0=jsc(recortar(bits, 0)); r.tipo='datos'; r.texto=t0===null?'[texto comprimido: falta el diccionario]':t0; r.comprimido=true; return r;
	}
	if(bits[0]){ // datos del modo Normal: [1][comprimido][70]
		var resto=bits.slice(1), comp=resto[0], cuerpo=recortar(resto, 1);
		r.tipo='datos'; r.comprimido=!!comp;
		if(comp){ var t1=jsc(cuerpo); r.texto=t1===null?'[texto comprimido: falta el diccionario]':t1; } else r.texto=huff(cuerpo);
		return r;
	}
	var flag=num(bits, 0, 3), rem=num(bits, 64, 8);
	if(flag===3){ // dirigido: [3][28 de][28 para][5 cmd] + [1 port. de][1 port. para][6 extra]
		var de=indicativo(num(bits, 3, 28), (rem>>7)&1), para=indicativo(num(bits, 31, 28), (rem>>6)&1), cmd=CMDS[num(bits, 59, 5)%32]||' ', ex=rem%64, partes=[cmd];
		if(ex!==0) partes.push(cmd===' SNR' || cmd===' HEARTBEAT SNR' ? snrTxt(ex-31) : String(ex-31));
		r.tipo='dirigido'; r.de=de; r.para=para; r.cmd=cmd.trim(); r.texto=de+': '+para+partes.join(' ')+' ';
		if(para[0]==='@') r.grupo=para;
		return r;
	}
	// Latido y compuesto: [3][50 indicativo][11] + [5][3]
	var ind=alfanum50(bits, 3), n=(num(bits, 53, 11)<<5)|(rem>>3), b3=rem&7;
	if(flag===0){
		var grid=cuadricula(n&0x7FFF), alt=(n>>15)&1;
		r.tipo=alt?'cq':'latido'; r.de=ind; r.cuadricula=grid;
		r.texto=ind+': '+(alt?'@ALLCALL '+CQS[b3]:'@HB HEARTBEAT')+(grid?' '+grid:'')+' ';
		return r;
	}
	if(flag===1 || flag===2){
		var extra='';
		if(n<=NBASEGRID) extra=' '+cuadricula(n);
		else if(n>=NUSERGRID && n<NMAXGRID){ var v=n-NUSERGRID, c2, nn=0; if(v&128){ nn=v&63; c2=v&64?29:25; } else c2=v&127; var cs=CMDS[c2]||''; extra=cs+((c2===25||c2===29)?' '+snrTxt(nn-31):''); }
		r.tipo='compuesto'; r.de=ind; r.texto=flag===1 ? ind+': ' : ind+extra+' ';
		if(flag===1 && extra) r.cuadricula=extra.trim();
		return r;
	}
	r.tipo='otro'; return r;
}

/* ── Decodificación de una ranura de 15 s (muestras desde el inicio de la ranura) ── */
function decodificar(muestras, tasa, opts){
	opts=opts||{};
	var x=Float32Array.from(F.remuestrear(muestras, tasa||12000, 12000)), profundo=opts.profundo!==false, pasadas=profundo?(opts.pasadas||3):1, vistos={}, out=[];
	// Varias pasadas: lo decodificado se resta del audio y aparecen las señales que tapaba (como JS8Call en profundidad 2 y 3)
	for(var pasada=1;pasada<=pasadas;pasada++){
		var W=new F.Monitor(JS8, 12000, opts); W.cargar(x);
		var cands=F.buscarCandidatos(W, opts.candidatos||120, opts.minimo===undefined?10:opts.minimo);
		var E=profundo?new F.Etapa2(x, JS8):null, nuevos=[];
		for(var i=0;i<cands.length;i++){
			var c=cands[i], f=(W.minBin+c.f+c.fs/W.osrF)/JS8.simbolo, t0=(c.t+c.ts/W.osrT-1)*JS8.simbolo;
			var r=intentar(primeraEtapa(W, c)), fino=null;
			if(!r && E){
				var a=E.afinar(f, t0), cs=E.simbolos(a);
				if(E.sincroDura(cs)>6){ var Ls=E.metricas(cs); for(var q=0;q<4 && !r;q++) r=intentar(Ls[q]); if(r) fino={f1:a.f1, t:a.t, cs:cs}; }
			}
			if(!r) continue;
			var clave=r.bits.join('')+r.tipo; if(vistos[clave]) continue; vistos[clave]=1;
			if(E && !fino){ var a2=E.afinar(f, t0); fino={f1:a2.f1, t:a2.t, cs:E.simbolos(a2)}; }
			var m=interpretar(r.bits, r.tipo), ts=codificar(r.bits, r.tipo);
			out.push({texto:m.texto, tipo:m.tipo, de:m.de, para:m.para, grupo:m.grupo, cuadricula:m.cuadricula||'', i3:r.tipo, primero:!!(r.tipo&1), ultimo:!!(r.tipo&2),
				freq:Math.round(fino?fino.f1:f), dt:Math.round(((fino?fino.t:t0)-0.5)*10)/10, snr:fino?E.snr(fino.cs, ts):null, bits:r.bits.join(''), pasada:pasada});
			if(fino) nuevos.push([ts, fino.f1, fino.t]);
		}
		if(!nuevos.length || pasada===pasadas) break;
		nuevos.forEach(function(n){ F.restarFino(x, n[0], n[1], n[2], JS8); });
	}
	return out.sort(function(a,b){ return a.freq-b.freq; });
}
function primeraEtapa(W, c){
	var L=new Float64Array(N), base=(((c.t*W.osrT)+c.ts)*W.osrF+c.fs)*W.bins+c.f, st=W.paso, mag=W.mag;
	for(var k=0;k<58;k++){
		var si=k+(k<29?7:14), b=3*k, bl=c.t+si;
		if(bl<0 || bl>=W.bloques) continue;
		var p=base+si*st, s=[]; for(var j=0;j<8;j++) s[j]=mag[p+j]*0.5-120;
		L[b]=Math.max(s[4],s[5],s[6],s[7])-Math.max(s[0],s[1],s[2],s[3]);
		L[b+1]=Math.max(s[2],s[3],s[6],s[7])-Math.max(s[0],s[1],s[4],s[5]);
		L[b+2]=Math.max(s[1],s[3],s[5],s[7])-Math.max(s[0],s[2],s[4],s[6]);
	}
	return normalizar(L);
}
/* Para pruebas: sintetiza una transmisión JS8 Normal (tonos en fase continua, sin conformado) */
function sintetizar(tonos, f0, tasa, inicio){
	var ns=Math.round(tasa*JS8.simbolo), n=Math.round(tasa*JS8.ranura), y=new Float32Array(n), fase=0, o=Math.round((inicio===undefined?0.5:inicio)*tasa);
	for(var k=0;k<79;k++) for(var i=0;i<ns;i++){ var idx=o+k*ns+i; if(idx>=n) break; fase+=2*Math.PI*(f0+tonos[k]*6.25)/tasa; y[idx]=Math.sin(fase); }
	return y;
}
/* Arma mensajes de varios marcos por frecuencia (el primero lleva la marca «primero», el último «último») */
function Ensamblador(){ this.abiertos={}; }
Ensamblador.prototype.agregar=function(d, hora){
	var k=Math.round(d.freq/10), ab=this.abiertos, clave=ab[k]?k:ab[k-1]?k-1:ab[k+1]?k+1:k, m=ab[clave];
	if(d.primero || !m){ m=ab[clave]={freq:d.freq, texto:'', inicio:hora, partes:0}; }
	m.texto+=d.texto; m.partes++; m.ultima=hora;
	if(d.ultimo){ delete ab[clave]; return {freq:m.freq, texto:m.texto.trim(), inicio:m.inicio, fin:hora, partes:m.partes}; }
	return null;
};

return { JS8:JS8, decodificar:decodificar, interpretar:interpretar, codificar:codificar, crc12:crc12, sintetizar:sintetizar, usarDiccionario:usarDiccionario, Ensamblador:Ensamblador,
	_bp:bp, _verificar:verificar };
});
