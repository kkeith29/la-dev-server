<?php

namespace app\classes\db;

use app\classes\db;
use app\exceptions\db as db_exception;
use app\traits\igsr;

class model {

	use igsr;

	public $table_name = null;

	protected $db;
	protected $table;
	protected $timestamps = true;

	public function __construct() {
		if ( is_null( $this->table_name ) ) {
			throw new db_exception('Table name not set');
		}
		$this->db = db::instance();
		$this->table = $thid->db->_table( $this->table_name );
	}

	public static function __callStatic( $method,$args ) {
		$instance = new static;
		return call_user_func_array( array( $instance,$method ),$args );
	}

	public function _find( $id ) {
		return $this->db->_query( 'SELECT * FROM %s WHERE %s = %d LIMIT 1',$this->table_name,$this->table->primary_key(),$id )->first();
	}

	public function _all() {
		return $this->db->_query( 'SELECT * FROM %s',$this->table_name );
	}

	public function _get() {
		
	}

	public function _create() {
		
	}

	public function _update() {
		
	}

	public function _delete() {
		
	}

	public function _destroy() {
		
	}

}

?>