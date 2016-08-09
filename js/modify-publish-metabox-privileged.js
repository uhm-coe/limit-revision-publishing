// If we're editing a post, the current user has the publish_{post_type}
// capability, and the post we're editing has a pending revision, this script
// gets loaded to add Publish Revision and Discard buttons to the publish metabox.
( function ( $ ) {
	$( document ).ready( function( $ ) {

		// Change "Update" button to "Publish Revision."
		$( '#publish, #original_publish' ).val( lrp_translations.publish_revision );

		// Hide "Move to Trash" link.
		$( 'a.submitdelete.deletion' ).hide();

		// Add link for "Discard Revision."
		$( 'a.submitdelete.deletion' ).before( '<a class="submitdelete discard-revision" href="javascript:void(0);">' + lrp_translations.discard_revision + '</a>' );
		$( document ).on( 'click', 'a.discard-revision', function ( event ) {
			// Wait spinner.
			$( '.spinner' ).css( 'visibility', 'visible' );

			// Submit discard request to server via ajax.
			$.post( lrp_data.ajaxurl, {
				'dataType': 'jsonp',
				'action': 'lrp_discard_revision',
				'nonce': lrp_data.nonce,
				'post_id': lrp_data.post_id,
				'pending_revision_id': lrp_data.pending_revision_id,
			}).done( function ( data ) {
				if ( data.success ) {
					window.location.reload();
				} else {
					// Remove wait spinner.
					$( '.spinner' ).css( 'visibility', 'hidden' );
				}
			}).fail( function ( data ) {
				// Remove wait spinner.
				$( '.spinner' ).css( 'visibility', 'hidden' );
			});
		});
	});
})( jQuery );
