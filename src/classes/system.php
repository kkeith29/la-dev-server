<?php

namespace app\classes;

use app\exceptions\app as app_exception;

class system {

	public static function network_interfaces() {
		exec( 'ifconfig',$output,$error );
		if ( $error !== 0 ) {
			throw new app_exception('Unable to get network interfaces');
		}
		$interfaces = array();
		$interface = null;
		foreach( $output as $line ) {
			if ( preg_match( '#^([a-zA-Z]+([0-9]+)?)\s*(.*)$#',$line,$match ) === 1 ) {
				$interface = $match[1];
				$interfaces[$interface] = array();
				$line = $match[3];
			}
			if ( is_null( $interface ) ) {
				throw new app_exception('Unable to parse interfaces');
			}
			$interfaces[$interface][] = $line;
		}
		$interfaces = array_map( function( $value ) {
			return implode( "\n",array_filter( array_map( 'trim',$value ) ) );
		},$interfaces );
		foreach( $interfaces as $name => &$info ) {
			if ( preg_match( '#inet addr:([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})#s',$info,$match ) !== 1/* || filter_var( $match[1],FILTER_VALIDATE_IP,FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) === false*/ || $match[1] === '127.0.0.1' ) {
				unset( $info,$interfaces[$name] );
				continue;
			}
			$info = $match[1];
			unset( $info );
		}
		return $interfaces;
	}

}

?>