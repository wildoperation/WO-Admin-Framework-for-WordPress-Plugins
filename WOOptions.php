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

	public function refresh( $key, $default = array() ) {
		$this->options[ $key ] = get_option( $this->key( $key ), $default );
	}

	public function get( $option, $group = null, $default = null ) {

		if ( $group !== null ) {
			if ( ! isset( $this->options[ $group ] ) ) {
				$this->refresh( $group );
			}

			if ( isset( $this->options[ $group ][ $option ] ) ) {
				return $this->options[ $group ][ $option ];
			}
		} else {
			if ( ! isset( $this->options[ $option ] ) ) {
				$this->refresh( $option );
			}

			if ( isset( $this->options[ $option ] ) ) {
				return $this->options[ $option ];
			}
		}

		return $default;
	}

	public function update( $group, $data, $refresh = false ) {
		update_option( $this->key( $group ), $data );

		if ( $refresh ) {
			$this->refresh( $group );
		}
	}

	public function initialize( $group, $default = array() ) {
		if ( false == get_option( $this->key( $group ) ) ) {
			add_option( $this->key( $group ), $default );
		}
	}
}
