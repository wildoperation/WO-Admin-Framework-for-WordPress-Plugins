<?php
namespace WOAdminFramework;

/**
 * Class for working with namespaced meta in a plugin.
 */
class WOMeta {
	/**
	 * The namespace of your plugin.
	 *
	 * @var string
	 */
	protected $ns;

	/**
	 * An instance of WOForms
	 *
	 * @var WOForms
	 */
	protected $wo_forms;

	/**
	 * An instance of WOAdmin
	 *
	 * @var WOAdmin
	 */
	private $wo_admin;

	/**
	 * __construct()
	 *
	 * @param string $ns The namespace for your meta fields.
	 * @param string $text_domain The text_domain for your plugin.
	 */
	public function __construct( $ns, $text_domain = 'default' ) {
		$this->ns       = $ns;
		$this->wo_forms = new WOForms( $text_domain );
	}

	/**
	 * Sets a new instance of WOAdmin if necessary and returns it.
	 *
	 * @return WOAdmin
	 */
	protected function woadmin() {
		if ( ! $this->wo_admin ) {
			$this->wo_admin = new WOAdmin();
		}

		return $this->wo_admin;
	}

	/**
	 * Create a meta key using the namespace and a string.
	 *
	 * @param string $key The key to append to the namespace.
	 * @param string $prefix The prefix before the key.
	 *
	 * @return string
	 */
	public function make_key( $key, $prefix = '_' ) {
		return $prefix . $this->ns . '_' . $key;
	}

	/**
	 * Gets the value of a non-namespaced meta key from an array of meta key/value pairs.
	 *
	 * @param array  $array An array of meta.
	 * @param string $key A non-namespaced key to retrive.
	 * @param null   $default The default value if the key is not found.
	 *
	 * @return mixed
	 */
	public function get_value( $array, $key, $default = null ) {
		$full_key = $this->make_key( $key );

		if ( isset( $array[ $full_key ] ) ) {
			return $array[ $full_key ];
		}

		return $default;
	}

	/**
	 * Retrieves and parses the post meta for a given post ID.
	 *
	 * @param int   $post_id Post ID to retrieve meta for.
	 * @param array $allowed_keys The allowed keys to be parsesd so that rogue meta is not returned.
	 *
	 * @return array
	 */
	public function get_post_meta( $post_id, $allowed_keys ) {
		$post_meta = get_post_meta( $post_id );
		return $this->parse_meta( $post_meta, $allowed_keys );
	}

	/**
	 * Retrieves and parses the term meta for a given term ID.
	 *
	 * @param int   $term_id Term ID to retrieve meta for.
	 * @param array $allowed_keys The allowed keys to be parsesd so that rogue meta is not returned.
	 *
	 * @return array
	 */
	public function get_term_meta( $term_id, $allowed_keys ) {
		$term_meta = get_term_meta( $term_id );
		return $this->parse_meta( $term_meta, $allowed_keys );
	}

	/**
	 * Parse an array of meta using a set of allowed keys.
	 *
	 * @param array $meta The meta to parse.
	 * @param array $allowed_keys The allowed keys to parse against.
	 *
	 * @return array
	 */
	private function parse_meta( $meta, $allowed_keys ) {
		$parsed_meta = array();

		if ( empty( $allowed_keys ) ) {
			return $parsed_meta;
		}

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

			$parsed_meta[ $full_key ] = maybe_unserialize( $value );

		}

