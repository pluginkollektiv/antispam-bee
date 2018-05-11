<?php

use Behat\Behat\Context\Context;
use PaulGibbs\WordpressBehatExtension\Context\RawWordpressContext;
use function PaulGibbs\WordpressBehatExtension\Util\buildCLIArgs;

class CommentContext extends RawWordpressContext implements Context {

	/**
	 * @Given a comment exists with :arg1 by :arg2 with email :arg3, URL :arg4, IP :arg5, date :arg6 and status :arg7
	 */
	public function aCommentExistsWithByWithEmailIpAndStatus( $text, $name, $email, $url, $ip, $date, $status ) {

		$wpcli_args = buildCLIArgs(
			[
				'comment_content','comment_author','comment_author_email',
				'comment_author_url','comment_author_IP','comment_approved',
				'comment_date',
			],[
			'comment_content' => $text,
			'comment_author' => $name,
			'comment_author_email' => $email,
			'comment_author_url' => $url,
			'comment_author_IP' => $ip,
			'comment_date' => $date,
			'comment_approved' => $status,
		]);

		array_unshift($wpcli_args, '--porcelain');
		$comment_id = (int) $this->getDriver()->wpcli('comment', 'create', $wpcli_args)['stdout'];

		if( ! $comment_id ) {
			throw new RuntimeException('Could not create the comment');
		}
	}
}