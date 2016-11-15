<?php

// Add filter to All Posts to only show posts with pending revisions.
class LRP_Filter_All_Posts_Controller {


	/**
	 * Class constructor.
	 * Register all plugin hooks.
	 */
	function __construct() {
		//
		add_action( 'restrict_manage_posts',
			array( $this, 'restrict_manage_posts__add_pending_filter' ),
			10, 0
		);
		add_action( 'pre_get_posts',
			array( $this, 'pre_get_posts__add_pending_filter' ),
			10, 1
		);
	}


	// Add Pending Revision filter to All Posts.
	function restrict_manage_posts__add_pending_filter() {
		global $typenow, $wp_query;
		$lrp = isset( $_GET['lrp'] ) ? $_GET['lrp'] : '0';
		$post_types = get_post_types(	array( 'public' => true ), 'objects' );
		if ( array_key_exists( $typenow, $post_types ) ) : ?>
			<select name="lrp" id="lrp" class="postform">
				<option value="0">
					<?php echo $post_types[$typenow]->labels->all_items; ?>
				</option>
				<option value="pending"<?php if ( $lrp === 'pending' ) echo ' selected="selected"'; ?>>
					<?php _e( 'Pending revisions only', 'limit-revision-publishing' ); ?>
				</option>
			</select>
		<?php endif;
	}


	// Wire up Pending Revision filter on All Posts.
	function pre_get_posts__add_pending_filter( $query ) {
		global $pagenow;
		if ( is_admin() && $pagenow === 'edit.php' && isset( $_GET['lrp'] ) && $_GET['lrp'] === 'pending' ) {
			$query->set( 'meta_key', 'lrp_pending_revision' );
			$query->set( 'meta_compare', '>' );
			$query->set( 'meta_value_num', '0' );
		}
		return $query;
	}


}
