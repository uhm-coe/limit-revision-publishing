<?php

class LRP_Edit_Form_Controller {
	private $options_controller;


	/**
	 * Class constructor.
	 * Register hooks.
	 */
	function __construct( $options_controller = null ) {
		$this->options_controller = $options_controller;

		add_action( 'admin_enqueue_scripts',
			array( $this, 'admin_enqueue_scripts__modify_publish_metabox' ),
			10, 1
		);

		add_action( 'admin_notices',
			array( $this, 'admin_notices__warn_when_editing_post_with_pending_revision' ),
			10, 1
		);

		add_action( 'edit_form_top',
			array( $this, 'edit_form_top__users_see_pending_revision' ),
			10, 1
		);

		add_filter( 'acf/load_value',
			array( $this, 'acf_load_value__users_see_pending_revision' ),
			10, 3
		);

		add_action( 'wp_ajax_lrp_discard_revision',
			array( $this, 'wp_ajax__lrp_discard_revision' ),
			10, 1
		);
	}


	/**
	 * Add script to modify the publish metabox to allow users without publish
	 * capabilities to submit a revision for approval, and privileged users the
	 * ability to Publish or Discard pending revisions.
	 *
	 * Action hook: https://codex.wordpress.org/Plugin_API/Action_Reference/admin_enqueue_scripts
	 *
	 * @param  string $hook_suffix The current admin page.
	 */
	function admin_enqueue_scripts__modify_publish_metabox( $hook_suffix ) {
		global $post;

		// Only add scripts if we're on the Edit Post screen.
		if ( $hook_suffix === 'post.php' ) {

			if ( ! $this->options_controller->current_user_can_publish( $post->post_type ) ) {
				// If the current user doesn't have the publish_{post_type} capability,
				// load the javascript that modifies the Publish button in the metabox.
				wp_enqueue_script(
					'lrp-modify-publish-metabox-unprivileged',
					plugins_url( '/js/modify-publish-metabox-unprivileged.js', dirname( __FILE__ ) ),
					array( 'jquery' ),
					'20160714'
				);
				wp_localize_script(
					'lrp-modify-publish-metabox-unprivileged',
					'lrp_translations',
					array(
						'update' => __( 'Update', 'limit-revision-publishing' ),
						'submit_for_review' => __( 'Submit for Review', 'limit-revision-publishing' ),
					)
				);

			} else if ( $pending_revision_id = intval( get_post_meta( $post->ID, 'lrp_pending_revision', true ) ) ) {
				// If the current user has the publish_{post_type} capability and the
				// current post has a pending revision, load the javascript that shows
				// the Publish Revision and Discard buttons.
				wp_enqueue_script(
					'lrp-modify-publish-metabox-privileged',
					plugins_url( '/js/modify-publish-metabox-privileged.js', dirname( __FILE__ ) ),
					array( 'jquery' ),
					'20160714'
				);
				wp_localize_script(
					'lrp-modify-publish-metabox-privileged',
					'lrp_data',
					array(
						'ajaxurl' => admin_url( 'admin-ajax.php' ),
						'nonce' => wp_create_nonce( 'lrp_nonce' ),
						'post_id' => $post->ID,
						'pending_revision_id' => $pending_revision_id,
					)
				);
				wp_localize_script(
					'lrp-modify-publish-metabox-privileged',
					'lrp_translations',
					array(
						'publish_revision' => __( 'Publish Revision', 'limit-revision-publishing' ),
						'discard_revision' => __( 'Discard', 'limit-revision-publishing' ),
					)
				);
			}

		}
	}


