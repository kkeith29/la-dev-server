<?php

namespace lb_dev\classes;

include path::internal('third-party/phpmailer/PHPMailerAutoload.php');

class email {

	const type_line = 1;
	const type_list = 2;

	private $data = array();

	public function add( $data ) {
		$this->data[] = array(
			'type' => self::type_line,
			'line' => $data
		);
		return $this;
	}

	public function add_list( $data ) {
		$this->data[] = array(
			'type' => self::type_list,
			'list' => $data
		);
		return $this;
	}

	public function send( $subject,$to,$from ) {
		if ( count( $this->data ) === 0 ) {
			return;
		}
		$message = '';
		foreach( $this->data as $data ) {
			switch( $data['type'] ) {
				case self::type_line:
					$message .= "<p>{$data['line']}</p>";
				break;
				case self::type_list:
					$message .= '<ul>';
					foreach( $data['list'] as $text ) {
						$message .= "<li>{$text}</li>";
					}
					$message .= '</ul>';
				break;
			}
		}
		$mail = new \PHPMailer();
		$mail->setFrom( 'no-reply@lifeboatcreative.com',$from );
		$mail->addAddress( $to );
		$mail->Subject = $subject;
		$mail->msgHTML( $message );
		return $mail->send();
	}

}

?>