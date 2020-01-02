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

    public function fromComment(\WP_Comment $comment) : CommentData
    {
        $data = [
            'comment_author' => $comment->comment_author,
            'comment_author_email' => $comment->comment_author_email,
            'comment_author_url' => $comment->comment_author_url,
            'comment_content' => $comment->comment_content,
            'user_ID' => (int) $comment->user_id,
            'comment_author_IP' => $comment->comment_author_IP,
            'comment_post_ID' => (int) $comment->comment_post_ID,
            'comment_type' => $comment->comment_type,
        ];
        return $this->get($data);
    }
}
