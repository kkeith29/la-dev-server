<?php

use app\classes\autoloader;

error_reporting(-1);
ini_set('display_errors',1);
ini_set('log_errors',0);
ini_set('html_errors',0);

include PATH_PHAR . 'classes/path.php';

include app\classes\path::internal('classes/autoloader.php');

autoloader::register();

?>