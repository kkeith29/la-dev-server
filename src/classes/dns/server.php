<?php

namespace app\classes\dns;

use app\classes\cli;
use app\classes\socket\server as socket_server;
use app\exceptions\dns as dns_exception;

class server {

	private $resolvers = array();
	private $config = array(
		'bind_ip'           => '0.0.0.0',
		'bind_port'         => 53,
		'ttl'               => 300,
		'max_packet_length' => 512
	);
	private $server;

	public function __construct( $config=array() ) {
		$this->config = array_merge( $this->config,$config );
	}

	public function add_resolver( resolver $resolver ) {
		$this->resolvers[] = $resolver;
	}

	public function start() {
		$this->server = new socket_server( socket_server::trans_udp,"{$this->config['bind_ip']}:{$this->config['bind_port']}" );
		$this->server->client_handler(array( $this,'handle_request' ));
		$this->server->listen();
		//bind to tcp port as well
	}

	public function handle_request( $client ) {
		//fork process here
		try {
			$request = new request( $client->read( $this->config['max_packet_length'] ) );
			$client->send('test');
		}
		catch( dns_exception $e ) {
			cli::line( 'Request error: %s',$e->getMessage() );
		}
	}

}

?>