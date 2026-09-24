/**
 * Branch location picker (Leaflet + OpenStreetMap, no API key required).
 */
( function ( $ ) {
	'use strict';

	var C = window.MapifyBranchEditor || {};

	$( function () {
		var el = document.getElementById( 'mapify-loc-map' );
		if ( ! el || ! window.L ) {
			return;
		}
		var $lat = $( '#mapify-lat' );
		var $lng = $( '#mapify-lng' );
		var $data = $( '#map_data' );
		var start = [ parseFloat( $lat.val() ), parseFloat( $lng.val() ) ];
		var has = ! isNaN( start[ 0 ] ) && ! isNaN( start[ 1 ] );
		var saved = {};
		try {
			saved = JSON.parse( $data.val() || '{}' ) || {};
		} catch ( e ) {}

		var map = L.map( el, { scrollWheelZoom: true } ).setView( has ? start : C.center, has ? saved.gzoom || 15 : C.zoom );
		L.tileLayer( 'https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
			maxZoom: 19,
			attribution: '© OpenStreetMap contributors',
		} ).addTo( map );

		var icon = L.divIcon( {
			className: 'mapify-loc__pin',
			html: '<svg viewBox="0 0 24 32" width="30" height="40"><path d="M12 0C5.37 0 0 5.3 0 11.86 0 20.75 12 32 12 32s12-11.25 12-20.14C24 5.3 18.63 0 12 0Z"/><circle cx="12" cy="12" r="4.6" fill="#fff"/></svg>',
			iconSize: [ 30, 40 ],
			iconAnchor: [ 15, 40 ],
		} );
		var marker = null;

		function write( lat, lng ) {
			lat = Math.round( lat * 1e7 ) / 1e7;
			lng = Math.round( lng * 1e7 ) / 1e7;
			$lat.val( lat );
			$lng.val( lng );
			$data.val( JSON.stringify( { latitude: lat, longitude: lng, gzoom: map.getZoom() } ) );
		}

		function place( lat, lng, pan ) {
			if ( ! marker ) {
				marker = L.marker( [ lat, lng ], { draggable: true, icon: icon } ).addTo( map );
				marker.on( 'dragend', function () {
					var p = marker.getLatLng();
					write( p.lat, p.lng );
				} );
			} else {
				marker.setLatLng( [ lat, lng ] );
			}
			if ( pan ) {
				map.setView( [ lat, lng ], Math.max( map.getZoom(), 15 ) );
			}
			write( lat, lng );
		}

		if ( has ) {
			place( start[ 0 ], start[ 1 ], false );
		}
		map.on( 'click', function ( e ) {
			place( e.latlng.lat, e.latlng.lng, false );
		} );
		map.on( 'zoomend', function () {
			if ( marker ) {
				var p = marker.getLatLng();
				write( p.lat, p.lng );
			}
		} );

		$lat.add( $lng ).on( 'change', function () {
			var lat = parseFloat( String( $lat.val() ).replace( ',', '.' ) );
			var lng = parseFloat( String( $lng.val() ).replace( ',', '.' ) );
			if ( ! isNaN( lat ) && ! isNaN( lng ) ) {
				place( lat, lng, true );
			}
		} );

		// Paste "lat, lng" into the latitude box.
		$lat.on( 'paste', function ( e ) {
			var text = ( e.originalEvent.clipboardData || window.clipboardData ).getData( 'text' );
			var m = String( text ).match( /(-?\d+(?:\.\d+)?)\s*[, ]\s*(-?\d+(?:\.\d+)?)/ );
			if ( m ) {
				e.preventDefault();
				place( parseFloat( m[ 1 ] ), parseFloat( m[ 2 ] ), true );
			}
		} );

		$( '.mapify-loc__clear' ).on( 'click', function () {
			if ( marker ) {
				map.removeLayer( marker );
				marker = null;
			}
			$lat.val( '' );
			$lng.val( '' );
			$data.val( '' );
		} );

		$( '.mapify-loc__locate' ).on( 'click', function () {
			if ( ! navigator.geolocation ) {
				return;
			}
			navigator.geolocation.getCurrentPosition( function ( pos ) {
				place( pos.coords.latitude, pos.coords.longitude, true );
			} );
		} );

		// Address search (OpenStreetMap Nominatim).
		var $q = $( '.mapify-loc__query' );
		var $results = $( '.mapify-loc__results' );
		function search() {
			var q = String( $q.val() ).trim();
			if ( ! q ) {
				return;
			}
			$results.attr( 'hidden', false ).html( '<li class="is-muted">…</li>' );
			fetch( 'https://nominatim.openstreetmap.org/search?format=jsonv2&limit=6&accept-language=' + encodeURIComponent( ( C.locale || 'en' ).split( '_' )[ 0 ] ) + '&q=' + encodeURIComponent( q ) )
				.then( function ( r ) {
					return r.json();
				} )
				.then( function ( rows ) {
					$results.empty();
					if ( ! rows.length ) {
						$results.append( $( '<li class="is-muted">' ).text( C.i18n.noResult ) );
						return;
					}
					rows.forEach( function ( row ) {
						$( '<li><button type="button"></button></li>' )
							.find( 'button' )
							.text( row.display_name )
							.on( 'click', function () {
								place( parseFloat( row.lat ), parseFloat( row.lon ), true );
								$results.attr( 'hidden', true );
								var $addr = $( '#mapify-field-address' );
								if ( $addr.length && ! $addr.val() ) {
									$addr.val( row.display_name );
								}
							} )
							.end()
							.appendTo( $results );
					} );
				} )
				.catch( function () {
					$results.html( '' ).append( $( '<li class="is-muted">' ).text( C.i18n.noResult ) );
				} );
		}
		$( '.mapify-loc__find' ).on( 'click', search );
		$q.on( 'keydown', function ( e ) {
			if ( e.key === 'Enter' ) {
				e.preventDefault();
				search();
			}
		} );

		// Meta boxes can be collapsed/moved; keep the map sized.
		$( document ).on( 'postbox-toggled postbox-moved', function () {
			setTimeout( function () {
				map.invalidateSize();
			}, 50 );
		} );
		setTimeout( function () {
			map.invalidateSize();
		}, 300 );

	} );

	// Pin image & color (branch editor and category screens).
	$( function () {
		if ( $.fn.wpColorPicker ) {
			$( '.mapify-color' ).wpColorPicker();
		}
		var frame;
		var $target;
		$( document ).on( 'click', '.mapify-pinbox__choose', function () {
			$target = $( this ).closest( '.mapify-pinbox__image' ).find( 'input[type="url"]' );
			if ( ! frame ) {
				frame = wp.media( { title: C.i18n.chooseImage, button: { text: C.i18n.useImage }, library: { type: 'image' }, multiple: false } );
				frame.on( 'select', function () {
					var a = frame.state().get( 'selection' ).first().toJSON();
					$target.val( a.url ).trigger( 'change' );
				} );
			}
			frame.open();
		} );
		$( document ).on( 'change input', '.mapify-pinbox__image input[type="url"]', function () {
			var v = $( this ).val();
			$( this ).closest( '.mapify-pinbox__image' ).find( 'img' ).attr( 'src', v ).attr( 'hidden', ! v );
		} );
		// The add-category form is submitted with AJAX; clear the pin fields afterwards.
		$( document ).ajaxSuccess( function ( e, xhr, settings ) {
			if ( settings && typeof settings.data === 'string' && settings.data.indexOf( 'action=add-tag' ) !== -1 && settings.data.indexOf( 'taxonomy=mapify_category' ) !== -1 ) {
				$( '#addtag .mapify-pinbox__image input[type="url"]' ).val( '' ).trigger( 'change' );
				$( '#addtag .mapify-color' ).each( function () {
					$( this ).wpColorPicker ? $( this ).wpColorPicker( 'color', '' ) : $( this ).val( '' );
				} );
			}
		} );
	} );
} )( jQuery );
