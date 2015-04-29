<?php

namespace app\classes\db;

use app\classes\db;
use app\classes\db\queries\select;
use app\exceptions\db as db_exception;

class table {

	const data_type_text = 1;
	const data_type_num  = 2;
	const data_type_int  = 3;
	const data_type_real = 4;
	const data_type_none = 5;

	public static $data_type_names = array(
		self::data_type_text => 'TEXT',
		self::data_type_num  => 'NUMERIC',
		self::data_type_int  => 'INTEGER',
		self::data_type_real => 'REAL',
		self::data_type_none => 'NONE'
	);

	private $db;
	private $name;
	private $exists = false;
	private $columns = array();

	public function __construct( db $db,$name ) {
		$this->db = $db;
		$this->name = $name;
		$this->exists = ( (int) $this->db->_query( "SELECT COUNT(*) AS count FROM sqlite_master WHERE type='table' AND name='%s'",$this->name )->first()->count === 1 );
		if ( $this->exists ) {
			//add caching here
			$columns = $this->db->_query( "PRAGMA table_info(%s)",$this->name );
			if ( count( $columns ) > 0 ) {
				foreach( $columns as $column ) {
					if ( ( $type = array_search( $column->type,self::$data_type_names ) ) === false ) {
						throw new db_exception( 'Unsupported column type: %s',$column->type );
					}
					$config = array(
						'not_null' => ( (int) $column->notnull === 1 ),
						'default'  => ( $column->dflt_value !== '' ? $column->dflt_value : '' ),
						'primary_key' => ( (int) $column->pk === 1 )
						//unique
					);
					$this->columns[$column->name] = array(
						'id'     => $column->cid,
						'name'   => $column->name,
						'type'   => $type,
						'config' => $config
					);
				}
			}
		}
	}

	public function db() {
		return $this->db;
	}

	public function name() {
		return $this->name;
	}

	public function exists() {
		return $this->exists;
	}

	public function has_column( $name ) {
		return isset( $this->columns[$name] );
	}

	public function get_column( $name ) {
		if ( !isset( $this->columns[$name] ) ) {
			return false;
		}
		return $this->columns[$name];
	}

	public function select() {
		return new select( $this );
	}

/*
	public function update() {
		return new update( $this );
	}
*/

}

?>