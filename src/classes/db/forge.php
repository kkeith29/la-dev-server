<?php

namespace app\classes\db;

use app\classes\db;
use app\exceptions\db as db_exception;

class forge {

	private $db;
	private $table = null;
	private $table_exists = false;
	private $config = array();
	private $columns = array();

	public function __construct( db $db ) {
		$this->db = $db;
	}

	public function table( $table ) {
		if ( preg_match( '#^[a-z0-9_]+$#',$table ) !== 1 ) {
			throw new db_exception( "Table name '%s' is not valid",$table );
		}
		$this->table = $table;
		$this->table_info = $this->db->_table( $table );
		return $this;
	}

	public function with_timestamps() {
		$this->config['timestamps'] = true;
		return $this;
	}

	public function with_soft_delete() {
		$this->config['soft_delete'] = true;
		return $this;
	}

	public function column( $name,$type,$config=array() ) {
		if ( preg_match( '#^[a-z0-9_]+$#',$name ) !== 1 ) {
			throw new db_exception( "Table column name '%s' is not valid",$name );
		}
		if ( !isset( table::$data_type_names[$type] ) ) {
			throw new db_exception( 'Invalid column type for %s',$name );
		}
		$this->columns[] = compact('name','type','config');
		return $this;
	}

	private function column_definition( $data ) {
		if ( !isset( $data['name'] ) ) {
			throw new db_exception('Name is required for column definition');
		}
		if ( !isset( $data['type'] ) ) {
			$data['type'] = table::data_type_none;
		}
		$str = $data['name'] . ' ' . table::$data_type_names[$data['type']];
		if ( isset( $data['config'] ) ) {
			if ( isset( $data['config']['default'] ) && $data['config']['default'] !== '' ) {
				$str .= " DEFAULT {$data['config']['default']}";
			}
			if ( isset( $data['config']['not_null'] ) && $data['config']['not_null'] ) {
				$str .= ' NOT NULL';
			}
			if ( isset( $data['config']['primary_key'] ) && $data['config']['primary_key'] ) {
				$str .= ' PRIMARY KEY';
			}
			elseif ( isset( $data['config']['unique'] ) && $data['config']['unique'] ) {
				$str .= ' UNIQUE';
			}
		}
		return $str;
	}

	//primary key for use with multi key setups here

	public function create( $config=array() ) {
		if ( is_null( $this->table ) ) {
			throw new db_exception('Must define a table name before trying to create');
		}
		if ( $this->table_info->exists() ) {
			throw new db_exception( 'Table %s already exists',$this->table );
		}
		if ( isset( $this->config['timestamps'] ) ) {
			$this->column( 'created_at',table::data_type_int );
			$this->column( 'updated_at',table::data_type_int );
		}
		if ( isset( $this->config['soft_delete'] ) ) {
			$this->column( 'deleted_at',table::data_type_int );
		}
		//add force config option that will drop before creation
		$columns = array_map( array( $this,'column_definition' ),$this->columns );
		if ( count( $columns ) === 0 ) {
			throw new db_exception('At least 1 column is required');
		}
		$sql = vsprintf( 'CREATE TABLE%s %s (%s)%s',array(
			( isset( $config['if_nonexistent'] ) && $config['if_nonexistent'] ? ' IF NOT EXISTS' : '' ),
			$this->table,
			implode( ',',$columns ),
			( isset( $config['without_rowid'] ) && $config['without_rowid'] ? ' WITHOUT ROWID' : '' )
		) );
		return $this->db->_exec( $sql );
	}

	public function drop() {
		if ( is_null( $this->table ) ) {
			throw new db_exception('Must define a table name before trying to drop');
		}
		if ( !$this->table_info->exists() ) {
			throw new db_exception( 'Table %s does not exist',$this->table );
		}
		return $this->db->_exec( 'DROP TABLE %s',$this->table );
	}

}

?>