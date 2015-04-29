<?php

namespace app\exceptions;

class api extends \Exception {

	public function __construct() {
		$args = func_get_args();
		$code = array_shift( $args );
		$message = array_shift( $args );
		if ( count( $args ) > 0 ) {
			$message = vsprintf( $message,$args );
		}
		parent::__construct( $message,$code );
	}

}

?>