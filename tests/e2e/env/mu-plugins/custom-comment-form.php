<?php
/**
 * Render a comment form that is not built with `comment_form()`.
 *
 * Themes and page builders do this, and it is what the honeypot's output-buffer
 * injection exists for: the `comment_form_field_comment` filter never sees such
 * a form. Visiting `/?asb_custom_comment_form=1` prints a bare page with one,
 * posting to post #1. Hooked after the plugin starts its output buffer.
 *
 * @package AntispamBee
 */

add_action(
	'template_redirect',
	function (): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['asb_custom_comment_form'] ) ) {
			return;
		}
		?>
<!DOCTYPE html>
<html>
<head><title>Custom comment form</title></head>
<body>
<form action="<?php echo esc_url( site_url( '/wp-comments-post.php' ) ); ?>" method="post" id="custom-comment-form">
	<label for="custom-comment">Comment</label>
	<textarea name="comment" id="custom-comment" rows="4"></textarea>
	<label for="custom-author">Name</label>
	<input type="text" name="author" id="custom-author" />
	<label for="custom-email">Email</label>
	<input type="email" name="email" id="custom-email" />
	<input type="hidden" name="comment_post_ID" value="1" />
	<button type="submit" id="custom-submit">Send</button>
</form>
</body>
</html>
		<?php
		exit;
	},
	20
);
