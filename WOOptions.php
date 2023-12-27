<?php
namespace WOAdminFramework;

class WOOptions {

	protected $options = array();
	protected $ns;

	public function __construct( $ns ) {
		$this->ns = $ns;
	}

	private function ns() {
		return $this->ns . '_';
	}

	public function key( $key ) {
		return $this->ns() . $key;
	}

	public function refresh( $group, $default = array() ) {
		$this->options[ $group ] = get_option( $this->key( $group ), $default );
	}

	public function get( $option, $group, $default = null ) {
		if ( ! isset( $this->options[ $group ] ) ) {
			$this->refresh( $group );
		}

		if ( isset( $this->options[ $group ][ $option ] ) ) {
			return $this->options[ $group ][ $option ];
		}

		return $default;
	}

	public function update( $group, $data ) {
		update_option( $this->key( $group ), $data );
	}

	public function initialize( $group, $default = array() ) {
		if ( false == get_option( $this->key( $group ) ) ) {
			add_option( $this->key( $group ), $default );
		}
	}

	public function sanitize_default( $value ) {
		if ( ! $value ) {
			return null;
		}

		return sanitize_text_field( $value );
	}

	public function sanitize_input_basic( $input, $capability ) {
		if ( ! current_user_can( $capability ) ) {
			die();
		}

		$output = array();

		if ( $input ) {
			foreach ( $input as $key => $value ) {
				$output[ $key ] = $this->sanitize_default( $value );
			}
		}

		return $output;
	}

	public function add_sections_and_settings( $settings, $class_instance ) {
		foreach ( $settings as $key => $group ) {
			$this->initialize( $key );

			/**
			 * This is the overall group
			 * It's a tab, and also the option key for the DB
			 */
			$opt_key = $this->key( $key );

			foreach ( $group['sections'] as $section_key => $section ) {
				$section_key = $this->key( $section_key );

				/**
				 * These are sub-sections within the option group
				 */
				add_settings_section(
					$section_key . '_settings_section',
					isset( $section['title'] ) ? $section['title'] : null,
					array( &$class_instance, 'settings_callback_' . $section_key ),
					$opt_key
				);

				/**
				 * Add fields
				 */
				if ( isset( $section['fields'] ) && ! empty( $section['fields'] ) ) {
					foreach ( $section['fields'] as $field_key => $value ) {
						add_settings_field(
							$field_key,
							$value,
							array( &$class_instance, 'field_' . $this->ns . '_' . $field_key ),
							$opt_key,
							$section_key . '_settings_section'
						);
					}
				}
			}
		}

		/**
		 * Register settings
		 */
		foreach ( $settings as $key => $group ) {
			$key = $this->key( $key );
			register_setting(
				$key,
				$key,
				array(
					'sanitize_callback' => array( &$class_instance, 'sanitize_' . $key ),
				),
			);
		}
	}
}
