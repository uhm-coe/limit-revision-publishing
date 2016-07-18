// If we're editing a post and the current user doesn't have the
// publish_{post_type} capability, this script gets loaded that modifies the
// publish metabox.
( function ( $ ) {
	$( document ).ready( function( $ ) {

		$( '#publish, #original_publish' ).val( 'Submit for Review' );

	});
})( jQuery );
