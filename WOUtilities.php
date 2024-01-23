<?php
namespace WOWPAds\Vendor\WOAdminFramework;

class WOUtilities {

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

	public static function sanitize_posted_ints( $key ) {
		if ( ! isset( $_POST ) || empty( $_POST ) || ! isset( $_POST[ $key ] ) || ! $_POST[ $key ] ) {
			return null;
		}

		$values = $_POST[ $key ];

		if ( ! is_array( $values ) ) {
			return intval( $values );
		}

		return array_map( 'intval', $values );
	}
}
