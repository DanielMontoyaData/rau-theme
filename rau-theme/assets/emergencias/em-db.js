/* Centro de Mando de Emergencias — almacenamiento local (IndexedDB).
 *
 * Tres almacenes: «ops» (operaciones conocidas en este dispositivo, con su token de sesión), «eventos»
 * (los registros, clave [op, u]) y «kv» (ajustes: indicativo, preparación, directorio guardado…).
 * Cada evento lleva «s»: >0 si el servidor lo guardó (su id), 0 si está pendiente de enviar, 'r' si el
 * servidor lo rechazó (con «err»). Nada se marca como enviado mientras solo esté en el dispositivo.
 *
 * Si IndexedDB no está disponible (navegación privada en algunos navegadores), funciona en memoria y
 * lo avisa: al cerrar la pestaña se pierde todo lo que no se haya sincronizado o exportado.
 */
(function(){
'use strict';
var NOMBRE='rau-emergencias', VERSION=1, db=null, memoria=null;

function req(r){ return new Promise(function(ok, ko){ r.onsuccess=function(){ ok(r.result); }; r.onerror=function(){ ko(r.error); }; }); }
function abrir(){
	if(db || memoria) return Promise.resolve();
	if(!window.indexedDB){ memoria={ops:{}, eventos:{}, kv:{}}; return Promise.resolve(); }
	return new Promise(function(ok){
		var r=indexedDB.open(NOMBRE, VERSION);
		r.onupgradeneeded=function(){
			var d=r.result;
			if(!d.objectStoreNames.contains('ops')) d.createObjectStore('ops', {keyPath:'codigo'});
			if(!d.objectStoreNames.contains('eventos')){ var s=d.createObjectStore('eventos', {keyPath:['op','u']}); s.createIndex('op', 'op'); }
			if(!d.objectStoreNames.contains('kv')) d.createObjectStore('kv');
		};
		r.onsuccess=function(){ db=r.result; db.onversionchange=function(){ db.close(); }; ok(); };
		r.onerror=r.onblocked=function(){ memoria={ops:{}, eventos:{}, kv:{}}; ok(); };
	}).then(migrarV1);
}
function tx(almacen, modo){ return db.transaction(almacen, modo||'readonly').objectStore(almacen); }
function fin(t){ return new Promise(function(ok, ko){ t.oncomplete=ok; t.onerror=t.onabort=function(){ ko(t.error); }; }); }

var API = {
	enMemoria: function(){ return !!memoria; },
	abrir: abrir,
	kvGet: function(k){ if(memoria) return Promise.resolve(memoria.kv[k]); return req(tx('kv').get(k)); },
	kvSet: function(k, v){ if(memoria){ memoria.kv[k]=v; return Promise.resolve(); } return req(tx('kv','readwrite').put(v, k)); },
	ops: function(){ if(memoria) return Promise.resolve(Object.keys(memoria.ops).map(function(k){ return memoria.ops[k]; })); return req(tx('ops').getAll()); },
	op: function(c){ if(memoria) return Promise.resolve(memoria.ops[c]); return req(tx('ops').get(c)); },
	guardarOp: function(o){ if(memoria){ memoria.ops[o.codigo]=o; return Promise.resolve(); } return req(tx('ops','readwrite').put(o)); },
	borrarOp: function(c){
		if(memoria){ delete memoria.ops[c]; delete memoria.eventos[c]; return Promise.resolve(); }
		var t=db.transaction(['ops','eventos'], 'readwrite'); t.objectStore('ops').delete(c);
		t.objectStore('eventos').index('op').openKeyCursor(IDBKeyRange.only(c)).onsuccess=function(ev){ var cur=ev.target.result; if(cur){ t.objectStore('eventos').delete(cur.primaryKey); cur.continue(); } };
		return fin(t);
	},
	eventos: function(c){ if(memoria) return Promise.resolve((memoria.eventos[c]||[]).slice()); return req(tx('eventos').index('op').getAll(IDBKeyRange.only(c))); },
	/* Guarda (o actualiza) varios eventos de una operación en una sola transacción */
	guardarEventos: function(c, lista){
		if(!lista.length) return Promise.resolve();
		if(memoria){ var m=memoria.eventos[c]=memoria.eventos[c]||[], i={}; m.forEach(function(e, k){ i[e.u]=k; }); lista.forEach(function(e){ e.op=c; if(i[e.u]!==undefined) m[i[e.u]]=e; else m.push(e); }); return Promise.resolve(); }
		var t=db.transaction('eventos', 'readwrite'), s=t.objectStore('eventos');
		lista.forEach(function(e){ e.op=c; s.put(e); });
		return fin(t);
	},
	/* Cuánto ocupa y si el navegador prometió no borrarlo */
	espacio: function(){
		var est=navigator.storage && navigator.storage.estimate ? navigator.storage.estimate() : Promise.resolve(null);
		var per=navigator.storage && navigator.storage.persisted ? navigator.storage.persisted() : Promise.resolve(false);
		return Promise.all([est, per]).then(function(r){ return {uso:r[0]&&r[0].usage, cuota:r[0]&&r[0].quota, persistente:r[1], memoria:!!memoria}; });
	},
	pedirPersistencia: function(){ return navigator.storage && navigator.storage.persist ? navigator.storage.persist() : Promise.resolve(false); }
};

/* Datos de la versión 19.52.0 (localStorage «rau_em_v1»): se copian una sola vez. Los tokens viejos
   no sirven con el servidor nuevo: esas operaciones quedan para volver a entrar con su clave. */
function migrarV1(){
	var viejo; try { viejo=JSON.parse(localStorage.getItem('rau_em_v1')||'null'); } catch(e){ viejo=null; }
	if(!viejo) return;
	return API.kvGet('migrado_v1').then(function(ya){
		if(ya) return;
		var p=[];
		if(viejo.yo && viejo.yo.ind) p.push(API.kvSet('yo', viejo.yo));
		if(viejo.prep) p.push(API.kvSet('prep', viejo.prep));
		Object.keys(viejo.incs||{}).forEach(function(c){
			var i=viejo.incs[c];
			p.push(API.guardarOp({codigo:c, nombre:i.nombre, tipo:i.tipo, zona:i.zona||'', estado:i.estado||'abierto', local:!!i.local, token:'', actor:null, cursor:0, visto:i.visto||0, migrado:true}));
			// Lo que no llegó al servidor sigue pendiente; lo que sí llegó se volverá a traer al entrar
			var evs=(viejo.ev[c]||[]).map(function(e){ return {u:e.u, t:e.t, a:e.a, ts:e.ts, d:e.d||{}, s:e.s>0?e.s:0}; });
			p.push(API.guardarEventos(c, evs));
		});
		return Promise.all(p).then(function(){ return API.kvSet('migrado_v1', Date.now()); });
	});
}

window.RauEmDB = API;
})();
