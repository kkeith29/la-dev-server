<?php

namespace app\classes\api;

use app\classes\cli;
use app\classes\socket\server as socket_server;
use app\classes\socket\ssl_certificate;
use app\exceptions\api as api_exception;

class server {

	private $config = array(
		'bind_ip'   => '0.0.0.0',
		'bind_port' => '6500',
		'ssl_cert'  => array(
			'country'      => '',
			'state'        => '',
			'city'         => '',
			'organization' => '',
			'department'   => '',
			'name'         => '',
			'email'        => '',
			'pem_file'     => ''
		)
	);
	private $server;

	public function __construct( $config=array() ) {
		$this->config = array_merge( $this->config,$config );
	}

	public function start() {
		$this->server = new socket_server( socket_server::trans_tcp,"{$this->config['bind_ip']}:{$this->config['bind_port']}" );
		
		$cert = ssl_certificate::create();
		$cert->country( $this->config['ssl_cert']['country'] );
		$cert->state( $this->config['ssl_cert']['state'] );
		$cert->city( $this->config['ssl_cert']['city'] );
		$cert->organization( $this->config['ssl_cert']['organization'] );
		$cert->department( $this->config['ssl_cert']['department'] );
		$cert->name( $this->config['ssl_cert']['name'] );
		$cert->email_address( $this->config['ssl_cert']['email'] );
		$cert->save( $this->config['ssl_cert']['pem_file'] );

		$this->server->ssl_cert( $cert );

		$this->server->client_handler(array( $this,'handle_request' ));
		$this->server->listen();
	}

	public function handle_request( $client ) {
		//$client->fork();
		try {
			$request = new request( $client );
		}
		catch( api_exception $e ) {
			cli::line( 'Request error: %s',$e->getMessage() );
			$client->send( response::create()->status( response::status_fail )->error( $e->getCode(),$e->getMessage() )->output() );
		}
		$client->close();
		cli::line( 'Client [%s]: connection closed',$client->get_name() );
	}

}

?>