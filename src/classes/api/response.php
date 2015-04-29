<?php

namespace app\classes\api;

use app\exceptions\app as app_exception;

class response {

	const status_ok   = 1;
	const status_fail = 2;

	private $meta;
	private $body;

	public function __construct() {
		$this->meta = new \stdClass;
	}

	public function body( $data ) {
		$this->body = $data;
		return $this;
	}

	public function status( $status ) {
		$this->meta->status = $status;
		return $this;
	}

	public function error( $code,$message ) {
		$this->meta->error = new \stdClass;
		$this->meta->error->code = $code;
		$this->meta->error->message = $message;
		return $this;
	}

	public function output() {
		if ( !isset( $this->meta->status ) ) {
			throw new app_exception('Response status is required');
		}
		if ( $this->meta->status !== self::status_ok && !isset( $this->meta->error ) ) {
			throw new app_exception('Response error message is required if status is not OK');
		}
		$json = new \stdClass;
		$json->meta = $this->meta;
		if ( !is_null( $this->body ) ) {
			$json->body = $this->body;
		}
		return json_encode( $json );
	}

	public static function create() {
		return new self;
	}

}

?>