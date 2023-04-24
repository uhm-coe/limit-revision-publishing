<?php
/**
 * Plugin Name: Limit Revision Publishing
 * Description: Limit Revision Publishing restricts edits made by users without the publish_{post_type} capability.
 * Author: Paul Ryan
 * Author URI: https://dcdc.coe.hawaii.edu
 * Plugin URI: https://wordpress.org/plugins/limit-revision-publishing
 * Text Domain: limit-revision-publishing
 * Domain Path: /languages
 * Requires at least: 3.9
 * Version: 1.1.10
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
