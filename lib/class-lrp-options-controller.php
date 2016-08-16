<?php

class LRP_Options_Controller {
	public $options_page_slug = 'limit-revision-publishing';
	public $lrp_settings = array();

	// TODO: options page:
	// * use select2.js for role/users to email when a revision is created.
	// * parse roles, show which category they fall into (edit_published_x but not publish_x means can only submit revisions).

	/**
	 * Class constructor.
	 * Register hooks.
	 */
	function __construct() {
		add_action( 'admin_enqueue_scripts',
			array( $this, 'admin_enqueue_scripts__add_options_styles' ),
			10, 1
		);

		add_action( 'admin_enqueue_scripts',
			array( $this, 'admin_enqueue_scripts__add_select2_for_plugin_options' ),
			10, 1
		);

		add_action( 'admin_menu',
			array( $this, 'admin_menu__create_plugin_options_menu_item' ),
			10, 1
		);

		add_action( 'admin_init',
			array( $this, 'admin_init__create_plugin_options_settings_fields' ),
			10, 1
		);
	}


	function get_option( $option ) {
		$option_value = '';

		if ( empty( $this->lrp_settings ) ) {
			$this->lrp_settings = get_option( 'lrp_settings' );
			$this->lrp_settings = $this->callback__sanitize_lrp_settings( $this->lrp_settings );
		}

		if ( array_key_exists( $option, $this->lrp_settings ) ) {
			$option_value = $this->lrp_settings[$option];
		}

		return $option_value;
	}


	/**
	 * Add custom styles for options page.
	 *
	 * Action hook: https://codex.wordpress.org/Plugin_API/Action_Reference/admin_enqueue_scripts
	 *
	 * @param  string $hook_suffix The current admin page.
	 */
	function admin_enqueue_scripts__add_options_styles( $hook_suffix ) {
		// Only load script on this plugin's options page.
		if ( $hook_suffix === "settings_page_{$this->options_page_slug}" ) {
			wp_enqueue_style(
				'lrp-options-styles',
				plugins_url( '/css/styles-lrp-options.css', dirname( __FILE__ ) ),
				array(),
				'20160816'
			);
		}
	}


	/**
	 * Add script for select2 so we can use its multi-value select for choosing
	 * users and roles to notify when a revision is awaiting approval.
	 *
	 * Action hook: https://codex.wordpress.org/Plugin_API/Action_Reference/admin_enqueue_scripts
	 *
	 * @param  string $hook_suffix The current admin page.
	 */
	function admin_enqueue_scripts__add_select2_for_plugin_options( $hook_suffix ) {
		// Only load script on this plugin's options page.
		if ( $hook_suffix === "settings_page_{$this->options_page_slug}" ) {
			wp_enqueue_script(
				'lrp-add-select2-to-options',
				plugins_url( '/js/add-select2-to-options.js', dirname( __FILE__ ) ),
				array( 'select2', 'jquery' ),
				'20160726'
			);
			wp_localize_script(
				'lrp-add-select2-to-options',
				'lrp_translations',
				array(
					'select_users_to_notify' => __( 'Select users to notify', 'limit-revision-publishing' ),
					'select_roles_to_notify' => __( 'Select roles to notify', 'limit-revision-publishing' ),
				)
			);

			wp_enqueue_script(
				'select2',
				plugins_url( '/vendor/select2-4.0.3/js/select2.min.js', dirname( __FILE__ ) ),
				array( 'jquery' ),
				'20160726'
			);
			wp_enqueue_style(
				'select2',
				plugins_url( '/vendor/select2-4.0.3/css/select2.min.css', dirname( __FILE__ ) ),
				array(),
				'20160726'
			);
		}
	}


	/**
	 * Create the admin menu item for the plugin options.
	 *
	 * Action hook: https://developer.wordpress.org/reference/hooks/admin_menu/
	 */
	function admin_menu__create_plugin_options_menu_item() {
		add_options_page(
			__( 'Limit Revision Publishing', 'limit-revision-publishing' ), // Page title
			__( 'Limit Revision Publishing', 'limit-revision-publishing' ), // Menu title
			'manage_options', // Capability required
			$this->options_page_slug, // Menu slug
			array( $this, 'callback__render_plugin_options_page' ) // Renderer callback
		);
	}


