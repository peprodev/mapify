/* Mapify — WPBakery custom params. */
( function ( $ ) {
	'use strict';
	$( document ).on( 'click', '.mapify-vc-images__item', function ( e ) {
		e.preventDefault();
		var $btn = $( this );
		var $wrap = $btn.closest( '.mapify-vc-images' );
		$wrap.find( '.is-selected' ).removeClass( 'is-selected' );
		$btn.addClass( 'is-selected' );
		$wrap.next( 'input.wpb_vc_param_value' ).val( $btn.data( 'value' ) ).trigger( 'change' );
	} );
} )( window.jQuery );
