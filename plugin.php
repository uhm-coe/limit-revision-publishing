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
 * Version: 0.1.0
 * Plugin URI: https://wordpress.org/plugins/limit-revision-publishing
 * License: GPL2+
 */

/**
 * LRP_Controller class.
 */
if ( ! class_exists( 'LRP_Controller' ) ) {
	require_once dirname( __FILE__ ) . '/lib/class-lrp-controller.php';
}


// If the current user doesn't have the publish_{post_type} capability and
// we're saving ACF fields on the main post, revert the changes to the
// existing value. Note: ACF field updates on the revision ID will go through.
// A more privileged user will have to approve the revision to make the
// changes apply to the main post ID.
function revert_acf_update_if_unprivileged( $value, $post_id, $field ) {
	global $wp_post_types;

	$post_type = get_post_type( $post_id );
	$publish_capability = $wp_post_types[$post_type]->cap->publish_posts;
	if (
		! current_user_can( $publish_capability ) &&
		! wp_is_post_revision( $post_id ) &&
		get_post_status( $post_id ) === 'publish'
	) {
		// Reset to the existing field value.
		$value = get_field( $field['key'], $post_id );
	} else if (	wp_is_post_revision( $post_id ) && isset( $GLOBALS['creating_unchanged_revision_after_changed_revision'] ) ) {
		// We are creating the "unchanged" revision after the changed revision, so
		// make sure it gets all the original, unchanged ACF field values.
		$parent_post_id = wp_get_post_parent_id( $post_id );
		$value = get_field( $field['key'], $parent_post_id );
	}

	return $value;
}
add_filter( 'acf/update_value', 'revert_acf_update_if_unprivileged', 10, 3 );


// If the current user doesn't have the publish_{post_type} capability and
// we're saving the main post (not a revision), revert the changes to the
// existing value. Note: Updates to the revision ID will go through.
// A more privileged user will have to approve the revision to make the
// changes apply to the main post ID.
function revert_update_if_unprivileged( $post_id, $post_after, $post_before ) {
	global $wp_post_types;

	$post_type = get_post_type( $post_id );
	$publish_capability = $wp_post_types[$post_type]->cap->publish_posts;
	if (
		! current_user_can( $publish_capability ) &&
		! wp_is_post_revision( $post_id ) &&
		get_post_status( $post_id ) === 'publish'
	) {
		// Unhook update functions so they don't loop infinitely.
		remove_action( 'post_updated', 'revert_update_if_unprivileged' );

		// Make a new revision with the original post.
		// Flag the creation of this revision so our ACF update handler knows to use
		// the original, unchanged field value(s) for this revision.
		$GLOBALS['creating_unchanged_revision_after_changed_revision'] = true;
		wp_update_post( $post_before );
		unset( $GLOBALS['creating_unchanged_revision_after_changed_revision'] );

		// Rehook update functions.
		add_action( 'post_updated', 'revert_update_if_unprivileged', 10, 3 );
	}

}
add_action( 'post_updated', 'revert_update_if_unprivileged', 10, 3 );


function lrp_debug() {
	// Role: Instructional Designer (Based on Editor + Admin (Some admin traits))
	remove_role( 'ohr_content_editor' );
	add_role( 'ohr_content_editor', 'OHR Content Editor', array(
		// Administrator capabilities:
		// 'update_core' => true,
		// 'manage_options' => true,
		// 'edit_dashboard' => true,
		// 'install_plugins' => true,
		// 'activate_plugins' => true,
		// 'update_plugins' => true,
		// 'edit_plugins' => true,
		// 'delete_plugins' => true,
		// 'install_themes' => true,
		// 'switch_themes' => true,
		// 'update_themes' => true,
		// 'edit_themes' => true,
		// 'delete_themes' => true,
		// 'edit_theme_options' => true,
		// 'create_users' => true,
		// 'list_users' => true,
		// 'edit_users' => true,
		// 'promote_users' => true,
		// 'remove_users' => true,
		// 'delete_users' => true,
		// 'edit_files' => true,
		// 'export' => true,
		// 'import' => true,
		// Editor capabilities:
		'unfiltered_html' => true,
		// 'manage_categories' => true,
		// 'manage_links' => true,
		// 'moderate_comments' => true,
		// 'edit_pages' => true,
		// 'delete_pages' => true,
		// 'publish_pages' => true,
		// 'edit_published_pages' => true,
		// 'delete_published_pages' => true,
		// 'edit_others_pages' => true,
		// 'delete_others_pages' => true,
		// 'read_private_pages' => true,
		// 'edit_private_pages' => true,
		// 'delete_private_pages' => true,
		'edit_others_posts' => true,
		// 'delete_others_posts' => true,
		// 'read_private_posts' => true,
		// 'edit_private_posts' => true,
		// 'delete_private_posts' => true,
		// Author capabilities:
		'upload_files' => true,
		// 'publish_posts' => true,
		'edit_published_posts' => true,
		// 'delete_published_posts' => true,
		// Contributor capabilities:
		'edit_posts' => true,
		'delete_posts' => true,
		// Subscriber capabilities:
		'read' => true,
	));
}
add_action( 'init', 'lrp_debug' );
