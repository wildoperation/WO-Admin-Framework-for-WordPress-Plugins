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
			$html .= '<option value="' . esc_attr( $option_value ) . '" ' . selected( $option_value, $current_value, false ) . '>' . esc_html( $text, $this->text_domain ) . '</option>';
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
				'classes' => null,
				'display' => true,
				'id'      => null,
			)
		);

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

	public function radiogroup( $name, $radios, $current_value, $args = array() ) {

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
			$args['id'] = $name;
		}

		if ( $args['classes'] && ! $args['wrap'] ) {
			$args['wrap'] = true;
		}

		$html = '';
		$idx  = 1;

		foreach ( $radios as $value => $text ) {
			$id = $args['id'] . '_' . $idx;

			$html .= '<input type="radio" id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '"' . checked( $value, $current_value, false ) . ' />';
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
}
