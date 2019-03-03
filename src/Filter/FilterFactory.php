<?php
/**
 * This factory builds the core filter of Antispam Bee.
 *
 * @package Antispam Bee Filter
 */

declare(strict_types=1);

namespace Pluginkollektiv\AntispamBee\Filter;

use Pluginkollektiv\AntispamBee\Option\OptionFactory;
use Pluginkollektiv\AntispamBee\Filter\Preparer\TimeSpamPreparer;
use Pluginkollektiv\AntispamBee\Helper\IP;

/**
 * Class FilterFactory
 *
 * @package Pluginkollektiv\AntispamBee\Filter
 */
class FilterFactory {

	/**
	 * The IP object.
	 *
	 * @var IP
	 */
	private $ip;

	/**
	 * The database.
	 *
	 * @var \wpdb
	 */
	private $wpdb;

	/**
	 * The option factory.
	 *
	 * @var OptionFactory
	 */
	private $option_factory;

	/**
	 * FilterFactory constructor.
	 *
	 * @param IP            $ip
	 * @param \wpdb         $wpdb
	 * @param OptionFactory $option_factory
	 */
	public function __construct( IP $ip, \wpdb $wpdb, OptionFactory $option_factory ) {
		$this->ip             = $ip;
		$this->wpdb           = $wpdb;
		$this->option_factory = $option_factory;
	}

	/**
	 * Constructs a filter based on its id.
	 *
	 * @param string $id The ID.
	 *
	 * @return FilterInterface
	 */
	public function from_id( string $id ) : FilterInterface {

		switch ( $id ) {
			case 'bbcode_check':
				$filter = new BBCodeSpam( $this->option_factory );
				break;
			case 'country_code':
				$filter = new CountrySpam( $this->option_factory, $this->ip );
				break;
			case 'spam_ip':
				$filter = new DbSpam( $this->option_factory, $this->wpdb );
				break;
			case 'time_check':
				$preparer = new TimeSpamPreparer( 'comment_form' );
				$filter   = new TimeSpam( $this->option_factory, $preparer );
				break;
			case 'gravatar_check':
				$filter = new ValidGravatar( $this->option_factory );
				break;
			default:
				$filter = new NullFilter( $this->option_factory->null() );
		}

		return $filter;
	}
}
