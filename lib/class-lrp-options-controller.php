<?php

class LRP_Options_Controller {
	public $textdomain = 'limit-revision-publishing';
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
	 * Create the admin menu item for the plugin options.
	 *
	 * Action hook: https://developer.wordpress.org/reference/hooks/admin_menu/
	 */
	function admin_menu__create_plugin_options_menu_item() {
		add_options_page(
			__( 'Limit Revision Publishing', $this->textdomain ), // Page title
			__( 'Limit Revision Publishing', $this->textdomain ), // Menu title
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
			__( 'Notification Settings', $this->textdomain ), // Section heading
			array( $this, 'callback__render_section_notification_settings' ), // Renderer callback
			$this->options_page_slug // Options page slug on which to show this section
		);

		add_settings_field(
			'users_to_notify', // Field ID
			__( 'Users to notify', $this->textdomain ), // Field title
			array( $this, 'callback__render_field_users_to_notify' ), // Renderer callback
			$this->options_page_slug, // Options page slug on which to show this field
			'section_notification_settings' // Section slug on which to show this field
		);

		add_settings_field(
			'roles_to_notify', // Field ID
			__( 'Roles to notify', $this->textdomain ), // Field title
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
			<h1><?php _e( "Limit Revision Publishing", $this->textdomain ); ?></h1>
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
		?><p><?php _e( "When a new revision is submitted for review, send a notification email to the following people.", $this->textdomain ); ?></p><?php
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
