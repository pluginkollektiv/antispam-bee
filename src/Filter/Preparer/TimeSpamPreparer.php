<?php
/**
 * In order to measure the time it needed to fill out a form and see, if this measurement is reasonable,
 * we need to hook into the form to render an input field, which basically sends the current time, so the
 * TimeSpam()-Check can produce a diff.
 *
 * @package Antispam Bee Preparer
 */

declare(strict_types = 1);

namespace Pluginkollektiv\AntispamBee\Filter\Preparer;

/**
 * Class TimeSpamPreparer
 *
 * @package Pluginkollektiv\AntispamBee\Preparer
 */
class TimeSpamPreparer implements PreparerInterface {

	/**
	 * The arguments needed to render the field.
	 *
	 * @var mixed
	 */
	private $args;

	/**
	 * The action hook.
	 *
	 * @var string
	 */
	private $action_hook;

	/**
	 * TimeSpamPreparer constructor.
	 *
	 * @param string $action_hook The action, in which the method should hook into.
	 */
	public function __construct( string $action_hook ) {
		$this->action_hook = $action_hook;
	}

	/**
	 * Hooks into 'comment_form'.
	 *
	 * @param mixed $args The arguments.
	 *
	 * @return bool.
	 */
	public function register( $args = null ) : bool {

		$this->args = $args;

		add_action(
			$this->action_hook,
			[
				$this,
				'prepare',
			]
		);

		return true;
	}

	/**
	 * Renders the time field for the TimeSpamFilter.
	 *
	 * @return bool
	 */
	public function prepare() : bool {
		echo '<input
            type="hidden"
            name="' . esc_attr( $this->args ) . '"
            value="' . (int) time() . '"
        />';
		return true;
	}
}
