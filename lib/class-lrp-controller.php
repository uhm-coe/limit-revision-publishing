<?php

class LRP_Controller {
	public $textdomain = 'limit-revision-publishing';
	public $reviewers = array();
	private $is_reverting = false;

	/**
	 * Class constructor.
	 * Register all plugin hooks.
	 */
	function __construct() {
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded__load_textdomain' ) );
		add_action( 'save_post', array( $this, 'save_post__revert_if_unprivileged' ), 1, 3 );

	}

	/**
	 * Load textdomain for internationalization.
	 * Action hook: https://codex.wordpress.org/Plugin_API/Action_Reference/plugins_loaded
	 */
	function plugins_loaded__load_textdomain() {
		load_plugin_textdomain(
			$this->textdomain, // 'limit-revision-publishing'
			false,
			plugin_basename( dirname( dirname( __FILE__ ) ) ) . '/languages'
		);
	}

	/**
	 * Save this post as a revision and revert to the previous revision if the
	 * current user doesn't have the publish_{post_type} capability.
	 * Action hook: https://codex.wordpress.org/Plugin_API/Action_Reference/save_post
	 * @param int $post_id The post ID.
	 * @param post $post The post object.
	 * @param bool $update Whether this is an existing post being updated or not.
	 */
	function save_post__revert_if_unprivileged( $post_id, $post, $update ) {
		global $wp_post_types;

		// Prevent this save (revert to previous revision) if we haven't already
		// reverted, this post is published, it's not an autosave, and the current
		// user doesn't have the publish_{post_type} capability.
		if (
			! $this->is_reverting &&
			$post->post_status === 'publish' &&
			! ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) &&
			! current_user_can( $wp_post_types[$post->post_type]->cap->publish_posts )
		) {
			// Flag that we're reverting (save_post hook will get called again below
			// in wp_restore_post_revision(), so we don't want this to keep firing).
			$this->is_reverting = true;

			// Revert to previous revision (latest revision is this one, so we want the
			// one right before it).
			$previous_revision = array_pop( wp_get_post_revisions( $post_id, array(
				'posts_per_page' => 2,
				'cache_results' => false,
			) ) );
			wp_restore_post_revision( $previous_revision );

			// TODO: send notification emails to reviewers.

		}
	}

}
