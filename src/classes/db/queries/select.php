<?php

namespace app\classes\db\queries;

use app\classes\db\table;
use app\classes\db\query;
use app\exceptions\db as db_exception;
use app\interfaces\db\query as iquery;
//use app\traits\db\queries\where;

class select extends query implements iquery {

	const clause_prepend   = 1;
	const clause_append    = 2;
	const clause_overwrite = 3;

	//use where;

	private $tables = array();
	private $table_aliases = array();
	private $table_idx = null;

	private $fields = array();
	private $field_aliases = array();

	//private $joins = array();
	//private $join_idx = 0;

	private $clauses = array();
	private $operators = array();

	public function __construct( table $table ) {
		parent::__construct( $table );
		$this->table_idx = $this->table->name();
		$this->tables[$this->table_idx] = [
			'instance'      => $this->table,
			'alias'         => false,
			'fields'        => [],
			'field_aliases' => []
		];
		$this->fields =& $this->tables[$this->table_idx]['fields'];
	}

	private function table_alias( $alias ) {
		if ( in_array( $alias,$this->table_aliases ) ) {
			throw new db_exception( "Table alias '%s' already in use",$alias );
		}
		$this->tables[$this->table_idx]['alias'] = $alias;
		$this->table_aliases[] = $alias;
	}

	private function field_alias( $field,$alias ) {
		if ( in_array( $alias,$this->field_aliases ) ) {
			throw new db_exception( "Field alias '%s' already in use",$alias );
		}
		$this->tables[$this->table_idx]['field_aliases'][$field] = $alias;
		$this->field_aliases[] = $alias;
	}

	public function fields() {
		$fields = func_get_args();
		if ( is_array( $fields[0] ) ) {
			$fields = $fields[0];
		}
		foreach( $fields as $field ) {
			$field = $this->parse_column( $field );
			if ( isset( $field['custom'] ) ) {
				throw new db_exception('Subqueries not allowed with this function, please use subquery()');
			}
			if ( $field['table'] !== false ) {
				throw new db_exception('Only fields from the current table can be requested with this function');
			}
			if ( $field['alias'] !== false ) {
				throw new db_exception('Field aliases are not allowed with this function, please use field()');
			}
			$this->_field( $field['name'] );
		}
		return $this;
	}

	public function field( $name,$alias=null,$alias_type=null ) {
		$field = $this->parse_column( $field );
		if ( isset( $field['custom'] ) ) {
			throw new db_exception('Subqueries not allowed with this function, please use subquery()');
		}
		if ( $field['alias'] !== false ) {
			throw new db_exception('Use 2nd parameter to define aliases');
		}
		if ( $field['table'] !== false ) {
			throw new db_exception('Only fields from the current table can be requested with this function');
		}
		$this->_field( $field['name'],$alias,$alias_type );
		return $this;
	}

	public function subquery( $query,$alias,$alias_type ) {
		if ( is_object( $query ) && $query instanceof select ) {
			$query = $query->build();
		}
		$this->_field( "({$query})",$alias,$alias_type );
		return $this;
	}

	public function func( $func,$column,$alias=null,$alias_type=null ) {
		$this->_field( strtoupper( $func ) . '(' . $this->handle_column( $column ) . ')',$alias,$alias_type );
		return $this;
	}

	private function _field( $data,$alias=null,$alias_type=null ) {
		if ( !is_null( $alias ) && is_null( $alias_type ) ) {
			throw new db_exception('Alias type required when defining an alias');
		}
		//validate alias type here
		$this->field_aliases[$alias] = $alias_type;
		$this->fields[] = array(
			'data'  => $data,
			'alias' => $alias
		);
	}

	/*public function join( $table,\Closure $func,$alias=null ) {
		$this->joins[++$this->join_idx] = array(
			'table'  => $table,
			'fields' => array()
		);
		if ( !is_null( $alias ) ) {
			$this->table_alias( $table,$alias );
		}
		$this->fields =& $this->joins[$this->join_idx]['fields'];
		call_user_func( $func,$this );
		$this->fields =& $this->main_fields;
		return $this;
	}*/

