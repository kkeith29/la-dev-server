<?php

namespace app\commands;

use app\classes\command;
use app\classes\dns\server;

class dns_server extends command {

	public function main() {
		
	}

	public function install() {
		
	}

	public function start() {
		//get settings from config file
		$server = new server(array(
			'bind_ip'   => '10.0.5.3',
			'bind_port' => '8088'
		));
		$server->start();
	}

}

?>