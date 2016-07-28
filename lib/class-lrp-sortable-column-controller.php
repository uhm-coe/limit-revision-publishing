<?php

// Add "Pending Revisions" sortable column to All Posts/Pages.
class LRP_Sortable_Column_Controller {


	/**
	 * Class constructor.
	 * Register all plugin hooks.
	 */
	function __construct() {
		// Add "Pending Revisions" column to All Posts/Pages.
		add_action( 'manage_posts_columns',
			array( $this, 'manage_posts_columns__add_revisions' ),
			10, 1
		);
		add_action( 'manage_pages_columns',
			array( $this, 'manage_posts_columns__add_revisions' ),
			10, 1
		);

		// Render "Pending Revisions" column.
		add_action( 'manage_posts_custom_column',
			array( $this, 'manage_posts_custom_column__render_revisions' ),
			10, 2
		);
		add_action( 'manage_pages_custom_column',
			array( $this, 'manage_posts_custom_column__render_revisions' ),
			10, 2
		);

		// Make "Pending Revisions" column sortable.
		add_filter( 'manage_edit-post_sortable_columns',
			array( $this, 'sortable_columns_add_revisions' ),
			10, 1
		);
		add_filter( 'manage_edit-page_sortable_columns',
			array( $this, 'sortable_columns_add_revisions' ),
			10, 1
		);

		// Modify query to sort by Pending Revisions.
		add_action( 'pre_get_posts',
			array( $this, 'pre_get_posts__orderby_lrp_pending_revision' ),
			10, 1
		);
	}


	function manage_posts_columns__add_revisions( $columns ) {
		return array_merge(
			$columns,
			array( 'lrp_pending_revision' => __( 'Pending Revision', 'limit-revision-publishing' ) )
		);
	}


	function manage_posts_custom_column__render_revisions( $column, $post_id ) {
		if ( $column === 'lrp_pending_revision' ) {
			// Check if this post has a pending revision.
			$pending_revision_id = intval( get_post_meta( $post_id, 'lrp_pending_revision', true ) );
			if ( $pending_revision_id > 0 ) {
				?><a href="<?php echo admin_url( 'revision.php?revision=' . $pending_revision_id ); ?>"><span class="dashicons dashicons-warning"></span></a><?php
			} else {
				?><span aria-hidden="true">—</span><?php
			}
		}
	}


	function sortable_columns_add_revisions( $columns ) {
		$columns['lrp_pending_revision'] = array(
			'pending_revision', // Value of orderby querystring parameter
			1 // Default sort order (0=asc, 1=desc)
		);
		return $columns;
	}


	function pre_get_posts__orderby_lrp_pending_revision( $query ) {
		// Only affect queries on admin pages.
		if ( ! is_admin() ) {
			return;
		}

		// Note: Use 'NOT EXISTS' to include posts without the meta key.
		// See http://wordpress.stackexchange.com/questions/102447/sort-on-meta-value-but-include-posts-that-dont-have-one
		if ( $query->get( 'orderby' ) === 'pending_revision' ) {
			$query->set( 'meta_query', array(
				'key' => 'lrp_pending_revision',
				'compare' => 'NOT EXISTS',
			));
			$query->set( 'orderby', 'meta_value_num' );
		}
	}

}
