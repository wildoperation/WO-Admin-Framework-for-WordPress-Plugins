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
			case 'int':
				$value = intval( $input );
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


	public function save_post_metadata( $post_id, $allowed_keys, $prefix = '_' ) {
		foreach ( $allowed_keys as $key => $allowed_keyvalue ) {
			$full_key = $this->make_key( $key );

			if ( isset( $_POST[ $full_key ] ) ) {
				$value = $this->sanitize_meta_input( $allowed_keyvalue, $_POST[ $full_key ] );
			} else {
				$value = $this->parse_default( $allowed_keyvalue );
			}

			update_post_meta( $post_id, $full_key, $value );
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
					<?php foreach ( $options as $key => $value ) : ?>
			<option value="<?php esc_attr_e( $key ); ?>"><?php esc_html_e( $value, $this->text_domain ); ?></option>
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
