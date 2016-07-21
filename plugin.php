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

// Instantiate plugin.
if ( class_exists( 'LRP_Controller' ) ) {
	$limit_revision_publishing = new LRP_Controller();
}


function lrp_debug() {
	// Role: OHR Content Editor
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
