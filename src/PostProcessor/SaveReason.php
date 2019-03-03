<?php
declare( strict_types = 1 );

namespace Pluginkollektiv\AntispamBee\PostProcessor;

use Pluginkollektiv\AntispamBee\Entity\DataInterface;
use Pluginkollektiv\AntispamBee\Option\OptionFactory;
use Pluginkollektiv\AntispamBee\Option\OptionInterface;

class SaveReason implements PostProcessorInterface {

	private $options;
	private $reason;
	private $option_factory;

	public function __construct( OptionFactory $option_factory ) {
		$this->option_factory = $option_factory;
	}

	public function execute( string $reason, DataInterface $data ) : bool {
		$this->reason = $reason;
		add_action(
			'comment_post',
			[
				$this,
				'add_spam_reason_to_comment',
			]
		);
	}

	public function add_spam_reason_to_comment( $comment_id ) : bool {

		return false !== add_comment_meta(
			$comment_id,
			'antispam_bee_reason',
			$this->reason
		);
	}

	public function id() : string {
		return 'savereason';
	}

	public function register() {
		// ToDo: Register and render the reason column in the comments table.
		return false;
	}

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