	public function clause( $clause,$data=null,$action=self::clause_append ) {
		if ( is_null( $data ) ) {
			return ( isset( $this->clauses[$clause] ) ? $this->clauses[$clause] : false );
		}
		if ( !isset( $this->clauses[$clause] ) && ( $action === self::clause_prepend || $action == self::clause_append ) ) {
			$this->clauses[$clause] = '';
		}
		switch( $action ) {
			case self::clause_prepend:
				$this->clauses[$clause] = $data . $this->clauses[$clause];
				break;
			case self::clause_append:
				$this->clauses[$clause] .= $data;
				break;
			case self::clause_overwrite:
				$this->clauses[$clause] = $data;
				break;
		}
	}

	protected function operator( $clause,$operator ) {
		if ( !isset( $this->operators[$clause] ) ) {
			$this->operators[$clause] = false;
		}
		$operator = ( isset( $this->clauses[$clause] ) && $this->operators[$clause] ? " {$operator} " : '' );
		if ( !$this->operators[$clause] ) {
			$this->operators[$clause] = true;
		}
		return $operator;
	}

	protected function operator_status( $clause,bool $status ) {
		$this->operators[$clause] = $status;
	}

	protected function get_column( $column ) {
		$column = $this->parse_column( $column );
		if ( $column['table'] === false && !isset( $this->field_aliases[$column['name']] ) && isset( $this->table_aliases[$this->table_idx] ) ) {
			$column['table'] = $this->table_aliases[$this->table_idx];
		}
		$column['formatted'] = $this->handle_column( $column );
		return $column;
	}

	public function where_raw() {
		$this->clause( 'where',$this->fill( func_get_args() ),self::clause_append );
		return $this;
	}

	protected function _where_group( \Closure $function,$operator ) {
		$this->where_raw( $this->operator( 'where',$operator ) . '(' );
		$this->operator_status( 'where',false );
		call_user_func( $function,$this );
		$this->operator_status( 'where',true );
		$this->where_raw(')');
	}

	protected function _where( $column,$datum_1,$datum_2,$operator ) {
		$column = $this->get_column( $column );
		$datum_1 = trim( $datum_1 );
		$where = "{$column['formatted']} {$datum_1}";
		if ( !is_null( $datum_2 ) ) {
			$where .= ' ' . sprintf( $this->get_format( $column['type'] ),$this->table->db()->escape( $datum_2 ) );
		}
		$this->clause( 'where',$this->operator( 'where',$operator ) . $where,self::clause_append );
	}

	protected function _where_columns( $col_1,$op,$col_2,$operator ) {
		$where = table::encapsulate( $this->get_column( $col_1 ) ) . " {$op} " . table::encapsulate( $this->get_column( $col_2 ) );
		$this->data->set( 'where',$this->get_operator( 'where',$operator ) . $where,igsr::append );
	}

	protected function _where_in( $column,$data,$not,$operator ) {
		$column = $this->get_column( $column );
		if ( is_array( $data ) ) {
			$data = vsprintf( implode( ',',array_fill( 0,count( $data ),table::get_format( $this->column_type( $column ) ) ) ),array_map( array( $this->table->db(),'escape' ),$data ) );
		}
		elseif ( is_object( $data ) ) {
			$data = $data->build();
		}
		$not = ( $not == true ? 'NOT ' : '' );
		$this->_where( $column,"{$not}IN({$data})",null,$operator );
	}

	protected function _where_like( $column,$data,$type,$not=false,$operator=null ) {
		$not = ( $not == true ? 'NOT ' : '' );
		$this->where_raw( $this->get_operator( 'where',$operator ) . table::encapsulate( $this->get_column( $column ) ) . " {$not}LIKE ?",$type,$data );
	}

	protected function _where_between( $column,$datum_1,$datum_2,$not=false,$operator=null ) {
		$not = ( $not == true ? 'NOT ' : '' );
		$column = $this->get_column( $column );
		$type = $this->column_type( $column );
		$this->where_raw( $this->get_operator( 'where',$operator ) . table::encapsulate( $column ) . " {$not}BETWEEN ? AND ?",str_repeat( $type,2 ),$datum_1,$datum_2 );
	}

	public function build() {
		//get fields
	}

}

?>