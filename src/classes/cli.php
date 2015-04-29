<?php

namespace app\classes;

use app\classes\cli\arguments;
use app\classes\cli\streams;

class cli {

	private static $args = null;

	public static function args() {
		if ( is_null( self::$args ) ) {
			$args = $_SERVER['argv'];
			array_shift( $args ); //remove filename
			self::$args = new arguments( $args );
		}
		return self::$args;
	}

	public static function out() {
		streams::call( 'out',func_get_args() );
	}

	public static function line() {
		streams::call( 'line',func_get_args() );
	}

	public static function err() {
		streams::call( 'err',func_get_args() );
	}

	public static function menu( $options,$config=array() ) {
		return streams::menu( $options,$config );
	}

	public static function prompt( $question,$config=array() ) {
		return streams::prompt( $question,$config );
	}

	public static function choose( $question,$choices=array('y','n'),$config=array() ) {
		return streams::choose( $question,$choices,$config );
	}

	public static function confirm( $question,$default=false ) {
		$config = array();
		if ( $default !== false ) {
			$config['default'] = $default;
		}
		return streams::choose( $question,array('y','n'),$config );
	}

}

?>