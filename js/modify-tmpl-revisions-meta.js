// If we're viewing the revision browser and the current user doesn't have the
// publish_{post_type} capability, load the javascript that disables the
// "Restore This Revision" button.
( function ( $ ) {
	$( document ).ready( function( $ ) {

		// Bail if the current page is not revision.php.
		if ( ! window.adminpage || 'revision-php' !== window.adminpage ) {
			return;
		}

		// Iterate through all the available revisions and unset the revision
		// restore URL; the side effect of this is the "Restore This Revision"
		// button will not be rendered.
		if ( window._wpRevisionsSettings && window._wpRevisionsSettings.revisionData ) {
			var revisions = window._wpRevisionsSettings.revisionData;
			for ( var i = 0, len = revisions.length; i < len; i++ ) {
				revisions[i].restoreUrl = '';
			}
		}

	});
})( jQuery );
