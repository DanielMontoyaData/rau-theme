#!/usr/bin/env bash
# Todas las pruebas, en el orden en que conviene leerlas. Lo usa GitHub Actions y sirve igual a mano.
#   bash rau-theme/tests/ci/todo.sh unidades                         (sin servidor)
#   bash rau-theme/tests/ci/todo.sh integracion /ruta/wordpress      (con montar-wp.sh corriendo)
set -uo pipefail
TEMA=$(cd "$(dirname "$0")/../.." && pwd); cd "$TEMA"
fallas=0
paso() { echo; echo "── $1"; shift; if "$@"; then echo "   bien"; else echo "   FALLÓ"; fallas=$((fallas+1)); fi; }
case "${1:-unidades}" in
unidades)
	paso "Sintaxis PHP" bash -c 'find . -name "*.php" -not -path "./node_modules/*" -print0 | xargs -0 -n1 php -l > /dev/null'
	paso "Verificador del tema" php bin/verificar.php
	paso "Núcleo del centro de mando, FT8/FT4, CW, módem, JS8 y RTTY/PSK31/SSTV" node --test tests/emergencias/core.test.mjs tests/radio/ftx.test.mjs tests/radio/cw.test.mjs tests/radio/modem.test.mjs tests/radio/js8.test.mjs tests/radio/digi.test.mjs
	;;
integracion)
	WP=${2:?ruta a wordpress}; export RAU_EM_BASE=${RAU_EM_BASE:-http://localhost:8099}
	for f in tests/emergencias/avisos.php tests/emergencias/cuentas.php tests/radio/propagacion.php tests/radio/js8-oido.php tests/radio/pskreporter.php tests/radio/pronostico.php tests/radio/grabaciones.php tests/radio/sstv.php tests/radio/vigia.php tests/sitio/imagenes.php tests/sitio/turnos.php tests/sitio/capacidades.php tests/sitio/libro.php; do
		paso "$f" php "$f" "$WP/wp-load.php"
	done
	paso "tests/sitio/dos-pasos.php" php tests/sitio/dos-pasos.php "$WP/wp-load.php" "$RAU_EM_BASE"
	paso "API y navegador: centro de mando, receptor, /modem/, sitio y presupuesto de rendimiento" \
		node --test --test-concurrency=1 tests/emergencias/api.test.mjs tests/emergencias/e2e.test.mjs tests/radio/receptor.e2e.mjs tests/radio/modem-pagina.e2e.mjs tests/sitio/sitio.e2e.mjs tests/sitio/presupuesto.mjs
	;;
esac
echo; [ $fallas -eq 0 ] && echo "Todo pasa" || echo "$fallas paso(s) fallaron"
exit $fallas