	/**
	 * Create plugin settings fields and sections.
	 *
	 * Action hook: https://developer.wordpress.org/reference/hooks/admin_init/
	 */
	function admin_init__create_plugin_options_settings_fields() {
		register_setting(
			'lrp_settings_group', // Settings group name
			'lrp_settings', // Option name
			array( $this, 'callback__sanitize_lrp_settings' ) // Sanitizer callback
		);

		add_settings_section(
			'section_notification_settings', // Section ID (used in 'id' attribute of html tags)
			__( 'Notification Settings', 'limit-revision-publishing' ), // Section heading
			array( $this, 'callback__render_section_notification_settings' ), // Renderer callback
			$this->options_page_slug // Options page slug on which to show this section
		);

		add_settings_field(
			'users_to_notify', // Field ID
			__( 'Users to notify', 'limit-revision-publishing' ), // Field title
			array( $this, 'callback__render_field_users_to_notify' ), // Renderer callback
			$this->options_page_slug, // Options page slug on which to show this field
			'section_notification_settings' // Section slug on which to show this field
		);

		add_settings_field(
			'roles_to_notify', // Field ID
			__( 'Roles to notify', 'limit-revision-publishing' ), // Field title
			array( $this, 'callback__render_field_roles_to_notify' ), // Renderer callback
			$this->options_page_slug, // Options page slug on which to show this field
			'section_notification_settings' // Section slug on which to show this field
		);
	}


	function callback__sanitize_lrp_settings( $lrp_settings ) {
		if ( ! is_array( $lrp_settings ) ) {
			$lrp_settings = array();
		}

		if (
			! array_key_exists( 'users_to_notify', $lrp_settings ) ||
			! is_array( $lrp_settings['users_to_notify'] )
		) {
			$lrp_settings['users_to_notify'] = array();
		}

		if (
			! array_key_exists( 'roles_to_notify', $lrp_settings ) ||
			! is_array( $lrp_settings['roles_to_notify'] )
		) {
			$lrp_settings['roles_to_notify'] = array();
		}

		return $lrp_settings;
	}


	function callback__render_plugin_options_page() {
		?><div class="wrap">
			<h1><?php _e( "Limit Revision Publishing", 'limit-revision-publishing' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'lrp_settings_group' );
				do_settings_sections( $this->options_page_slug );
				submit_button();
				?>
			</form>
		</div><?php
	}


	function callback__render_section_notification_settings() {
		?><p><?php _e( "When a new revision is submitted for review, send a notification email to the following people.", 'limit-revision-publishing' ); ?></p><?php
	}


	function callback__render_field_users_to_notify() {
		$option = $this->get_option( 'users_to_notify' );
		$users = get_users( array() ); ?>
		<select id="lrp_settings_users_to_notify" name="lrp_settings[users_to_notify][]" multiple="multiple" style="width: 100%;">
			<?php	foreach ( (array)$users as $user ) :
				$selected = in_array( $user->ID, $option ) ? ' selected="selected"' : ''; ?>
				<option value="<?php echo $user->ID; ?>"<?php echo $selected; ?>>
					<?php echo $user->display_name; ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}


	function callback__render_field_roles_to_notify() {
		$option = $this->get_option( 'roles_to_notify' );
		$roles = get_editable_roles(); ?>
		<select id="lrp_settings_roles_to_notify" name="lrp_settings[roles_to_notify][]" multiple="multiple" style="width: 100%;">
			<?php foreach ( $roles as $name => $role ) :
				$selected = in_array( $name, $option ) ? ' selected="selected"' : '';	?>
				<option value="<?php echo $name; ?>"<?php echo $selected; ?>>
					<?php echo $role['name']; ?>
				</option>
			<?php endforeach; ?>
		</select>
		<?php
	}


}
