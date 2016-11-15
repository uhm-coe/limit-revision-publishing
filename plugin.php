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
 * Plugin URI: https://wordpress.org/plugins/limit-revision-publishing
 * Text Domain: limit-revision-publishing
 * Domain Path: /languages
 * Version: 1.1.3
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
if ( ! class_exists( 'LRP_Filter_All_Posts_Controller' ) ) {
	require_once dirname( __FILE__ ) . '/lib/class-lrp-filter-all-posts-controller.php';
}
if ( ! class_exists( 'LRP_Edit_Form_Controller' ) ) {
	require_once dirname( __FILE__ ) . '/lib/class-lrp-edit-form-controller.php';
}
if ( ! class_exists( 'LRP_Revisions_Form_Controller' ) ) {
	require_once dirname( __FILE__ ) . '/lib/class-lrp-revisions-form-controller.php';
}
if ( ! class_exists( 'LRP_Options_Controller' ) ) {
	require_once dirname( __FILE__ ) . '/lib/class-lrp-options-controller.php';
}

// Instantiate plugin.
if ( class_exists( 'LRP_Controller' ) ) {
	$limit_revision_publishing = new LRP_Controller();
}
