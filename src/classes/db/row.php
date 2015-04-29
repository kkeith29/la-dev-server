<?php

namespace app\classes\db;

class row implements \ArrayAccess {

	private $result;
	private $data = array();

	public function __construct( result $result,$data ) {
		$this->result = $result;
		$this->data = $data;
	}

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
		if ( isset( $this->data[$key] ) ) {
			unset( $this->data[$key] );
		}
	}

	public function offsetSet( $offset,$value ) {
		if ( is_null( $offset ) ) {
			$this->data[] = $value;
		}
		else {
			$this->data[$offset] = $value;
		}
	}
	
	public function offsetExists( $offset ) {
		return isset( $this->data[$offset] );
	}
	
	public function offsetUnset( $offset ) {
		if ( !isset( $this->data[$offset] ) ) {
			return;
		}
		unset( $this->data[$offset] );
	}
	
	public function offsetGet( $offset ) {
		if ( !isset( $this->data[$key] ) ) {
			return null;
		}
		return $this->data[$key];
	}

}

?>