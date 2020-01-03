<?php
/**
 * If the ping comes from the same blog, we let it pass.
 *
 * @package Antispam Bee Filter
 */

declare(strict_types=1);

namespace Pluginkollektiv\AntispamBee\Filter;

use Pluginkollektiv\AntispamBee\Entity\CommentDataTypes;
use Pluginkollektiv\AntispamBee\Entity\DataInterface;
use Pluginkollektiv\AntispamBee\Option\OptionInterface;

/**
 * Class Selfping
 *
 * @package Pluginkollektiv\AntispamBee\Filter
 */
class Selfping implements NoSpamFilterInterface {


	/**
	 * When already created, contains the Options for this filter.
	 *
	 * @var OptionInterface $options
	 */
	private $options;

	/**
	 * The types of data, which can be filtered with this filter.
	 *
	 * @var array
	 */
	private $types;

	/**
	 * Selfping constructor.
	 *
	 * @param OptionInterface $options The options for this filter.
	 * @param array           $types The allowed types for this filter.
	 */
	public function __construct( OptionInterface $options, array $types = [ CommentDataTypes::PING ] ) {
		$this->options = $options;
		$this->types   = $types;
	}

	/**
	 * Checks the data.
	 *
	 * @param DataInterface $data The data to filter.
	 *
	 * @return float
	 */
	public function filter( DataInterface $data ) : float {
		if ( 0 !== strpos( $data->website(), home_url() ) ) {
			return 0;
		}
		$original_post_id = (int) url_to_postid( $data->website() );
		if ( ! $original_post_id ) {
			return 0;
		}
		$post = get_post( $original_post_id );
		if ( ! $post ) {
			return 0;
		}
		$urls        = wp_extract_urls( $post->post_content );
		$url_to_find = get_permalink( $data->post() );
		if ( ! $url_to_find ) {
			return 0;
		}
		foreach ( $urls as $url ) {
			if ( strpos( $url, $url_to_find ) === 0 ) {
				return 1;
			}
		}
		return 0;
	}

	/**
	 * Registers this filter.
	 *
	 * @return bool
	 */
	public function register() : bool {
		return true;
	}

	/**
	 * The options for this filter.
	 *
	 * @return OptionInterface
	 */
	public function options() : OptionInterface {
		return $this->options;
	}

	/**
	 * The ID of this filter.
	 *
	 * @return string
	 */
	public function id() : string {
		return 'selfping';
	}

	/**
	 * Returns whether a data object can be checked.
	 *
	 * @param DataInterface $data The data to be checked.
	 *
	 * @return bool
	 */
	public function can_check_data( DataInterface $data ) : bool {
		return in_array( $data->type(), $this->types, true );
	}
}
