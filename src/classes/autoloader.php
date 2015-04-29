<?php

namespace app\classes;

class autoloader {

	private static $namespaces = array(
		'app.classes'    => 'classes',
		'app.commands'   => 'commands',
		'app.endpoints'  => 'endpoints',
		'app.exceptions' => 'exceptions',
		'app.interfaces' => 'interfaces',
		'app.models'     => 'models',
		'app.traits'     => 'traits'
	);

	public static function register() {
		spl_autoload_register( __CLASS__ . '::load' );
	}

	public static function load( $name ) {
		if ( class_exists( $name,false ) ) {
			return true;
		}
		$name = ltrim( $name,'\\' );
		$namespace = false;
		if ( ( $pos = strrpos( $name,'\\' ) ) !== false ) {
			$namespace = explode( '\\',$name );
		}
		$path = false;
		if ( $namespace !== false ) {
			$_path = array();
			while( ( $name = array_pop( $namespace ) ) !== null ) {
				$_path[] = $name;
				$_namespace = implode( '.',$namespace );
				if ( isset( self::$namespaces[$_namespace] ) ) {
					$path = self::$namespaces[$_namespace] . '/' . implode( '/',array_reverse( $_path ) ) . '.php';
					break;
				}
			}
			if ( $path === false ) {
				return false;
			}
		}
		else {
			$path = "{$name}.php";
		}
		if ( defined('IN_PHAR') && IN_PHAR ) {
			$path = path::internal( $path );
		}
		else {
			$path = path::external( $path );
		}
		if ( !file_exists( $path ) ) {
			return false;
		}
		require_once $path;
		return true;
	}

}

?>