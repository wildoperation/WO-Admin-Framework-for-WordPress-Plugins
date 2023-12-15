<?php
namespace WOAdminFramework;

class WOOptions {

	protected $options = array();
	protected $ns;

	public function __construct( $ns = null ) {
		if ( ! $ns ) {
			$ns = 'wo';
		}

		$this->ns = $ns . '_';
	}

	private function ns() {
		return $this->ns;
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
		return esc_url_raw( wp_strip_all_tags( stripslashes( $value ) ) );
	}

	public function add_sections_and_settings( $settings, $class_instance ) {
		foreach ( $settings as $key => $group ) {
			$this->initialize( $key );

			$opt_key    = $this->key( $key );
			$section_id = $opt_key . '_settings_section';

			add_settings_section(
				$section_id,
				$group['title'],
				array( &$class_instance, $opt_key . '_settings_callback' ),
				$opt_key
			);

			/**
			 * Add fields
			 */
			if ( isset( $group['fields'] ) && ! empty( $group['fields'] ) ) {
				foreach ( $group['fields'] as $field_key => $field_title ) {
					add_settings_field(
						$field_key,
						$field_title,
						array( &$class_instance, 'field_wgs_' . $field_key ),
						$opt_key,
						$section_id
					);
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
