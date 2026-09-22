<?php
/**
 * Minimal stand-in for the WordPress error object, enough for unit tests to
 * construct one and read back its code, message and data.
 */

class WP_Error {

	protected $code;
	protected $message;
	protected $data;

	public function __construct( $code = '', $message = '', $data = '' ) {
		$this->code    = $code;
		$this->message = $message;
		$this->data    = $data;
	}

	public function get_error_code() {
		return $this->code;
	}

	public function get_error_message() {
		return $this->message;
	}

	public function get_error_data() {
		return $this->data;
	}
}
