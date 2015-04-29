<?php

namespace app\classes\socket;

use app\exceptions\app as app_exception;

//see: http://blog.leenix.co.uk/2011/05/howto-php-tcp-serverclient-with-ssl.html

class ssl_certificate {

	private $dn = array(
		'countryName'            => 'Country Name',
		'stateOrProvinceName'    => 'State or Province Name',
		'localityName'           => 'City Name',
		'organizationName'       => 'Organization Name',
		'organizationalUnitName' => 'Department Name',
		'commonName'             => 'Name Here',
		'emailAddress'           => 'info@something.com'
	);
	private $pem_passphrase = 'password';
	private $pem_data       = '';
	private $pem_file       = null;

	public function __call( $method,$args ) {
		$dn_funcs = array(
			'country'       => 'countryName',
			'state'         => 'stateOrProvinceName',
			'province'      => 'stateOrProvinceName',
			'city'          => 'localityName',
			'organization'  => 'organizationName',
			'department'    => 'organizationalUnitName',
			'name'          => 'commonName',
			'email_address' => 'emailAddress'
		);
		if ( isset( $dn_funcs[$method] ) ) {
			if ( isset( $args[0] ) ) {
				$this->dn[$dn_funcs[$method]] = $args[0];
				return $this;
			}
			return $this->dn[$dn_funcs[$method]];
		}
		switch( $method ) {
			case 'passphrase':
				if ( isset( $args[0] ) ) {
					$this->pem_passphrase = $args[0];
					return $this;
				}
				return $this->pem_passphrase;
				break;
			default:
				throw new app_exception( 'Invalid method: %s',$method );
				break;
		}
	}

	public function pem_file() {
		if ( is_null( $this->pem_file ) ) {
			throw new app_exception('save() function must be run before trying to get filename');
		}
		return $this->pem_file;
	}

	private function _create() {
		$pkey = openssl_pkey_new();
		$cert = openssl_csr_new( $this->dn,$pkey );
		$cert = openssl_csr_sign( $cert,null,$pkey,365 );
		$pem = array();
		openssl_x509_export( $cert,$pem[0] );
		openssl_pkey_export( $pkey,$pem[1],$this->pem_passphrase );
		$this->pem_data = implode( $pem );
	}

	public function save( $file ) {
		$this->_create();
		if ( file_put_contents( $file,$this->pem_data ) === false ) {
			throw new app_exception( 'Unable to write PEM file: %s',$file );
		}
		if ( chmod( $file,0600 ) === false ) {
			throw new app_exception( 'Unable to set permissions on PEM file: %s',$file );
		}
		$this->pem_file = $file;
	}

	//output function if needed as string

	public static function create() {
		return new self;
	}

}

?>