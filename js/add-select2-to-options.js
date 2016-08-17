// Convert user/role dropdowns to select2 multi-value inputs in plugin options.
( function ( $ ) {
	$( document ).ready( function( $ ) {

		$( '#lrp_settings_users_to_notify' ).select2({
			placeholder: lrp_translations.select_users_to_notify
		});

		$( '#lrp_settings_roles_to_notify' ).select2({
			placeholder: lrp_translations.select_roles_to_notify
		});

		$( '#lrp_settings_roles_to_restrict' ).select2({
			placeholder: lrp_translations.select_roles_to_restrict
		});

	});
})( jQuery );
