<?php

namespace app\exceptions;

class app extends \Exception {

	public function __construct() {
		$args = func_get_args();
		$message = array_shift( $args );
		if ( count( $args ) > 0 ) {
			$message = vsprintf( $message,$args );
		}
		parent::__construct( $message );
	}

}

?>