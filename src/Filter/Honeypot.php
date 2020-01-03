<?php
/**
 * The honeypot filter.
 *
 * @package Antispam Bee Filter
 */

declare(strict_types=1);

namespace Pluginkollektiv\AntispamBee\Filter;

use Pluginkollektiv\AntispamBee\Entity\CommentDataTypes;
use Pluginkollektiv\AntispamBee\Entity\DataInterface;
use Pluginkollektiv\AntispamBee\Filter\Preparer\PreparerInterface;
use Pluginkollektiv\AntispamBee\Option\OptionFactory;
use Pluginkollektiv\AntispamBee\Option\OptionInterface;

/**
 * Class Honeypot
 *
 * @package Pluginkollektiv\AntispamBee\Filter
 */
class Honeypot implements SpamFilterInterface {

	/**
	 * This value is used as a key in $_POST to share the information whether the honeypot was populated or not.
	 */
	const DID_RUN_INTO_HONEYPOT_KEY = 'ab_spam__hidden_field';

	/**
	 * If already created, this will contain the OptionInterface.
	 *
	 * @var OptionInterface
	 */
	private $options;

	/**
	 * The factory will produce the option interface.
	 *
	 * @var OptionFactory
	 */
	private $option_factory;

	/**
	 * The honeypot preparer.
	 *
	 * @var PreparerInterface
	 */
	private $preparer;

	/**
	 * What type of data can be checked.
	 *
	 * @var array
	 */
	private $types;

	/**
	 * Honeypot constructor.
	 *
	 * @param OptionFactory     $option_factory The option factory.
	 * @param PreparerInterface $preparer The honeypot preparer.
	 * @param array             $types The allowed types for this check.
	 */
	public function __construct( OptionFactory $option_factory, PreparerInterface $preparer, array $types = [ CommentDataTypes::COMMENT ] ) {
		$this->option_factory = $option_factory;
		$this->preparer       = $preparer;
		$this->types          = $types;
	}

	/**
	 * Evaluates, whether the current data is spam or not.
	 *
	 * @param DataInterface $data The data to filter.
	 *
	 * @return float
	 */
	public function filter( DataInterface $data ) : float {
	    // phpcs:disable WordPress.CSRF.NonceVerification.NoNonceVerification
		if ( isset( $_POST[ self::DID_RUN_INTO_HONEYPOT_KEY ] ) && ! empty( $_POST[ self::DID_RUN_INTO_HONEYPOT_KEY ] ) ) {
			return 1;
		}
        // phpcs:enable WordPress.CSRF.NonceVerification.NoNonceVerification

		return 0;
	}

	/**
	 * Registers the filter.
	 *
	 * @return bool
	 */
	public function register() : bool {
		$did_register = $this->preparer->register( self::DID_RUN_INTO_HONEYPOT_KEY );
		return $did_register;
	}

	/**
	 * Returns the options for the honeypot filter.
	 *
	 * @return OptionInterface
	 */
	public function options() : OptionInterface {
		if ( $this->options ) {
			return $this->options;
		}
		$args          = [
			'name'        => __( 'Honeypot', 'antispam-bee' ),
			'description' => __( 'text.', 'antispam-bee' ),
		];
		$this->options = $this->option_factory->from_args( $args );
		return $this->options;
	}

	/**
	 * Returns the ID for the honeypot filter.
	 *
	 * @return string
	 */
	public function id() : string {
		return 'honeypot';
	}

	/**
	 * Whether or not a specific data can be checked using this filter.
	 *
	 * @param DataInterface $data The Data to be checked.
	 *
	 * @return bool
	 */
	public function can_check_data( DataInterface $data ) : bool {
		$can_check = in_array( $data->type(), $this->types, true );
		return $can_check;
	}
}
