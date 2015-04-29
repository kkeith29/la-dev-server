<?php

namespace app\traits;

trait igsr {

	protected $data = array();

	public function __isset( $key ) {
		return isset( $this->data[$key] );
	}

	public function __get( $key ) {
		if ( !isset( $this->data[$key] ) ) {
			return null;
		}
		return $this->data[$key];
	}

	public function __set( $key,$value ) {
		$this->data[$key] = $value;
	}

	public function __unset( $key ) {
		if ( !isset( $this->data[$key] ) ) {
			return;
		}
		unset( $this->data[$key] );
	}

}

?>