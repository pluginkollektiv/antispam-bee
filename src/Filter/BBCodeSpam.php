<?php
/**
 * The BB Code Spam Filter.
 *
 * BBCode is often a sign for spam. This filter checks, if a data structure contains bbcode.
 *
 * @package Antispam Bee Filter
 */

declare( strict_types = 1 );

namespace Pluginkollektiv\AntispamBee\Filter;

use Pluginkollektiv\AntispamBee\Entity\CommentDataTypes;
use Pluginkollektiv\AntispamBee\Entity\DataInterface;
use Pluginkollektiv\AntispamBee\Option\OptionFactory;
use Pluginkollektiv\AntispamBee\Option\OptionInterface;

/**
 * Class BBCode
 *
 * @package Pluginkollektiv\AntispamBee\Filter
 */
class BBCodeSpam implements SpamFilterInterface {


	/**
	 * If already created, the option interface.
	 *
	 * @var OptionInterface $options
	 */
	private $options;

	/**
	 * Creates the OptionInterface.
	 *
	 * @var OptionFactory $option_factory
	 */
	private $option_factory;

	/**
	 * The types of data, which can be filtered with this filter.
	 *
	 * @var array
	 */
	private $types;

	/**
	 * BBCodeSpam constructor.
	 *
	 * @param OptionFactory $option_factory The option factory.
	 * @param array         $types The allowed data types to be checked.
	 */
	public function __construct( OptionFactory $option_factory, array $types = CommentDataTypes::ALL ) {
		$this->option_factory = $option_factory;
		$this->types          = $types;
	}

	/**
	 * Determine the spam indication.
	 *
	 * @param DataInterface $data The data to filter.
	 *
	 * @return float
	 */
	public function filter( DataInterface $data ) : float {

		return (float) preg_match( '/\[url[=\]].*\[\/url\]/is', $data->text() );
	}

	/**
	 * Nothing to register for this filter.
	 *
	 * @return bool
	 */
	public function register() : bool {
		return true;
	}

	/**
	 * Returns the options for this filter.
	 *
	 * @return OptionInterface
	 */
	public function options() : OptionInterface {
		if ( $this->options ) {
			return $this->options;
		}
		$args          = [
			'name'        => __( 'BBCode', 'antispam-bee' ),
			'description' => __( 'Checks for BBCode in the comment.', 'antispam-bee' ),
		];
		$this->options = $this->option_factory->from_args( $args );
		return $this->options;
	}

	/**
	 * Returns the ID for this filter.
	 *
	 * @return string
	 */
	public function id() : string {
		return 'bbcode_check';
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
