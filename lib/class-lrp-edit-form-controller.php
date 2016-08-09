<?php

class LRP_Edit_Form_Controller {


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
	 *
	 * Action hook: https://codex.wordpress.org/Plugin_API/Action_Reference/admin_enqueue_scripts
	 *
	 * @param  string $hook_suffix The current admin page.
	 */
	function admin_enqueue_scripts__modify_publish_metabox( $hook_suffix ) {
		global $post, $wp_post_types;

		// If we're editing a post and the current user doesn't have the
		// publish_{post_type} capability, load the javascript that modifies the
		// publish metabox.
		if (
			$hook_suffix === 'post.php' &&
			! current_user_can( $wp_post_types[$post->post_type]->cap->publish_posts )
		) {
			wp_enqueue_script(
				'lrp-modify-publish-metabox',
				plugins_url( '/js/modify-publish-metabox.js', dirname( __FILE__ ) ),
				array( 'jquery' ),
				'20160714'
			);
			wp_localize_script(
				'lrp-modify-publish-metabox',
				'lrp_translations',
				array(
					'update' => __( 'Update', 'limit-revision-publishing' ),
					'submit_for_review' => __( 'Submit for Review', 'limit-revision-publishing' ),
				)
			);
		}
	}


	/**
	 * Warn users with the publish capability if they are editing a post with
	 * pending revisions. Provide them a link to the revisions browser.
	 *
	 * Action hook: https://codex.wordpress.org/Plugin_API/Action_Reference/admin_notices
	 */
	function admin_notices__warn_when_editing_post_with_pending_revision() {
		global $post, $wp_post_types;
		$screen = get_current_screen();

		if (
			$screen->base === 'post' &&
			current_user_can( $wp_post_types[$screen->post_type]->cap->publish_posts ) &&
			$pending_revision_id = intval( get_post_meta( $post->ID, 'lrp_pending_revision', true ) )
		) {
			?><div class="notice notice-warning"><p><span class="dashicons dashicons-warning" style="color: #ffb900; vertical-align: sub;"></span> A revision is pending. Please approve or deny the revision before making further changes. <a href="<?php echo admin_url( 'revision.php?revision=' . $pending_revision_id ); ?>" class="button">View changes</a></p></div><?php
		}
	}


	/**
	 * Load pending revision contents when unprivileged users try to edit a post
	 * with pending revisions.
	 *
	 * Action hook: https://developer.wordpress.org/reference/hooks/edit_form_top/
	 *
	 * @param  WP_Post $post_object Post object.
	 */
	function edit_form_top__unprivileged_users_see_pending_revision( $post_object ) {
		global $post, $wp_post_types;

		// If a user without publish_{post_type} capability is editing a page and
		// there is a pending revision, load the contents of that revision to edit.
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


	/**
	 * Load pending revision contents (for ACF fields) when unprivileged users try
	 * to edit a post with pending revisions.
	 *
	 * Filter hook: https://www.advancedcustomfields.com/resources/acfload_value/
	 *
	 * @param  string $value The value of the field as found in the database.
	 * @param  int $post_id The post ID which the value was loaded from.
	 * @param  array $field The field array.
	 * @return string $value
	 */
	function acf_load_value__unprivileged_users_see_pending_revision( $value, $post_id, $field ) {
		global $post, $wp_post_types;

		// If a user without publish_{post_type} capability is editing a page and
		// there is a pending revision, load the contents of that revision to edit.
		if (
			! current_user_can( $wp_post_types[$post->post_type]->cap->publish_posts ) &&
			$pending_revision_id = intval( get_post_meta( $post_id, 'lrp_pending_revision', true ) )
		) {
			$value = get_field( $field['key'], $pending_revision_id );
		}

		return $value;
	}


}
