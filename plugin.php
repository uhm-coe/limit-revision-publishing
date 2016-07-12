<?php
/**
 * Plugin Name: Limit Revision Publishing
 * Description: Revisions made by users without the publish_{post_type}
 * capability will be held back. The All Posts view in the WordPress Dashboard
 * will contain a column indicating posts with unpublished revisions.
 * Notification emails can be sent to a subset of users whenever a new revision
 * has been submitted so the more privileged users can publish them.
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
