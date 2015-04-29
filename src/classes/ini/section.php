<?php

namespace app\classes\ini;

use app\classes\ini;

class section extends ini {

	protected $name = null;
	protected $parent = null;

	public function __construct( $name ) {
		$this->name = $name;
	}

	public function load( $data ) {
		foreach( $data as $key => $value ) {
			if ( is_array( $value ) ) {
				$this->sections[$key] = new section( $key );
				$this->sections[$key]->load( $value )->parent( $this );
				continue;
			}
			$this->vars[$key] = $value;
		}
		return $this;
	}

	public function name() {
		return ( !is_null( $this->parent ) ? $this->parent->name() . ':' : '' ) . $this->name;
	}

	public function parent( section $section ) {
		$this->parent = $section;
	}

	public function section( $name ) {
		$section = new section( $name );
		$section->parent( $this );
		$this->sections[] = $section;
		return $section;
	}

	public function write( $parent=null ) {
		$str = '[' . $this->name() . ']' . NL;
		foreach( $this->vars as $key => $value ) {
			$str .= "{$key} = " . $this->value( $value ) . NL;
		}
		foreach( $this->sections as $section ) {
			$str .= $section->write() . NL;
		}
		return $str;
	}

}

?>