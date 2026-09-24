/**
 * Elementor control view for the Mapify thumbnail gallery (mapify_gallery).
 */
( function ( $ ) {
	'use strict';

	$( window ).on( 'elementor:init', function () {
		var Base = elementor.modules.controls.BaseData;

		var View = Base.extend( {
			ui: function () {
				var ui = Base.prototype.ui.apply( this, arguments );
				ui.items = '.mapify-gallery__item';
				ui.search = '.mapify-gallery__search';
				ui.empty = '.mapify-gallery__empty';
				return ui;
			},

			events: function () {
				return _.extend( Base.prototype.events.apply( this, arguments ), {
					'click @ui.items': 'onPick',
					'input @ui.search': 'onSearch',
				} );
			},

			onReady: function () {
				this.mark();
				var selected = this.$el.find( '.mapify-gallery__item.is-selected' )[ 0 ];
				if ( selected && selected.scrollIntoView ) {
					setTimeout( function () {
						selected.scrollIntoView( { block: 'nearest' } );
					}, 0 );
				}
			},

			onPick: function ( e ) {
				this.setValue( e.currentTarget.getAttribute( 'data-value' ) );
				this.mark();
			},

			onSearch: function ( e ) {
				var term = String( e.currentTarget.value || '' ).trim().toLowerCase();
				var any = false;
				this.$el.find( '.mapify-gallery__item' ).each( function () {
					var show = ! term || this.getAttribute( 'data-search' ).indexOf( term ) !== -1;
					this.hidden = ! show;
					any = any || show;
				} );
				this.$el.find( '.mapify-gallery__empty' ).prop( 'hidden', any );
			},

			mark: function () {
				var value = String( this.getControlValue() );
				this.$el.find( '.mapify-gallery__item' ).each( function () {
					var on = this.getAttribute( 'data-value' ) === value;
					this.classList.toggle( 'is-selected', on );
					this.setAttribute( 'aria-selected', on ? 'true' : 'false' );
				} );
			},
		} );

		elementor.addControlView( 'mapify_gallery', View );
	} );
} )( jQuery );
