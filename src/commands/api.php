<?php

namespace app\commands;

use app\classes\api\server;
use app\classes\command;
use app\classes\config;
use app\classes\path;
use app\classes\system;

class api extends command {

	public function main() {
		
	}

	public function start() {
		$config = config::get('api');
		$interfaces = system::network_interfaces();
		if ( !isset( $interfaces[$config['interface']] ) ) {
			throw new app_exception( 'Unable to find network interface: %s',$config['interface'] );
		}
		$ssl_cert = config::get('api:ssl_cert');
		$server = new server(array(
			'bind_ip'   => $interfaces[$config['interface']],
			'bind_port' => $config['port'],
			'ssl_cert'  => array(
				'country'      => $ssl_cert['country'],
				'state'        => $ssl_cert['state_province'],
				'city'         => $ssl_cert['city'],
				'organization' => $ssl_cert['organization'],
				'department'   => $ssl_cert['department'],
				'name'         => $ssl_cert['name'],
				'email'        => $ssl_cert['email'],
				'pem_file'     => path::data('api.pem')
			)
		));
		$server->start();
	}

}

?>