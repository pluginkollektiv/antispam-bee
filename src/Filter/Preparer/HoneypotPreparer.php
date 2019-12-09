<?php
declare(strict_types=1);

namespace Pluginkollektiv\AntispamBee\Filter\Preparer;

class HoneypotPreparer implements PreparerInterface
{

    private $did_run_into_honeypot_key = '';

    /**
     * The current post ID.
     *
     * @var int
     */
    private $current_post_id = 0;



    /**
     * The salt.
     *
     * @var string
     */
    private $salt;

    /**
     *
     * @var bool
     */
    private $comments_shown_when_not_single = false;

    public function __construct()
    {
        $salt        = defined( 'NONCE_SALT' ) ? NONCE_SALT : ABSPATH;
        $this->salt  = substr( sha1( $salt ), 0, 10 );
    }

    public function register($args = null): bool
    {
        if (! is_string(($args))) {
            return false;
        }
        $this->did_run_into_honeypot_key = $args;
        $success = add_action(
            'wp',
            function() {
                if (0 === $this->current_post_id) {
                    $this->current_post_id = (int) get_the_ID();
                }
            }
        );
        $success = $success && add_filter(
            'init',
                [
                    $this,
                    'precheck_incoming_request',
                ]
        );
        $success = $success && add_filter(
            'comment_form_fields',
            [
                $this,
                'prepare_comment_fields',
            ]
        );

        return $success;
    }

    public function precheck_incoming_request() {
        if ( is_feed() || is_trackback() || empty( $_POST ) || ! isset($_POST['comment_post_ID']) ) {
            return;
        }

        $request_uri  = wp_parse_url(sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])));
        $request_path = isset($request_uri['path']) ? (string) $request_uri['path'] : '';

        if ( strpos( $request_path, 'wp-comments-post.php' ) === false ) {
            return;
        }

        $post_id      = (int) wp_unslash($_POST['comment_post_ID']);
        $hidden_field = isset($_POST['comment']) ? $_POST['comment'] : '';
        $plugin_field = isset($_POST[$this->get_secret_name_for_post($post_id)]) ? $_POST[$this->get_secret_name_for_post($post_id)] : '';

        if ( empty( $hidden_field ) && ! empty( $plugin_field ) ) {
            $_POST['comment'] = $plugin_field;
            unset( $_POST[ self::get_secret_name_for_post( $post_id ) ] );
        } else {
            $_POST[$this->did_run_into_honeypot_key] = 1;
        }
    }

    public function prepare_comment_fields($fields) : array {
        if (! isset( $fields['comment'])) {
            return $fields;
        }

        $fields['comment'] = $this->add_honeypot_to_fields((string) $fields['comment']);

        return (array) $fields;
    }

    private function add_honeypot_to_fields(string $data) : string
    {
        if ( empty( $data ) ) {
            return '';
        }
        if ( ! preg_match( '#<textarea.+?name=["\']comment["\']#s', $data ) ) {
            return $data;
        }
        return preg_replace_callback(
            '/(?P<all>                                                              (?# match the whole textarea tag )
				<textarea                                                           (?# the opening of the textarea and some optional attributes )
				(                                                                   (?# match a id attribute followed by some optional ones and the name attribute )
					(?P<before1>[^>]*)
					(?P<id1>id=["\'](?P<id_value1>[^>"\']*)["\'])
					(?P<between1>[^>]*)
					name=["\']comment["\']
					|                                                               (?# match same as before, but with the name attribute before the id attribute )
					(?P<before2>[^>]*)
					name=["\']comment["\']
					(?P<between2>[^>]*)
					(?P<id2>id=["\'](?P<id_value2>[^>"\']*)["\'])
					|                                                               (?# match same as before, but with no id attribute )
					(?P<before3>[^>]*)
					name=["\']comment["\']
					(?P<between3>[^>]*)
				)
				(?P<after>[^>]*)                                                    (?# match any additional optional attributes )
				>                                                                   (?# the closing of the textarea opening tag )
				(?s)(?P<content>.*?)                                                (?# any textarea content )
				<\/textarea>                                                        (?# the closing textarea tag )
			)/x',
            function(array $matches) : string {
                $output = '<textarea autocomplete="new-password" ' . $matches['before1'] . $matches['before2'] . $matches['before3'];
                $id_script = '';
                if ( ! empty( $matches['id1'] ) || ! empty( $matches['id2'] ) ) {
                    $output   .= 'id="' . $this->get_secret_id_for_post( $this->current_post_id ) . '" ';
                    $id_script = '<script data-noptimize type="text/javascript">document.getElementById("comment").setAttribute( "id", "a' . substr( esc_js( md5( (string) time() ) ), 0, 31 ) . '" );document.getElementById("' . esc_js( $this->get_secret_id_for_post( $this->current_post_id ) ) . '").setAttribute( "id", "comment" );</script>';
                }
                $output .= ' name="' . esc_attr( $this->get_secret_name_for_post( $this->current_post_id ) ) . '" ';
                $output .= $matches['between1'] . $matches['between2'] . $matches['between3'];
                $output .= $matches['after'] . '>';
                $output .= $matches['content'];
                $output .= '</textarea><textarea id="comment" aria-hidden="true" name="comment" autocomplete="new-password" style="padding:0;clip:rect(1px, 1px, 1px, 1px);position:absolute !important;white-space:nowrap;height:1px;width:1px;overflow:hidden;" tabindex="-1"></textarea>';
                $output .= $id_script;
                return $output;
            },
            $data,
            -1
        );
    }

    private function get_secret_id_for_post(int $post_id) : string
    {
        if ( $this->comments_shown_when_not_single ) {
            $secret = substr( sha1( md5( 'comment-id' . $this->salt ) ), 0, 10 );
        } else {
            $secret = substr( sha1( md5( 'comment-id' . $this->salt . (int) $post_id ) ), 0, 10 );
        }

        $secret = $this->ensure_secret_starts_with_letter( $secret );

        /**
         * Filters the secret for a post, which is used in the textarea id attribute.
         *
         * @param string $secret The secret.
         * @param int    $post_id The post ID.
         * @param bool   $comments_shown_when_not_single Whether the comment form is used outside of the single post view or not.
         */
        return (string) apply_filters(
            'ab_get_secret_id_for_post',
            $secret,
            (int) $post_id,
            (bool) $this->comments_shown_when_not_single
        );
    }

    private function ensure_secret_starts_with_letter( $secret ) {

        $first_char = substr( $secret, 0, 1 );
        if ( is_numeric( $first_char ) ) {
            return chr( $first_char + 97 ) . substr( $secret, 1 );
        } else {
            return $secret;
        }
    }

    private function get_secret_name_for_post(int $post_id) : string
    {
        if ( $this->comments_shown_when_not_single ) {
            $secret = substr( sha1( md5( 'comment-id' . $this->salt ) ), 0, 10 );
        } else {
            $secret = substr( sha1( md5( 'comment-id' . $this->salt . (int) $post_id ) ), 0, 10 );
        }

        $secret = $this->ensure_secret_starts_with_letter( $secret );

        /**
         * Filters the secret for a post, which is used in the textarea name attribute.
         *
         * @param string $secret The secret.
         * @param int    $post_id The post ID.
         * @param bool   $comments_shown_when_not_single Whether the comment form is used outside of the single post view or not.
         */
        return apply_filters(
            'ab_get_secret_name_for_post',
            $secret,
            (int) $post_id,
            (bool) $this->comments_shown_when_not_single
        );
    }

    public function prepare(): bool
    {
        return true;
    }
}