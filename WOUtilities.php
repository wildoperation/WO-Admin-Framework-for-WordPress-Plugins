<?php
namespace WOAdminFramework;

class WOUtilities {

	public static function truthy( $value ) {
		if ( is_bool( $value ) ) {
			return $value;
		}

		if ( is_int( $value ) ) {
			return $value > 0 ? true : false;
		}

		return in_array( strtolower( $value ), array( 'yes', 'y', 'true', '1' ) );
	}
}
