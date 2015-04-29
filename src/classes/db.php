<?php

namespace app\classes;

use app\classes\db\forge;
use app\classes\db\table;
use app\classes\db\result;
use app\exceptions\db as db_exception;

class db {

	private static $instances = array();
	private static $instance_idx = null;

	private $path = '';
	private $db = null;
	private $tables = array();

	public function __construct( $db_file ) {
		$this->path = $db_file;
		try {
			$this->db = new \SQLite3( $this->path );
		}
		catch( \Exception $e ) {
			throw new db_exception( 'Unable to open database: %s',$e->getMessage() );
		}
	}

	public function _table( $name ) {
		if ( !isset( $this->tables[$name] ) ) {
			$this->tables[$name] = new table( $this,$name );
		}
		return $this->tables[$name];
	}

	private function handle_sql( $args ) {
		$sql = array_shift( $args );
		if ( count( $args ) > 0 ) {
			$args = array_map( array( $this->db,'escapeString' ),$args );
			$sql = vsprintf( $sql,$args );
		}
		return $sql;
	}

	public function _query() {
		$query = $this->handle_sql( func_get_args() );
		if ( ( $result = @$this->db->query( $query ) ) === false ) {
			throw new db_exception( "Unable to execute query '%s': %s",$query,$this->db->lastErrorMsg() );
		}
		return new result( $this,$result );
	}

	public function _exec() {
		$query = $this->handle_sql( func_get_args() );
		if ( ( $result = @$this->db->exec( $query ) ) === false && $this->db->lastErrorCode() !== 0 ) {
			throw new db_exception( "Unable to execute query '%s': %s",$query,$this->db->lastErrorMsg() );
		}
		return $result;
	}

	public function _forge() {
		return new forge( $this );
	}

	public static function __callStatic( $method,$args ) {
		if ( is_null( self::$instance_idx ) ) {
			throw new db_exception('No db instances found, please open() before trying to access functions');
		}
		$db = self::$instances[self::$instance_idx];
		$method = "_{$method}";
		if ( !method_exists( $db,$method ) ) {
			throw new app_exception( 'Unable to find method %s',$method );
		}
		return call_user_func_array( array( $db,$method ),$args );
	}

	public static function open( $db_file,$alias=null ) {
		if ( !is_null( $alias ) && isset( self::$instances[$alias] ) ) {
			throw new db_exception( "Alias '%s' already in use",$alias );
		}
		self::$instance_idx = ( is_null( $alias ) ? ( count( self::$instances ) + 1 ) : $alias );
		self::$instances[self::$instance_idx] = new self( $db_file );
		return self::$instance_idx;
	}

}

?>