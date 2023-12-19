<?php
namespace WOAdminFramework;

class WOMeta {
	private $ns;
	private $text_domain;

	public function __construct( $ns, $text_domain = 'default' ) {
		$this->ns          = $ns;
		$this->text_domain = $text_domain;
	}

	public static function truthy( $value ) {
		if ( is_bool( $value ) ) {
			return $value;
		}

		return intval( $value ) > 0 ? true : false;
	}

	public function get_value( $array, $key, $default = null ) {
		if ( isset( $array[ $this->make_key( $key ) ] ) ) {
			return $array[ $this->make_key( $key ) ];
		}

		return $default;
	}

	public function make_key( $key, $prefix = '_' ) {
		return $prefix . $this->ns . '_' . $key;
	}

	public function get_post_meta( $post_id, $allowed_keys ) {
		$post_meta = get_post_meta( $post_id );
		return $this->parse_meta( $post_meta, $allowed_keys );
	}

	public function get_term_meta( $term_id, $allowed_keys ) {
		$term_meta = get_term_meta( $term_id );
		return $this->parse_meta( $term_meta, $allowed_keys );
	}

	public function parse_meta( $meta, $allowed_keys ) {
		$parsed_meta = array();

		foreach ( $allowed_keys as $key => $allowed_keyvalue ) {
			$full_key = $this->make_key( $key );
			$value    = $this->parse_default( $allowed_keyvalue );

			if ( isset( $meta[ $full_key ] ) ) {
				$value         = $meta[ $full_key ];
				$is_type_array = isset( $allowed_keyvalue['type'] ) && $allowed_keyvalue['type'] === 'arr' ? true : false;

				if ( is_array( $value ) && ! $is_type_array ) {
					$value = $value[0];
				} elseif ( $is_type_array && ! is_array( $value ) ) {
					$value = array( $value );
				}
			}

			$parsed_meta[ $full_key ] = $value;

		}

		return $parsed_meta;
	}

	public function parse_default( $allowed_keyvalue ) {
		if ( isset( $allowed_keyvalue['default'] ) ) {
			return $allowed_keyvalue['default'];
		}

		switch ( $allowed_keyvalue['type'] ) {
			case 'bool':
				return 0;
			break;

			default:
				return null;
			break;
		}
	}

	public function sanitize_meta_input( $allowed_keyvalue, $input ) {
		if ( ! isset( $allowed_keyvalue['type'] ) ) {
			error_log( 'Specify key type for proper sanitization.' );
			$allowed_keyvalue['type'] = 'str';
		}

		switch ( $allowed_keyvalue['type'] ) {
			case 'bool':
				$value = intval( wp_strip_all_tags( $input ) ) > 0 ? 1 : 0;
				break;

			case 'int':
				$value = ( ! $input || $input === null ) ? null : intval( wp_strip_all_tags( $input ) );
				break;

			case 'url':
				$value = sanitize_url( wp_strip_all_tags( $input ) );
				break;

			case 'str':
			default:
				$value = sanitize_text_field( $input );
				break;
		}

		if ( $value !== null && isset( $allowed_keyvalue['restricted'] ) ) {
			if ( ! in_array( $value, $allowed_keyvalue['restricted'] ) ) {
				$value = $this->parse_default( $allowed_keyvalue );
			}
		}

		return $value;
	}


	public function save_posted_metadata( $post, $allowed_keys ) {
		if ( isset( $post->post_status ) && 'auto-draft' === $post->post_status || 'trash' === $post->post_status ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! isset( $_POST ) || ( isset( $_POST ) && empty( $_POST ) ) ) {
			return;
		}

		foreach ( $allowed_keys as $key => $allowed_keyvalue ) {
			$full_key = $this->make_key( $key );

			if ( isset( $_POST[ $full_key ] ) ) {
				$value = $this->sanitize_meta_input( $allowed_keyvalue, $_POST[ $full_key ] );
			} else {
				$value = $this->parse_default( $allowed_keyvalue );
			}

			update_post_meta( $post->ID, $full_key, $value );
		}
	}

	public function save_posted_term_metadata( $term_id, $allowed_keys ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! $term_id || ! isset( $_POST ) || ( isset( $_POST ) && empty( $_POST ) ) ) {
			return;
		}

