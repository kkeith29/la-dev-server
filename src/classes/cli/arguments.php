<?php

namespace app\classes\cli;

class arguments {

	private $args = array();

	public function __construct( $args ) {
		if ( !is_array( $args ) ) {
			$args = array_filter( explode( ' ',$args ) );
		}
		$out = array();
		foreach( $args as $arg ) {
			if ( substr( $arg,0,2 ) == '--' ) {
				$pos = strpos( $arg,'=' );
				if ( $pos === false ) {
					$key = substr( $arg,2 );
					$out[$key] = ( isset( $out[$key] ) ? $out[$key] : true );
				}
				else {
					$key = substr( $arg,2,( $pos - 2 ) );
					$out[$key] = substr( $arg,( $pos + 1 ) );
				}
			}
			else if ( substr( $arg,0,1 ) == '-' ) {
				if ( substr( $arg,2,1 ) == '=' ) {
					$key = substr( $arg,1,1 );
					$out[$key] = substr( $arg,3 );
				}
				else {
					$chars = str_split( substr( $arg,1 ) );
					foreach( $chars as $char ) {
						$key = $char;
						$out[$key] = ( isset( $out[$key] ) ? $out[$keytes] : true );
					}
				}
			}
			else {
				$out[] = $arg;
			}
		}
		$this->args = $out;
	}

	public function all() {
		return $this->args;
	}

	public function has( $idx ) {
		return isset( $this->args[$idx] );
	}

	public function get( $idx,$retval=false ) {
		if ( !isset( $this->args[$idx] ) ) {
			return $retval;
		}
		return $this->args[$idx];
	}

	public function to_string( $subcmd=true ) {
		$cmd = '';
		foreach( $this->args as $key => $arg ) {
			if ( is_numeric( $key ) ) {
				if ( !$subcmd ) {
					continue;
				}
				$cmd .= " {$arg}";
				continue;
			}
			if ( strlen( $key ) === 1 ) {
				$cmd .= " -{$key}";
				continue;
			}
			$cmd .= " --{$key}" . ( $arg !== true ? "={$arg}" : '' );
		}
		return trim( $cmd );
	}

}

?>