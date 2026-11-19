/* Decodifica ranuras FT8/FT4 fuera del hilo principal: así el audio del receptor no se entrecorta.
   Recibe {id, modo, muestras (Float32Array), tasa, profundo}; devuelve {id, res, ms} o {id, error}.
   profundo=false usa solo la etapa rápida (equipos lentos). La tabla de indicativos con hash se
   conserva entre ranuras, como en WSJT-X. */
importScripts('ftx.js' + (self.location.search || ''));
var hashes = new RauFTX.Hashes();
self.onmessage = function(e){
	var d = e.data, t0 = Date.now();
	try {
		var res = RauFTX.decodificar(d.muestras, d.tasa, { modo: d.modo, hashes: hashes, profundo: d.profundo !== false });
		self.postMessage({ id: d.id, res: res, ms: Date.now() - t0 });
	} catch (err) {
		self.postMessage({ id: d.id, error: String(err && err.message || err) });
	}
};
