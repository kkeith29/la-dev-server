<?php

namespace app\classes\socket;

use app\classes\cli;
use app\exceptions\socket as socket_exception;

class server {

	const trans_tcp  = 'tcp';
	const trans_udp  = 'udp';
	const trans_unix = 'unix';

	private $transport;
	private $target;
	private $ssl_cert = null;
	private $handler = null;

	private $listen = false;

	public function __construct( $transport,$target ) {
		$this->transport = $transport;
		$this->target = $target;
		$transports = stream_get_transports();
		if ( !in_array( $transport,$transports ) ) {
			throw new socket_exception( "Transport '%s' not available",$transport );
		}
	}

	public function get_transport() {
		return $this->transport;
	}

	public function ssl_cert( ssl_certificate $cert ) {
		$this->ssl_cert = $cert;
	}

	public function client_handler( $handler ) {
		$this->handler = $handler;
	}

	public function listen() {
		if ( is_null( $this->handler ) ) {
			throw new socket_exception('Connection handler not defined');
		}
		$context = stream_context_create();
		if ( !is_null( $this->ssl_cert ) ) {
			stream_context_set_option( $context,'ssl','local_cert',$this->ssl_cert->pem_file() );
			stream_context_set_option( $context,'ssl','passphrase',$this->ssl_cert->passphrase() );
			stream_context_set_option( $context,'ssl','allow_self_signed',true );
			stream_context_set_option( $context,'ssl','verify_peer',false );
		}
		$flags = ( $this->transport === self::trans_udp ? STREAM_SERVER_BIND : STREAM_SERVER_BIND | STREAM_SERVER_LISTEN );
		$this->socket = stream_socket_server( "{$this->transport}://{$this->target}",$errno,$errstr,$flags,$context );
		if ( $this->socket === false ) {
			throw new socket_exception( 'Unable to create socket server: [%d] %s',$errno,$errstr );
		}
		/*if ( !is_null( $this->ssl_cert ) ) {
			stream_set_blocking( $this->socket,true );
			if ( stream_socket_enable_crypto( $this->socket,false,STREAM_CRYPTO_METHOD_TLS_SERVER ) === false ) {
				throw new socket_exception('Unable to disable encryption');
			}
			stream_set_blocking( $this->socket,false );
		}*/
		cli::line( 'Listening on %s://%s for connections',$this->transport,$this->target );
		$this->listen = true;
		if ( $this->transport === self::trans_tcp ) {
			while( $this->listen ) {
				if ( ( $client = stream_socket_accept( $this->socket,-1,$peername ) ) === false ) {
					continue;
				}
				cli::line( 'Client %s connected',$peername );
				if ( !is_null( $this->ssl_cert ) ) {
					stream_set_blocking( $client,true );
					if ( @stream_socket_enable_crypto( $client,true,STREAM_CRYPTO_METHOD_TLS_SERVER ) === false ) {
						cli::line('Unable to enable encryption, closing...');
						fclose( $client );
						continue;
					}
					stream_set_blocking( $client,false );
				}
				$client = new client( $this,$client,$peername );
				call_user_func( $this->handler,$client );
			}
			return;
		}
		while( $this->listen ) {
			stream_socket_recvfrom( $this->socket,1,STREAM_PEEK,$address );
			$client = new client( $this,$this->socket,$address );
			call_user_func( $this->handler,$client );
		}
	}

}

?>