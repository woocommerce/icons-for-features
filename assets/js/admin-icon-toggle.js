jQuery( document ).ready( function ( e ) {
	var newIconObj, oldIconObj;
	jQuery( '#feature-icon' ).find( '.feature-icon-selector' ).change( function ( e ) {
		oldIconObj = jQuery( '#feature-icon' ).find( '.currently-selected-icon' );
		newIconObj = jQuery( this );

		jQuery( '.icon-preview' ).removeClass( oldIconObj.val() ).addClass( newIconObj.val() );
		oldIconObj.val( newIconObj.val() );
	} );
});