		foreach ( $allowed_keys as $key => $allowed_keyvalue ) {
			$full_key = $this->make_key( $key );

			if ( isset( $_POST[ $full_key ] ) ) {
				$value = $this->sanitize_meta_input( $allowed_keyvalue, $_POST[ $full_key ] );
			} else {
				$value = $this->parse_default( $allowed_keyvalue );
			}

			update_term_meta( $term_id, $full_key, $value );
		}
	}

	private function maybe_class( $classes = null ) {
		if ( $classes ) {

			if ( is_array( $classes ) ) {
				$classes = implode( ' ', $classes );
			}

			return ' class="' . esc_attr( $classes ) . '"';
		}

		return '';
	}


	public function label( $key, $text, $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'classes' => null,
				'display' => true,
			)
		);

		$html  = '<label for="' . esc_attr( $this->make_key( $key ) ) . '"';
		$html .= $this->maybe_class( $args['classes'] );
		$html .= '>';
		$html .= esc_html( $text, $this->text_domain );
		$html .= '</label>';

		if ( ! $args['display'] ) {
			return $html;
		}

		echo $html;
	}

	public function select( $key, $options, $current_value, $args = array() ) {

		$args = wp_parse_args(
			$args,
			array(
				'classes'    => null,
				'display'    => true,
				'id'         => null,
				'empty_text' => null,
			)
		);

		$name = $this->make_key( $key );

		if ( ! $args['id'] ) {
			$args['id'] = $name;
		}

		$html  = '<select name="' . esc_attr( $name ) . '" id="' . esc_attr( $args['id'] ) . '"';
		$html .= $this->maybe_class( $args['classes'] );
		$html .= '>';

		if ( $args['empty_text'] ) {
			$html .= '<option value="">' . esc_html( $empty_text, $this->text_domain ) . '</option>';
		}

		foreach ( $options as $option_value => $text ) {
			$html .= '<option value="' . esc_attr( $option_value ) . '" ' . selected( $option_value, $current_value, false ) . '>' . esc_html( $text, $this->text_domain ) . '</option>';
		}

		$html .= '</select>';

		if ( ! $args['display'] ) {
			return $html;
		}

		echo $html;
	}

	public function input( $key, $value, $type = 'text', $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'classes' => null,
				'display' => true,
				'id'      => null,
			)
		);

		$name = $this->make_key( $key );

		if ( ! $args['id'] ) {
			$args['id'] = $name;
		}

		$html  = '<input type="' . esc_attr( $type ) . '" value="' . esc_attr( $value ) . '" name="' . esc_attr( $name ) . '" id="' . esc_attr( $args['id'] ) . '"';
		$html .= $this->maybe_class( $args['classes'] );
		$html .= ' />';

		if ( ! $args['display'] ) {
			return $html;
		}

		echo $html;
	}

	public function checkbox( $key, $current_value, $checked_value, $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'classes' => null,
				'display' => true,
				'id'      => null,
			)
		);

		$name = $this->make_key( $key );

		if ( ! $args['id'] ) {
			$args['id'] = $name;
		}

		$html  = '<input type="checkbox" value="' . esc_attr( $checked_value ) . '" name="' . esc_attr( $name ) . '" id="' . esc_attr( $args['id'] ) . '" ' . checked( $checked_value, $current_value, false );
		$html .= $this->maybe_class( $args['classes'] );
		$html .= '/>';

		if ( ! $args['display'] ) {
			return $html;
		}

		echo $html;
	}

	public function message( $message, $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'classes' => null,
				'display' => true,
			)
		);

		$html  = '<p';
		$html .= $this->maybe_class( $args['classes'] );
		$html .= '>';
		$html .= esc_html( $message, $this->text_domain );
		$html .= '</p>';

		if ( ! $args['display'] ) {
			return $html;
		}

		echo $html;
	}

	public function term_meta_row( $th, $td, $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'classes' => array(),
				'display' => true,
				'message' => null,
			)
		);

		if ( $args['classes'] && ! is_array( $args['classes'] ) ) {
			$args['classes'] = array( $args['classes'] );
		}

		$args['classes'][] = 'form-field';

		if ( $args['message'] ) {
			$td .= $this->message(
				$args['message'],
				array(
					'classes' => 'description',
					'display' => false,
				)
			);
		}

		$html  = '<tr';
		$html .= $this->maybe_class( $args['classes'] );
		$html .= '>';
		$html .= '<th>' . $th . '</th>';
		$html .= '<td>' . $td . '</td>';
		$html .= '</tr>';

		if ( ! $args['display'] ) {
			return $html;
		}

		echo $html;
	}
}
