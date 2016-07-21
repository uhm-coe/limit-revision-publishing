<?php

class LRP_Edit_Form_Controller {
	public $textdomain = 'limit-revision-publishing';


	/**
	 * Class constructor.
	 * Register hooks.
	 */
	function __construct() {
		add_action( 'admin_enqueue_scripts',
			array( $this, 'admin_enqueue_scripts__modify_publish_metabox' ),
			10, 1
		);

		add_action( 'admin_notices',
			array( $this, 'admin_notices__warn_when_editing_post_with_pending_revision' ),
			10, 1
		);

		add_action( 'edit_form_top',
			array( $this, 'edit_form_top__unprivileged_users_see_pending_revision' ),
			10, 1
		);

		add_filter( 'acf/load_value',
			array( $this, 'acf_load_value__unprivileged_users_see_pending_revision' ),
			10, 3
		);
	}


	/**
	 * Add script to modify the publish metabox to allow users without publish
	 * capabilities to submit a revision for approval.
	 * Action hook: https://codex.wordpress.org/Plugin_API/Action_Reference/admin_enqueue_scripts
	 */
	function admin_enqueue_scripts__modify_publish_metabox( $hook ) {
		global $post, $wp_post_types;

		// If we're editing a post and the current user doesn't have the
		// publish_{post_type} capability, load the javascript that modifies the
		// publish metabox.
		if (
			$hook === 'post.php' &&
			! current_user_can( $wp_post_types[$post->post_type]->cap->publish_posts )
		) {
			wp_enqueue_script(
				'lrp-modify-publish-metabox',
				plugins_url( '/js/modify-publish-metabox.js', dirname( __FILE__ ) ),
				array(),
				'20160714'
			);
			wp_localize_script(
				'lrp-modify-publish-metabox',
				'lrp_L10n',
				array(
					'update' => __( 'Update', $this->textdomain ),
				)
			);
		}
	}


	// Warn users with the publish capability if they are editing a post with
	// pending revisions. Provide them a link to the revisions browser.
	function admin_notices__warn_when_editing_post_with_pending_revision() {
		global $post, $wp_post_types;
		$screen = get_current_screen();

		if (
			$screen->base === 'post' &&
			current_user_can( $wp_post_types[$screen->post_type]->cap->publish_posts ) &&
			$pending_revision_id = intval( get_post_meta( $post->ID, 'lrp_pending_revision', true ) )
		) {
			?><div class="notice notice-warning"><p><span class="dashicons dashicons-warning" style="color: red;"></span> A revision is pending. Please <a href="<?php echo admin_url( 'revision.php?revision=' . $pending_revision_id ); ?>">approve or deny the revision</a> before making further changes.</p></div><?php
		}
	}


	// Load pending revision contents when unprivileged users try to edit a post
	// with pending revisions.
	function edit_form_top__unprivileged_users_see_pending_revision( $post_object ) {
		global $post, $wp_post_types;

		// If a user with publish_{post_type} capability is editing a page and there
		// is a pending revision, load the contents of that revision to edit.
		if (
			! current_user_can( $wp_post_types[$post->post_type]->cap->publish_posts ) &&
			$pending_revision_id = intval( get_post_meta( $post->ID, 'lrp_pending_revision', true ) )
		) {
			$pending_revision = wp_get_post_revision( $pending_revision_id );
			$post->post_title = $pending_revision->post_title;
			$post->post_content = $pending_revision->post_content;
			$post->post_excerpt = $pending_revision->post_excerpt;
			$post->post_content_filtered = $pending_revision->post_content_filtered;
		}
	}


	// Load pending revision contents (for ACF fields) when unprivileged users try
	// to edit a post with pending revisions.
	function acf_load_value__unprivileged_users_see_pending_revision( $value, $post_id, $field ) {
		global $wp_post_types;

		// If a user with publish_{post_type} capability is editing a page and there
		// is a pending revision, load the contents of that revision to edit.
		if (
			! current_user_can( $wp_post_types[$post->post_type]->cap->publish_posts ) &&
			$pending_revision_id = intval( get_post_meta( $post_id, 'lrp_pending_revision', true ) )
		) {
			$value = get_field( $field['key'], $pending_revision_id );
		}

		return $value;
	}


}
