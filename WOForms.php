<?php
namespace WOAdminFramework;

class WOForms {
	protected $text_domain;

	public function __construct( $text_domain = 'default' ) {
		$this->text_domain = $text_domain;
	}

	public function maybe_class( $classes = null ) {
		if ( $classes ) {

			if ( is_array( $classes ) ) {
				$classes = implode( ' ', $classes );
			}

			return ' class="' . esc_attr( $classes ) . '"';
		}

		return '';
	}

	public function label( $id, $text, $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'classes' => null,
				'display' => true,
			)
		);

		$html  = '<label for="' . esc_attr( $id ) . '"';
		$html .= $this->maybe_class( $args['classes'] );
		$html .= '>';
		$html .= esc_html( $text, $this->text_domain );
		$html .= '</label>';

		if ( ! $args['display'] ) {
			return $html;
		}

		echo $html;
	}

	public function select( $name, $options, $current_value, $args = array() ) {

		$args = wp_parse_args(
			$args,
			array(
				'classes'    => null,
				'display'    => true,
				'id'         => null,
				'empty_text' => null,
			)
		);

		if ( ! $args['id'] ) {
			$args['id'] = $name;
		}

		$html  = '<select name="' . esc_attr( $name ) . '" id="' . esc_attr( $args['id'] ) . '"';
		$html .= $this->maybe_class( $args['classes'] );
		$html .= '>';

		if ( $args['empty_text'] ) {
			$html .= '<option value="">' . esc_html( $args['empty_text'], $this->text_domain ) . '</option>';
		}

		foreach ( $options as $option_value => $text ) {
			$disabled = false;

			if ( substr( $option_value, 0, 9 ) === 'disabled:' ) {
				$disabled     = true;
				$option_value = '';
			}

			$html .= '<option value="' . esc_attr( $option_value ) . '" ';

			if ( $disabled ) {
				$html .= 'disabled="true"';
			} else {
				$html .= selected( $option_value, $current_value, false );
			}

			$html .= '>' . esc_html( $text, $this->text_domain ) . '</option>';
		}

		$html .= '</select>';

		if ( ! $args['display'] ) {
			return $html;
		}

		echo $html;
	}

	public function input( $name, $value, $type = 'text', $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'classes'     => null,
				'display'     => true,
				'id'          => null,
				'placeholder' => null,
				'min'         => null,
				'max'         => null,
			)
		);

		if ( ! $args['id'] ) {
			$args['id'] = $name;
		}

		$html  = '<input type="' . esc_attr( $type ) . '" value="' . esc_attr( $value ) . '" name="' . esc_attr( $name ) . '" id="' . esc_attr( $args['id'] ) . '"';
		$html .= $this->maybe_class( $args['classes'] );

		if ( $args['placeholder'] ) {
			$html .= ' placeholder="' . esc_attr( $args['placeholder'] ) . '"';
		}

		if ( $args['min'] ) {
			$html .= ' min="' . esc_attr( $args['min'] ) . '"';
		}

		if ( $args['max'] ) {
			$html .= ' max="' . esc_attr( $args['max'] ) . '"';
		}

		$html .= ' />';

		if ( ! $args['display'] ) {
			return $html;
		}

		echo $html;
	}

	public function textarea( $name, $value, $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'classes' => null,
				'display' => true,
				'id'      => null,
				'rows'    => 10,
				'cols'    => 90,
			)
		);

		if ( ! $args['id'] ) {
			$args['id'] = $name;
		}

		$html  = '<textarea name="' . esc_attr( $name ) . '" id="' . esc_attr( $args['id'] ) . '" rows="' . esc_attr( $args['rows'] ) . '" cols="' . esc_attr( $args['cols'] ) . '"';
		$html .= $this->maybe_class( $args['classes'] );
		$html .= '>' . $value . '</textarea>';

		if ( ! $args['display'] ) {
			return $html;
		}

		echo $html;
	}

	public function checkbox( $name, $current_value, $checked_value = 1, $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'classes' => null,
				'display' => true,
				'id'      => null,
			)
		);

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

	public function inputgroup( $name, $options, $current_value, $type = 'radio', $args = array() ) {

		if ( empty( $options ) ) {
			return '';
		}

		$args = wp_parse_args(
			$args,
			array(
				'classes'    => null,
				'display'    => true,
				'id'         => null,
				'empty_text' => null,
				'wrap'       => true,
			)
		);

		if ( ! $args['id'] ) {
			$args['id'] = str_replace( '[]', '', $name );
		}

		if ( $args['classes'] && ! $args['wrap'] ) {
			$args['wrap'] = true;
		}

		if ( $type === 'checkbox' ) {
			if ( $current_value === null || $current_value === false ) {
				$current_value = array();
			}

			if ( ! is_array( $current_value ) ) {
				$current_value = array( $current_value );
			}

			if ( substr( $name, -2 ) !== '[]' ) {
				$name .= '[]';
			}
		}

		$html = '';
		$idx  = 1;

		foreach ( $options as $value => $text ) {
			$id = $args['id'] . '_' . $idx;

			$html .= '<input type="' . esc_attr( $type ) . '" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '"';

			if ( $type === 'radio' ) {
				$html .= checked( $value, $current_value, false );
			} else {
				$html .= checked( true, in_array( $value, $current_value ), false );
			}

			$html .= ' />';
			$html .= $this->label( $id, $text, array( 'display' => false ) );

			++$idx;
		}

		if ( $args['wrap'] ) {
			$html = '<div' . $this->maybe_class( $args['classes'] ) . '>' . $html . '</div>';
		}

		if ( ! $args['display'] ) {
			return $html;
		}

		echo $html;
	}

	public function message( $message, $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'classes'      => null,
				'display'      => true,
				'allowed_html' => wp_kses_allowed_html( 'post' ),
			)
		);

		$html  = '<p';
		$html .= $this->maybe_class( $args['classes'] );
		$html .= '>';
		$html .= __( wp_kses( $message, $args['allowed_html'] ), $this->text_domain );
		$html .= '</p>';

		if ( ! $args['display'] ) {
			return $html;
		}

		echo $html;
	}
}
