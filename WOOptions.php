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
