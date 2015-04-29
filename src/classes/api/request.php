<?php

namespace app\classes\api;

use app\classes\socket\client;
use app\exceptions\api as api_exception;

class request {

	private static $json_errors = array(
		JSON_ERROR_NONE             => null,
		JSON_ERROR_DEPTH            => 'Maximum stack depth exceeded',
		JSON_ERROR_STATE_MISMATCH   => 'Underflow or the modes mismatch',
		JSON_ERROR_CTRL_CHAR        => 'Unexpected control character found',
		JSON_ERROR_SYNTAX           => 'Syntax error, malformed JSON',
		JSON_ERROR_UTF8             => 'Malformed UTF-8 characters, possibly incorrectly encoded'
	);

	private $client;
	private $headers;
	private $body;

	public function __construct( client $client ) {
		$this->client = $client;
		$this->parse();
		$this->handle();
	}

	private function json_error_message( $code ) {
		if ( !function_exists('json_last_error_msg') ) {
			return ( array_key_exists( self::$json_errors[$code] ) ? self::$json_errors[$code] : "Unknown error ({$code})" );
		}
		return json_last_error_msg();
	}

	private function parse() {
		$headers = $this->client->read_until( chr(0) );
		if ( $headers === false ) {
			throw new api_exception( 1,'Header error: No null byte encountered' );
		}
		$headers = json_decode( $headers,false,30 );
		if ( is_null( $headers ) ) {
			throw new api_exception( 2,'Header error: Unable to decode JSON - reason: %s',$this->json_error_message( json_last_error() ) );
		}
		if ( !isset( $headers->content_length ) ) {
			throw new api_exception( 3,'Header error: No content length specified' );
		}
		if ( !is_int( $headers->content_length ) ) {
			throw new api_exception( 4,'Header error: Content length must be numeric' );
		}
		if ( $headers->content_length < 0 ) {
			throw new api_exception( 5,'Header error: Content length must not be negative' );
		}
		$this->headers = $headers;
		$body = $this->client->read( $headers->content_length );
		if ( strlen( $body ) < $headers->content_length ) {
			throw new api_exception( 6,'Header error: Content length does not match size of body content' );
		}
		$body = json_decode( $body,false,30 );
		if ( is_null( $body ) ) {
			throw new api_exception( 7,'Body error: Unable to decode JSON - reason: %s',$this->json_error_message( json_last_error() ) );
		}
		$this->body = $body;
		unset( $headers,$body );
	}

	private function handle() {
		if ( !isset( $this->body->version ) ) {
			throw new api_exception( 8,'Request error: API version is required' );
		}
		if ( !isset( $this->body->endpoint ) ) {
			throw new api_exception( 9,'Request error: API endpoint is required' );
		}
		//test for endpoint existance (like commands)
	}

}

?>