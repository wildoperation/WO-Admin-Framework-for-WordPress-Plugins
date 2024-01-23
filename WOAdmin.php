<?php
namespace WOWPAds\Vendor\WOAdminFramework;

class WOAdmin {
	public function authorize_action( $action_nonce, $nonce_key, $capability, $required_post = array() ) {
		check_ajax_referer( $action_nonce, $nonce_key );

		if ( ! current_user_can( $capability ) ) {
			wp_die();
		}

		if ( $required_post ) {
			if ( ! is_array( $required_post ) ) {
				$required_post = array( $required_post );
			}

			foreach ( $required_post as $required ) {
				if ( ! isset( $_POST[ $required ] ) ) {
					wp_die();
				}
			}
		}
	}

	private static function do_sanitize_by_type( $input, $type ) {
		switch ( $type ) {
			case 'date':
				$input  = sanitize_text_field( $input );
				$format = 'F j, Y';
				$date   = \DateTime::createFromFormat( $format, $input );

				if ( $date && $date->format( $format ) ) {
					$value = $date->format( $format );
				} else {
					$value = null;
				}

				break;
			case 'bool':
				$value = intval( wp_strip_all_tags( $input ) ) > 0 ? 1 : 0;
				break;

			case 'int':
				$value = ( ! $input || $input === null ) ? null : intval( wp_strip_all_tags( $input ) );
				break;

			case 'url':
				$value = sanitize_url( wp_strip_all_tags( $input ) );
				break;

			case 'textarea':
				$value = sanitize_textarea_field( $input );
				break;

			case 'str':
			default:
				$value = sanitize_text_field( $input );
				break;
		}

		return $value;
	}

	public static function sanitize_by_type( $input, $type = 'str' ) {
		if ( is_array( $input ) ) {
			$value = array();

			foreach ( $input as $i ) {
				if ( empty( $i ) ) {
					continue;
				}

				$value[] = self::do_sanitize_by_type( $i, $type );
			}
		} else {
			$value = self::do_sanitize_by_type( $input, $type );
		}

		return $value;
	}
}