	/**
	 * Warn users with the publish capability if they are editing a post with
	 * pending revisions. Provide them a link to the revisions browser.
	 *
	 * Action hook: https://codex.wordpress.org/Plugin_API/Action_Reference/admin_notices
	 */
	function admin_notices__warn_when_editing_post_with_pending_revision() {
		global $post;
		$screen = get_current_screen();

		if (
			$screen->base === 'post' &&
			$this->options_controller->current_user_can_publish( $screen->post_type ) &&
			$pending_revision_id = intval( get_post_meta( $post->ID, 'lrp_pending_revision', true ) )
		) {
			?><div class="notice notice-warning"><p><span class="dashicons dashicons-warning" style="color: #ffb900; vertical-align: sub;"></span> <?php _e( 'You are editing a pending revision. Please publish or discard it before making further changes.', 'limit-revision-publishing'); ?> <a href="<?php echo admin_url( 'revision.php?revision=' . $pending_revision_id ); ?>" class="button"><?php _e( 'Compare versions', 'limit-revision-publishing' ); ?></a></p></div><?php
		}
	}


	/**
	 * Load pending revision contents when any user tries to edit a post with a
	 * pending revision.
	 *
	 * Action hook: https://developer.wordpress.org/reference/hooks/edit_form_top/
	 *
	 * @param  WP_Post $post Post object.
	 */
	function edit_form_top__users_see_pending_revision( $post ) {
		// If a revision is pending, load the contents of that revision to edit.
		$pending_revision_id = intval( get_post_meta( $post->ID, 'lrp_pending_revision', true ) );
		if ( $pending_revision_id ) {
			$pending_revision = wp_get_post_revision( $pending_revision_id );
			$post->post_title = $pending_revision->post_title;
			$post->post_content = $pending_revision->post_content;
			$post->post_excerpt = $pending_revision->post_excerpt;
			$post->post_content_filtered = $pending_revision->post_content_filtered;
		}
	}


	/**
	 * Load pending revision contents (for ACF fields) when any user tries to edit
	 * a post with a pending revision.
	 *
	 * Filter hook: https://www.advancedcustomfields.com/resources/acf-load_value/
	 *
	 * @param  string $value The value of the field as found in the database.
	 * @param  int $post_id The post ID which the value was loaded from.
	 * @param  array $field The field array.
	 * @return string $value
	 */
	function acf_load_value__users_see_pending_revision( $value, $post_id, $field ) {
		// Only show pending revision content when editing a post.
		if ( is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			// If a revision is pending, load the contents of that revision to edit.
			$pending_revision_id = intval( get_post_meta( $post_id, 'lrp_pending_revision', true ) );
			if ( $pending_revision_id > 0 ) {
				$value = acf_get_metadata( $pending_revision_id, $field['name'] );
				$value = apply_filters( "acf/load_value", $value, $pending_revision_id, $field );
			}
		}

		return $value;
	}


	/**
	 * Ajax handler fired when user clicks "Discard Revision" in the Publish
	 * metabox on the Edit Post screen.
	 *
	 * Action hook: https://codex.wordpress.org/Plugin_API/Action_Reference/wp_ajax_(action)
	 */
	function wp_ajax__lrp_discard_revision() {
		// Nonce check.
		if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'lrp_nonce' ) ) {
			die( '' );
		}

		// Fail if invalid post ID provided for the revision we're trying to discard.
		$post_id = array_key_exists( 'post_id', $_REQUEST ) ? intval( $_REQUEST['post_id'] ) : 0;
		if ( $post_id < 1 ) {
			die( '' );
		}

		// Fail if current user doesn't have permission to publish the post.
		$post_type = get_post_type( $post_id );
		if ( ! $this->options_controller->current_user_can_publish( $post_type ) ) {
			die( '' );
		}

		$success = delete_post_meta( $post_id, 'lrp_pending_revision' );

		if ( $success ) {
			$message = __( 'Revision discarded.', 'limit-revision-publishing' );
		} else {
			$message = __( 'Failed to delete the pending revision flag.', 'limit-revision-publishing' );
		}

		// Respond to ajax call.
		$response = array(
			'success' => $success,
			'message' => $message,
		);
		header( 'Content-Type: application/json' );
		echo json_encode( $response );
		exit;
	}


}
