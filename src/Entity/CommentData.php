<?php
/**
 * This is the data entity, Antispam Bee will check to find out whether a comment is spam or not.
 *
 * @package Antispam Bee Entity
 */

declare(strict_types = 1);

namespace Pluginkollektiv\AntispamBee\Entity;

use Pluginkollektiv\AntispamBee\Exceptions\Runtime;

/**
 * Class CommentData
 *
 * @package Pluginkollektiv\AntispamBee\Entity
 */
class CommentData implements DataInterface
{

    /**
     * The data.
     *
     * @var array $data
     */
    private $data;

    /**
     * CommentData constructor.
     *
     * @throws Runtime If no valid data is submitted.
     *
     * @param array $data The raw comment data.
     */
    public function __construct( array $data )
    {

        if (! $this->validate_data($data) ) {
            throw new Runtime('Comment data is wrong.');
        }
        $this->data = $data;
    }

    /**
     * Validates the data.
     *
     * @param array $data The data array to validate.
     *
     * @return bool
     */
    private function validate_data( array $data ) : bool
    {

        $keys = [

        'comment_author'       => 'is_string',
        'comment_author_email' => 'is_email',
        'comment_author_url'   => 'is_string',
        'comment_content'      => 'is_string',
        'user_ID'              => 'is_numeric',
        'comment_author_IP'    => 'is_string',
        'comment_post_ID'      => 'is_int',
        'comment_type'         => function ( $type ) : bool {
            return in_array((string) $type, CommentDataTypes::ALL, true);
        },
        ];

        foreach ( $keys as $key => $validator ) {
            if (! isset($data[ $key ]) || ! $validator($data[ $key ]) ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns the text.
     *
     * @return string
     */
    public function text() : string
    {
        return $this->data['comment_content'];
    }

    /**
     * Returns the email address of the commenter.
     *
     * @return string
     */
    public function email() : string
    {
        return $this->data['comment_author_email'];
    }

    /**
     * Returns the IP of the commenter.
     *
     * @return string
     */
    public function ip() : string
    {
        return $this->data['comment_author_IP'];
    }

    /**
     * Returns the name of the commenter.
     *
     * @return string
     */
    public function author() : string
    {
        return $this->data['comment_author'];
    }

    /**
     * Returns the website of the commenter.
     *
     * @return string
     */
    public function website() : string
    {
        return $this->data['comment_author_url'];
    }

    /**
     * Returns the user ID of the commenter.
     *
     * @return int
     */
    public function user_id() : int
    {
        return (int) $this->data['user_id'];
    }

    /**
     * Returns 'comment', 'pingback' or 'trackback'. We have some checks, which can only run
     * on certain types, for example the 'title_is_name'-check, which makes only sense for trackbacks.
     *
     * We can use this method, to determine, whether it's a comment or a trackback or a pingback.
     *
     * @return string
     */
    public function type() : string
    {
        return $this->data['comment_type'];
    }

    /**
     * Returns the associated post ID of the comment.
     *
     * @return int
     */
    public function post() : int
    {
        return (int) $this->data['comment_post_ID'];
    }
}
