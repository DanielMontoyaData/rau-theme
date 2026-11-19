/* Decodifica ranuras FT8/FT4/JS8 fuera del hilo principal: así el audio del receptor no se entrecorta.
   Recibe {id, modo, muestras (Float32Array), tasa, profundo}; devuelve {id, res, ms, mensajes} o {id, error}.
   profundo=false usa solo la etapa rápida (equipos lentos). La tabla de indicativos con hash se
   conserva entre ranuras, como en WSJT-X. Para JS8 se carga js8.js y, la primera vez, el diccionario
   de compresión de JS8Call (1 MB); los mensajes de varios marcos se arman aquí. */
var VER = self.location.search || '';
importScripts('ftx.js' + VER);
var hashes = new RauFTX.Hashes(), js8 = null, ensamblador = null;
function cargarJS8() {
	if (js8) return js8;
	importScripts('js8.js' + VER);
	ensamblador = new RauJS8.Ensamblador();
	js8 = fetch('js8-jsc.json.gz' + VER).then(function (r) {
		if (!r.ok) throw new Error('diccionario');
		return r.arrayBuffer();
	}).then(function (ab) {
		var b = new Uint8Array(ab);
		// Algunos servidores ya lo entregan descomprimido (Content-Encoding: gzip)
		if (b[0] === 0x1f && b[1] === 0x8b && typeof DecompressionStream !== 'undefined')
			return new Response(new Blob([b]).stream().pipeThrough(new DecompressionStream('gzip'))).text();
		return new TextDecoder().decode(b);
	}).then(function (t) { RauJS8.usarDiccionario(JSON.parse(t)); }).catch(function () { /* sin diccionario: el texto comprimido se marca */ });
	return js8;
}
self.onmessage = function (e) {
	var d = e.data, t0 = Date.now();
	function fallo(err) { self.postMessage({ id: d.id, error: String(err && err.message || err) }); }
	try {
		if (d.modo === 'JS8') {
			cargarJS8().then(function () {
				try {
					var res = RauJS8.decodificar(d.muestras, d.tasa, { profundo: d.profundo !== false }), mensajes = [];
					res.forEach(function (r) { var m = ensamblador.agregar(r, d.id); if (m) mensajes.push(m); });
					self.postMessage({ id: d.id, res: res, mensajes: mensajes, ms: Date.now() - t0 });
				} catch (err) { fallo(err); }
			});
			return;
		}
		var res = RauFTX.decodificar(d.muestras, d.tasa, { modo: d.modo, hashes: hashes, profundo: d.profundo !== false });
		self.postMessage({ id: d.id, res: res, ms: Date.now() - t0 });
	} catch (err) { fallo(err); }
};
