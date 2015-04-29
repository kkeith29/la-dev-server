<?php

namespace app\classes\db;

use app\exceptions\db as db_exception;

class query {

	const like_any   = 1;
	const like_start = 2;
	const like_end   = 3;

	protected static $data_types = [
		table::data_type_text => 's',
		table::data_type_num  => 'i',
		table::data_type_int  => 'i',
		table::data_type_real => 'i',
		table::data_type_none => 's'
	];

	protected $table;

	public function __construct( table $table ) {
		$this->table = $table;
	}

	public function debug() {
		return $this->build();
	}

	protected function get_table( $table ) {
		if ( $table === $this->table->name() ) {
			return $this->table;
		}
		return $this->table->db()->_table( $table );
	}

	protected function parse_column( $column ) {
		if ( !is_string( $column ) ) {
			throw new db_exception('Only string arguments are allowed');
		}
		$retval = array(
			'alias' => false
		);
		if ( false !== ( $pos = stripos( $column,' AS ' ) ) ) {
			list( $column,$retval['alias'] ) = explode( substr( $column,$pos,4 ),$column,2 );
		}
		if ( strpos( $column,'(' ) === 0 && strrpos( $column,')' ) === ( strlen( $column ) - 1 ) ) {
			$retval['custom'] = $column;
			return $retval;
		}
		$retval['name'] = $column;
		$retval['table'] = false;
		if ( strpos( $column,'.' ) !== false ) {
			list( $retval['table'],$retval['name'] ) = explode( '.',$retval['name'] );
		}
		$table = $this->get_table( $retval['table'] );
		if ( !$table->exists() ) {
			throw new db_exception( "Unable to find table '%s'",$retval['table'] );
		}
		if ( ( $column = $table->get_column( $retval['name'] ) ) === false ) {
			throw new db_exception( "Unable to find column '%s' of table '%s'",$retval['name'],$retval['table'] );
		}
		$retval['type'] = $column['type'];
		return $retval;
	}

	protected function handle_column( $column ) {
		if ( !is_array( $column ) ) {
			$column = $this->parse_column( $column );
		}
		if ( isset( $column['custom'] ) ) {
			return $column['custom'];
		}
		return ( isset( $column['table'] ) ? "{$column['table']}." : '' ) . $column['column'];
	}

	protected function fill( $args ) {
		$where = array_shift( $args );
		if ( count( $args ) > 0 ) {
			$types = str_split( array_shift( $args ) );
			if ( count( $types ) !== count( $args ) ) {
				throw new db_exception('Number of data types is not equal to the fields provided');
			}
			$position = array();
			foreach( $args as $i => $arg ) {
				if ( is_array( $arg ) ) {
					foreach( $arg as $part => $value ) {
						$pos = 0;
						while( false !== ( $pos = strpos( $where,":{$part}",$pos ) ) && substr( $where,( isset( $where[$pos-1] ) ? ( $pos - 1 ) : $pos ),1 ) !== '\\' ) {
							$where = substr_replace( $where,$this->get_format( $types[$i] ),$pos,( strlen( $part ) + 1 ) );
							$position[$pos] = $value;
						}
					}
					continue;
				}
				if ( false !== ( $pos = strpos( $where,'?' ) ) && substr( $where,( isset( $where[$pos-1] ) ? ( $pos - 1 ) : $pos ),1 ) !== '\\' ) {
					$where = substr_replace( $where,$this->get_format( $types[$i] ),$pos,1 );
					$position[$pos] = $arg;
				}
			}
			$where = str_replace( array('\?','\:'),array('?',':'),$where );
			ksort( $position );
			$values = array();
			foreach( array_values( $position ) as $value ) {
				$values[] = $this->table->db()->escape( $value );
			}
			$where = vsprintf( $where,$values );
		}
		return $where;
	}

	protected function get_format( $type ) {
		switch( $type ) {
			case 'i': //numeric
				$str = '%d';
				break;
			case 'b': //numeric
			case 'd':
			case 'u':
			case 'o':
				$str = "%{$type}";
				break;
			case 'c': //strings
			case 'e':
			case 'f':
			case 's':
			case 'x':
			case 'X':
				$str = "'%{$type}'";
				break;
			case 'n': //custom type
				$str = '%s';
				break;
			case self::like_any: //mysql like types
				$str = "'%%%s%%'";
				break;
			case self::like_start:
				$str = "'%%%s'";
				break;
			case self::like_end:
				$str = "'%s%%'";
				break;
			default:
				throw new db_exception( "Invalid data type '%s'",$type );
				break;
		}
		return $str;
	}

}

?>