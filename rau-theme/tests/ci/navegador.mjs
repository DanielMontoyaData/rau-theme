// Si RAU_CHROMIUM apunta a un navegador ya instalado (p. ej. el Chrome de las máquinas de GitHub), las
// pruebas lo usan en lugar del Chromium que descarga Playwright. Se carga con NODE_OPTIONS=--import.
import { createRequire } from 'node:module';
if (process.env.RAU_CHROMIUM) {
	const pw = createRequire(process.cwd() + '/')('playwright');
	const lanzar = pw.chromium.launch.bind(pw.chromium);
	pw.chromium.launch = (o = {}) => lanzar({ executablePath: process.env.RAU_CHROMIUM, ...o });
}
