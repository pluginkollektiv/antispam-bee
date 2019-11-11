<?php
declare(strict_types=1);

namespace Pluginkollektiv\AntispamBee\Filter;

use Pluginkollektiv\AntispamBee\Entity\CommentDataTypes;
use Pluginkollektiv\AntispamBee\Entity\DataInterface;
use Pluginkollektiv\AntispamBee\Option\NullOption;
use Pluginkollektiv\AntispamBee\Option\OptionFactory;
use Pluginkollektiv\AntispamBee\Option\OptionInterface;

class Selfping implements NoSpamFilterInterface
{

    /**
     * When already created, contains the Options for this filter.
     *
     * @var OptionInterface $options
     */
    private $options;

    /**
     * Creates the options for this filter.
     *
     * @var OptionFactory
     */
    private $option_factory;

    /**
     * The types of data, which can be filtered with this filter.
     *
     * @var array
     */
    private $types;

    /**
     * ValidGravatar constructor.
     *
     * @param OptionFactory $option_factory
     */
    public function __construct( OptionInterface $options, array $types = [CommentDataTypes::PING] )
    {
        $this->options = $options;
        $this->types = $types;
    }

    public function filter(DataInterface $data): float
    {
        if (0 !== strpos($data->website(), home_url()) ) {
            return 0;
        }
        $original_post_id = (int) url_to_postid($data->website());
        if (! $original_post_id ) {
            return 0;
        }
        $post = get_post($original_post_id);
        if (! $post ) {
            return 0;
        }
        $urls        = wp_extract_urls($post->post_content);
        $url_to_find = get_permalink($data->post());
        if (! $url_to_find ) {
            return 0;
        }
        foreach ( $urls as $url ) {
            if (strpos($url, $url_to_find) === 0 ) {
                return 1;
            }
        }
        return 0;
    }

    public function register(): bool
    {
        return true;
    }

    public function options(): OptionInterface
    {
        return $this->options;
    }

    public function id(): string
    {
        return 'selfping';
    }

    public function can_check_data(DataInterface $data): bool
    {
        return in_array($data->type(), $this->types, true);
    }
}