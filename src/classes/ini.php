<?php

namespace app\classes;

use app\classes\ini\section;

class ini {

	protected $vars = array();
	protected $sections = array();

	public function load( $file ) {
		$data = self::read( $file );
		foreach( $data as $key => $value ) {
			if ( is_array( $value ) ) {
				$this->sections[$key] = new section( $key );
				$this->sections[$key]->load( $value );
				continue;
			}
			$this->vars[$key] = $value;
		}
		return $this;
	}

	public function item( $key,$value ) {
		$this->vars[$key] = $value;
		return $this;
	}

	public function get_items() {
		return $this->vars;
	}

	public function has_section( $name ) {
		return isset( $this->sections[$name] );
	}

	public function section( $name ) {
		if ( !isset( $this->sections[$name] ) ) {
			$this->sections[$name] = new section( $name );
		}
		return $this->sections[$name];
	}

	protected function value( $value ) {
		return ( is_bool( $value ) ? ( $value === true ? 'true' : 'false' ) : ( preg_match( '#^[0-9]+$#',$value ) === 1 ? $value : '"' . $value . '"' ) );
	}

	public function write( $file ) {
		$str = '';
		foreach( $this->vars as $key => $value ) {
			$str .= "{$key} = " . $this->value( $value ) . NL;
		}
		foreach( $this->sections as $section ) {
			$str .= $section->write() . NL;
		}
		if ( file_put_contents( $file,$str ) === false ) {
			throw new app_exception('Unable to write ini file');
		}
		return true;
	}

	public function __isset( $key ) {
		return isset( $this->vars[$key] );
	}

	public function __get( $key ) {
		if ( !isset( $this->vars[$key] ) ) {
			return null;
		}
		return $this->vars[$key];
	}

	public function __set( $key,$value ) {
		$this->vars[$key] = $value;
	}

	public function __unset( $key ) {
		if ( !isset( $this->vars[$key] ) ) {
			return;
		}
		unset( $this->vars[$key] );
	}

	public static function read( $file ) {
		return parse_ini_file( $file,true );
	}

	/*public static function write( $file,$data ) {
		$str = '';
		foreach( $data as $key => $datum ) {
			if ( is_array( $datum ) ) {
				$str .= "[{$key}]" . NL;
				foreach( $datum as $_key => $value ) {
					$str .= "{$_key} = " . ( is_bool( $value ) ? ( $value === true ? 'true' : 'false' ) : ( preg_match( '#^[0-9]+$#',$value ) === 1 ? $value : '"' . $value . '"' ) ) . NL;
				}
				$str .= NL;
				continue;
			}
			$str .= "{$key} = " . ( is_bool( $datum ) ? ( $datum === true ? 'true' : 'false' ) : ( preg_match( '#^[0-9]+$#',$datum ) === 1 ? $datum : '"' . $datum . '"' ) ) . NL;
		}
		file_put_contents( $file,$str );
	}*/

}

?>