<?php

namespace AntispamBee\Interfaces;

interface Controllable {
	public static function get_label();

	public static function get_description();

	/**
	 * First thoughts on how a rule can specify what kind of advanced option it is. If `type` is no callable,
	 * ASB takes care of rendering the option. If it is a callable, the rule has to take care of rendering, loading
	 * the data, saving.
	 * [
	 *   [
	 *     'type' => 'textarea|radio|checkbox|input|select|callable',
	 *       'input_type' => 'email|password|number...',
	 *       'option_name' => 'asb_deny_langcodes',
	 *       'options' => [ 'Option A', 'Option B' ],
	 *       'multiple' => true,
	 *       'placeholder' => 'My placeholder text',
	 *       'default' => 'Default value',
	 *       'sanitize' => callable,
	 *       'persist' => callable,
	 *       'load' => callable,
	 *   ]
	 * ]
	 * @return mixed
	 */
	public static function get_options();

	public static function is_active( $type );
}
