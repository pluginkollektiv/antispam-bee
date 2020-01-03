<?php
/**
 * The SaveReason post processor saves the reason, why a comment was marked as spam in the comments meta data.
 *
 * @package Antispam Bee PostProcessor
 */

declare( strict_types = 1 );

namespace Pluginkollektiv\AntispamBee\PostProcessor;

use Pluginkollektiv\AntispamBee\Entity\DataInterface;
use Pluginkollektiv\AntispamBee\Option\OptionFactory;
use Pluginkollektiv\AntispamBee\Option\OptionInterface;
use Pluginkollektiv\AntispamBee\Repository\ReasonsRepository;

/**
 * Class SaveReason
 *
 * @package Pluginkollektiv\AntispamBee\PostProcessor
 */
class SaveReason implements PostProcessorInterface {

	/**
	 * The options.
	 *
	 * @var OptionInterface
	 */
	private $options;

	/**
	 * The reasons repository.
	 *
	 * @var ReasonsRepository|null
	 */
	private $reason;

	/**
	 * The option factory.
	 *
	 * @var OptionFactory
	 */
	private $option_factory;

	/**
	 * SaveReason constructor.
	 *
	 * @param OptionFactory $option_factory The option factory.
	 */
	public function __construct( OptionFactory $option_factory ) {
		$this->option_factory = $option_factory;
	}

	/**
	 * Executds the save reason post processor.
	 *
	 * @param ReasonsRepository $reason The reasons repository.
	 * @param DataInterface     $data The current data.
	 *
	 * @return bool
	 */
	public function execute( ReasonsRepository $reason, DataInterface $data ) : bool {
		$this->reason = $reason;
		return (bool) add_action(
			'comment_post',
			[
				$this,
				'add_spam_reason_to_comment',
			]
		);
	}

	/**
	 * Adds the reasons as meta data to the comment.
	 *
	 * @param int $comment_id The ID of the comment.
	 *
	 * @return bool
	 */
	public function add_spam_reason_to_comment( $comment_id ) : bool {

		if ( ! is_a( $this->reason, ReasonsRepository::class ) ) {
			return false;
		}

		/**
		 * ToDo: This is a different data structure than in ASB2. If kept, this should be handled in
		 * the Spam Reason view. Another possibility would be to just save the highest reason, but I
		 * think, there can be quite some advantages to save all reasons further down the road.
		 */
		return false !== add_comment_meta(
			$comment_id,
			'antispam_bee_reason',
			$this->reason->all()
		);
	}

	/**
	 * The ID of the save reason post processor.
	 *
	 * @return string
	 */
	public function id() : string {
		return 'savereason';
	}

	/**
	 * Registers the post processor.
	 *
	 * @return bool
	 */
	public function register() : bool {
		// ToDo: Register and render the reason column in the comments table.
		return false;
	}

	/**
	 * Return the options for the save reason processor.
	 *
	 * @return OptionInterface
	 */
	public function options() : OptionInterface {

		if ( $this->options ) {
			return $this->options;
		}
		$args          = [
			'name'        => __( 'Save Spam Reason', 'antispam-bee' ),
			'description' => __( 'Save the reason, why a comment was marked as spam.', 'antispam-bee' ),
		];
		$this->options = $this->option_factory->from_args( $args );
		return $this->options;
	}

}
