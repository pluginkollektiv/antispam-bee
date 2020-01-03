<?php
/**
 * How long did it take to send a form. If the time span is very short, it is quite likely, the form
 * was send by a bot. This is what this spam filter tries to detect.
 *
 * @package Antispam Bee Filter
 */

declare(strict_types = 1);

namespace Pluginkollektiv\AntispamBee\Filter;

use Pluginkollektiv\AntispamBee\Entity\CommentDataTypes;
use Pluginkollektiv\AntispamBee\Entity\DataInterface;
use Pluginkollektiv\AntispamBee\Option\OptionFactory;
use Pluginkollektiv\AntispamBee\Option\OptionInterface;
use Pluginkollektiv\AntispamBee\Filter\Preparer\PreparerInterface;

/**
 * Class TimeSpam
 *
 * @package Pluginkollektiv\AntispamBee\Filter
 */
class TimeSpam implements SpamFilterInterface {


	/**
	 * A filter hook, so you can change the allowed time limit.
	 */
	const FILTER_INTERVAL = 'ab_action_time_limit';

	/**
	 * This constant is used to read from the $_POST input the time, when the form was rendered.
	 */
	private const POST_KEY = 'time';

	/**
	 * When already created, the OptionInterface.
	 *
	 * @var OptionInterface $options
	 */
	private $options;

	/**
	 * Creates the OptionInterface for this filter.
	 *
	 * @var OptionFactory
	 */
	private $option_factory;

	/**
	 * This filter needs to render an input field in the form to determine how long it took to send the form. The
	 * preparer takes care of it.
	 *
	 * @var PreparerInterface $preparer
	 */
	private $preparer;

	/**
	 * The types of data, which can be filtered with this filter.
	 *
	 * @var array
	 */
	private $types;

	/**
	 * TimeSpam constructor.
	 *
	 * @param OptionFactory     $option_factory The option factory.
	 * @param PreparerInterface $preparer The preparer.
	 * @param array             $types The allowed types of data.
	 */
	public function __construct( OptionFactory $option_factory, PreparerInterface $preparer, array $types = [ CommentDataTypes::COMMENT ] ) {
		$this->option_factory = $option_factory;
		$this->preparer       = $preparer;
		$this->types          = $types;
	}

	/**
	 * Calculates the time diff between form rendering and form sending to evaluate the spam value.
	 *
	 * @param DataInterface $data The data to be checked.
	 *
	 * @return float
	 */
	public function filter( DataInterface $data ) : float {

        // phpcs:disable WordPress.CSRF.NonceVerification.NoNonceVerification
		$init_time = isset( $_POST[ self::POST_KEY ] ) ? absint( wp_unslash( $_POST[ self::POST_KEY ] ) ) : 0;
        // phpcs:enable WordPress.CSRF.NonceVerification.NoNonceVerification

		if ( 0 === $init_time ) {
			return (float) 1;
		}

		if ( time() - $init_time < apply_filters( self::FILTER_INTERVAL, 5 ) ) {
			return (float) 1;
		}

		return (float) 0;
	}

	/**
	 * Registers the preparer and gives the POST_KEY, so the correct post key is used.
	 *
	 * @return bool
	 */
	public function register() : bool {
		return $this->preparer->register( self::POST_KEY );
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
			'name'        => __( 'Time Spam', 'antispam-bee' ),
			'description' => __( 'text.', 'antispam-bee' ),
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
		return 'time_check';
	}

	/**
	 * Returns whether a data object can be cheked.
	 *
	 * @param DataInterface $data The data to be checked.
	 *
	 * @return bool
	 */
	public function can_check_data( DataInterface $data ) : bool {
		return in_array( $data->type(), $this->types, true );
	}
}
