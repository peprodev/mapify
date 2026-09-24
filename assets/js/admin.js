/**
 * Mapify admin screens (settings + shortcode builder), built with @wordpress/components.
 * Plain ES5 + wp.element.createElement so the plugin needs no build step.
 */
( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var useState = wp.element.useState;
	var useEffect = wp.element.useEffect;
	var useRef = wp.element.useRef;
	var useMemo = wp.element.useMemo;
	var C = wp.components;
	var __ = wp.i18n.__;
	var sprintf = wp.i18n.sprintf;
	var apiFetch = wp.apiFetch;
	var D = window.MapifyAdmin || {};
	var hooks = wp.hooks;

	var modern = { __nextHasNoMarginBottom: true, __next40pxDefaultSize: true };
	function props( extra ) {
		return Object.assign( {}, modern, extra );
	}

	/* ------------------------------------------------------------------ shared UI */

	function Header( p ) {
		return el(
			'div',
			{ className: 'mapify-admin__header' },
			el(
				'div',
				{ className: 'mapify-admin__brand' },
				el( 'span', { className: 'mapify-admin__logo', 'aria-hidden': true }, el( MapIcon ) ),
				el(
					'div',
					null,
					el( 'h1', { className: 'mapify-admin__title' }, p.title ),
					el( 'p', { className: 'mapify-admin__subtitle' }, p.subtitle )
				)
			),
			el( 'div', { className: 'mapify-admin__actions' }, p.actions )
		);
	}

	function MapIcon() {
		return el(
			'svg',
			{ viewBox: '0 0 24 24', width: 22, height: 22 },
			el( 'path', { fill: 'currentColor', d: 'M12 2a7 7 0 0 0-7 7c0 5.25 7 13 7 13s7-7.75 7-13a7 7 0 0 0-7-7Zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5Z' } )
		);
	}

	function Snackbar( p ) {
		if ( ! p.notice ) {
			return null;
		}
		return el(
			'div',
			{ className: 'mapify-admin__snackbar' },
			el( C.Snackbar, { onRemove: p.onRemove }, p.notice )
		);
	}

	function copyText( text ) {
		if ( navigator.clipboard && window.isSecureContext ) {
			return navigator.clipboard.writeText( text );
		}
		var t = document.createElement( 'textarea' );
		t.value = text;
		t.style.position = 'fixed';
		t.style.opacity = '0';
		document.body.appendChild( t );
		t.select();
		document.execCommand( 'copy' );
		t.remove();
		return Promise.resolve();
	}

	function CopyButton( p ) {
		var s = useState( false );
		return el(
			C.Button,
			props( {
				variant: p.variant || 'secondary',
				icon: s[ 0 ] ? 'yes' : 'admin-page',
				onClick: function () {
					copyText( p.text ).then( function () {
						s[ 1 ]( true );
						setTimeout( function () {
							s[ 1 ]( false );
						}, 1600 );
					} );
				},
			} ),
			s[ 0 ] ? __( 'Copied!', 'mapify' ) : p.label || __( 'Copy', 'mapify' )
		);
	}

	function StatusRow( p ) {
		return el(
			'div',
			{ className: 'mapify-status' },
			el( 'span', { className: 'mapify-status__dot ' + ( p.ok ? 'is-ok' : 'is-off' ) } ),
			el( 'div', { className: 'mapify-status__body' }, el( 'strong', null, p.title ), el( 'span', null, p.text ) )
		);
	}

	function downloadJson( data, name ) {
		var blob = new Blob( [ JSON.stringify( data, null, 2 ) ], { type: 'application/json' } );
		var a = document.createElement( 'a' );
		a.href = URL.createObjectURL( blob );
		a.download = name;
		document.body.appendChild( a );
		a.click();
		setTimeout( function () {
			URL.revokeObjectURL( a.href );
			a.remove();
		}, 100 );
	}

	function today() {
		return new Date().toISOString().slice( 0, 10 );
	}

	function readJsonFile( file ) {
		return new Promise( function ( resolve, reject ) {
			var r = new FileReader();
			r.onload = function () {
				try {
					resolve( JSON.parse( r.result ) );
				} catch ( e ) {
					reject( new Error( __( 'The file is not valid JSON.', 'mapify' ) ) );
				}
			};
			r.onerror = function () {
				reject( new Error( __( 'The file could not be read.', 'mapify' ) ) );
			};
			r.readAsText( file );
		} );
	}

	/* ------------------------------------------------------------------ settings screen */

	function SettingsScreen() {
		var initial = D.settings || {};
		var st = useState( initial );
		var values = st[ 0 ];
		var setValues = st[ 1 ];
		var saved = useState( initial );
		var busy = useState( false );
		var notice = useState( null );
		var dirty = JSON.stringify( values ) !== JSON.stringify( saved[ 0 ] );

		function set( key ) {
			return function ( v ) {
				var next = Object.assign( {}, values );
				next[ key ] = v;
				setValues( next );
			};
		}

		function save() {
			busy[ 1 ]( true );
			var data = {};
			data[ D.optionKey ] = values;
			apiFetch( { path: '/wp/v2/settings', method: 'POST', data: data } )
				.then( function ( res ) {
					var v = res[ D.optionKey ] || values;
					setValues( v );
					saved[ 1 ]( v );
					notice[ 1 ]( __( 'Settings saved.', 'mapify' ) );
				} )
				.catch( function ( err ) {
					notice[ 1 ]( ( err && err.message ) || __( 'Could not save settings.', 'mapify' ) );
				} )
				.finally( function () {
					busy[ 1 ]( false );
				} );
		}

		var engineOptions = Object.keys( D.engines ).map( function ( k ) {
			return { value: k, label: D.engines[ k ] };
		} );

		var tabs = hooks.applyFilters( 'mapify.settings.tabs', [
			{ name: 'providers', title: __( 'Map providers', 'mapify' ) },
			{ name: 'defaults', title: __( 'Defaults', 'mapify' ) },
			{ name: 'branches', title: __( 'Branches', 'mapify' ) },
			{ name: 'advanced', title: __( 'Advanced', 'mapify' ) },
		] );

		function reload() {
			apiFetch( { path: '/wp/v2/settings' } ).then( function ( res ) {
				if ( res[ D.optionKey ] ) {
					setValues( res[ D.optionKey ] );
					saved[ 1 ]( res[ D.optionKey ] );
				}
			} );
		}

		function provider( title, desc, link, children ) {
			return el(
				C.Card,
				{ className: 'mapify-card', size: 'medium' },
				el(
					C.CardHeader,
					null,
					el( 'div', null, el( 'h2', { className: 'mapify-card__title' }, title ), el( 'p', { className: 'mapify-card__desc' }, desc ) ),
					link ? el( C.ExternalLink, { href: link.href }, link.label ) : null
				),
				el( C.CardBody, { className: 'mapify-stack' }, children )
			);
		}

		// What extensions get to build their own tabs and cards.
		var api = { values: values, set: set, setValues: setValues, reload: reload };

		function tab( t ) {
			var custom = hooks.applyFilters( 'mapify.settings.tab', null, t.name, api );
			if ( custom ) {
				return custom;
			}
			switch ( t.name ) {
				case 'providers':
					return el(
						'div',
						{ className: 'mapify-stack' },
						provider(
							__( 'OpenStreetMap', 'mapify' ),
							__( 'Free tiles that need no key: OpenStreetMap, OpenTopoMap and Esri (light, dark, street, topographic and satellite). Also used as the fallback when a provider key is missing.', 'mapify' ),
							null,
							el( 'div', { className: 'mapify-pill is-ok' }, __( 'Ready — no configuration needed', 'mapify' ) )
						),
						provider(
							__( 'Google Maps', 'mapify' ),
							__( 'JavaScript API key with the Maps JavaScript API enabled.', 'mapify' ),
							{ href: 'https://console.cloud.google.com/google/maps-apis/credentials', label: __( 'Get a key', 'mapify' ) },
							[
								el( C.TextControl, props( { key: 'k', label: __( 'API key', 'mapify' ), value: values.google_api_key, onChange: set( 'google_api_key' ), className: 'is-ltr', autoComplete: 'off' } ) ),
								el( C.TextControl, props( { key: 'm', label: __( 'Map ID (optional)', 'mapify' ), help: __( 'Enables Advanced Markers and cloud-based styling. Leave empty to use the built-in JSON styles.', 'mapify' ), value: values.google_map_id, onChange: set( 'google_map_id' ), className: 'is-ltr' } ) ),
								el( C.TextControl, props( { key: 'l', label: __( 'Map language (optional)', 'mapify' ), placeholder: 'fa', help: __( 'Two-letter code such as fa, en or ar. Empty = visitor browser language.', 'mapify' ), value: values.google_language, onChange: set( 'google_language' ), className: 'is-ltr' } ) ),
							]
						),
						provider(
							__( 'Mapbox', 'mapify' ),
							__( 'Public access token (starts with pk.). Works with Mapbox styles and your own Mapbox Studio styles.', 'mapify' ),
							{ href: 'https://account.mapbox.com/access-tokens/', label: __( 'Get a token', 'mapify' ) },
							el( C.TextControl, props( { label: __( 'Access token', 'mapify' ), value: values.mapbox_token, onChange: set( 'mapbox_token' ), className: 'is-ltr', autoComplete: 'off' } ) )
						),
						provider(
							__( 'Neshan', 'mapify' ),
							__( 'Persian map by Neshan. Create a “Web map” key in the Neshan platform panel.', 'mapify' ),
							{ href: 'https://platform.neshan.org/panel/api-key', label: __( 'Get an API key', 'mapify' ) },
							el( C.TextControl, props( { label: __( 'Web map API key', 'mapify' ), value: values.neshan_api_key, onChange: set( 'neshan_api_key' ), className: 'is-ltr', autoComplete: 'off' } ) )
						),
						provider(
							__( 'Parsimap', 'mapify' ),
							__( 'Persian raster map tiles by Parsimap.', 'mapify' ),
							{ href: 'https://account.parsimap.ir/token-registration', label: __( 'Get a token', 'mapify' ) },
							el( C.TextControl, props( { label: __( 'API token', 'mapify' ), value: values.parsimap_api_key, onChange: set( 'parsimap_api_key' ), className: 'is-ltr', autoComplete: 'off' } ) )
						),
						provider(
							__( 'Mapup', 'mapify' ),
							__( 'Persian map tiles by Mapup — free, no key needed.', 'mapify' ),
							{ href: 'https://mapup.ir', label: 'mapup.ir' },
							el( 'div', { className: 'mapify-pill is-ok' }, __( 'Ready — no configuration needed', 'mapify' ) )
						),
						provider(
							__( 'Map.ir', 'mapify' ),
							__( 'Persian map tiles with Iranian street names and places.', 'mapify' ),
							{ href: 'https://corp.map.ir/registration/', label: __( 'Get an API key', 'mapify' ) },
							el( C.TextControl, props( { label: __( 'API key', 'mapify' ), value: values.mapir_api_key, onChange: set( 'mapir_api_key' ), className: 'is-ltr', autoComplete: 'off' } ) )
						)
					);
				case 'defaults':
					return el(
						C.Card,
						{ className: 'mapify-card' },
						el( C.CardHeader, null, el( 'div', null, el( 'h2', { className: 'mapify-card__title' }, __( 'Map defaults', 'mapify' ) ), el( 'p', { className: 'mapify-card__desc' }, __( 'Used by every map that does not override them.', 'mapify' ) ) ) ),
						el(
							C.CardBody,
							{ className: 'mapify-stack' },
							el( C.SelectControl, props( { label: __( 'Default map engine', 'mapify' ), value: values.default_engine, options: engineOptions, onChange: set( 'default_engine' ) } ) ),
							el(
								'div',
								{ className: 'mapify-grid-2' },
								el( C.TextControl, props( { label: __( 'Default center (lat,lng)', 'mapify' ), value: values.default_center, onChange: set( 'default_center' ), className: 'is-ltr' } ) ),
								el( C.TextControl, props( { label: __( 'Default height', 'mapify' ), value: values.default_height, onChange: set( 'default_height' ), className: 'is-ltr', help: __( 'Any CSS length, e.g. 500px or 60vh.', 'mapify' ) } ) )
							),
							el( C.RangeControl, props( { label: __( 'Default zoom', 'mapify' ), value: parseInt( values.default_zoom, 10 ) || 5, min: 1, max: 20, onChange: set( 'default_zoom' ) } ) ),
							el(
								C.BaseControl,
								props( { label: __( 'Accent color', 'mapify' ), id: 'mapify-accent', help: __( 'Pins, clusters, active items and buttons.', 'mapify' ) } ),
								el( C.ColorPalette, {
									value: values.accent_color,
									onChange: function ( v ) {
										set( 'accent_color' )( v || '#e05a46' );
									},
									colors: [
										{ name: 'Coral', color: '#e05a46' },
										{ name: 'Purple', color: '#703192' },
										{ name: 'Blue', color: '#2271b1' },
										{ name: 'Teal', color: '#0f766e' },
										{ name: 'Amber', color: '#d97706' },
										{ name: 'Graphite', color: '#1f2937' },
									],
								} )
							)
						)
					);
				case 'branches':
					return el(
						C.Card,
						{ className: 'mapify-card' },
						el( C.CardHeader, null, el( 'div', null, el( 'h2', { className: 'mapify-card__title' }, __( 'Branch pages', 'mapify' ) ), el( 'p', { className: 'mapify-card__desc' }, __( 'How single branch pages look and where they live.', 'mapify' ) ) ) ),
						el(
							C.CardBody,
							{ className: 'mapify-stack' },
							el( C.SelectControl, props( {
								label: __( 'Branch page layout', 'mapify' ),
								value: values.branch_template,
								options: [
									{ value: 'content', label: __( 'Branch card (address, contact, directions) + content', 'mapify' ) },
									{ value: 'post', label: __( 'Content only (theme template)', 'mapify' ) },
								],
								onChange: set( 'branch_template' ),
							} ) ),
							el( C.TextControl, props( { label: __( 'URL base', 'mapify' ), value: values.branch_slug, onChange: set( 'branch_slug' ), className: 'is-ltr', help: __( 'Branch URLs look like /map/branch-name/. Visit Settings → Permalinks after changing it.', 'mapify' ) } ) ),
							el( C.SelectControl, props( {
								label: __( 'Address search in the branch editor', 'mapify' ),
								value: values.geocoder,
								options: [
									{ value: 'nominatim', label: __( 'OpenStreetMap Nominatim', 'mapify' ) },
									{ value: 'none', label: __( 'Disabled', 'mapify' ) },
								],
								onChange: set( 'geocoder' ),
							} ) )
						)
					);
				default:
					return el(
						'div',
						{ className: 'mapify-stack' },
						el(
							C.Card,
							{ className: 'mapify-card' },
							el( C.CardHeader, null, el( 'h2', { className: 'mapify-card__title' }, __( 'Advanced', 'mapify' ) ) ),
							el(
								C.CardBody,
								{ className: 'mapify-stack' },
								el( C.ToggleControl, props( { label: __( 'Delete settings on uninstall', 'mapify' ), help: __( 'Branches are kept either way.', 'mapify' ), checked: !! values.clear_on_uninstall, onChange: set( 'clear_on_uninstall' ) } ) )
							)
						),
						hooks.applyFilters( 'mapify.settings.advanced', [], api )
					);
			}
		}

		var sample = '[' + 'pepro-mapify maptype="iran" branchtype="all"]';

		return el(
			Fragment,
			null,
			el( Header, {
				title: __( 'Mapify — Map Settings', 'mapify' ),
				subtitle: sprintf( __( 'Version %s · Providers, defaults and branch pages', 'mapify' ), D.version ),
				actions: [
					dirty ? el( 'span', { key: 'd', className: 'mapify-admin__dirty' }, __( 'Unsaved changes', 'mapify' ) ) : null,
					el( C.Button, props( { key: 's', variant: 'primary', isBusy: busy[ 0 ], disabled: busy[ 0 ] || ! dirty, onClick: save } ), __( 'Save changes', 'mapify' ) ),
				],
			} ),
			el(
				'div',
				{ className: 'mapify-admin__body' },
				el( 'div', { className: 'mapify-admin__main' }, el( C.TabPanel, { className: 'mapify-tabs', tabs: tabs }, tab ) ),
				el(
					'aside',
					{ className: 'mapify-admin__aside' },
					el(
						C.Card,
						{ className: 'mapify-card' },
						el( C.CardHeader, null, el( 'h2', { className: 'mapify-card__title' }, __( 'Add a map to a page', 'mapify' ) ) ),
						el(
							C.CardBody,
							{ className: 'mapify-stack' },
							el( StatusRow, { ok: !! D.builders.elementor, title: 'Elementor', text: D.builders.elementor ? sprintf( __( 'Active (%s) — widget “Branches Map” in PeproDev Elements', 'mapify' ), D.builders.elementor ) : __( 'Not active', 'mapify' ) } ),
							el( StatusRow, { ok: !! D.builders.wpbakery, title: 'WPBakery Page Builder', text: D.builders.wpbakery ? sprintf( __( 'Active (%s) — element “Branches Map”', 'mapify' ), D.builders.wpbakery ) : __( 'Not active', 'mapify' ) } ),
							el( StatusRow, { ok: true, title: __( 'Shortcode', 'mapify' ), text: __( 'Works everywhere, including the block editor', 'mapify' ) } ),
							el( 'code', { className: 'mapify-code' }, sample ),
							el( 'div', { className: 'mapify-row' }, el( CopyButton, { text: sample } ), el( C.Button, props( { variant: 'secondary', href: D.links.builder } ), __( 'Open shortcode builder', 'mapify' ) ) )
						)
					),
					el(
						C.Card,
						{ className: 'mapify-card' },
						el( C.CardHeader, null, el( 'h2', { className: 'mapify-card__title' }, __( 'Branches', 'mapify' ) ) ),
						el(
							C.CardBody,
							{ className: 'mapify-stack' },
							el( 'p', { className: 'mapify-bignum' }, D.branchCount, el( 'span', null, __( 'published branches', 'mapify' ) ) ),
							el( 'div', { className: 'mapify-row' }, el( C.Button, props( { variant: 'secondary', href: D.links.newBranch } ), __( 'Add branch', 'mapify' ) ), el( C.Button, props( { variant: 'tertiary', href: D.links.branches } ), __( 'Manage', 'mapify' ) ) ),
							el( 'p', { className: 'mapify-muted' }, sprintf( 'PHP %s · WordPress %s', D.env.php, D.env.wp ) )
						)
					)
				)
			),
			el( Snackbar, { notice: notice[ 0 ], onRemove: function () { notice[ 1 ]( null ); } } )
		);
	}

	/* ------------------------------------------------------------------ builder screen */

	function effective( values, key ) {
		var v = values[ key ];
		if ( key === 'maptype' && ! v ) {
			return D.settings.default_engine;
		}
		return v;
	}

	function isVisible( field, values, byKey ) {
		if ( ! field.condition ) {
			return true;
		}
		return Object.keys( field.condition ).every( function ( k ) {
			var allowed = field.condition[ k ];
			var v = effective( values, k );
			var ok = allowed.indexOf( v ) !== -1;
			return ok && ( ! byKey[ k ] || isVisible( byKey[ k ], values, byKey ) );
		} );
	}

	function buildShortcode( values ) {
		var parts = [];
		var content = '';
		D.schema.fields.forEach( function ( f ) {
			var v = values[ f.key ];
			var d = D.defaults[ f.key ];
			if ( f.key === 'popup_markup' ) {
				if ( v && v.trim() ) {
					content = v.trim();
				}
				return;
			}
			if ( JSON.stringify( v ) === JSON.stringify( d ) || v === undefined ) {
				return;
			}
			if ( f.type === 'repeater' ) {
				if ( ! v.length ) {
					return;
				}
				v = encodeURIComponent( JSON.stringify( v ) );
			} else if ( Array.isArray( v ) ) {
				v = v.join( ',' );
			} else if ( typeof v === 'boolean' ) {
				v = v ? 'yes' : 'no';
			}
			v = String( v ).replace( /"/g, '&quot;' ).replace( /\[/g, '&#91;' ).replace( /\]/g, '&#93;' );
			parts.push( f.key + '="' + v + '"' );
		} );
		var open = '[' + D.tag + ( parts.length ? ' ' + parts.join( ' ' ) : '' ) + ']';
		return content ? open + '\n' + content + '\n[/' + D.tag + ']' : open;
	}

	function MediaInput( p ) {
		var frame = useRef( null );
		return el(
			C.BaseControl,
			props( { label: p.label, help: p.help, id: 'mapify-media-' + p.id } ),
			el(
				'div',
				{ className: 'mapify-media' },
				p.value && /\.(png|jpe?g|gif|webp|svg|avif)(\?|$)/i.test( p.value ) ? el( 'img', { src: p.value, alt: '' } ) : null,
				el( C.TextControl, props( { value: p.value || '', onChange: p.onChange, placeholder: 'https://', className: 'is-ltr', hideLabelFromVision: true, label: p.label } ) ),
				el(
					'div',
					{ className: 'mapify-row' },
					el(
						C.Button,
						props( {
							variant: 'secondary',
							onClick: function () {
								if ( ! frame.current ) {
									frame.current = wp.media( { title: p.label, multiple: false } );
									frame.current.on( 'select', function () {
										p.onChange( frame.current.state().get( 'selection' ).first().toJSON().url );
									} );
								}
								frame.current.open();
							},
						} ),
						__( 'Media library', 'mapify' )
					),
					p.value ? el( C.Button, props( { variant: 'tertiary', isDestructive: true, onClick: function () { p.onChange( '' ); } } ), __( 'Remove', 'mapify' ) ) : null
				)
			)
		);
	}

	function StylePicker( p ) {
		var q = useState( '' );
		var term = q[ 0 ].trim().toLowerCase();
		var options = p.field.options.filter( function ( o ) {
			return ! term || ( o.label + ' ' + o.value ).toLowerCase().indexOf( term ) !== -1;
		} );
		return el(
			C.BaseControl,
			props( { label: p.field.label, help: p.field.description, id: 'mapify-style-' + p.field.key } ),
			p.field.options.length > 12 ? el( C.SearchControl, { __nextHasNoMarginBottom: true, value: q[ 0 ], onChange: q[ 1 ], placeholder: __( 'Search styles…', 'mapify' ), className: 'mapify-styles__search' } ) : null,
			el(
				'div',
				{ className: 'mapify-styles', role: 'listbox' },
				options.map( function ( o ) {
					return el(
						'button',
						{
							key: o.value,
							type: 'button',
							role: 'option',
							'aria-selected': p.value === o.value,
							className: 'mapify-styles__item' + ( p.value === o.value ? ' is-selected' : '' ),
							onClick: function () {
								p.onChange( o.value );
							},
						},
						o.image ? el( 'img', { src: o.image, alt: '', loading: 'lazy' } ) : el( 'span', { className: 'mapify-styles__blank' } ),
						el( 'span', null, o.label )
					);
				} )
			),
			! options.length ? el( 'p', { className: 'mapify-muted' }, __( 'No style found.', 'mapify' ) ) : null,
			p.field.note ? el( 'p', { className: 'mapify-muted mapify-styles__note' }, p.field.note ) : null
		);
	}

	/** Read-only message; "pro" notices show the feature greyed out. */
	function NoticeField( p ) {
		var f = p.field;
		if ( f.variant !== 'pro' ) {
			return el( C.Notice, { status: 'info', isDismissible: false }, f.content );
		}
		return el(
			'div',
			{ className: 'mapify-pro-field', 'aria-disabled': 'true' },
			el( C.TextareaControl, { __nextHasNoMarginBottom: true, label: f.label, value: '', disabled: true, rows: 3, onChange: function () {} } ),
			el( 'p', { className: 'mapify-muted' }, f.content )
		);
	}

	function MultiCheck( p ) {
		if ( ! p.field.options.length ) {
			return el( C.Notice, { status: 'info', isDismissible: false }, __( 'Nothing to choose yet — add branches and categories first.', 'mapify' ) );
		}
		var value = p.value || [];
		return el(
			C.BaseControl,
			props( { label: p.field.label, id: 'mapify-multi-' + p.field.key } ),
			el(
				'div',
				{ className: 'mapify-checklist' },
				p.field.options.map( function ( o ) {
					return el( C.CheckboxControl, {
						key: o.value,
						__nextHasNoMarginBottom: true,
						label: o.label,
						checked: value.indexOf( o.value ) !== -1,
						onChange: function ( on ) {
							var next = value.filter( function ( v ) {
								return v !== o.value;
							} );
							if ( on ) {
								next.push( o.value );
							}
							p.onChange( next );
						},
					} );
				} )
			)
		);
	}

	function Field( p ) {
		var control = FieldControl( p );
		var link = p.field.link;
		return link ? el( Fragment, null, control, el( 'p', { className: 'mapify-field-link' }, el( C.ExternalLink, { href: link.url }, link.label ) ) ) : control;
	}

	function FieldControl( p ) {
		var f = p.field;
		var v = p.value;
		var on = p.onChange;
		switch ( f.type ) {
			case 'select':
				if ( f.previews ) {
					return el( StylePicker, p );
				}
				return el( C.SelectControl, props( { label: f.label, help: f.description, value: v, options: f.options, onChange: on } ) );
			case 'multiselect':
				return el( MultiCheck, p );
			case 'toggle':
				return el( C.ToggleControl, props( { label: f.label, help: f.description, checked: !! v, onChange: on } ) );
			case 'number':
				return el( C.TextControl, props( { type: 'number', label: f.label, help: f.description, value: v === '' || v === undefined ? '' : v, min: f.min, max: f.max, placeholder: f.placeholder, onChange: function ( x ) { on( x === '' ? '' : Number( x ) ); } } ) );
			case 'color':
				return el(
					C.BaseControl,
					props( { label: f.label, id: 'mapify-color-' + f.key } ),
					el( C.ColorPalette, { value: v || undefined, onChange: function ( x ) { on( x || '' ); }, clearable: true, colors: [ { name: 'Coral', color: '#e05a46' }, { name: 'Purple', color: '#703192' }, { name: 'Blue', color: '#2271b1' }, { name: 'Teal', color: '#0f766e' }, { name: 'White', color: '#ffffff' }, { name: 'Graphite', color: '#1f2937' } ] } )
				);
			case 'media':
				return el( MediaInput, { id: f.key, label: f.label, help: f.description, value: v, onChange: on } );
			case 'code':
			case 'textarea':
				return el( C.TextareaControl, {
					__nextHasNoMarginBottom: true,
					label: f.label,
					help: f.description,
					value: f.key === 'popup_markup' && ! v ? D.popupTemplate : v,
					rows: f.type === 'code' ? 9 : 4,
					className: f.type === 'code' ? 'mapify-code-input is-ltr' : '',
					onChange: on,
				} );
			case 'repeater':
				return el( Pins, p );
			case 'notice':
				return el( NoticeField, p );
			default:
				return el( C.TextControl, props( { label: f.label, help: f.description, value: v || '', placeholder: f.placeholder, onChange: on } ) );
		}
	}

	function Pins( p ) {
		var f = p.field;
		var pins = p.value || [];
		function update( i, key, val ) {
			var next = pins.map( function ( pin, j ) {
				if ( j !== i ) {
					return pin;
				}
				var c = Object.assign( {}, pin );
				c[ key ] = val;
				return c;
			} );
			p.onChange( next );
		}
		function add() {
			var pin = {};
			Object.keys( f.fields ).forEach( function ( k ) {
				pin[ k ] = f.fields[ k ].default;
			} );
			pin.title = sprintf( __( 'Pin %d', 'mapify' ), pins.length + 1 );
			p.onChange( pins.concat( [ pin ] ) );
		}
		return el(
			'div',
			{ className: 'mapify-pins' },
			el( 'p', { className: 'mapify-muted' }, f.description ),
			pins.map( function ( pin, i ) {
				return el(
					'div',
					{ key: i, className: 'mapify-pins__item' },
					el(
						'div',
						{ className: 'mapify-pins__head' },
						el( 'strong', null, pin.title || sprintf( __( 'Pin %d', 'mapify' ), i + 1 ) ),
						el( C.Button, { icon: 'trash', label: __( 'Remove pin', 'mapify' ), isDestructive: true, size: 'small', onClick: function () { p.onChange( pins.filter( function ( x, j ) { return j !== i; } ) ); } } )
					),
					el(
						'div',
						{ className: 'mapify-pins__grid' },
						Object.keys( f.fields ).map( function ( k ) {
							var sub = Object.assign( { key: k }, f.fields[ k ] );
							return el(
								'div',
								{ key: k, className: 'mapify-pins__cell mapify-pins__cell--' + k },
								el( Field, { field: sub, value: pin[ k ], onChange: function ( val ) { update( i, k, val ); } } )
							);
						} )
					)
				);
			} ),
			el( C.Button, props( { variant: 'secondary', icon: 'plus', onClick: add } ), __( 'Add pin', 'mapify' ) )
		);
	}

	function BuilderScreen() {
		var byKey = useMemo( function () {
			var m = {};
			D.schema.fields.forEach( function ( f ) {
				m[ f.key ] = f;
			} );
			return m;
		}, [] );
		var st = useState( Object.assign( {}, D.defaults ) );
		var values = st[ 0 ];
		var setValues = st[ 1 ];
		var device = useState( 'desktop' );
		var frameName = 'mapify-preview-frame';
		var formRef = useRef( null );
		var shortcode = buildShortcode( values );

		useEffect(
			function () {
				var t = setTimeout( function () {
					if ( formRef.current ) {
						formRef.current.submit();
					}
				}, 600 );
				return function () {
					clearTimeout( t );
				};
			},
			[ JSON.stringify( values ) ]
		);

		function set( key, v ) {
			var next = Object.assign( {}, values );
			next[ key ] = v;
			setValues( next );
		}

		var previewSettings = Object.assign( {}, values );
		delete previewSettings.popup_markup;

		return el(
			Fragment,
			null,
			el( Header, {
				title: __( 'Mapify — Shortcode Builder', 'mapify' ),
				subtitle: __( 'Design a map, preview it live and paste the shortcode anywhere.', 'mapify' ),
				actions: hooks.applyFilters(
					'mapify.builder.actions',
					[
						el( C.Button, props( { key: 'r', variant: 'tertiary', onClick: function () { setValues( Object.assign( {}, D.defaults ) ); } } ), __( 'Reset', 'mapify' ) ),
						el( CopyButton, { key: 'c', text: shortcode, variant: 'primary', label: __( 'Copy shortcode', 'mapify' ) } ),
					],
					{ values: values, setValues: setValues, byKey: byKey }
				),
			} ),
			el(
				'div',
				{ className: 'mapify-builder' },
				el(
					'div',
					{ className: 'mapify-builder__controls' },
					el(
						C.Panel,
						null,
						D.schema.groups.map( function ( g, gi ) {
							var fields = D.schema.fields.filter( function ( f ) {
								return f.group === g.key && isVisible( f, values, byKey );
							} );
							if ( ! fields.length ) {
								return null;
							}
							return el(
								C.PanelBody,
								{ key: g.key, title: g.label, initialOpen: gi < 2 },
								el(
									'div',
									{ className: 'mapify-stack' },
									fields.map( function ( f ) {
										return el( Field, { key: f.key, field: f, value: values[ f.key ], onChange: function ( v ) { set( f.key, v ); } } );
									} )
								)
							);
						} )
					)
				),
				el(
					'div',
					{ className: 'mapify-builder__main' },
					el(
						C.Card,
						{ className: 'mapify-card' },
						el( C.CardHeader, null, el( 'h2', { className: 'mapify-card__title' }, __( 'Shortcode', 'mapify' ) ), el( CopyButton, { text: shortcode } ) ),
						el( C.CardBody, null, el( 'pre', { className: 'mapify-code mapify-code--block', dir: 'ltr' }, shortcode ) )
					),
					el(
						C.Card,
						{ className: 'mapify-card mapify-preview-card' },
						el(
							C.CardHeader,
							null,
							el( 'h2', { className: 'mapify-card__title' }, __( 'Live preview', 'mapify' ) ),
							el(
								'div',
								{ className: 'mapify-devices' },
								[
									[ 'desktop', 'desktop', __( 'Desktop', 'mapify' ) ],
									[ 'tablet', 'tablet', __( 'Tablet', 'mapify' ) ],
									[ 'mobile', 'smartphone', __( 'Mobile', 'mapify' ) ],
								].map( function ( d ) {
									return el( C.Button, { key: d[ 0 ], icon: d[ 1 ], label: d[ 2 ], isPressed: device[ 0 ] === d[ 0 ], onClick: function () { device[ 1 ]( d[ 0 ] ); } } );
								} )
							)
						),
						el(
							C.CardBody,
							{ className: 'mapify-preview' },
							el(
								'form',
								{ ref: formRef, method: 'post', action: D.previewUrl, target: frameName, style: { display: 'none' } },
								el( 'input', { type: 'hidden', name: 'mapify_preview', value: '1' } ),
								el( 'input', { type: 'hidden', name: '_wpnonce', value: D.previewNonce } ),
								el( 'input', { type: 'hidden', name: 'settings', value: JSON.stringify( previewSettings ) } ),
								el( 'input', { type: 'hidden', name: 'content', value: values.popup_markup || '' } )
							),
							el( 'div', { className: 'mapify-preview__frame is-' + device[ 0 ] }, el( 'iframe', { name: frameName, title: __( 'Map preview', 'mapify' ) } ) )
						)
					)
				)
			)
		);
	}

	// Shared with extensions (window.MapifyAdminKit), which add tabs, cards and builder actions through wp.hooks.
	window.MapifyAdminKit = {
		el: el,
		C: C,
		props: props,
		useState: useState,
		useRef: useRef,
		useEffect: useEffect,
		Field: Field,
		CopyButton: CopyButton,
		downloadJson: downloadJson,
		readJsonFile: readJsonFile,
		today: today,
	};

	function App() {
		return D.screen === 'settings' ? el( SettingsScreen ) : el( BuilderScreen );
	}

	wp.domReady( function () {
		var root = document.getElementById( 'mapify-admin-root' );
		if ( ! root ) {
			return;
		}
		if ( wp.element.createRoot ) {
			wp.element.createRoot( root ).render( el( App ) );
		} else {
			wp.element.render( el( App ), root );
		}
	} );
} )( window.wp );
