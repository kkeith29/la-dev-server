<?php

namespace app\classes;

use app\classes\manifest\item;
use app\exceptions\app as app_exception;

class manifest {

	private $file = null;
	private $data;
	private $versions = array();

	public function __construct( $file,$exception=true ) {
		if ( strpos( $file,'http' ) !== 0 ) {
			$this->file = $file;
		}
		if ( ( $data = @file_get_contents( $file ) ) === false ) {
			if ( $exception === true ) {
				throw new app_exception('Unable to get manifest');
			}
			$data = '[]';
		}
		$this->data = json_decode( $data );
		if ( !is_array( $this->data ) ) {
			throw new app_exception('Manifest file may be corrupted');
		}
		foreach( $this->data as $i => $item ) {
			$this->versions[$item->version] = $i;
		}
	}

	public function get_versions() {
		return array_keys( $this->versions );
	}

	public function has_version( $version ) {
		return isset( $this->versions[$version] );
	}

	public function latest_version( $config=array() ) {
		if ( count( $this->versions ) === 0 ) {
			return false;
		}
		if ( !isset( $config['dev'] ) ) {
			$config['dev'] = false;
		}
		$versions = array_filter( $this->get_versions(),function( $version ) use ( $config ) {
			$pos = strrpos( $version,'-' );
			if ( !$config['dev'] && $pos !== false ) {
				return false;
			}
			if ( $config['dev'] && !in_array( substr( $version,( $pos + 1 ) ),array('dev','alpha','beta') ) ) {
				return false;
			}
			return true;
		} );
		usort( $versions,'version_compare' );
		return array_pop( $versions );
	}

	public function version_info( $version ) {
		if ( !$this->has_version( $version ) ) {
			return false;
		}
		return $this->data[$this->versions[$version]];
	}

	public function add_item( item $item ) {
		$this->data[] = $item->to_array();
	}

	public function write() {
		if ( is_null( $this->file ) ) {
			throw new app_exception('Local file not set');
		}
		file_put_contents( $this->file,json_encode( $this->data ) );
	}

}

?>