		return $parsed_meta;
	}

	/**
	 * Find the default value for a particular allowed key.
	 *
	 * @param array $allowed_keyvalue An allowed key/value pair with optional pre-set default value.
	 *
	 * @return mixed
	 */
	public function parse_default( $allowed_keyvalue ) {
		if ( isset( $allowed_keyvalue['default'] ) ) {
			return $allowed_keyvalue['default'];
		}

		if ( ! isset( $allowed_keyvalue['type'] ) ) {
			return null;
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

	/**
	 * Sanitize meta input using a specified type from the $allowed_keyvalue array.
	 *
	 * @param array $allowed_keyvalue Array of allowed key/value pairs and related info.
	 * @param mixed $input The user input to sanitize.
	 *
	 * @return mixed
	 */
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


	/**
	 * Determine if we have a valid saved post to process.
	 *
	 * @param mixed $object Object that was potentially posted for meta processing.
	 * @param array $nonce The nonce to  verify.
	 *
	 * @return bool
	 */
	private function is_posted( $object, $nonce ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return false;
		}

		if ( isset( $object->post_status ) && ( 'auto-draft' === $object->post_status || 'trash' === $object->post_status ) ) {
			return false;
		}

		if ( ! isset( $nonce['action'] ) || ! isset( $nonce['name'] ) || ! isset( $_POST[ $nonce['name'] ] ) ) {
			return false;
		}

		return isset( $_POST ) && ! empty( $_POST ) && wp_verify_nonce( sanitize_key( $_POST[ $nonce['name'] ] ), $nonce['action'] );
	}

	/**
	 * Process the meta that was posted.
	 *
	 * @param int    $id The posted object ID for a post, term, etc.
	 * @param array  $allowed_keys The allowed key/value set to use while processing.
	 * @param string $context The context of the post; term of post.
	 *
	 * @return void
	 */
	private function process_posted_meta( $id, $allowed_keys, $context = 'post' ) {

		foreach ( $allowed_keys as $key => $allowed_keyvalue ) {
			if ( isset( $allowed_keyvalue['ignore_post'] ) && $allowed_keyvalue['ignore_post'] === true ) {
				continue;
			}

			$full_key = $this->make_key( $key );

			$value = null;

			if ( isset( $_POST[ $full_key ] ) ) {
				$value = $this->sanitize_meta_input( $allowed_keyvalue, $_POST[ $full_key ] );
			} elseif ( isset( $allowed_keyvalue['type'] ) && $allowed_keyvalue['type'] === 'bool' ) {
				$value = 0;
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

	/**
	 * Update the meta of a post using a non-namespaced key.
	 *
	 * @param int    $id The post to update.
	 * @param string $key The none-namespaced key to update.
	 * @param mixed  $value The value to save.
	 *
	 * @return int|bool
	 */
	public function update_post_meta( $id, $key, $value ) {
		return update_post_meta( $id, $this->make_key( $key ), $value );
	}

	/**
	 * Check for a valid post and process meta data if allowed.
	 *
	 * @param object $post WP_Post or other posted object.
	 * @param array  $allowed_keys The allowed key/value set to use while processing.
	 * @param array  $nonce The nonce to  verify.
	 *
	 * @return bool
	 */
	public function save_posted_metadata( $post, $allowed_keys, $nonce ) {
		if ( ! $this->is_posted( $post, $nonce ) ) {
			return false;
		}

		$this->process_posted_meta( $post->ID, $allowed_keys, 'post' );

		return true;
	}

	/**
	 * Check for a valid post and process meta data if allowed.
	 *
	 * @param int   $term_id Term ID of posted object.
	 * @param array $allowed_keys The allowed key/value set to use while processing.
	 * @param array $nonce The nonce to  verify.
	 *
	 * @return bool
	 */
	public function save_posted_term_metadata( $term_id, $allowed_keys, $nonce ) {

		if ( ! $term_id || ! $this->is_posted( null, $nonce ) ) {
			return false;
		}

		$this->process_posted_meta( $term_id, $allowed_keys, 'term' );

		return true;
	}

	/**
	 * Creates a form label using our meta field.
	 *
	 * @param string $key Form field ID.
	 * @param string $text Text of the label.
	 * @param array  $args Optional args to pass to WOForms.
	 *
	 * @return string
	 */
	public function label( $key, $text, $args = array() ) {
		return $this->wo_forms->label( $this->make_key( $key ), $text, $args );
	}

	/**
	 * Creates a select dropdown for meta.
	 *
	 * @param string $key Form field ID.
	 * @param array  $options The options in the select field.
	 * @param mixed  $current_value The current value to select.
	 * @param array  $args Optional args to pass to WOForms.
	 *
	 * @return string
	 */
	public function select( $key, $options, $current_value = null, $args = array() ) {
		return $this->wo_forms->select( $this->make_key( $key ), $options, $current_value, $args );
	}

	/**
	 * Creates an input of specified $type for a meta field.
	 *
	 * @param string $key Form field ID.
	 * @param mixed  $current_value The current value of the input.
	 * @param string $type The type of input (e.g., 'text' or 'number').
	 * @param array  $args Optional args to pass to WOForms.
	 *
	 * @return string
	 */
	public function input( $key, $current_value, $type = 'text', $args = array() ) {
		return $this->wo_forms->input( $this->make_key( $key ), $current_value, $type, $args );
	}

	/**
	 * Creates a textarea for a meta field.
	 *
	 * @param string $key Form field ID.
	 * @param mixed  $current_value The current value of the textarea.
	 * @param array  $args Optional args to pass to WOForms.
	 *
	 * @return string
	 */
	public function textarea( $key, $current_value, $args = array() ) {
		return $this->wo_forms->textarea( $this->make_key( $key ), $current_value, $args );
	}

	/**
	 * Creates a checkbox for meta fields.
	 *
	 * @param string $key Form field ID.
	 * @param mixed  $current_value The current value.
	 * @param mixed  $checked_value The value when checked.
	 * @param array  $args Optional args to pass to WOForms.
	 *
	 * @return string
	 */
	public function checkbox( $key, $current_value, $checked_value = 1, $args = array() ) {
		return $this->wo_forms->checkbox( $this->make_key( $key ), $current_value, $checked_value, $args );
	}

	/**
	 * Creates a group of checkboxes for a meta field.
	 *
	 * @param string $key Form field ID.
	 * @param array  $checkboxes A key/value array of checkboxes.
	 * @param array  $current_values An array of current values that should be checked.
	 * @param array  $args Optional args to pass to WOForms.
	 *
	 * @return string
	 */
	public function checkgroup( $key, $checkboxes, $current_values = array(), $args = array() ) {
		return $this->wo_forms->inputgroup( $this->make_key( $key ), $checkboxes, $current_values, 'checkbox', $args );
	}

	/**
	 * Creates a group of radios for a meta field.
	 *
	 * @param string $key Form field ID.
	 * @param array  $radios A key/value array of checkboxes.
	 * @param mixed  $current_value The current value to select.
	 * @param array  $args Optional args to pass to WOForms.
	 *
	 * @return string
	 */
	public function radiogroup( $key, $radios, $current_value = null, $args = array() ) {
		return $this->wo_forms->inputgroup( $this->make_key( $key ), $radios, $current_value, 'radio', $args );
	}

	/**
	 * Creates a message for a meta field.
	 *
	 * @param string $message The message to display.
	 * @param array  $args Optional args to pass to WOForms.
	 *
	 * @return string
	 */
	public function message( $message, $args = array() ) {
		return $this->wo_forms->message( $message, $args );
	}

	/**
	 * Enqueue scripts and styles used for repeater fields.
	 *
	 * @return void
	 */
	public function repeater_enqueue() {
		$this->woadmin()->enqueue_woadmin_styles();

		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-sortable' );

		wp_register_script( 'wometa-repeater', $this->woadmin()->assets_url() . 'js/repeater.js', array( 'jquery', 'jquery-ui-core', 'jquery-ui-sortable' ), '1.0.0', array( 'in_footer' => true ) );

		$sort_handle = $this->repeater_sort_handle();

		wp_localize_script(
			'wometa-repeater',
			'wometa_repeater',
			array(
				'has_sort_handle'      => ( $sort_handle ) ? 'yes' : 'no',
				'sort_handle_selector' => apply_filters( 'wo_repeater_sort_handle_selector', '.wometa-repeater-sort-icon' ),
			)
		);

		wp_enqueue_script( 'wometa-repeater' );
	}

	/**
	 * Create a row in a term edit table.
	 *
	 * @param string $th The label/th for the table row.
	 * @param string $td The content of the td for the table row.
	 * @param array  $args Optional arguments for the table row.
	 *
	 * @return string|void
	 */
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
		$html .= $this->wo_forms->maybe_class( $args['classes'] );
		$html .= '>';
		$html .= '<th>' . $th . '</th>';
		$html .= '<td>' . $td . '</td>';
		$html .= '</tr>';

		if ( ! $args['display'] ) {
			return $html;
		}

		echo $html;
	}

	/**
	 * The drag-and-drop sort handle for a repeater row.
	 *
	 * @return string
	 */
	public function repeater_sort_handle() {
		return apply_filters( 'wo_repeater_draggable_icon', '<img src="' . esc_url( $this->woadmin()->assets_url() . 'img/drag.png' ) . '" alt="" class="wometa-repeater-sort-icon" />' );
	}

	/**
	 * The width of an icon in a repeater row.
	 *
	 * @return int
	 */
	public function repeater_icon_width() {
		return intval( apply_filters( 'wo_repeater_draggable_icon_width', 10 ) );
	}

	/**
	 * Start a repeater table.
	 *
	 * @param array $columns The columns in a repeater.
	 * @param array $args Optional arguments for a repeater.
	 *
	 * @return string|void
	 */
	public function repeater_start( $columns = array(), $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'classes'         => array(),
				'display'         => false,
				'width'           => '100%',
				'controls_column' => '',
			)
		);

		if ( $args['classes'] && ! is_array( $args['classes'] ) ) {
			$args['classes'] = array( $args['classes'] );
		}

		$args['classes'][] = $this->ns . '-repeater';

		$sort_handle = $this->repeater_sort_handle();
		$sort_width  = $this->repeater_icon_width();

		$html = '<table';

		if ( $args['width'] ) {
			$html .= ' width="' . esc_attr( $args['width'] ) . '"';
		}

		$html .= $this->wo_forms->maybe_class( $args['classes'] );
		$html .= '>';

		if ( ! empty( $columns ) ) {
			$html .= '<thead><tr>';

			if ( $sort_handle ) {
				$html .= '<th width="' . esc_attr( $sort_width ) . '"></th>';
			}

			foreach ( $columns as $column ) {
				$width = null;
				$text  = '';

				if ( is_array( $column ) ) {
					$width = ( isset( $column['width'] ) ) ? $column['width'] : null;
					$text  = ( isset( $column['text'] ) ) ? $column['text'] : '';
				} else {
					$text = $column;
				}

				$html .= '<th';
				if ( $width ) {
					$html .= ' width="' . esc_attr( $width ) . '"';
				}
				$html .= '>';

				if ( $text ) {
					$html .= esc_html( $text );
				}

				$html .= '</th>';
			}

			if ( $args['controls_column'] !== false ) {
				$html .= '<th>' . esc_attr( $args['controls_column'] ) . '</th>';
			}

			$html .= '</tr></thead>';
		}

		$html .= '<tbody>';

		if ( ! $args['display'] ) {
			return $html;
		}

		echo $html;
	}

	/**
	 * End a repeater.
	 *
	 * @param array $args Optional arguments for repeater end.
	 *
	 * @return string|void
	 */
	public function repeater_end( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'display' => false,
			)
		);

		$html = '</tbody></table>';

		if ( ! $args['display'] ) {
			return $html;
		}

		echo $html;
	}

	/**
	 * A row in a repeater.
	 *
	 * @param array $cells The table cells for this row.
	 * @param array $args Optional arguments for the repeater row.
	 *
	 * @return string|void
	 */
	public function repeater_row( $cells = array(), $args = array() ) {

		$args = wp_parse_args(
			$args,
			array(
				'display'         => false,
				'controls_column' => true,
			)
		);

		$sort_handle = $this->repeater_sort_handle();
		$sort_width  = $this->repeater_icon_width();

		$html = '<tr class="wometa-repeater-row">';

		if ( ! empty( $cells ) ) {
			if ( $sort_handle ) {
				$html .= '<td width="' . esc_attr( $sort_width ) . '" class="wometa-repeater-sort">' . $sort_handle . '</td>';
			}

			foreach ( $cells as $cell ) {
				$html .= $this->repeater_cell( $cell );
			}

			if ( $args['controls_column'] ) {
				$html .= '<td class="' . esc_attr( $this->ns . '-wometa-repeater-controls wometa-repeater-controls' ) . '">';
				$html .= '<button class="wometa-repeater-controls--remove">&ndash;</button>';
				$html .= '<button class="wometa-repeater-controls--add">+</button>';
				$html .= '</td>';
			}
		}

		$html .= '</tr>';

		if ( ! $args['display'] ) {
			return $html;
		}

		echo $html;
	}

	/**
	 * An individual cell in a repeater row.
	 *
	 * @param string $contents The contents of a repeater cell.
	 * @param array  $args Optional arguments of a repeater cell.
	 *
	 * @return string|void
	 */
	public function repeater_cell( $contents = '', $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'display' => false,
			)
		);

		$html = '<td>' . $contents . '</td>';

		if ( ! $args['display'] ) {
			return $html;
		}

		echo $html;
	}
	/**
	 * Creates a repeater table for use in metaboxes.
	 *
	 * @param array $columns An array of columns for this table.
	 * @param array $rows An array of arrays; Individual table cells contained in each row
	 * @param array $args Optional arguments for the repeater table.
	 *
	 * @return string|void
	 */
	public function repeater_table( $columns = array(), $rows = array(), $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'classes'         => array(),
				'display'         => true,
				'width'           => '100%',
				'controls_column' => '',
			)
		);

		$table_start_args            = $args;
		$table_start_args['display'] = false;

		$html = $this->repeater_start( $columns, $table_start_args );

		foreach ( $rows as $cells ) {
			$html .= $this->repeater_row( $cells );
		}

		$html .= $this->repeater_end();

		if ( ! $args['display'] ) {
			return $html;
		}

		echo $html;
	}
}
