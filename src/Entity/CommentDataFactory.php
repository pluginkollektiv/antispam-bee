<?php
/**
 * This factory produces the CommentData.
 *
 * @package Antispam Bee Entity
 */
declare(strict_types = 1);

namespace Pluginkollektiv\AntispamBee\Entity;

/**
 * Class CommentDataFactory
 *
 * @package Pluginkollektiv\AntispamBee\Entity
 */
class CommentDataFactory
{

    /**
     * Returns the comment data based on the data array. See CommentData() for what valid array data
     * would be. Basically, the $comment returned by WordPress during the 'pre_comment_approved' hook
     * is sufficient.
     *
     * @param array $data The comment data.
     *
     * @return CommentData
     */
    public function get( array $data ) : CommentData
    {

        if (empty($data['comment_type']) ) {
            $data['comment_type'] = 'comment';
        }
        return new CommentData($data);
    }
}
