<?php
namespace WOAdminFramework;

class WOMeta {
	private $ns;
	private $text_domain;

	public function __construct( $ns, $text_domain = 'default' ) {
		$this->ns          = $ns;
		$this->text_domain = $text_domain;
	}

	public function make_key( $key, $prefix = '_' ) {
		return $prefix . $this->ns . '_' . $key;
	}

	public function get_post_meta( $post_id, $allowed_keys ) {
		$post_meta   = get_post_meta( $post_id );
		$parsed_meta = array();

		foreach ( $allowed_keys as $key => $allowed_keyvalue ) {
			$full_key = $this->make_key( $key );
			$value    = $this->parse_default( $allowed_keyvalue );

			if ( isset( $post_meta[ $full_key ] ) ) {
				$value         = $post_meta[ $full_key ];
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


	public function label( $key, $text, $classes = null ) {
		?>
		<label for="<?php esc_attr_e( $this->make_key( $key ) ); ?>"
								<?php
								if ( $classes ) :
									?>
			class="<?php esc_attr_e( $classes ); ?>"<?php endif; ?>><?php esc_html_e( $text, $this->text_domain ); ?></label>
				<?php
	}

	public function select( $key, $options, $current_value, $empty_text, $classes = '', $id = '' ) {
		$name = $this->make_key( $key );

		if ( ! $id ) {
			$id = $name;
		}
		?>
		<select name="<?php esc_attr_e( $name ); ?>" id="<?php esc_attr_e( $id ); ?>" 
								<?php
								if ( $classes ) :
									?>
			class="<?php esc_attr_e( $classes ); ?>"<?php endif; ?>>
					<?php
					if ( $empty_text ) :
						?>
				<option value=""><?php esc_html_e( $empty_text, $this->text_domain ); ?></option><?php endif; ?>
					<?php foreach ( $options as $key => $text ) : ?>
			<option value="<?php esc_attr_e( $key ); ?>" <?php selected( $key, $current_value ); ?>><?php esc_html_e( $text, $this->text_domain ); ?></option>
			<?php endforeach; ?>
		</select>
				<?php
	}

	public function input( $key, $value, $type = 'text', $id = null ) {
		$name = $this->make_key( $key );

		if ( ! $id ) {
			$id = $name;
		}
		?>
		<input type="<?php esc_attr_e( $type ); ?>" value="<?php esc_attr_e( $value ); ?>" name="<?php esc_attr_e( $name ); ?>" id="<?php esc_attr_e( $id ); ?>" />
		<?php
	}

	public function checkbox( $key, $current_value, $checked_value, $id = null ) {
		$name = $this->make_key( $key );

		if ( ! $id ) {
			$id = $name;
		}
		?>
		<input type="checkbox" value="<?php esc_attr_e( $checked_value ); ?>" name="<?php esc_attr_e( $name ); ?>" id="<?php esc_attr_e( $id ); ?>" <?php checked( $checked_value, $current_value ); ?> />
		<?php
	}

	public function message( $message, $classes = '' ) {
		?>
		<p 
		<?php
		if ( $classes ) :
			?>
			class="<?php esc_attr_e( $classes ); ?>"<?php endif; ?>><?php esc_html_e( $message, $this->text_domain ); ?></p>
			<?php
	}
}
