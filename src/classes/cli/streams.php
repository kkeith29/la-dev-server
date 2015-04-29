<?php

namespace app\classes\cli;

class streams {

	private static $in  = STDIN;
	private static $out = STDOUT;
	private static $err = STDERR;

	public static function call( $method,$args ) {
		return call_user_func_array( __CLASS__ . "::{$method}",$args );
	}

	public static function render() {
		$args = func_get_args();
		$data = array_shift( $args );
		if ( count( $args ) === 0 ) {
			return $data;
		}
		$data = preg_replace( '#(%([^\w]|$))#','%$1',$data );
		return vsprintf( $data,$args );
	}

	public static function out() {
		fwrite( self::$out,self::call( 'render',func_get_args() ) );
	}

	public static function line() {
		$args = array_merge( func_get_args(),array('') );
		$args[0] .= "\n";
		self::call( 'out',$args );
	}

	public static function err() {
		$args = array_merge( func_get_args(),array('') );
		$args[0] .= "\n";
		fwrite( self::$err,self::call( 'render',$args ) );
	}

	public static function input() {
		$line = fgets( self::$in );
		if ( $line === false ) {
			throw new app_exception('Caught ^D during input');
		}
		return trim( $line );
	}

	public static function prompt( $question,$config=array() ) {
		if ( isset( $config['default'] ) && strpos( $question,'[' ) === false ) {
			$question .= " [{$config['default']}]";
		}
		if ( !isset( $config['marker'] ) ) {
			$config['marker'] = ': ';
		}
		while(true) {
			self::out( $question . $config['marker'] );
			$line = self::input();
			if ( $line !== '' ) {
				if ( isset( $config['validation'] ) && is_callable( $config['validation'] ) && !call_user_func( $config['validation'],$line ) ) {
					continue;
				}
				return $line;
			}
			if ( isset( $config['default'] ) ) {
				return $config['default'];
			}
		}
	}

	public static function menu( $options,$config=array() ) {
		if ( isset( $config['default'] ) && !isset( $options[$config['default']] ) ) {
			throw new app_exception('Default selection must be a valid option');
		}
		if ( !isset( $config['title'] ) ) {
			$config['title'] = 'Choose an item';
		}
		if ( isset( $config['default'] ) && strpos( $config['title'],'[' ) === false ) {
			$config['title'] .= " [{$options[$config['default']]}]";
		}
		$list = array_values( $options );
		if ( isset( $config['cancel'] ) && $config['cancel'] ) {
			$cancel_idx = count( $list );
			$list[$cancel_idx] = ( isset( $config['cancel_title'] ) ? $config['cancel_title'] : 'Cancel' );
		}
		$list_count = count( $list );
		foreach( $list as $idx => $item ) {
			self::line( '  %d. %s',( $idx + 1 ),$item );
		}
		self::line();
		while(true) {
			self::out( '%s: ',$config['title'] );
			$line = self::input();
			if ( is_numeric( $line ) ) {
				$line = ( (int) $line - 1 );
				if ( isset( $cancel_idx ) && $line === $cancel_idx ) {
					return false;
				}
				if ( isset( $list[$line] ) ) {
					return array_search( $list[$line],$options );
				}
				if ( $line < 0 || $line >= $list_count ) {
					self::err('Invalid menu selection: out of range');
				}
			}
			elseif ( isset( $config['default'] ) ) {
				return $config['default'];
			}
		}
	}

	public static function choose( $question,$choices=array('y','n'),$config=array() ) {
		if ( !is_array( $choices ) ) {
			$choices = str_split( $choices );
		}
		$choices = $_choices = array_map( 'strtolower',$choices );
		$prompt_config = array();
		if ( isset( $config['default'] ) && ( $d_key = array_search( $config['default'],$_choices ) ) !== false ) {
			$_choices[$d_key] = strtoupper( $_choices[$d_key] );
			$prompt_config['default'] = $config['default'];
		}
		$_choices = trim( implode( '/',$_choices ),'/' );
		while(true) {
			$line = self::prompt( "{$question} [{$_choices}]",$prompt_config );
			$line = strtolower( $line );
			if ( in_array( $line,$choices ) ) {
				return $line;
			}
			if ( isset( $config['default'] ) ) {
				return $config['default'];
			}
		}
	}

}

?>