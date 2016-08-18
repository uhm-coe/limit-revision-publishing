<?php

class LRP_Revisions_Form_Controller {
	private $options_controller;


	/**
	 * Class constructor.
	 * Register hooks.
	 */
	function __construct( $options_controller = null ) {
		$this->options_controller = $options_controller;

		add_filter( 'user_has_cap',
			array( $this, 'user_has_cap__unprivileged_users_cannot_restore_revisions' ),
			10, 3
		);

		add_action( 'admin_enqueue_scripts',
			array( $this, 'admin_enqueue_scripts__modify_tmpl_revisions_meta' ),
			10, 1
		);
	}


	/**
	 * This function is used to explicitly deny restoring revisions for users that
	 * do not have the publish_posts capability (but may have the
	 * edit_published_posts capability).
	 *
	 * Filter hook: https://codex.wordpress.org/Plugin_API/Filter_Reference/user_has_cap
	 *
	 * @param array $allcaps All the capabilities of the user
	 * @param array $cap     [0] Required capability
	 * @param array $args    [0] Requested capability
	 *                       [1] User ID
	 *                       [2] Associated object ID
	 */
	function user_has_cap__unprivileged_users_cannot_restore_revisions( $allcaps, $cap, $args ) {
		// Bail if we're not restoring a revision.
		if (
			! array_key_exists( 'revision', $_REQUEST ) || intval( $_REQUEST['revision'] ) < 1 ||
			! array_key_exists( 'action', $_REQUEST ) || $_REQUEST['action'] !== 'restore' ||
			$args[0] !== 'edit_post'
		) {
			return $allcaps;
		}

		// Bail if the current user is allowed to publish posts.
		$post_id = $args[2];
		$post_type = get_post_type( $post_id );
		if ( $this->options_controller->current_user_can_publish( $post_type ) ) {
			return $allcaps;
		}

		// The current user is trying to restore a post revision, but does not have
		// the publish_{post_type} capability. Mark their 'edit_post' capability as
		// false for the post they are trying to restore a revision revision of
		// (because /wp-admin/revision.php:37 checks the edit_post capability to
		// determine if the user has the rights to restore a post revision).
		foreach ( $cap as $required_capability ) {
			$allcaps[$required_capability] = false;
		}

		return $allcaps;
	}


	/**
	 * Add script to modify the revisions browser to prevent users without publish
	 * capabilities from restoring a revision.
	 *
	 * Action hook: https://codex.wordpress.org/Plugin_API/Action_Reference/admin_enqueue_scripts
	 *
	 * @param  string $hook_suffix The current admin page.
	 */
	function admin_enqueue_scripts__modify_tmpl_revisions_meta( $hook_suffix ) {
		global $post;

		// If we're viewing the revision browser and the current user doesn't have the
		// publish_{post_type} capability, load the javascript that disables the
		// "Restore This Revision" button.
		if (
			$hook_suffix === 'revision.php' &&
			! $this->options_controller->current_user_can_publish( $post->post_type )
		) {
			wp_enqueue_script(
				'lrp-modify-tmpl-revisions-meta',
				plugins_url( '/js/modify-tmpl-revisions-meta.js', dirname( __FILE__ ) ),
				array( 'jquery' ),
				'20160808'
			);
		}
	}


}
