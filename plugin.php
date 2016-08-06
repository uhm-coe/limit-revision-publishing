<?php
/**
 * Plugin Name: Limit Revision Publishing
 * Description: Revisions made by users without the publish_{post_type}
 * capability will be queued, and the original post will remain published. The
 * All Posts view in the WordPress Dashboard will contain a column indicating
 * posts with unpublished revisions. Notification emails can be sent to a subset
 * of users whenever a new revision has been submitted so users with elevated
 * privileges can publish them.
 * Author: Paul Ryan
 * Author URI: https://dcdc.coe.hawaii.edu
 * Text Domain: limit-revision-publishing
 * Domain Path: /languages
 * Version: 0.1.0
 * Plugin URI: https://wordpress.org/plugins/limit-revision-publishing
 * License: GPL2+
 */

/**
 * Include plugin classes.
 */
if ( ! class_exists( 'LRP_Controller' ) ) {
	require_once dirname( __FILE__ ) . '/lib/class-lrp-controller.php';
}
if ( ! class_exists( 'LRP_Sortable_Column_Controller' ) ) {
	require_once dirname( __FILE__ ) . '/lib/class-lrp-sortable-column-controller.php';
}
if ( ! class_exists( 'LRP_Edit_Form_Controller' ) ) {
	require_once dirname( __FILE__ ) . '/lib/class-lrp-edit-form-controller.php';
}
if ( ! class_exists( 'LRP_Options_Controller' ) ) {
	require_once dirname( __FILE__ ) . '/lib/class-lrp-options-controller.php';
}

// Instantiate plugin.
if ( class_exists( 'LRP_Controller' ) ) {
	$limit_revision_publishing = new LRP_Controller();
}

function lrp_debug( $allcaps, $cap, $args ) {
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
add_filter( 'user_has_cap', 'lrp_debug', 10, 3 );
