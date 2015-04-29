<?php

namespace app\classes;

class path {

	public static function internal( $path ) {
		if ( !defined('IN_PHAR') || !IN_PHAR ) {
			return PATH_PHAR . $path;
		}
		return 'phar://' . PHAR_NAME . '/' . ltrim( $path,'/' );
	}

	public static function external( $path ) {
		if ( defined('IN_PHAR') && IN_PHAR ) {
			return 'file://' . $path;
		}
		return PATH_PHAR . $path;
	}

	public static function local( $path='' ) {
		$phar_file = \Phar::running(false);
		return ( $phar_file === '' ? '' : dirname( $phar_file ) . '/' ) . $path;
	}

	public static function binary_file() {
		return config::get('paths.bin') . APP_NAME;
	}

	public static function phar_file() {
		return config::get('paths.data') . PHAR_NAME;
	}

	public static function config( $path='' ) {
		return PATH_ETC . $path;
	}

	public static function data( $path='' ) {
		return config::get('paths.data') . $path;
	}

	public static function template( $path='' ) {
		return path::internal('templates/') . $path;
	}

}

?>