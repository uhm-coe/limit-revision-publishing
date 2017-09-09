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
		add_filter( 'init',
			array( $this, 'init__register_sortable_columns_for_custom_post_types' ),
			10, 1
		);

		// Modify query to sort by Pending Revisions.
		add_action( 'posts_join_paged',
			array( $this, 'posts_join_paged__orderby_lrp_pending_revision' ),
			10, 2
		);
		add_action( 'posts_orderby',
			array( $this, 'posts_orderby__orderby_lrp_pending_revision' ),
			10, 2
		);
	}


	function init__register_sortable_columns_for_custom_post_types() {
		// Make columns sortable for custom post types. (Note: we hook into init to
		// register these hooks because custom post types won't be created before
		// then.)
		foreach ( get_post_types( array( 'public' => true, '_builtin' => false ) ) as $custom_post_type => $name ) {
			add_filter( "manage_edit-{$custom_post_type}_sortable_columns",
				array( $this, 'sortable_columns_add_revisions' ),
				10, 1
			);
		}
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


	// NOTE: Hook into posts_join_paged and posts_orderby instead of pre_get_posts
	// because of a bug in how WordPress sorts by postmeta values when some posts
	// don't have that postmeta (the posts with NULL postmeta will get some other
	// random value applied, so they will be sorted randomly). See the following
	// for details:
	// http://wordpress.stackexchange.com/questions/102447/sort-on-meta-value-but-include-posts-that-dont-have-one/141367#141367
	function posts_join_paged__orderby_lrp_pending_revision( $join_statement, $query ) {
		global $wpdb;
		if ( is_admin() && $query->get( 'orderby' ) === 'pending_revision' ) {
			$join_pending_revision = " LEFT JOIN {$wpdb->postmeta} AS lrp_postmeta ON {$wpdb->posts}.ID = lrp_postmeta.post_id AND lrp_postmeta.meta_key = 'lrp_pending_revision'";
			if ( strpos( $join_statement, $join_pending_revision ) === FALSE ) {
				$join_statement .= $join_pending_revision;
			}
		}
		return $join_statement;
	}


	function posts_orderby__orderby_lrp_pending_revision( $orderby_statement, $query ) {
		if ( is_admin() && $query->get( 'orderby' ) === 'pending_revision' ) {
			$order = strtolower( $query->get( 'order' ) ) === 'asc' ? 'ASC' : 'DESC';
			$orderby_statement = "lrp_postmeta.meta_value $order";
		}
		return $orderby_statement;
	}

}
