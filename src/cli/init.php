<?php

use app\classes\cli;
use app\classes\config;
use app\classes\command;
use app\classes\db;
use app\classes\path;
use app\exceptions\app as app_exception;

define('NL',"\n");

try {
	$local_version = getenv('PACKAGE_VERSION');
	if ( $local_version !== false && version_compare( $local_version,REQUIRED_VERSION ) < 0 ) {
		throw new app_exception( 'Client version not allowed. Please upgrade to version: %s',REQUIRED_VERSION );
	}
	config::load( FILE_CONFIG_MAIN,false );
	if ( ( $command = cli::args()->get(0,false) ) === false ) {
		throw new app_exception('Command is required');
	}
	db::open( path::data( FILE_CONFIG_DB ),'main' );
	command::run( $command );
}
catch( app_exception $e ) {
	echo $e->getMessage() . NL;
	exit(1);
}

exit(0);

?>