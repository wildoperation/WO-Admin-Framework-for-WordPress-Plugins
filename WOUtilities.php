<?php
namespace WOAdminFramework;

/**
 * General utility class for use by this framework and plugins.
 */
class WOUtilities {

	/**
	 * Determine if $value should be treated as a boolean true or false.
	 *
	 * @param mixed $value The value to check.
	 *
	 * @return bool
	 */
	public static function truthy( $value ) {
		if ( is_bool( $value ) ) {
			return $value;
		}

		if ( is_int( $value ) ) {
			return $value > 0 ? true : false;
		}

		if ( is_string( $value ) ) {
			return in_array( strtolower( $value ), array( 'yes', 'y', 'true', '1' ) );
		}

		return false;
	}

	/**
	 * Sanitize an array of ints from the $_REQUEST variable.
	 *
	 * @param string $key The key to sanitize from $_REQUEST.
	 *
	 * @return array|null
	 */
	public static function sanitize_request_ints( $key ) {
		if ( ! isset( $_REQUEST ) || empty( $_REQUEST ) || ! isset( $_REQUEST[ $key ] ) || ! $_REQUEST[ $key ] ) {
			return null;
		}

		return self::sanitize_int_array( $_REQUEST[ $key ] );
	}

	/**
	 * Sanitize an array of expected integers.
	 *
	 * @param array|string $values The values to sanitize.
	 * @param bool         $force_array_return If values are provided but are not an array, return them in an array.
	 *
	 * @return array|int|null
	 */
	public static function sanitize_int_array( $values, $force_array_return = false ) {
		if ( ! $values ) {
			return null;
		}

		if ( is_array( $values ) ) {
			$values = array_map( 'sanitize_text_field', $values );
		} elseif ( strpos( $values, ',' ) !== false ) {
			$values = explode( ',', sanitize_text_field( $values ) );
		}

		if ( ! is_array( $values ) ) {
			$values = intval( $values );

			if ( $force_array_return ) {
				$values = array( $values );
			}

			return $values;
		}

		return array_map( 'intval', $values );
	}
}
