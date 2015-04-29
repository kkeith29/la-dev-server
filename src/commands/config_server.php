<?php

namespace app\commands;

use app\classes\cli;
use app\classes\command;
use app\classes\db;

class config_server extends command {

	public function main() {
		$query = db::table('users')->select()->fields('first_name','last_name');
		cli::line( $query->debug() );
		
	}

	public function install() {
		
	}

}

?>