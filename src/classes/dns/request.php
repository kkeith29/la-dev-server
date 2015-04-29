<?php

namespace app\classes\dns;

use app\exceptions\dns as dns_exception;

class request {

	private $data;
	private $header    = array();
	private $labels    = array();
	private $questions = array();
	private $offset    = 0;

	public function __construct( $data ) {
		$this->data = $data;
		$this->parse_header();
		$this->parse_question();
		print_r( $this->header );
		print_r( $this->questions );
	}

	private function data( $length=null,$add=true ) {
		$data = substr( $this->data,$this->offset,$length );
		if ( !is_null( $length ) ) {
			if ( $data === false || strlen( $data ) !== $length ) {
				return null;
			}
			if ( $add ) {
				$this->offset += $length;
			}
		}
		return $data;
	}

	private function parse_header() {
		if ( strlen( $this->data ) < 12 ) {
			throw new dns_exception('Unable to parse request header - not enough data received');
		}
		$header = unpack( 'nid/nfields/nqdcount/nancount/nnscount/narcount',$this->data(12) );
		$header['rcode']  = $header['fields'] & bindec('1111');
		$header['z']      = ( $header['fields'] >> 4 ) & bindec('111');
		$header['ra']     = ( $header['fields'] >> 7 ) & 1;
		$header['rd']     = ( $header['fields'] >> 8 ) & 1;
		$header['tc']     = ( $header['fields'] >> 9 ) & 1;
		$header['aa']     = ( $header['fields'] >> 10 ) & 1;
		$header['opcode'] = ( $header['fields'] >> 11 ) & bindec('1111');
		$header['qr']     = ( $header['fields'] >> 15 ) & 1;
		unset( $header['fields'] );
		$this->header = $header;
	}

	private function parse_question() {
		if ( is_null( $this->data(2,false) ) ) {
			throw new dns_exception('Unable to parse question - not enough data received');
		}
		$offset = $this->offset;
		$this->labels[$offset] = $this->parse_labels();
		if ( is_null( $data = $this->data(4) ) ) {
			throw new dns_exception('Unable to parse question - could not determine question type or class');
		}
		$data = unpack( 'ntype/nclass',$data );
		$this->questions[] = array(
			'name'  => implode( '.',$this->labels[$offset] ),
			'type'  => $data['type'],
			'class' => $data['class']
		);
		if ( $this->header['qdcount'] !== count( $this->questions ) ) {
			$this->parse_question();
		}
	}

	private function parse_resource_record() {

	}

	private function get_pointer() {
		$data = $this->data(2,false);
		if ( is_null( $data ) ) {
			return false;
		}
		$info = unpack( 'nseq',$data );
		if ( ( $info['seq'] & bindec('1100000000000000') ) === 0 ) {
			return false;
		}
		$this->offset += 2;
		return ( $info['seq'] & bindec('0011111111111111') );
	}

	private function parse_labels() {
		$labels = array();
		while(true) {
			if ( ord( $this->data(1,false) ) === 0 ) {
				$this->offset++;
				break;
			}
			if ( ( $pointer = $this->get_pointer() ) !== false ) {
				if ( !isset( $this->labels[$pointer] ) ) {
					throw new dns_exception( 'Unable to find labels at pointer: %d',$pointer );
				}
				$labels = array_merge( $labels,$this->labels[$pointer] );
				break;
			}
			$length = $this->data(1);
			if ( is_null( $length ) ) {
				throw new dns_exception('Label length octet not found');
			}
			$length = ord( $length );
			$idx = count( $labels );
			$labels[$idx] = $this->data( $length );
			if ( is_null( $labels[$idx] ) ) {
				throw new dns_exception('Unable to find correct amount of chars based on provided label length');
			}
		}
		return $labels;
	}

}

?>