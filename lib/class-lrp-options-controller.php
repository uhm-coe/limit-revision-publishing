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


	function current_user_can_publish( $post_type ) {
		global $wp_post_types;

		// Fail if an invalid post_type is passed.
		if ( ! array_key_exists( $post_type, $wp_post_types ) ) {
			return false;
		}

		// Get whether the current user is a member of a restricted role.
		$is_restricted = false;
		$current_user = wp_get_current_user();
		$restricted_roles = $this->get_option( 'roles_to_restrict' );
		foreach ( $restricted_roles as $role_id ) {
			if ( in_array( $role_id, (array)$current_user->roles ) ) {
				$is_restricted = true;
			}
		}

		return current_user_can( $wp_post_types[$post_type]->cap->publish_posts ) && ! $is_restricted;
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
					'select_users_to_notify' => __( 'Select specific users to notify', 'limit-revision-publishing' ),
					'select_roles_to_notify' => __( 'Select roles to notify', 'limit-revision-publishing' ),
					'select_roles_to_restrict' => __( 'Select roles to restrict', 'limit-revision-publishing' ),
				)
			);

			wp_enqueue_script(
				'select2',
				plugins_url( '/vendor/select2-4.0.13/js/select2.min.js', dirname( __FILE__ ) ),
				array( 'jquery' ),
				'20160726'
			);
			wp_enqueue_style(
				'select2',
				plugins_url( '/vendor/select2-4.0.13/css/select2.min.css', dirname( __FILE__ ) ),
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
		add_settings_field(
			'notification_email_subject', // Field ID
			__( 'Email subject', 'limit-revision-publishing' ), // Field title
			array( $this, 'callback__render_field_notification_email_subject' ), // Renderer callback
			$this->options_page_slug, // Options page slug on which to show this field
			'section_notification_settings' // Section slug on which to show this field
		);
		add_settings_field(
			'notification_email_body', // Field ID
			__( 'Email body', 'limit-revision-publishing' ), // Field title
			array( $this, 'callback__render_field_notification_email_body' ), // Renderer callback
			$this->options_page_slug, // Options page slug on which to show this field
			'section_notification_settings' // Section slug on which to show this field
		);

		add_settings_section(
			'section_role_settings', // Section ID (used in 'id' attribute of html tags)
			__( 'Role Settings', 'limit-revision-publishing' ), // Section heading
			array( $this, 'callback__render_section_role_settings' ), // Renderer callback
			$this->options_page_slug // Options page slug on which to show this section
		);
		add_settings_field(
			'roles_to_restrict', // Field ID
			'<span class="dashicons dashicons-lock"></span>' . __( 'Roles to restrict', 'limit-revision-publishing' ), // Field title
			array( $this, 'callback__render_field_roles_to_restrict' ), // Renderer callback
			$this->options_page_slug, // Options page slug on which to show this field
			'section_role_settings' // Section slug on which to show this field
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

		if (
			! array_key_exists( 'notification_email_subject', $lrp_settings ) ||
			strlen( $lrp_settings['notification_email_subject'] ) < 1
		) {
			$lrp_settings['notification_email_subject'] = sprintf(
				/* TRANSLATORS: 1: Shortcode for editor email 2: Shortcode for revision title */
				__( 'Pending revision by %1$s on %2$s', 'limit-revision-publishing' ),
				'[editor_email]',
				'[revision_title]'
			);
		}

		if (
			! array_key_exists( 'notification_email_body', $lrp_settings ) ||
			strlen( $lrp_settings['notification_email_body'] ) < 1
		) {
			$lrp_settings['notification_email_body'] = sprintf(
				/* TRANSLATORS: 1: Shortcode for edit URL 2: Shortcode for revision title 3: Shortcode for editor name 4: Shortcode for editor email */
				__( "A new revision has been submitted for review. Please approve or deny it here:\n%1\$s\n\nTitle: %2\$s\nRevision submitted by: %3\$s <%4\$s>", 'limit-revision-publishing' ),
				'[edit_url]',
				'[revision_title]',
				'[editor_name]',
				'[editor_email]'
			);
		}

		if (
			! array_key_exists( 'roles_to_restrict', $lrp_settings ) ||
			! is_array( $lrp_settings['roles_to_restrict'] )
		) {
			$lrp_settings['roles_to_restrict'] = array();
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
		?><p><?php _e( "Configure the notification email sent when a new revision is submitted for review.", 'limit-revision-publishing' ); ?></p><?php
	}


	function callback__render_field_users_to_notify() {
		$option_name = 'users_to_notify';
		$option = $this->get_option( $option_name );
		$users = get_users( array() );
		?>
		<select id="lrp_settings_<?php echo $option_name; ?>" name="lrp_settings[<?php echo $option_name; ?>][]" multiple="multiple" style="width: 100%;">
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
		$option_name = 'roles_to_notify';
		$option = $this->get_option( $option_name );
		$roles = get_editable_roles();
		?>
		<select id="lrp_settings_<?php echo $option_name; ?>" name="lrp_settings[<?php echo $option_name; ?>][]" multiple="multiple" style="width: 100%;">
			<?php foreach ( $roles as $role_id => $role ) :
				$selected = in_array( $role_id, $option ) ? ' selected="selected"' : '';	?>
				<option value="<?php echo $role_id; ?>"<?php echo $selected; ?>>
					<?php echo $role['name']; ?>
				</option>
			<?php endforeach; ?>
		</select>
		<small><?php _e( 'All users in these roles will receive notification emails.', 'limit-revision-publishing' ); ?></small>
		<?php
	}


	function callback__render_field_notification_email_subject( $args = '' ) {
		$option_name = 'notification_email_subject';
		$option = $this->get_option( $option_name );
		?>
		<input type="text" id="lrp_settings_<?php echo $option_name; ?>" name="lrp_settings[<?php echo $option_name; ?>]" value="<?php echo $option; ?>" style="width:100%;" /><br />
		<small><?php printf(
			/* TRANSLATORS: 1: Shortcode for editor name 2: Shortcode for editor email 3: Shortcode for revision title 4: Shortcode for revision URL 5: Shortcode for edit URL */
			__( 'You can use %1$s, %2$s, %3$s, %4$s, and %5$s shortcodes.', 'limit-revision-publishing' ),
			'<b>[editor_name]</b>',
			'<b>[editor_email]</b>',
			'<b>[revision_title]</b>',
			'<b>[revision_url]</b>',
			'<b>[edit_url]</b>'
		); ?></small>
		<?php
	}


	function callback__render_field_notification_email_body( $args = '' ) {
		$option_name = 'notification_email_body';
		$option = $this->get_option( $option_name );
		wp_editor(
			wpautop( $option ),
			"lrp_settings_{$option_name}",
			array(
				'media_buttons' => false,
				'textarea_name' => "lrp_settings[$option_name]",
				'textarea_rows' => 9,
				'tinymce' => true,
				'teeny' => true,
				'quicktags' => false,
			)
		);
		?>
		<small><?php printf(
			/* TRANSLATORS: 1: Shortcode for editor name 2: Shortcode for editor email 3: Shortcode for revision title 4: Shortcode for revision URL 5: Shortcode for edit URL */
			__( 'You can use %1$s, %2$s, %3$s, %4$s, and %5$s shortcodes.', 'limit-revision-publishing' ),
			'<b>[editor_name]</b>',
			'<b>[editor_email]</b>',
			'<b>[revision_title]</b>',
			'<b>[revision_url]</b>',
			'<b>[edit_url]</b>'
		); ?></small>
		<?php
	}


	function callback__render_section_role_settings() {
		$post_types = get_post_types(	array( 'public' => true ), 'objects' );
		$roles = get_editable_roles();
		$restricted_roles = $this->get_option( 'roles_to_restrict' );
		?>
		<p><?php _e( "Configure roles that should have restricted publishing.", 'limit-revision-publishing' ); ?></p>
		<table class="widefat">
			<thead>
				<tr>
					<th><?php _e( 'Role', 'limit-revision-publishing' ); ?></th>
					<?php foreach ( $post_types as $post_type ) : ?>
						<th><?php echo $post_type->labels->name; ?></th>
					<?php endforeach; ?>
					<th><?php _e( 'Edits Locked Until Approved', 'limit-revision-publishing' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php $row_number = 0;
				foreach ( $roles as $role_id => $role ) :
					$row_number++;
					$is_restricted = in_array( $role_id, $restricted_roles );
					$is_partially_restricted = false;
					$can_publish_all = true;
					$can_publish_none = true;
					?>
					<tr class="<?php echo $row_number % 2 ? 'alternate' : ''; ?>">
						<td><?php echo $role['name']; ?></td>
						<?php foreach ( $post_types as $post_type ) :
							$can_publish = array_key_exists( $post_type->cap->publish_posts, $role['capabilities'] ) && $role['capabilities'][$post_type->cap->publish_posts] == 1;
							$can_edit_published = array_key_exists( $post_type->cap->edit_published_posts, $role['capabilities'] ) && $role['capabilities'][$post_type->cap->edit_published_posts] == 1;
							$is_partially_restricted |= ( $can_edit_published && ! $can_publish );
							$can_publish_all &= $can_edit_published && $can_publish;
							$can_publish_none &= ! ( $can_edit_published && $can_publish );
							?>
							<td>
								<span class="dashicons dashicons-<?php echo $can_publish ? 'yes' : 'no'; ?>"></span>
								<?php if ( $is_restricted ) : ?>
									<strike>Publish</strike>
								<?php else : ?>
									Publish
								<?php endif; ?>
								<br>
								<span class="dashicons dashicons-<?php echo $can_edit_published ? 'yes' : 'no'; ?>"></span>
								Edit Published
							</td>
						<?php endforeach; ?>
						<td>
							<?php if ( $is_restricted ) : ?>
								<span class="dashicons dashicons-lock"></span>
								<?php _e( 'Edits to all published posts must be approved.', 'limit-revision-publishing' ); ?>
							<?php elseif ( $is_partially_restricted ) : ?>
								<span class="dashicons dashicons-unlock partial"></span>
								<?php _e( 'Edits to some published post types must be approved.', 'limit-revision-publishing' ); ?>
							<?php elseif ( ! $can_publish_all && ! $can_publish_none ) : ?>
								<span class="dashicons dashicons-minus"></span>
								<?php _e( 'Can edit and publish some post types, but not others.', 'limit-revision-publishing' ); ?>
							<?php elseif ( $can_publish_all ) : ?>
								<span class="dashicons dashicons-unlock"></span>
								<?php _e( 'Can publish edits to any post type.', 'limit-revision-publishing' ); ?>
							<?php elseif ( $can_publish_none ) : ?>
								<span class="dashicons dashicons-no-alt"></span>
								<?php _e( 'Cannot edit any published posts.', 'limit-revision-publishing' ); ?>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}


	function callback__render_field_roles_to_restrict() {
		$option_name = 'roles_to_restrict';
		$option = $this->get_option( $option_name );
		$roles = get_editable_roles();
		?>
		<select id="lrp_settings_<?php echo $option_name; ?>" name="lrp_settings[<?php echo $option_name; ?>][]" multiple="multiple" style="width: 100%;">
			<?php foreach ( $roles as $role_id => $role ) :
				$selected = in_array( $role_id, $option ) ? ' selected="selected"' : '';	?>
				<option value="<?php echo $role_id; ?>"<?php echo $selected; ?>>
					<?php echo $role['name']; ?>
				</option>
			<?php endforeach; ?>
		</select>
		<small><?php _e( 'All users in these roles will be restricted from publishing revisions on <strong>all</strong> post types.', 'limit-revision-publishing' ); ?></small>
		<?php
	}


}
