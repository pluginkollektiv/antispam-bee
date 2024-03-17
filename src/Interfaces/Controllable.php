<?php
/**
 * Controllables interface.
 *
 * @package AntispamBee\Interfaces
 */

namespace AntispamBee\Interfaces;

/**
 * Common interface for controllable elements.
 * This can be options, processors or rules.
 */
interface Controllable {

	/**
	 * Get element name.
	 *
	 * @return string
	 */
	public static function get_name();

	/**
	 * Get element label (optional).
	 *
	 * @return string|null
	 */
	public static function get_label();

	/**
	 * Get element description (optional).
	 *
	 * @return string|null
	 */
	public static function get_description();

	/**
	 * First thoughts on how a rule can specify what kind of advanced option it is. If `type` is no callable,
	 * ASB takes care of rendering the option. If it is a callable, the rule has to take care of rendering, loading
	 * the data, saving.
	 * [
	 *   [
	 *     'type' => 'textarea|radio|checkbox|input|select|callable',
	 *       'input_type' => 'email|password|number...',
	 *     *   'label' => 'asb_deny_langcodes',
	 *       'option_name' => 'asb_deny_langcodes',
	 *       'options' => [ [ 'value' => 1, 'label' => 'Option 1' ], [ 'value' => 2, 'label' => 'Option 2' ] ],
	 *       'multiple' => true,
	 *       'placeholder' => 'My placeholder text',
	 *       'default' => 'Default value',
	 *       'sanitize' => callable,
	 *       'persist' => callable,
	 *       'load' => callable,
	 *   ]
	 * ]
	 *
	 * @return mixed
	 */
	public static function get_options();


	/**
	 * Returns activation state for this element.
	 *
	 * @param string $type One of the types defined in AntispamBee\Helpers\ItemTypeHelper::get_types().
	 *
	 * @return mixed|null
	 */
	public static function is_active( $type );

	/**
	 * Only print custom options?
	 * If enabled, the default options will not be generated.
	 *
	 * @return bool
	 */
	public static function only_print_custom_options();

	/**
	 * Get a list of supported types.
	 *
	 * @return string[]
	 */
	public static function get_supported_types();

	/**
	 * Get type of the controllable.
	 *
	 * @return string
	 */
	public static function get_type();

	/**
	 * Get controllable slug.
	 *
	 * @return string
	 */
	public static function get_slug();

	/**
	 * Get option name.
	 * This will typically add type and slug prefixes to the short name.
	 *
	 * @param string $name Name suffix.
	 * @return string Corresponding option name
	 */
	public static function get_option_name( $name );
}
