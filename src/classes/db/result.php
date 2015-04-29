<?php

namespace app\classes\db;

use app\classes\db;

class result implements \Iterator,\Countable {

	private $db;
	private $rows = array();
	private $position = 0;

	public function __construct( db $db,\SQLite3Result $result ) {
		$this->db = $db;
		while( $data = $result->fetchArray( SQLITE3_ASSOC ) ) {
			$this->rows[] = new row( $this,$data );
		}
		$result->finalize();
		unset( $result );
	}

	public function first() {
		return reset( $this->rows );
	}

	public function rewind() {
		$this->position = 0;
	}

	public function current() {
		return $this->rows[$this->position];
	}
	
	public function key() {
		return $this->position;
	}
	
	public function next() {
		++$this->position;
	}
	
	public function valid() {
		return isset( $this->rows[$this->position] );
	}

	public function count() {
		return count( $this->rows );
	}

	public function num_rows() { //alias for count
		return $this->count();
	}

}

?>