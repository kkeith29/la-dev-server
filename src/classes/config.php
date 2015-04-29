<?php

namespace app\classes;

use app\exceptions\app as app_exception;

class config {

	private static $config = array();

	public static function load( $file,$error=true ) {
		if ( !file_exists( $file ) ) {
			if ( $error ) {
				throw new app_exception('Unable to load config file');
			}
			return false;
		}
		self::$config = func::array_merge_recursive_distinct( self::$config,ini::read( $file ) );
	}

	public static function get( $key=null,$retval=false,$sep='.' ) {
		$array = self::$config;
		if ( is_null( $key ) ) {
			return $array;
		}
		if ( isset( $array[$key] ) ) {
			return $array[$key];
		}
		foreach( explode( $sep,$key ) as $_key ) {
			if ( !is_array( $array ) || !array_key_exists( $_key,$array ) ) {
				return $retval;
			}
			$array = $array[$_key];
		}
		return $array;
	}

	public static function set( $key,$value,$sep='.' ) {
		$array =& self::$config;
		if ( isset( $array[$key] ) ) {
			$array[$key] = $value;
			return;
		}
		$keys = explode( $sep,$key );
		while( count( $keys ) > 1 ) {
			$key = array_shift( $keys );
			if ( !isset( $array[$key] ) || !is_array( $array[$key] ) ) {
				$array[$key] = array();
			}
			$array =& $array[$key];
		}
		$array[array_shift( $keys )] = $value;
	}

}

?>