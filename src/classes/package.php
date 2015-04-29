<?php

namespace app\classes;

use app\exceptions\app as app_exception;

class package {

	private static $version = null;

	public static function installed() {
		if ( !file_exists( FILE_CONFIG_MAIN ) ) {
			return false;
		}
		if ( !file_exists( path::binary_file() ) ) {
			return false;
		}
		return true;
	}

	public static function version() {
		if ( is_null( self::$version ) ) {
			$package_file = path::internal('package.json');
			if ( !file_exists( $package_file ) ) {
				throw new app_exception('Package file is missing or damaged, please reinstall');
			}
			$data = json_decode( file_get_contents( $package_file ) );
			self::$version = $data->version;
		}
		return self::$version;
	}

	public static function is_dev() {
		$version = self::version();
		if ( ( $pos = strrpos( $version,'-' ) ) === false ) {
			return false;
		}
		$type = substr( $version,( $pos + 1 ) );
		if ( !in_array( $type,array('dev','alpha','beta') ) ) {
			return false;
		}
		return true;
	}

}

?>