<?php
namespace WOAdminFramework;

class WOAdmin {

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

	public function create_tabs_from_settings( $settings, $admin_url, $opt_framework ) {

		$tabs = array();

		foreach ( $settings as $key => $group ) {
			$opt_key = $opt_framework->key( $key );
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

	public function select( $id, $options, $current = null, $default = null ) {
		$name = $this->name( $id );
		$id   = $this->id( $id );

		if ( ! $current ) {
			$current = $default;
		}
		?>
		<select id="<?php esc_attr_e( $id ); ?>" name="<?php esc_attr_e( $name ); ?>">
			<?php foreach ( $options as $key => $value ) : ?>
				<option value="<?php esc_attr_e( $key ); ?>"<?php selected( esc_attr( $key ), $current ); ?>><?php esc_html_e( $value ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	public function settings_page( $admin_title, $admin_action, $admin_url, $settings, $options_framework ) {
		$this->start();
		$this->title( $admin_title );
		$this->form_start( $admin_action );

		settings_errors();

		/**
		 * Tabs
		 */
		$tabs       = $this->create_tabs_from_settings( $settings, $admin_url, $options_framework );
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
}
