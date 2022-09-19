<?php

namespace AntispamBee\Admin\Fields;

class Composable {

	/**
	 * @param $data
	 *
	 * @return void
	 */
	public static function fromString( $data ) {
		$parts = parse_string($data['blueprint']); // placeholders -> werden ersetzt -> string -> werden zu labels

		foreach ( $parts as $part ) {
			$html =
		}
	}
}
