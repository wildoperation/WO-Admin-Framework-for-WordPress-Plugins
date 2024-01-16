<?php
namespace WOAdminFramework;

use WOAdminFramework\WOOptions;

class WOSettings extends WOOPtions {

	protected $text_domain;
	public $woforms;

	public function __construct( $text_domain, $ns ) {
		parent::__construct( $ns );

		$this->text_domain = $text_domain;
		$this->woforms     = new WOForms( $text_domain );
	}

	private function id( $id ) {
		if ( is_array( $id ) ) {
			return $id[ array_key_first( $id ) ];
		}

		return $id;
	}

	private function name( $id ) {
		if ( is_array( $id ) ) {
			$key = array_key_first( $id );
			return $key . '[' . $id[ $key ] . ']';
		}

		return $id;
	}

	public function start() {
		?>
		<div class="wrap">
		<?php
	}

	public function end() {
		?>
		</div>
		<?php
	}

	public function title( $title ) {
		?>
		<h2><?php esc_html_e( $title ); ?></h2>
		<?php
	}

	public function form_start( $action, $method = 'post' ) {
		?>
		<form method="<?php esc_attr_e( $method ); ?>" action="options.php">
		<?php
	}

	public function form_end() {
		?>
		</form>
		<?php
	}

	private function get_tab_url( $tab_key, $admin_url ) {
		return add_query_arg(
			array(
				'tab' => $tab_key,
			),
			$admin_url
		);
	}

	public function create_tabs_from_settings( $settings, $admin_url ) {

		$tabs = array();

		foreach ( $settings as $key => $group ) {
			$opt_key = $this->key( $key );
			$tabs[]  = array(
				'key'  => $opt_key,
				'text' => $group['title'],
				'url'  => $this->get_tab_url( $opt_key, $admin_url ),
			);
		}

		return $tabs;
	}

	public function display_tabs( $tabs = array(), $active_tab = null ) {
		if ( empty( $tabs ) ) {
			return;
		}

		if ( $active_tab === null ) {
			$active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : $tabs[0]['key'];
		}
		?>
		<h2 class="nav-tab-wrapper">
			<?php foreach ( $tabs as $tab ) : ?>
				<a href="<?php echo esc_url( $tab['url'] ); ?>" class="nav-tab<?php echo $active_tab === $tab['key'] ? ' nav-tab-active' : ''; ?>"><?php esc_html_e( $tab['text'] ); ?></a>
			<?php endforeach; ?>
		</h2>
		<?php
	}

	public function settings_page( $admin_title, $admin_action, $admin_url, $settings ) {
		$this->start();
		$this->title( $admin_title );
		$this->form_start( $admin_action );

		settings_errors();

		/**
		 * Tabs
		 */
		$tabs       = $this->create_tabs_from_settings( $settings, $admin_url );
		$active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : $tabs[0]['key'];
		$this->display_tabs( $tabs, $active_tab );

		foreach ( $tabs as $tab ) {
			if ( $active_tab !== $tab['key'] ) {
				continue;
			}

			settings_fields( $tab['key'] );
			do_settings_sections( $tab['key'] );
		}

		/**
		 * End page
		 */
		submit_button();
		$this->form_end();
		$this->end();
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

	public function label( $id, $text, $args = array() ) {
		$id = $this->id( $id );
		return $this->woforms->label( $id, $text, $args );
	}

	public function select( $id, $options, $current_value = null, $args = array() ) {
		$name       = $this->name( $id );
		$args['id'] = $this->id( $id );

		if ( ! $current_value && isset( $args['default'] ) ) {
			$current_value = $args['default'];
		}

		return $this->woforms->select( $name, $options, $current_value, $args );
	}

	public function checkbox( $id, $current_value, $checked_value = 1, $args = array() ) {
		$name       = $this->name( $id );
		$args['id'] = $this->id( $id );

		return $this->woforms->checkbox( $name, $current_value, $checked_value, $args );
	}

	public function textarea( $id, $value, $args = array() ) {
		$name       = $this->name( $id );
		$args['id'] = $this->id( $id );

		return $this->woforms->textarea( $name, $value, $args );
	}

	public function input( $id, $value, $type = 'text', $args = array() ) {
		$name       = $this->name( $id );
		$args['id'] = $this->id( $id );

		return $this->woforms->input( $name, $value, $type, $args );
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
}
