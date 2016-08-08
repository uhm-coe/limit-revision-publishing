<?php

class LRP_Revisions_Form_Controller {


	/**
	 * Class constructor.
	 * Register hooks.
	 */
	function __construct() {
		add_filter( 'user_has_cap',
			array( $this, 'user_has_cap__unprivileged_users_cannot_restore_revisions' ),
			10, 3
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
		global $wp_post_types;
		$post_id = $args[2];
		$post_type = get_post_type( $post_id );
		if ( current_user_can( $wp_post_types[$post_type]->cap->publish_posts ) ) {
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


}
