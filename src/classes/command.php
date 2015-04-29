<?php

namespace app\classes;

use app\exceptions\app as app_exception;

class command {

	private static $instance = null;

	protected $args;

	public function __construct( $subcommand ) {
		if ( !package::installed() && get_class( $this ) !== 'app\\commands\\package' ) {
			throw new app_exception('Package is not installed');
		}
		$subcommand = ltrim( $subcommand,'_' );
		try {
			$ref = new \ReflectionMethod( $this,$subcommand );
			if ( !$ref->isPublic() || $ref->isStatic() ) {
				throw new app_exception( 'Unable to find subcommand: %s',$subcommand );
			}
		}
		catch( \ReflectionException $e ) {
			throw new app_exception( 'Unable to find subcommand: %s',$subcommand );
		}
		$this->args = cli::args();
		$ref->invoke( $this );
	}

	protected function _run( $command,$args=array() ) {
		if ( !isset( $this->commands[$command] ) ) {
			throw new app_exception( "Unable to run subcommand '%s' - Reason: Subcommand not found",$command );
		}
		$method = str_replace( '-','_',$command );
		if ( !method_exists( $this,$method ) ) {
			throw new app_exception( "Unable to run subcommand '%s' - Reason: Method not found",$method );
		}
		call_user_func_array( array( $this,$method ),$args );
	}

	public static function run( $command ) {
		$parts = array_map( function( $value ) {
			return str_replace( '-','_',$value );
		},explode( ':',$command ) );
		$method = 'main';
		while( count( $parts ) > 0 ) {
			$class = '\\app\\commands\\' . implode( '\\',$parts );
			if ( autoloader::load( $class ) ) {
				self::$instance = new $class( $method );
				return;
			}
			$method = array_pop( $parts );
		}
		throw new app_exception( 'Invalid command: %s',$command );
	}

}

?>