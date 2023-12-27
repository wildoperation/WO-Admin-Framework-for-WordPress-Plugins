<?php
namespace WOAdminFramework;

class WOMeta {
	private $ns;
	private $text_domain;
	private $woforms;

	public function __construct( $ns, $text_domain = 'default' ) {
		$this->ns          = $ns;
		$this->text_domain = $text_domain;

		$this->woforms = new WOForms( $text_domain );
	}

	public function make_key( $key, $prefix = '_' ) {
		return $prefix . $this->ns . '_' . $key;
	}

	public static function truthy( $value ) {
		if ( is_bool( $value ) ) {
			return $value;
		}

		return intval( $value ) > 0 ? true : false;
	}

	public function get_value( $array, $key, $default = null ) {
		$full_key = $this->make_key( $key );

		if ( isset( $array[ $full_key ] ) ) {
			return $array[ $full_key ];
		}

		return $default;
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

		$value = WOAdmin::sanitize_by_type( $input, $allowed_keyvalue['type'] );

		if ( $value !== null && isset( $allowed_keyvalue['restricted'] ) ) {
			if ( ! in_array( $value, $allowed_keyvalue['restricted'] ) ) {
				$value = $this->parse_default( $allowed_keyvalue );
			}
		}

		return $value;
	}


	private function is_posted() {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return false;
		}

		return isset( $_POST ) && ! empty( $_POST );
	}

	private function process_posted_meta( $id, $allowed_keys, $context = 'post' ) {
		foreach ( $allowed_keys as $key => $allowed_keyvalue ) {
			$full_key = $this->make_key( $key );

			if ( isset( $_POST[ $full_key ] ) ) {
				$value = $this->sanitize_meta_input( $allowed_keyvalue, $_POST[ $full_key ] );
			} else {
				$value = $this->parse_default( $allowed_keyvalue );
			}

			if ( $context === 'term' ) {
				update_term_meta( $id, $full_key, $value );
			} else {
				update_post_meta( $id, $full_key, $value );
			}
		}
	}

	public function save_posted_metadata( $post, $allowed_keys ) {
		if ( ! $this->is_posted() ||
			( isset( $post->post_status ) && ( 'auto-draft' === $post->post_status || 'trash' === $post->post_status ) )
			) {
			return;
		}

		$this->process_posted_meta( $post->ID, $allowed_keys, 'post' );
	}

	public function save_posted_term_metadata( $term_id, $allowed_keys ) {

		if ( ! $term_id || ! $this->is_posted() ) {
			return;
		}

		$this->process_posted_meta( $term_id, $allowed_keys, 'term' );
	}

	public function label( $key, $text, $args = array() ) {
		return $this->woforms->label( $this->make_key( $key ), $text, $args );
	}

	public function select( $key, $options, $current_value = null, $args = array() ) {
		return $this->woforms->select( $this->make_key( $key ), $options, $current_value, $args );
	}

	public function input( $key, $value, $type = 'text', $args = array() ) {
		return $this->woforms->input( $this->make_key( $key ), $value, $type, $args );
	}

	public function checkbox( $key, $current_value, $checked_value = 1, $args = array() ) {
		return $this->woforms->checkbox( $this->make_key( $key ), $current_value, $checked_value, $args );
	}

	public function message( $message, $args = array() ) {
		return $this->woforms->message( $message, $args );
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

		$args['classes'][] = $this->ns . '-form-field form-field';

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
		$html .= $this->woforms->maybe_class( $args['classes'] );
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
