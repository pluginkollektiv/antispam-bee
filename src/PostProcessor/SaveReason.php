<?php
declare( strict_types = 1 );

namespace Pluginkollektiv\AntispamBee\PostProcessor;

use Pluginkollektiv\AntispamBee\Entity\DataInterface;
use Pluginkollektiv\AntispamBee\Option\OptionFactory;
use Pluginkollektiv\AntispamBee\Option\OptionInterface;
use Pluginkollektiv\AntispamBee\Repository\ReasonsRepository;

class SaveReason implements PostProcessorInterface
{

    private $options;

    /**
     * @var ReasonsRepository|null
     */
    private $reason;
    private $option_factory;

    public function __construct( OptionFactory $option_factory )
    {
        $this->option_factory = $option_factory;
    }

    public function execute( ReasonsRepository $reason, DataInterface $data ) : bool
    {
        $this->reason = $reason;
        add_action(
            'comment_post',
            [
            $this,
            'add_spam_reason_to_comment',
            ]
        );
    }

    public function add_spam_reason_to_comment( $comment_id ) : bool
    {

        if (! is_a($this->reason, ReasonsRepository::class) ) {
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

    public function id() : string
    {
        return 'savereason';
    }

    public function register() : bool
    {
        // ToDo: Register and render the reason column in the comments table.
        return false;
    }

    public function options() : OptionInterface
    {

        if ($this->options ) {
            return $this->options;
        }
        $args          = [
        'name'        => __('Save Spam Reason', 'antispam-bee'),
        'description' => __('Save the reason, why a comment was marked as spam.', 'antispam-bee'),
        ];
        $this->options = $this->option_factory->from_args($args);
        return $this->options;
    }

}
