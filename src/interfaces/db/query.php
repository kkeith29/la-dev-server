<?php

namespace app\interfaces\db;

use app\classes\db\table;

interface query {

	public function __construct( table $table );
	public function build();

}

?>