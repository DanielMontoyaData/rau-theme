#!/usr/bin/env bash
# Monta un WordPress de prueba con SQLite (sin MySQL) y el tema, y lo deja corriendo en el puerto 8099.
#   bash rau-theme/tests/ci/montar-wp.sh /ruta/de/trabajo /ruta/al/tema
set -euo pipefail
DIR=${1:?carpeta de trabajo}; TEMA=$(cd "${2:?carpeta del tema}" && pwd); PUERTO=${PUERTO:-8099}
mkdir -p "$DIR" && cd "$DIR"
[ -d wordpress ] || { curl -fsSL https://wordpress.org/latest.tar.gz | tar xz; }
[ -d wordpress/wp-content/plugins/sqlite-database-integration ] || {
	curl -fsSL -o sqlite.zip https://downloads.wordpress.org/plugin/sqlite-database-integration.zip
	unzip -q sqlite.zip -d wordpress/wp-content/plugins/
}
sed "s#{SQLITE_IMPLEMENTATION_FOLDER_PATH}#$DIR/wordpress/wp-content/plugins/sqlite-database-integration#; s#{SQLITE_PLUGIN}#sqlite-database-integration/load.php#" \
	wordpress/wp-content/plugins/sqlite-database-integration/db.copy > wordpress/wp-content/db.php
if [ ! -f wordpress/wp-config.php ]; then
	SAL=$(php -r 'for($i=0;$i<8;$i++) echo "define(\"".["AUTH_KEY","SECURE_AUTH_KEY","LOGGED_IN_KEY","NONCE_KEY","AUTH_SALT","SECURE_AUTH_SALT","LOGGED_IN_SALT","NONCE_SALT"][$i]."\", \"".bin2hex(random_bytes(24))."\");\n";')
	cat > wordpress/wp-config.php <<CONF
<?php
define( 'DB_NAME', 'wp' ); define( 'DB_USER', '' ); define( 'DB_PASSWORD', '' ); define( 'DB_HOST', '' );
define( 'DB_CHARSET', 'utf8mb4' ); define( 'DB_COLLATE', '' );
$SAL
\$table_prefix = 'wp_';
define( 'WP_DEBUG', false );
define( 'RAU_EM_PRUEBAS', true );
define( 'WP_ENVIRONMENT_TYPE', 'local' );
if ( ! defined( 'ABSPATH' ) ) define( 'ABSPATH', __DIR__ . '/' );
require_once ABSPATH . 'wp-settings.php';
CONF
fi
ln -sfn "$TEMA" wordpress/wp-content/themes/rau-theme
cat > router.php <<'PHP'
<?php
$f = __DIR__ . '/wordpress' . parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
if ( is_file( $f ) ) return false;
$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__ . '/wordpress/index.php';
PHP
php "$TEMA/tests/ci/sembrar.php" "$DIR/wordpress" "http://localhost:$PUERTO"
( cd wordpress && PHP_CLI_SERVER_WORKERS=6 setsid nohup php -S "localhost:$PUERTO" ../router.php < /dev/null > "$DIR/servidor.log" 2>&1 & )
for i in $(seq 1 30); do curl -fs -o /dev/null "http://localhost:$PUERTO/" && { echo "WordPress listo en http://localhost:$PUERTO"; exit 0; }; sleep 1; done
echo "El servidor no respondió"; tail -20 "$DIR/servidor.log"; exit 1
