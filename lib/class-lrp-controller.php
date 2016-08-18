<?php

class LRP_Controller {
	public $reviewers = array();
	private $is_reverting = false;
	private $options_controller;


	/**
	 * Class constructor.
	 * Register hooks.
	 */
	function __construct() {
		add_action( 'plugins_loaded',
			array( $this, 'plugins_loaded__load_textdomain' ),
			10, 1
		);

		add_action( 'save_post',
			array( $this, 'save_post__revert_if_unprivileged' ),
			1, 3
		);

		add_filter( 'acf/update_value',
			array( $this, 'acf_update_value__revert_if_unprivileged' ),
			10, 3
		);

		// Add plugin options screen.
		$this->options_controller = new LRP_Options_Controller();

		// Add sortable column to All Posts showing which posts have pending revisions.
		$controller = new LRP_Sortable_Column_Controller();

		// Add modifications to Edit Post form for unprivileged users.
		$controller = new LRP_Edit_Form_Controller( $this->options_controller );

		// Add modifications to Revisions Browser for unprivileged users.
		$controller = new LRP_Revisions_Form_Controller( $this->options_controller );
	}


	/**
	 * Load textdomain for internationalization.
	 *
	 * Action hook: https://codex.wordpress.org/Plugin_API/Action_Reference/plugins_loaded
	 */
	function plugins_loaded__load_textdomain() {
		load_plugin_textdomain(
			'limit-revision-publishing', // 'limit-revision-publishing'
			false,
			plugin_basename( dirname( dirname( __FILE__ ) ) ) . '/languages'
		);
	}


	/**
	 * Save this post as a revision and revert to the previous revision if the
	 * current user doesn't have the publish_{post_type} capability.
	 *
	 * Action hook: https://codex.wordpress.org/Plugin_API/Action_Reference/save_post
	 *
	 * @param int $post_id The post ID.
	 * @param post $post The post object.
	 * @param bool $update Whether this is an existing post being updated or not.
	 */
	function save_post__revert_if_unprivileged( $post_id, $post, $update ) {
		// If someone is updating an already published post, check their
		// capabilities. If they don't have the publish_{post_type} capability,
		// prevent the save and flag the revision as pending. If they do have the
		// publish_{post_type} capability, clear any existing pending revision flag.
		if (
			! $this->is_reverting &&
			$post->post_status === 'publish' &&
			! ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		) {

			if ( $this->options_controller->current_user_can_publish( $post->post_type ) ) {
				// Clear any pending revision flag if this post is published, it's not an
				// autosave, and the current user has the publish_{post_type} capability.
				delete_post_meta( $post_id, 'lrp_pending_revision' );

			} else {
				// Prevent this save (revert to previous revision) if the current user
				// doesn't have the publish_{post_type} capability.

				// Flag that we're reverting (save_post hook will get called again below
				// in wp_restore_post_revision(), so we don't want this to keep firing).
				$this->is_reverting = true;

				// Get recent revisions (the latest revision is this one, so we want the
				// one right before so we can restore it).
				$recent_revisions = wp_get_post_revisions( $post_id, array(
					'posts_per_page' => 2,
					'cache_results' => false,
				) );
				$current_revision = array_shift( $recent_revisions );
				$previous_revision = array_shift( $recent_revisions );

				// Revert to previous revision.
				wp_restore_post_revision( $previous_revision );

				// Add postmeta flag indicating this post has a revision pending.
				update_post_meta( $post_id, 'lrp_pending_revision', $current_revision->ID );

				// Get reviewers to send notifications to.
				$reviewers = array();
				$users_to_notify = $this->options_controller->get_option( 'users_to_notify' );
				foreach ( $users_to_notify as $user_id ) {
					$reviewers[$user_id] = get_user_by( $user_id );
				}
				$users_in_roles = get_users( array(
					'role__in' => $this->options_controller->get_option( 'roles_to_notify' ),
				));
				foreach ( $users_in_roles as $user ) {
					$reviewers[$user->ID] = $user;
				}

				// Send notification email to reviewers.
				$editor = wp_get_current_user();

				$email_subject = $this->options_controller->get_option( 'notification_email_subject' );
				$email_subject = str_replace( '[editor_name]', $editor->display_name, $email_subject );
				$email_subject = str_replace( '[editor_email]', $editor->user_email, $email_subject );
				$email_subject = str_replace( '[revision_title]', $previous_revision->post_title, $email_subject );
				$email_subject = str_replace( '[revision_url]', admin_url( 'revision.php?revision=' . $current_revision->ID ), $email_subject );

				$email_body = $this->options_controller->get_option( 'notification_email_body' );
				$email_body = str_replace( '[editor_name]', $editor->display_name, $email_body );
				$email_body = str_replace( '[editor_email]', $editor->user_email, $email_body );
				$email_body = str_replace( '[revision_title]', $previous_revision->post_title, $email_body );
				$email_body = str_replace( '[revision_url]', admin_url( 'revision.php?revision=' . $current_revision->ID ), $email_body );

				foreach ( $reviewers as $user_id => $reviewer ) {
					wp_mail( $reviewer->user_email, $email_subject, $email_body );
				}

			}

		}
	}


	/**
	 * Workaround: ACF hooks into wp_restore_post_revision to properly restore ACF
	 * field values when a post revision is restored (by updating the postmeta
	 * values associated with the published post ID), but it fails to update the
	 * field values for the newly created revision ID. These values still match
	 * the previous revision. Use the code below to update the postmeta field
	 * values attached to the new revision ID with those from the main post ID.
	 *
	 * Filter hook: https://www.advancedcustomfields.com/resources/acfupdate_value/
	 *
	 * @param  string $value ACF field value
	 * @param  int $post_id Post ID this postmeta is attached to
	 * @param  array $field ACF field array
	 * @return string ACF field value
	 */
	function acf_update_value__revert_if_unprivileged( $value, $post_id, $field ) {
		// If we are creating the "unchanged" revision after the changed revision
		// (created by wp_restore_post_revision() above), make sure it gets all the
		// original, unchanged ACF field values.
		if ( $this->is_reverting && wp_is_post_revision( $post_id ) ) {
			$parent_post_id = wp_get_post_parent_id( $post_id );
			$value = get_post_meta( $parent_post_id, $field['name'], true );
		}

		return $value;
	}


}
