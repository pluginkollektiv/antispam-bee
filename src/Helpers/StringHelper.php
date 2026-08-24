<?php
/**
 * String helper.
 *
 * @package AntispamBee\Helpers
 */

namespace AntispamBee\Helpers;

/**
 * Helper for string operations.
 */
class StringHelper {

	/**
	 * Whether a plugin version string marks a pre-release.
	 *
	 * A version is a pre-release if the measured number is followed by a
	 * semantic versioning pre-release suffix, e.g. `3.0.0-RC.1` or
	 * `3.0.0-beta.2`. The stable `3.0.0` has no such suffix.
	 *
	 * @param string $version The version string.
	 *
	 * @return bool Whether the version is a pre-release.
	 */
	public static function is_pre_release( string $version ): bool {
		return 1 === preg_match( '/^\d+(?:\.\d+){0,2}-[0-9A-Za-z-]+(?:\.[0-9A-Za-z-]+)*$/', $version );
	}
}
