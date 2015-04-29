<?php

namespace app\classes;

use app\exceptions\app as app_exception;

class func {

	public static function rand_string( $length,$type='all',$str='' ) {
		$types = array(
			'alpha'       => 'bcdfghjklmnpqrstvwxyzBCDFGHJKLMNPQRSTVWXYZ',
			'alpha-lower' => 'bcdfghjklmnpqrstvwxyz',
			'alpha-upper' => 'BCDFGHJKLMNPQRSTVWXYZ',
			'numeric'     => '0123456789',
			'special'     => '`!?$?%^&*()_-+={[}]:;@~#|<,>.?/'
		);
		if ( $type !== 'custom' ) {
			if ( $type == 'all' ) {
				unset( $types['alpha'] );
				foreach( $types as $type => $_str ) {
					$str .= $_str;
				}
			}
			else {
				$_types = explode( ',',$type );
				foreach( $_types as $type ) {
					if ( !isset( $types[$type] ) ) {
						throw new app_exception( "Type '%s' is invalid",$type );
					}
					$str .= $types[$type];
				}
			}
		}
		$data = array();
		if ( is_string( $str ) ) {
			$data = str_split( $str,1 );
		}
		$i = 0;
		$str = '';
		while ( $i < $length ) {
			$rand = mt_rand( 0,( count( $data ) - 1 ) );
			$str .= $data[$rand];
		$i++;
		}
		return $str;
	}

	public static function array_merge_recursive_distinct( $a1,$a2 ) {
		$arrays = func_get_args();
		$base = array_shift( $arrays );
		if ( !is_array( $base ) ) {
			$base = ( empty( $base ) ? array() : array( $base ) );
		}
		foreach( $arrays as $array ) {
			if ( !is_array( $array ) ) {
				$array = array( $array );
			}
			foreach( $array as $key => $value ) {
				if ( !array_key_exists( $key,$base ) && !is_numeric( $key ) ) {
					$base[$key] = $array[$key];
					continue;
				}
				if ( is_array( $value ) || ( isset( $base[$key] ) && is_array( $base[$key] ) ) ) {
					$base[$key] = self::merge_recursive_distinct( ( isset( $base[$key] ) ? $base[$key] : array() ),$array[$key] );
				}
				elseif ( is_numeric( $key ) ) {
					if ( !in_array( $value,$base ) ) {
						$base[] = $value;
					}
				}
				else {
					$base[$key] = $value;
				}
			}
		}
		return $base;
	}

	public static function exec() {
		$args = func_get_args();
		$cmd = array_shift( $args );
		$args = array_map( 'escapeshellarg',$args );
		$cmd = vsprintf( $cmd,$args );
		$retval = exec( $cmd,$output,$error );
		if ( $error !== 0 ) {
			return false;
		}
		return $retval;
	}

	public static function passthru() {
		$args = func_get_args();
		$cmd = array_shift( $args );
		$args = array_map( 'escapeshellarg',$args );
		$cmd = vsprintf( $cmd,$args );
		passthru( $cmd,$error );
		if ( $error !== 0 ) {
			return false;
		}
		return true;
	}

	public static function make_temp( $dir=false ) {
		return self::exec( 'mktemp ' . ( $dir ? '-d ' : '' ) . ' /tmp/tmp.XXXXXX' );
	}

	public static function is_root() {
		$id = self::exec('id -u');
		if ( $id === false || (int) $id !== 0 ) {
			return false;
		}
		return true;
	}

	public static function set_permissions( $dir ) {
		$items = scandir( $dir );
		foreach( $items as $item ) {
			if ( $item == '.' || $item == '..' ) {
				continue;
			}
			$item = $dir . $item;
			if ( is_dir( $item ) ) {
				$octal = 0775;
				chmod( $item,$octal );
				self::set_permissions( "{$item}/" );
			}
			else {
				$octal = 0664;
				chmod( $item,$octal );
			}
		}
	}
	
	public static function clear_directory( $dir,$config=array() ) {
		if ( !isset( $config['delete'] ) ) {
			$config['delete'] = false;
		}
		if ( !$config['delete'] && !is_dir( $dir ) ) {
			throw new app_exception( '%s is not a directory',$dir );
		}
		foreach( scandir( $dir ) as $item ) {
			if ( $item == '.' || $item == '..' ) {
				continue;
			}
			$name = ( isset( $config['name'] ) ? $config['name'] . '/' : '' ) . $item;
			if ( isset( $config['keep'] ) && in_array( $name,$config['keep'] ) ) {
				continue;
			}
			if ( is_dir( $dir . DIRECTORY_SEPARATOR . $item ) ) {
				$_config = $config;
				$_config['name'] = $name;
				$_config['delete'] = true;
				self::clear_directory( $dir . DIRECTORY_SEPARATOR . $item,$_config );
				continue;
			}
			unlink( $dir . DIRECTORY_SEPARATOR . $item );
		}
		if ( $config['delete'] ) {
			rmdir( $dir );
		}
	}

	public static function validate_site_name( $name ) {
		if ( preg_match( '#^[a-z\-]+$#',$name ) !== 1 || strpos( $name,'-' ) === 0 ) {
			return false;
		}
		return true;
	}

	/*public static function http_query( $server,$config=array() ) {
		$url = "http://{$server}.lifeboatcreative.com/index.php?";
		$args = array();
		foreach( $config as $key => $value ) {
			$args[] = "{$key}=" . urlencode( $value );
		}
		$url .= implode( '&',$args );
		if ( ( $data = @file_get_contents( $url ) ) === false ) {
			throw new app_exception('Unable to query remote server');
		}
		$data = json_decode( $data );
		if ( isset( $data->error ) ) {
			throw new app_exception( $data->error );
		}
		return $data;
	}

	public static function get_server( $name ) {
		try {
			$data = self::http_query( SERVER_REGISTRY,array(
				'type' => 'site',
				'cmd'  => 'get-server',
				'name' => $name
			) );
			return $data->server;
		}
		catch( app_exception $e ) {
			return false;
		}
	}*/

	public static function run_file( $file,$vars=array() ) {
		extract( $vars );
		include $file;
	}

}

?>