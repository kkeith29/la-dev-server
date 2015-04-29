<?php

use app\classes\path;

if ( php_sapi_name() !== 'cli' ) {
	die('Phar must be run with PHP CLI');
}

define('APP_NAME','la-dev');
define('PHAR_NAME',APP_NAME . '.phar');
define('API_SERVICE_NAME',APP_NAME . '-api');
define('PATH_PHAR','phar://' . PHAR_NAME . '/');
define('PATH_API_SERVICE','/etc/init.d/' . API_SERVICE_NAME);
define('IN_PHAR',true);
define('REQUIRED_VERSION','1.0.0');

define('PATH_ETC','/etc/' . APP_NAME . '/');

define('FILE_CONFIG_MAIN',PATH_ETC . 'main.ini');
define('FILE_CONFIG_DB','config.db');

define('FILE_MANIFEST','http://projects.liquidawesomedev.com/la-dev-server/manifest.json');

require PATH_PHAR . 'includes/init.php';

Phar::mapPhar();
require path::internal('cli/init.php');

__HALT_COMPILER();