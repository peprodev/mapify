/**
 * Mapify front-end.
 *
 * One script drives every engine: Google Maps, Leaflet tile maps (OpenStreetMap, Mapbox,
 * Map.ir, custom XYZ) and "plane" maps (offline Iran SVG, custom SVG, image as map).
 * Vendor libraries are loaded on demand, so pages only download what their maps use.
 *
 * @package Mapify
 */
( function () {
	'use strict';

	var G = window.MapifyGlobals || { vendor: {} };
	var loading = {};

	/* ------------------------------------------------------------------ helpers */

	function loadScript( src ) {
		if ( ! loading[ src ] ) {
			loading[ src ] = new Promise( function ( resolve, reject ) {
				var s = document.createElement( 'script' );
				s.src = src;
				s.async = true;
				s.onload = resolve;
				s.onerror = function () {
					reject( new Error( 'Mapify: failed to load ' + src ) );
				};
				document.head.appendChild( s );
			} );
		}
		return loading[ src ];
	}

	function loadCss( href ) {
		if ( ! loading[ href ] ) {
			loading[ href ] = new Promise( function ( resolve ) {
				var l = document.createElement( 'link' );
				l.rel = 'stylesheet';
				l.href = href;
				l.onload = resolve;
				l.onerror = resolve;
				document.head.appendChild( l );
			} );
		}
		return loading[ href ];
	}

	function loadLeaflet( cluster ) {
		var v = G.vendor;
		var chain = Promise.all( [ loadCss( v.leafletCss ), window.L ? null : loadScript( v.leafletJs ) ] );
		if ( cluster ) {
			chain = chain.then( function () {
				return Promise.all( [ loadCss( v.clusterCss ), window.L.markerClusterGroup ? null : loadScript( v.clusterJs ) ] );
			} );
		}
		return chain;
	}

	/** Neshan's SDK is a full Leaflet build (it defines window.L), so it replaces our bundled copy. */
	function loadNeshan( n, cluster ) {
		var chain = Promise.all( [ loadCss( n.css ), loadScript( n.js ) ] );
		if ( cluster ) {
			chain = chain.then( function () {
				return Promise.all( [ loadCss( G.vendor.clusterCss ), window.L.markerClusterGroup ? null : loadScript( G.vendor.clusterJs ) ] );
			} );
		}
		return chain;
	}

	function loadGoogle( opts ) {
		if ( window.google && window.google.maps && window.google.maps.Map ) {
			return Promise.resolve();
		}
		if ( ! loading.google ) {
			loading.google = new Promise( function ( resolve, reject ) {
				window.__mapifyGoogleReady = resolve;
				var q = [
					'key=' + encodeURIComponent( opts.key || '' ),
					'loading=async',
					'v=weekly',
					'libraries=marker',
					'callback=__mapifyGoogleReady',
				];
				if ( opts.language ) {
					q.push( 'language=' + encodeURIComponent( opts.language ) );
				}
				var s = document.createElement( 'script' );
				s.src = 'https://maps.googleapis.com/maps/api/js?' + q.join( '&' );
				s.async = true;
				s.onerror = reject;
				document.head.appendChild( s );
			} );
		}
		return loading.google;
	}

	function esc( value ) {
		return String( value == null ? '' : value ).replace( /[&<>"']/g, function ( c ) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ c ];
		} );
	}

	function fillTemplate( tpl, item ) {
		return String( tpl || '' ).replace( /\{([a-z0-9_]+)(?:\|([^{}]*))?\}/gi, function ( all, key, fallback ) {
			if ( key === 'directions' ) {
				// Built by Mapify itself (escaped there), so it is inserted as HTML.
				return item._directions || '';
			}
			var v = item[ key ];
			if ( v && typeof v === 'object' ) {
				v = Object.keys( v ).map( function ( k ) {
					return v[ k ];
				} ).join( '، ' );
			}
			if ( v === null || v === undefined || v === '' ) {
				v = fallback || '';
			}
			return esc( v );
		} );
	}

	function hasCoords( item ) {
		return typeof item.latitude === 'number' && typeof item.longitude === 'number' && ! isNaN( item.latitude ) && ! isNaN( item.longitude );
	}

	function cssVar( el, name, fallback ) {
		var v = getComputedStyle( el ).getPropertyValue( name ).trim();
		return v || fallback;
	}

	var MARKER_PATH = 'M12 0C5.37 0 0 5.3 0 11.86 0 20.75 12 32 12 32s12-11.25 12-20.14C24 5.3 18.63 0 12 0Z';

	function pinElement( item, cfg, index ) {
		var el = document.createElement( 'span' );
		var img = cfg.pinImage || item.pin_img;
		var style = img ? 'image' : cfg.pinStyle === 'image' ? 'marker' : cfg.pinStyle;
		el.className = 'mapify-pin mapify-pin--' + style;
		el.setAttribute( 'data-id', item.id );
		el.style.setProperty( '--i', index || 0 );
		if ( item.color ) {
			el.style.setProperty( '--mapify-pin-color', item.color );
		}
		if ( cfg.tooltip && item.title ) {
			el.setAttribute( 'data-tip', item.title );
		}
		if ( style === 'image' ) {
			var i = document.createElement( 'img' );
			i.src = img;
			i.alt = item.title || '';
			i.draggable = false;
			el.appendChild( i );
		} else if ( style === 'dot' ) {
			el.innerHTML = '<span class="mapify-pin__dot"></span>';
		} else {
			el.innerHTML = '<svg class="mapify-pin__shape" viewBox="0 0 24 32" aria-hidden="true"><path d="' + MARKER_PATH + '"/><circle class="mapify-pin__hole" cx="12" cy="12" r="4.6"/></svg>';
		}
		return el;
	}

	function markerDataUrl( color, size ) {
		var svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' + size * 0.75 + '" height="' + size + '" viewBox="0 0 24 32"><path fill="' + color + '" stroke="rgba(0,0,0,.25)" stroke-width="1" d="' + MARKER_PATH + '"/><circle fill="#fff" cx="12" cy="12" r="4.6"/></svg>';
		return 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent( svg );
	}

	function clusterDataUrl( color, count ) {
		var size = count < 10 ? 38 : count < 100 ? 44 : 52;
		var svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' + size + '" height="' + size + '" viewBox="0 0 40 40"><circle cx="20" cy="20" r="19" fill="' + color + '" opacity=".25"/><circle cx="20" cy="20" r="14" fill="' + color + '"/></svg>';
		return { url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent( svg ), size: size };
	}

	/* ------------------------------------------------------------------ Leaflet */

	function LeafletEngine( inst ) {
		this.inst = inst;
		this.markers = {};
	}

	LeafletEngine.prototype.mount = function () {
		var self = this;
		var inst = this.inst;
		var cfg = inst.cfg;
		var t = cfg.tiles || {};
		var lib = t.neshan ? loadNeshan( t.neshan, cfg.cluster ) : loadLeaflet( cfg.cluster );
		return lib.then( function () {
			var L = window.L;
			var attr = cfg.attribution || {};
			var opts = {
				center: cfg.center,
				zoom: cfg.zoom,
				scrollWheelZoom: cfg.scrollZoom,
				doubleClickZoom: cfg.dblClickZoom !== false,
				touchZoom: cfg.touchZoom !== false,
				zoomControl: false,
				attributionControl: attr.mode !== 'hide',
			};
			var maxZoom = t.maxZoom || 19;
			if ( cfg.maxZoom ) {
				maxZoom = Math.min( maxZoom, cfg.maxZoom );
			}
			opts.maxZoom = maxZoom;
			if ( cfg.minZoom ) {
				opts.minZoom = Math.min( cfg.minZoom, maxZoom );
			}
			if ( t.neshan ) {
				// Neshan's Leaflet build adds the base layer from these options.
				opts.key = t.neshan.key;
				opts.maptype = t.neshan.maptype;
				opts.poi = false;
				opts.traffic = false;
			}
			var map = ( self.map = L.map( inst.canvas, opts ) );
			if ( cfg.zoomControl ) {
				L.control.zoom( { position: cfg.zoomPosition || 'topleft', zoomInTitle: cfg.i18n.zoomIn, zoomOutTitle: cfg.i18n.zoomOut } ).addTo( map );
			}
			if ( t.neshan ) {
				if ( attr.mode === 'custom' && map.attributionControl ) {
					map.attributionControl.setPrefix( attr.html || false );
				}
				map.whenReady( function () {
					inst.ready();
				} );
			} else {
				var custom = attr.mode === 'custom';
				if ( map.attributionControl ) {
					map.attributionControl.setPrefix( custom ? false : '<a href="https://leafletjs.com">Leaflet</a>' );
				}
				var layer = L.tileLayer( t.url, {
					attribution: custom ? attr.html || '' : t.attribution || '',
					maxZoom: t.maxZoom || 19,
					tileSize: t.tileSize || 256,
					zoomOffset: t.zoomOffset || 0,
					subdomains: 'abc',
					crossOrigin: true,
					className: t.filter ? 'mapify-tiles--' + t.filter : '',
				} ).addTo( map );
				if ( t.overlay ) {
					// Labels / boundaries drawn over the base layer (e.g. satellite with labels).
					L.tileLayer( t.overlay, { maxZoom: t.maxZoom || 19, subdomains: 'abc', crossOrigin: true, className: 'mapify-tiles--overlay' } ).addTo( map );
				}
				layer.once( 'load', function () {
					inst.ready();
				} );
			}
			setTimeout( function () {
				inst.ready();
			}, 4000 );

			self.group = cfg.cluster
				? L.markerClusterGroup( {
						maxClusterRadius: cfg.clusterRadius,
						showCoverageOnHover: false,
						spiderfyOnMaxZoom: true,
						iconCreateFunction: function ( c ) {
							return L.divIcon( {
								html: '<span class="mapify-cluster"><span>' + c.getChildCount() + '</span></span>',
								className: 'mapify-cluster-icon',
								iconSize: L.point( 44, 44 ),
							} );
						},
				  } )
				: L.featureGroup();

			var pinSize = parseFloat( cssVar( inst.el, '--mapify-pin-size', '36' ) ) || 36;
			inst.items.forEach( function ( item, i ) {
				if ( ! hasCoords( item ) ) {
					return;
				}
				var el = pinElement( item, cfg, i );
				var isDot = el.classList.contains( 'mapify-pin--dot' );
				var m = L.marker( [ item.latitude, item.longitude ], {
					icon: L.divIcon( { html: el, className: 'mapify-leaflet-icon', iconSize: null } ),
					title: cfg.tooltip ? '' : item.title,
					riseOnHover: true,
					alt: item.title,
				} );
				m.on( 'click', function () {
					inst.activate( item, 'pin' );
				} );
				m.mapifyItem = item;
				m.popupOffset = isDot ? -pinSize / 3 : -pinSize;
				self.markers[ item.id ] = m;
				self.group.addLayer( m );
			} );
			self.group.addTo( map );
			self.fit();
		} );
	};

	LeafletEngine.prototype.fit = function () {
		var cfg = this.inst.cfg;
		var layers = this.group.getLayers();
		if ( ! cfg.fitBounds || ! layers.length ) {
			return;
		}
		if ( layers.length === 1 ) {
			this.map.setView( layers[ 0 ].getLatLng(), Math.max( cfg.zoom, 14 ) );
		} else {
			this.map.fitBounds( this.group.getBounds(), { padding: [ 48, 48 ], maxZoom: 15 } );
		}
	};

	LeafletEngine.prototype.openPopup = function ( item, html ) {
		var self = this;
		var m = this.markers[ item.id ];
		if ( ! m ) {
			return;
		}
		var show = function () {
			window.L.popup( {
				className: 'mapify-leaflet-popup',
				offset: [ 0, m.popupOffset ],
				maxWidth: 640,
				minWidth: 120,
				autoPanPadding: [ 24, 24 ],
				// Keep the popup clear of the category chips at the top of the map.
				autoPanPaddingTopLeft: [ 24, self.inst.el.querySelector( '.mapify__cats--top' ) ? 64 : 24 ],
			} )
				.setLatLng( m.getLatLng() )
				.setContent( html )
				.openOn( self.map );
		};
		if ( this.group.zoomToShowLayer ) {
			this.group.zoomToShowLayer( m, show );
		} else {
			show();
		}
	};

	LeafletEngine.prototype.focus = function ( item ) {
		var m = this.markers[ item.id ];
		if ( m && ! this.group.zoomToShowLayer ) {
			this.map.setView( m.getLatLng(), Math.max( this.map.getZoom(), 14 ) );
		} else if ( m ) {
			this.group.zoomToShowLayer( m, function () {} );
		}
	};

	LeafletEngine.prototype.filter = function ( ids ) {
		var self = this;
		this.group.clearLayers();
		Object.keys( this.markers ).forEach( function ( id ) {
			if ( ! ids || ids.indexOf( String( id ) ) !== -1 ) {
				self.group.addLayer( self.markers[ id ] );
			}
		} );
		this.map.closePopup();
		this.fit();
	};

	LeafletEngine.prototype.resize = function () {
		this.map.invalidateSize();
	};

	/* ------------------------------------------------------------------ Google */

	function GoogleEngine( inst ) {
		this.inst = inst;
		this.markers = {};
	}

	GoogleEngine.prototype.mount = function () {
		var self = this;
		var inst = this.inst;
		var cfg = inst.cfg;
		var g = cfg.google || {};
		window.gm_authFailure = function () {
			document.querySelectorAll( '.mapify--engine-google' ).forEach( function ( el ) {
				el.classList.add( 'is-failed' );
			} );
			inst.notice( cfg.i18n.googleFailed );
		};
		return loadGoogle( g )
			.then( function () {
				return cfg.cluster && ! window.markerClusterer ? loadScript( G.vendor.gClusterJs ) : null;
			} )
			.then( function () {
				var gm = window.google.maps;
				var advanced = !! ( g.mapId && gm.marker && gm.marker.AdvancedMarkerElement );
				var opts = {
					center: { lat: cfg.center[ 0 ], lng: cfg.center[ 1 ] },
					zoom: cfg.zoom,
					mapTypeId: g.type || 'roadmap',
					disableDefaultUI: ! cfg.controls,
					fullscreenControl: false,
					zoomControl: !! cfg.zoomControl,
					zoomControlOptions: { position: gm.ControlPosition[ { topleft: 'LEFT_TOP', topright: 'RIGHT_TOP', bottomleft: 'LEFT_BOTTOM', bottomright: 'RIGHT_BOTTOM' }[ cfg.zoomPosition ] || 'RIGHT_BOTTOM' ] },
					disableDoubleClickZoom: cfg.dblClickZoom === false,
					gestureHandling: cfg.scrollZoom ? 'greedy' : 'cooperative',
					clickableIcons: false,
				};
				if ( cfg.minZoom ) {
					opts.minZoom = cfg.minZoom;
				}
				if ( cfg.maxZoom ) {
					opts.maxZoom = cfg.maxZoom;
				}
				if ( advanced ) {
					opts.mapId = g.mapId;
				} else if ( g.styles && g.styles.length ) {
					opts.styles = g.styles;
				}
				var map = ( self.map = new gm.Map( inst.canvas, opts ) );
				if ( cfg.attribution && cfg.attribution.mode === 'custom' && cfg.attribution.html ) {
					inst.attributionOverlay( cfg.attribution.html );
				}
				gm.event.addListenerOnce( map, 'tilesloaded', function () {
					inst.ready();
				} );
				self.info = new gm.InfoWindow( { maxWidth: 640 } );
				self.info.addListener( 'closeclick', function () {
					inst.setActive( null );
				} );
				map.addListener( 'click', function () {
					self.info.close();
					inst.setActive( null );
				} );

				var color = cssVar( inst.el, '--mapify-pin-color', cssVar( inst.el, '--mapify-accent', '#e05a46' ) );
				var size = parseFloat( cssVar( inst.el, '--mapify-pin-size', '36' ) ) || 36;
				self.bounds = new gm.LatLngBounds();
				inst.items.forEach( function ( item, i ) {
					if ( ! hasCoords( item ) ) {
						return;
					}
					var pos = { lat: item.latitude, lng: item.longitude };
					var marker;
					if ( advanced ) {
						marker = new gm.marker.AdvancedMarkerElement( {
							position: pos,
							content: pinElement( item, cfg, i ),
							title: item.title,
						} );
					} else {
						var img = cfg.pinImage || item.pin_img;
						var c = item.color || color;
						marker = new gm.Marker( {
							position: pos,
							title: item.title,
							optimized: true,
							animation: cfg.animation === 'drop' ? gm.Animation.DROP : null,
							icon: img
								? { url: img, scaledSize: new gm.Size( size, size ) }
								: cfg.pinStyle === 'dot'
								? { path: gm.SymbolPath.CIRCLE, scale: size / 4, fillColor: c, fillOpacity: 1, strokeColor: '#fff', strokeWeight: 3 }
								: { url: markerDataUrl( c, size ), scaledSize: new gm.Size( size * 0.75, size ), anchor: new gm.Point( size * 0.375, size ) },
						} );
					}
					marker.addListener( 'click', function () {
						inst.activate( item, 'pin' );
					} );
					marker.mapifyItem = item;
					self.markers[ item.id ] = marker;
					self.bounds.extend( pos );
				} );

				self.visible = Object.keys( self.markers ).map( function ( k ) {
					return self.markers[ k ];
				} );
				if ( cfg.cluster && window.markerClusterer ) {
					var mc = window.markerClusterer;
					self.clusterer = new mc.MarkerClusterer( {
						map: map,
						markers: self.visible,
						algorithm: new mc.SuperClusterAlgorithm( { radius: cfg.clusterRadius, minPoints: cfg.clusterMin } ),
						renderer: {
							render: function ( cluster ) {
								var count = cluster.count;
								if ( advanced ) {
									var el = document.createElement( 'span' );
									el.className = 'mapify-cluster';
									el.innerHTML = '<span>' + count + '</span>';
									return new gm.marker.AdvancedMarkerElement( { position: cluster.position, content: el, zIndex: 1000 + count } );
								}
								var icon = clusterDataUrl( color, count );
								return new gm.Marker( {
									position: cluster.position,
									icon: { url: icon.url, scaledSize: new gm.Size( icon.size, icon.size ), anchor: new gm.Point( icon.size / 2, icon.size / 2 ) },
									label: { text: String( count ), color: '#fff', fontSize: '12px', fontWeight: '600' },
									zIndex: 1000 + count,
								} );
							},
						},
					} );
				} else {
					self.visible.forEach( function ( m ) {
						m.setMap ? m.setMap( map ) : ( m.map = map );
					} );
				}
				self.fit();
			} );
	};

	GoogleEngine.prototype.fit = function () {
		var cfg = this.inst.cfg;
		if ( ! cfg.fitBounds || ! this.visible.length ) {
			return;
		}
		if ( this.visible.length === 1 ) {
			var m = this.visible[ 0 ];
			this.map.setCenter( m.getPosition ? m.getPosition() : m.position );
			this.map.setZoom( Math.max( cfg.zoom, 14 ) );
			return;
		}
		var b = new window.google.maps.LatLngBounds();
		this.visible.forEach( function ( m ) {
			b.extend( m.getPosition ? m.getPosition() : m.position );
		} );
		this.map.fitBounds( b, 48 );
	};

	GoogleEngine.prototype.openPopup = function ( item, html ) {
		var m = this.markers[ item.id ];
		if ( ! m ) {
			return;
		}
		this.info.setContent( html );
		this.info.open( { anchor: m, map: this.map } );
	};

	GoogleEngine.prototype.focus = function ( item ) {
		var m = this.markers[ item.id ];
		if ( ! m ) {
			return;
		}
		this.map.panTo( m.getPosition ? m.getPosition() : m.position );
		if ( this.map.getZoom() < 14 ) {
			this.map.setZoom( 14 );
		}
	};

	GoogleEngine.prototype.filter = function ( ids ) {
		var self = this;
		this.info.close();
		this.visible = [];
		Object.keys( this.markers ).forEach( function ( id ) {
			var m = self.markers[ id ];
			var show = ! ids || ids.indexOf( String( id ) ) !== -1;
			if ( show ) {
				self.visible.push( m );
			}
			if ( ! self.clusterer ) {
				m.setMap ? m.setMap( show ? self.map : null ) : ( m.map = show ? self.map : null );
			}
		} );
		if ( this.clusterer ) {
			this.clusterer.clearMarkers();
			this.clusterer.addMarkers( this.visible );
		}
		this.fit();
	};

	GoogleEngine.prototype.resize = function () {};

	/* ------------------------------------------------------------------ Plane (SVG / image) */

	function mercY( lat ) {
		return Math.log( Math.tan( Math.PI / 4 + ( lat * Math.PI ) / 360 ) );
	}

	function cleanSvg( text ) {
		var doc = new DOMParser().parseFromString( text, 'image/svg+xml' );
		var svg = doc.documentElement;
		if ( ! svg || svg.nodeName.toLowerCase() !== 'svg' ) {
			return null;
		}
		svg.querySelectorAll( 'script,foreignObject,iframe,embed,object' ).forEach( function ( n ) {
			n.remove();
		} );
		svg.querySelectorAll( '*' ).forEach( function ( n ) {
			Array.prototype.slice.call( n.attributes ).forEach( function ( a ) {
				if ( /^on/i.test( a.name ) || ( /href$/i.test( a.name ) && /^\s*javascript:/i.test( a.value ) ) ) {
					n.removeAttribute( a.name );
				}
			} );
		} );
		return document.importNode( svg, true );
	}

	function PlaneEngine( inst ) {
		this.inst = inst;
		this.pins = {};
		this.regionItems = {};
	}

	PlaneEngine.prototype.mount = function () {
		var self = this;
		var inst = this.inst;
		var p = inst.cfg.plane || {};
		var plane = ( this.plane = document.createElement( 'div' ) );
		plane.className = 'mapify-plane';
		this.layer = document.createElement( 'div' );
		this.layer.className = 'mapify-plane__pins';
		inst.canvas.appendChild( plane );

		var ready;
		if ( p.svg ) {
			ready = fetch( p.svg, { credentials: 'same-origin' } )
				.then( function ( r ) {
					if ( ! r.ok ) {
						throw new Error( 'HTTP ' + r.status );
					}
					return r.text();
				} )
				.then( function ( text ) {
					var svg = cleanSvg( text );
					if ( ! svg ) {
						throw new Error( 'Invalid SVG' );
					}
					svg.removeAttribute( 'width' );
					svg.removeAttribute( 'height' );
					svg.setAttribute( 'class', ( svg.getAttribute( 'class' ) || '' ) + ' mapify-plane__svg' );
					svg.setAttribute( 'preserveAspectRatio', 'xMidYMid meet' );
					plane.appendChild( svg );
					var vb = svg.viewBox && svg.viewBox.baseVal;
					if ( ! vb || ! vb.width ) {
						var bb = svg.getBBox();
						svg.setAttribute( 'viewBox', [ bb.x, bb.y, bb.width, bb.height ].join( ' ' ) );
						vb = svg.viewBox.baseVal;
					}
					self.svg = svg;
					self.box = { x: vb.x, y: vb.y, w: vb.width, h: vb.height };
					if ( ! p.bounds && svg.getAttribute( 'data-north' ) ) {
						p.bounds = {
							north: parseFloat( svg.getAttribute( 'data-north' ) ),
							south: parseFloat( svg.getAttribute( 'data-south' ) ),
							west: parseFloat( svg.getAttribute( 'data-west' ) ),
							east: parseFloat( svg.getAttribute( 'data-east' ) ),
						};
						p.projection = svg.getAttribute( 'data-projection' ) || p.projection;
					}
				} );
		} else {
			ready = new Promise( function ( resolve, reject ) {
				var img = new Image();
				img.className = 'mapify-plane__image';
				img.alt = '';
				img.decoding = 'async';
				img.onload = function () {
					self.box = { x: 0, y: 0, w: img.naturalWidth, h: img.naturalHeight };
					resolve();
				};
				img.onerror = function () {
					reject( new Error( 'Image failed to load' ) );
				};
				img.src = p.image;
				plane.appendChild( img );
			} );
		}

		return ready.then( function () {
			plane.style.aspectRatio = self.box.w + ' / ' + self.box.h;
			plane.appendChild( self.layer );
			self.regions();
			self.drawPins();
			self.popup = document.createElement( 'div' );
			self.popup.className = 'mapify-plane__popup';
			self.popup.hidden = true;
			self.layer.appendChild( self.popup );
			plane.addEventListener( 'click', function ( e ) {
				if ( ! e.target.closest( '.mapify-pin, .mapify-plane__popup, .mapify-region' ) ) {
					self.closePopup();
					inst.setActive( null );
				}
			} );
			self.setupZoom();
			inst.ready();
		} );
	};

	/**
	 * Zoom and pan for image / SVG maps: buttons, mouse wheel, double click, drag and pinch.
	 * The plane is scaled with a CSS transform; pins and popups are scaled back so they keep their size.
	 */
	PlaneEngine.prototype.setupZoom = function () {
		var self = this;
		var inst = this.inst;
		var cfg = inst.cfg;
		var wrap = inst.canvas;
		var plane = this.plane;
		var z = ( this.z = { s: 1, x: 0, y: 0, max: Math.max( 1, cfg.planeMaxZoom || 1 ) } );
		if ( z.max <= 1 ) {
			return;
		}
		plane.style.transformOrigin = '0 0';
		wrap.classList.add( 'is-zoomable' );

		function apply() {
			var w = plane.offsetWidth;
			var h = plane.offsetHeight;
			z.s = Math.min( z.max, Math.max( 1, z.s ) );
			z.x = Math.min( 0, Math.max( w - w * z.s, z.x ) );
			z.y = Math.min( 0, Math.max( h - h * z.s, z.y ) );
			plane.style.transform = z.s === 1 ? '' : 'translate(' + z.x + 'px,' + z.y + 'px) scale(' + z.s + ')';
			plane.style.setProperty( '--mapify-pin-k', 1 / z.s );
			wrap.classList.toggle( 'is-zoomed', z.s > 1 );
			wrap.style.touchAction = z.s > 1 ? 'none' : 'pan-x pan-y';
			if ( self.zoomUi ) {
				self.zoomUi.querySelector( '[data-zoom="1"]' ).disabled = z.s >= z.max;
				self.zoomUi.querySelector( '[data-zoom="-1"]' ).disabled = z.s <= 1;
				self.zoomUi.querySelector( '[data-zoom="0"]' ).hidden = z.s <= 1;
			}
		}

		/** Zoom to scale ns keeping the screen point (cx, cy) in place. */
		function zoomAt( cx, cy, ns ) {
			var r = wrap.getBoundingClientRect();
			var px = cx - r.left;
			var py = cy - r.top;
			ns = Math.min( z.max, Math.max( 1, ns ) );
			var lx = ( px - z.x ) / z.s;
			var ly = ( py - z.y ) / z.s;
			z.x = px - lx * ns;
			z.y = py - ly * ns;
			z.s = ns;
			apply();
		}

		function zoomCenter( factor ) {
			var r = wrap.getBoundingClientRect();
			zoomAt( r.left + r.width / 2, r.top + r.height / 2, factor ? z.s * factor : 1 );
		}
		this.zoomBy = zoomCenter;

		if ( cfg.zoomControl ) {
			var ui = ( this.zoomUi = document.createElement( 'div' ) );
			ui.className = 'mapify__zoom mapify__zoom--' + ( cfg.zoomPosition || 'topleft' );
			[
				[ '1', '+', cfg.i18n.zoomIn ],
				[ '-1', '\u2212', cfg.i18n.zoomOut ],
				[ '0', '\u21BA', cfg.i18n.zoomReset ],
			].forEach( function ( b ) {
				var btn = document.createElement( 'button' );
				btn.type = 'button';
				btn.setAttribute( 'data-zoom', b[ 0 ] );
				btn.setAttribute( 'aria-label', b[ 2 ] );
				btn.title = b[ 2 ];
				btn.textContent = b[ 1 ];
				ui.appendChild( btn );
			} );
			ui.addEventListener( 'click', function ( e ) {
				var b = e.target.closest( '[data-zoom]' );
				if ( b ) {
					var d = parseInt( b.getAttribute( 'data-zoom' ), 10 );
					zoomCenter( d === 1 ? 1.5 : d === -1 ? 1 / 1.5 : 0 );
				}
			} );
			inst.stage.appendChild( ui );
		}

		if ( cfg.scrollZoom ) {
			wrap.addEventListener(
				'wheel',
				function ( e ) {
					e.preventDefault();
					zoomAt( e.clientX, e.clientY, z.s * ( e.deltaY < 0 ? 1.2 : 1 / 1.2 ) );
				},
				{ passive: false }
			);
		}
		if ( cfg.dblClickZoom !== false ) {
			wrap.addEventListener( 'dblclick', function ( e ) {
				if ( e.target.closest( '.mapify-pin, .mapify-plane__popup' ) ) {
					return;
				}
				e.preventDefault();
				zoomAt( e.clientX, e.clientY, z.s >= z.max ? 1 : z.s * 2 );
			} );
		}

		// Drag to pan (when zoomed) and pinch to zoom.
		var pts = {};
		var start = null;
		var moved = false;
		function count() {
			return Object.keys( pts ).length;
		}
		function pinchInfo() {
			var k = Object.keys( pts );
			var a = pts[ k[ 0 ] ];
			var b = pts[ k[ 1 ] ];
			return { d: Math.hypot( a.x - b.x, a.y - b.y ), cx: ( a.x + b.x ) / 2, cy: ( a.y + b.y ) / 2 };
		}
		wrap.addEventListener( 'pointerdown', function ( e ) {
			if ( e.button > 0 || e.target.closest( '.mapify-plane__popup, .mapify__zoom' ) ) {
				return;
			}
			pts[ e.pointerId ] = { x: e.clientX, y: e.clientY };
			moved = false;
			if ( count() === 2 && cfg.touchZoom !== false ) {
				var p = pinchInfo();
				start = { pinch: true, d: p.d, s: z.s };
			} else if ( count() === 1 ) {
				start = { x: e.clientX, y: e.clientY, zx: z.x, zy: z.y };
			}
		} );
		wrap.addEventListener( 'pointermove', function ( e ) {
			if ( ! pts[ e.pointerId ] || ! start ) {
				return;
			}
			pts[ e.pointerId ] = { x: e.clientX, y: e.clientY };
			if ( start.pinch && count() === 2 ) {
				var p = pinchInfo();
				moved = true;
				zoomAt( p.cx, p.cy, start.s * ( p.d / ( start.d || 1 ) ) );
			} else if ( ! start.pinch && z.s > 1 ) {
				var dx = e.clientX - start.x;
				var dy = e.clientY - start.y;
				if ( moved || Math.abs( dx ) + Math.abs( dy ) > 4 ) {
					if ( ! moved && wrap.setPointerCapture ) {
						try {
							wrap.setPointerCapture( e.pointerId );
						} catch ( err ) {}
					}
					moved = true;
					z.x = start.zx + dx;
					z.y = start.zy + dy;
					apply();
				}
			}
		} );
		function end( e ) {
			delete pts[ e.pointerId ];
			if ( count() === 1 ) {
				var k = Object.keys( pts )[ 0 ];
				start = { x: pts[ k ].x, y: pts[ k ].y, zx: z.x, zy: z.y };
			} else if ( ! count() ) {
				start = null;
			}
		}
		wrap.addEventListener( 'pointerup', end );
		wrap.addEventListener( 'pointercancel', end );
		// A drag should not also count as a click on a pin or region.
		wrap.addEventListener(
			'click',
			function ( e ) {
				if ( moved ) {
					e.stopPropagation();
					e.preventDefault();
					moved = false;
				}
			},
			true
		);
		window.addEventListener( 'resize', apply );
		apply();
	};

	/** Returns [fx, fy] fractions (0..1) inside the plane, or null. */
	PlaneEngine.prototype.position = function ( item ) {
		var p = this.inst.cfg.plane || {};
		var box = this.box;
		if ( typeof item.x === 'number' && typeof item.y === 'number' ) {
			if ( item.unit === 'px' ) {
				return [ item.x / box.w, item.y / box.h ];
			}
			return [ item.x / 100, item.y / 100 ];
		}
		var b = p.bounds;
		if ( ! b || ! hasCoords( item ) ) {
			return null;
		}
		var fx = ( item.longitude - b.west ) / ( b.east - b.west );
		var fy =
			p.projection === 'equirectangular'
				? ( b.north - item.latitude ) / ( b.north - b.south )
				: ( mercY( b.north ) - mercY( item.latitude ) ) / ( mercY( b.north ) - mercY( b.south ) );
		if ( fx < -0.05 || fx > 1.05 || fy < -0.05 || fy > 1.05 ) {
			return null;
		}
		return [ fx, fy ];
	};

	PlaneEngine.prototype.drawPins = function () {
		var self = this;
		var inst = this.inst;
		inst.items.forEach( function ( item, i ) {
			var pos = self.position( item );
			if ( ! pos ) {
				return;
			}
			var el = pinElement( item, inst.cfg, i );
			el.setAttribute( 'role', 'button' );
			el.setAttribute( 'tabindex', '0' );
			el.setAttribute( 'aria-label', item.title || '' );
			el.style.left = pos[ 0 ] * 100 + '%';
			el.style.top = pos[ 1 ] * 100 + '%';
			el.addEventListener( 'click', function ( e ) {
				e.stopPropagation();
				inst.activate( item, 'pin' );
			} );
			el.addEventListener( 'keydown', function ( e ) {
				if ( e.key === 'Enter' || e.key === ' ' ) {
					e.preventDefault();
					inst.activate( item, 'pin' );
				}
			} );
			item._pos = pos;
			self.pins[ item.id ] = el;
			self.layer.appendChild( el );
		} );
	};

	PlaneEngine.prototype.regions = function () {
		var self = this;
		var inst = this.inst;
		var p = inst.cfg.plane || {};
		if ( ! this.svg ) {
			return;
		}
		var nodes = Array.prototype.slice.call( this.svg.querySelectorAll( '.mapify-region, path[id], polygon[id], g[id], rect[id], circle[id], ellipse[id]' ) );
		nodes = nodes.filter( function ( n ) {
			var parent = n.parentElement && n.parentElement.closest( '[id]' );
			return ! parent || parent === self.svg || nodes.indexOf( parent ) === -1;
		} );
		var tip = document.createElement( 'div' );
		tip.className = 'mapify-plane__tip';
		tip.hidden = true;
		this.plane.appendChild( tip );

		var lang = p.labels || 'en';
		nodes.forEach( function ( n ) {
			n.classList.add( 'mapify-region' );
			var title = n.querySelector( 'title' );
			var name =
				( lang === 'fa' && n.getAttribute( 'data-name-fa' ) ) ||
				n.getAttribute( 'data-name' ) ||
				( title && title.textContent ) ||
				( n.id || '' ).replace( /[-_]+/g, ' ' );
			n.setAttribute( 'data-label', name );
			self.regionItems[ n.id ] = [];
		} );

		// Which branches fall inside each region (uses the SVG geometry itself).
		if ( window.DOMPoint ) {
			inst.items.forEach( function ( item ) {
				var pos = self.position( item );
				if ( ! pos ) {
					return;
				}
				var pt = new DOMPoint( self.box.x + pos[ 0 ] * self.box.w, self.box.y + pos[ 1 ] * self.box.h );
				nodes.some( function ( n ) {
					var shapes = n.isPointInFill ? [ n ] : Array.prototype.slice.call( n.querySelectorAll( 'path,polygon,rect,circle,ellipse' ) );
					var hit = shapes.some( function ( s ) {
						try {
							return s.isPointInFill( pt );
						} catch ( e ) {
							return false;
						}
					} );
					if ( hit ) {
						self.regionItems[ n.id ].push( String( item.id ) );
					}
					return hit;
				} );
			} );
		}

		nodes.forEach( function ( n ) {
			var ids = self.regionItems[ n.id ] || [];
			if ( p.regionHighlight && ids.length ) {
				n.classList.add( 'has-items' );
			}
			if ( p.regionTooltip ) {
				n.addEventListener( 'mousemove', function ( e ) {
					var r = self.plane.getBoundingClientRect();
					var k = self.z ? self.z.s : 1;
					tip.textContent = n.getAttribute( 'data-label' ) + ( ids.length ? ' (' + ids.length + ')' : '' );
					tip.style.left = ( e.clientX - r.left ) / k + 'px';
					tip.style.top = ( e.clientY - r.top ) / k + 'px';
					tip.hidden = false;
				} );
				n.addEventListener( 'mouseleave', function () {
					tip.hidden = true;
				} );
			}
			if ( p.regionClick === 'filter' ) {
				n.addEventListener( 'click', function ( e ) {
					e.stopPropagation();
					var active = n.classList.contains( 'is-selected' );
					nodes.forEach( function ( o ) {
						o.classList.remove( 'is-selected' );
					} );
					if ( active ) {
						inst.applyFilter( null, '' );
					} else {
						n.classList.add( 'is-selected' );
						inst.applyFilter( ids, n.getAttribute( 'data-label' ) );
					}
				} );
			}
		} );
		this.regionNodes = nodes;
	};

	PlaneEngine.prototype.openPopup = function ( item, html ) {
		var el = this.pins[ item.id ];
		if ( ! el ) {
			return;
		}
		var pop = this.popup;
		pop.innerHTML = html;
		var close = document.createElement( 'button' );
		close.type = 'button';
		close.className = 'mapify-popup__close';
		close.setAttribute( 'aria-label', this.inst.cfg.i18n.close );
		close.innerHTML = '&times;';
		close.addEventListener( 'click', this.closePopup.bind( this ) );
		pop.firstChild.appendChild( close );
		pop.style.left = el.style.left;
		pop.style.top = el.style.top;
		var cr = this.inst.canvas.getBoundingClientRect();
		var zoomed = this.z && this.z.s > 1;
		pop.classList.toggle( 'is-below', ! zoomed && item._pos[ 1 ] < 0.4 );
		pop.classList.toggle( 'is-dot', el.classList.contains( 'mapify-pin--dot' ) );
		pop.hidden = false;
		// A zoomed map clips its content, so open the popup below the pin when there is no room above.
		if ( zoomed && pop.getBoundingClientRect().top < cr.top + 4 ) {
			pop.classList.add( 'is-below' );
		}
		// Keep the popup inside the visible map horizontally.
		pop.style.setProperty( '--shift', '0px' );
		var r = pop.getBoundingClientRect();
		var pr = cr;
		var shift = 0;
		if ( r.left < pr.left + 8 ) {
			shift = pr.left + 8 - r.left;
		} else if ( r.right > pr.right - 8 ) {
			shift = pr.right - 8 - r.right;
		}
		pop.style.setProperty( '--shift', shift + 'px' );
	};

	PlaneEngine.prototype.closePopup = function () {
		if ( this.popup ) {
			this.popup.hidden = true;
		}
	};

	PlaneEngine.prototype.focus = function () {};

	PlaneEngine.prototype.filter = function ( ids ) {
		var self = this;
		this.closePopup();
		Object.keys( this.pins ).forEach( function ( id ) {
			self.pins[ id ].hidden = !! ids && ids.indexOf( String( id ) ) === -1;
		} );
		if ( ! ids && this.regionNodes ) {
			this.regionNodes.forEach( function ( n ) {
				n.classList.remove( 'is-selected' );
			} );
		}
	};

	PlaneEngine.prototype.resize = function () {};

	/* ------------------------------------------------------------------ Instance */

	function isAndroid() {
		return /Android/i.test( navigator.userAgent );
	}

	function isIOS() {
		return /iPad|iPhone|iPod/.test( navigator.userAgent ) || ( /Macintosh/.test( navigator.userAgent ) && navigator.maxTouchPoints > 1 );
	}

	function isApple() {
		return isIOS() || /Macintosh/.test( navigator.userAgent );
	}

	function isMobile() {
		return isAndroid() || isIOS() || /Mobi/i.test( navigator.userAgent );
	}

	function onPlatform( platform ) {
		switch ( platform ) {
			case 'android':
				return isAndroid();
			case 'apple':
				return isApple();
			case 'mobile':
				return isMobile();
			case 'desktop':
				return ! isMobile();
		}
		return true;
	}

	/** Fill an app URL template: {lat} {lng} {title} {address}. */
	function appUrl( template, item ) {
		var url = String( template || '' )
			.replace( /\{lat\}/g, item.latitude )
			.replace( /\{lng\}/g, item.longitude )
			.replace( /\{title\}/g, encodeURIComponent( item.title || '' ) )
			.replace( /\{address\}/g, encodeURIComponent( item.address || '' ) );
		return /^\s*(javascript|data|vbscript):/i.test( url ) ? '' : url;
	}

	var GOOGLE_DIR = 'https://www.google.com/maps/dir/?api=1&destination={lat},{lng}';

	function Mapify( el ) {
		this.el = el;
		try {
			this.cfg = JSON.parse( el.getAttribute( 'data-mapify' ) );
		} catch ( e ) {
			return;
		}
		this.items = this.cfg.items || [];
		this.byId = {};
		// Current filters: search text, chosen categories and the clicked region (ids or null).
		this.state = { term: '', cats: [], region: null };
		var self = this;
		this.items.forEach( function ( item ) {
			self.byId[ String( item.id ) ] = item;
			item._search = [ item.title, item.address, item.phone ]
				.concat(
					item.categories
						? Object.keys( item.categories ).map( function ( k ) {
								return item.categories[ k ];
						  } )
						: []
				)
				.join( ' ' )
				.toLowerCase();
		} );
		this.canvas = el.querySelector( '.mapify__map' );
		this.stage = el.querySelector( '.mapify__stage' );
		this.dir = getComputedStyle( el ).direction || 'ltr';
		var E = this.cfg.engine;
		this.engine = E === 'google' ? new GoogleEngine( this ) : E === 'iran' || E === 'svg' || E === 'image' ? new PlaneEngine( this ) : new LeafletEngine( this );
		this.bindList();
		this.bindCategories();
		this.bindDirections();
		this.bindFullscreen();
		this.engine.mount().catch( function ( err ) {
			self.ready();
			self.notice( err && err.message ? err.message : String( err ) );
			window.console && console.error( err ); // eslint-disable-line no-console
		} );
	}

	Mapify.prototype.ready = function () {
		this.el.classList.add( 'is-ready' );
	};

	Mapify.prototype.notice = function ( msg ) {
		var n = document.createElement( 'div' );
		n.className = 'mapify__notice';
		n.textContent = msg;
		this.stage.appendChild( n );
	};

	/** Custom attribution text for engines whose own attribution cannot be replaced (Google). */
	Mapify.prototype.attributionOverlay = function ( html ) {
		var n = document.createElement( 'div' );
		n.className = 'mapify__attribution';
		n.innerHTML = html;
		this.stage.appendChild( n );
	};

	Mapify.prototype.popupImage = function ( item ) {
		var mode = this.cfg.popupImage || 'featured';
		var pin = item.pin_img || '';
		if ( mode === 'none' ) {
			return '';
		}
		if ( mode === 'pin' ) {
			return pin;
		}
		if ( mode === 'auto' ) {
			return item.image || pin;
		}
		return item.image || '';
	};

	Mapify.prototype.directionsButton = function ( item ) {
		var d = this.cfg.directions;
		if ( ! d || ! hasCoords( item ) ) {
			return '';
		}
		return '<a class="mapify-card__directions" href="' + esc( appUrl( GOOGLE_DIR, item ) ) + '" target="_blank" rel="noopener" data-mapify-directions="' + esc( item.id ) + '">'
			+ '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21.7 11.3 12.7 2.3a1 1 0 0 0-1.4 0l-9 9a1 1 0 0 0 0 1.4l9 9a1 1 0 0 0 1.4 0l9-9a1 1 0 0 0 0-1.4ZM14 14.5V12h-4v3H8v-4a1 1 0 0 1 1-1h5V7.5l3.5 3.5-3.5 3.5Z"/></svg>'
			+ '<span>' + esc( d.label ) + '</span></a>';
	};

	Mapify.prototype.popupHtml = function ( item ) {
		var body;
		if ( item.custom ) {
			body = '<div class="mapify-card"><div class="mapify-card__body">' + ( item.content || '<h3 class="mapify-card__title">' + esc( item.title ) + '</h3>' ) + '</div></div>';
		} else {
			var data = Object.assign( {}, item );
			var btn = this.directionsButton( item );
			var img = this.popupImage( item );
			// {image} is always the featured image; {popup_image} follows the "Popup image" setting.
			data.image = item.image || '';
			data.pin_image = item.pin_img || '';
			data.popup_image = img || ( this.cfg.popupImage === 'none' ? '' : this.cfg.placeholder || '' );
			data._directions = btn;
			var tpl = String( this.cfg.template || '' );
			body = fillTemplate( tpl, data );
			if ( btn && tpl.indexOf( '{directions' ) === -1 ) {
				body += '<div class="mapify-card__actions mapify-card__actions--after">' + btn + '</div>';
			}
			if ( ! data.popup_image && tpl.indexOf( '{popup_image' ) !== -1 ) {
				body = body.replace( /<img\b[^>]*class="[^"]*mapify-card__image[^"]*"[^>]*>/gi, '' );
			}
		}
		return '<div class="mapify-popup" dir="' + this.dir + '">' + body + '</div>';
	};

	Mapify.prototype.setActive = function ( id ) {
		this.el.querySelectorAll( '.is-active:not(.mapify__cat)' ).forEach( function ( n ) {
			n.classList.remove( 'is-active' );
		} );
		var select = this.el.querySelector( '.mapify__select' );
		if ( id === null || id === undefined ) {
			if ( select ) {
				select.value = '';
			}
			return;
		}
		this.el.querySelectorAll( '[data-id="' + String( id ).replace( /"/g, '' ) + '"]' ).forEach( function ( n ) {
			n.classList.add( 'is-active' );
		} );
		if ( select ) {
			select.value = String( id );
		}
	};

	/** Bring the map into view when it is (partly) off screen, e.g. below a long list. */
	Mapify.prototype.scrollToMap = function () {
		var r = this.stage.getBoundingClientRect();
		var vh = window.innerHeight || document.documentElement.clientHeight;
		if ( r.top >= 0 && r.bottom <= vh ) {
			return;
		}
		var reduce = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
		this.stage.scrollIntoView( { behavior: reduce ? 'auto' : 'smooth', block: r.height > vh ? 'start' : 'center' } );
	};

	/** from: 'pin' | 'list' */
	Mapify.prototype.activate = function ( item, from ) {
		var cfg = this.cfg;
		this.setActive( item.id );
		if ( from === 'list' ) {
			if ( cfg.listScroll !== false ) {
				this.scrollToMap();
			}
			this.engine.focus( item );
			if ( cfg.listPopup !== false ) {
				this.engine.openPopup( item, this.popupHtml( item ) );
			}
			return;
		}
		var action = cfg.action;
		if ( item.custom ) {
			action = item.content || ! item.url ? 'popup' : 'url';
		}
		if ( action === 'url' && item.url ) {
			window.open( item.url, cfg.target || '_self', cfg.target === '_blank' ? 'noopener' : '' );
		} else if ( action === 'popup' ) {
			this.engine.openPopup( item, this.popupHtml( item ) );
		}
	};

	Mapify.prototype.bindList = function () {
		var self = this;
		var list = this.el.querySelector( '.mapify__list' );
		if ( ! list ) {
			return;
		}
		list.addEventListener( 'click', function ( e ) {
			var btn = e.target.closest( '.mapify__item' );
			var row = e.target.closest( 'tr.mapify__row' );
			var id = btn ? btn.getAttribute( 'data-id' ) : row && ! e.target.closest( 'a' ) ? row.getAttribute( 'data-id' ) : null;
			if ( id && self.byId[ id ] ) {
				self.activate( self.byId[ id ], 'list' );
			}
			var nav = e.target.closest( '.mapify__carousel-btn' );
			if ( nav ) {
				var track = nav.closest( '.mapify__group' ).querySelector( '.mapify__items' );
				var step = track.clientWidth * 0.8 * parseInt( nav.getAttribute( 'data-dir' ), 10 );
				track.scrollBy( { left: self.dir === 'rtl' ? -step : step, behavior: 'smooth' } );
			}
			if ( e.target.closest( '.mapify__reset' ) ) {
				self.applyFilter( null, '' );
			}
		} );
		var select = list.querySelector( '.mapify__select' );
		if ( select ) {
			select.addEventListener( 'change', function () {
				if ( self.byId[ select.value ] ) {
					self.activate( self.byId[ select.value ], 'list' );
				}
			} );
		}
		var input = list.querySelector( '.mapify__search-input' );
		if ( input ) {
			var timer;
			input.addEventListener( 'input', function () {
				clearTimeout( timer );
				timer = setTimeout( function () {
					self.search( input.value );
				}, 150 );
			} );
		}
	};

	/** Category chips above the list and on the map; both stay in sync. */
	Mapify.prototype.bindCategories = function () {
		var self = this;
		var bars = this.el.querySelectorAll( '.mapify__cats' );
		if ( ! bars.length ) {
			return;
		}
		bars.forEach( function ( bar ) {
			bar.addEventListener( 'click', function ( e ) {
				var chip = e.target.closest( '.mapify__cat' );
				if ( ! chip ) {
					return;
				}
				var id = chip.getAttribute( 'data-cat' );
				var cats = self.state.cats.slice();
				if ( ! id ) {
					cats = [];
				} else if ( cats.indexOf( id ) !== -1 ) {
					cats.splice( cats.indexOf( id ), 1 );
				} else {
					cats = self.cfg.catMultiple ? cats.concat( [ id ] ) : [ id ];
				}
				self.filterCategories( cats );
			} );
		} );
	};

	Mapify.prototype.filterCategories = function ( cats ) {
		this.state.cats = cats || [];
		var active = this.state.cats;
		this.el.querySelectorAll( '.mapify__cat' ).forEach( function ( chip ) {
			var id = chip.getAttribute( 'data-cat' );
			var on = id ? active.indexOf( id ) !== -1 : ! active.length;
			chip.classList.toggle( 'is-active', on );
			chip.setAttribute( 'aria-pressed', on ? 'true' : 'false' );
		} );
		this.refresh();
	};

	Mapify.prototype.search = function ( term ) {
		this.state.term = String( term || '' ).trim().toLowerCase();
		this.refresh();
	};

	/** Region filter: limits list and pins to ids (null = all). */
	Mapify.prototype.applyFilter = function ( ids, label ) {
		var list = this.el.querySelector( '.mapify__list' );
		this.state.region = ids ? ids.map( String ) : null;
		if ( list ) {
			var input = list.querySelector( '.mapify__search-input' );
			if ( input ) {
				input.value = '';
			}
			this.state.term = '';
			var chip = list.querySelector( '.mapify__filter' );
			if ( ids ) {
				if ( ! chip ) {
					chip = document.createElement( 'div' );
					chip.className = 'mapify__filter';
					list.insertBefore( chip, list.querySelector( '.mapify__groups' ) );
				}
				chip.innerHTML = '<span>' + esc( label ) + '</span><button type="button" class="mapify__reset">' + esc( this.cfg.i18n.showAll ) + '</button>';
			} else if ( chip ) {
				chip.remove();
			}
		}
		if ( ! ids && this.engine.regionNodes ) {
			this.engine.regionNodes.forEach( function ( n ) {
				n.classList.remove( 'is-selected' );
			} );
		}
		this.refresh();
	};

	Mapify.prototype.matches = function ( item ) {
		var st = this.state;
		if ( st.term && ( item.custom || ( item._search || '' ).indexOf( st.term ) === -1 ) ) {
			return false;
		}
		if ( st.cats.length ) {
			var cats = item.cats || [];
			var hit = st.cats.some( function ( c ) {
				return cats.indexOf( c ) !== -1;
			} );
			if ( ! hit ) {
				return false;
			}
		}
		if ( st.region && st.region.indexOf( String( item.id ) ) === -1 ) {
			return false;
		}
		return true;
	};

	/** Apply search, category and region filters to the list and the map. */
	Mapify.prototype.refresh = function () {
		var self = this;
		var st = this.state;
		var filtered = !! ( st.term || st.cats.length || st.region );
		var ids = [];
		this.items.forEach( function ( item ) {
			if ( self.matches( item ) ) {
				ids.push( String( item.id ) );
			}
		} );
		this.el.querySelectorAll( '.mapify__row' ).forEach( function ( row ) {
			var show = ! filtered || ids.indexOf( row.getAttribute( 'data-id' ) ) !== -1;
			row.hidden = ! show;
			if ( row.tagName === 'OPTION' ) {
				row.disabled = ! show;
			}
		} );
		this.syncGroups();
		this.engine.filter( filtered ? ids : null );
	};

	Mapify.prototype.syncGroups = function () {
		var any = false;
		this.el.querySelectorAll( '.mapify__group' ).forEach( function ( g ) {
			var visible = g.querySelector( '.mapify__row:not([hidden])' );
			g.hidden = ! visible;
			any = any || !! visible;
		} );
		this.el.querySelectorAll( '.mapify__select optgroup' ).forEach( function ( g ) {
			g.hidden = ! g.querySelector( '.mapify__row:not([hidden])' );
		} );
		var empty = this.el.querySelector( '.mapify__empty' );
		if ( empty ) {
			empty.hidden = any;
		}
	};

	/* Directions ---------------------------------------------------------- */

	Mapify.prototype.bindDirections = function () {
		var self = this;
		if ( ! this.cfg.directions ) {
			return;
		}
		// Popups live inside the map, so one delegated listener covers every engine.
		this.el.addEventListener( 'click', function ( e ) {
			var a = e.target.closest( '[data-mapify-directions]' );
			if ( ! a ) {
				return;
			}
			var item = self.byId[ a.getAttribute( 'data-mapify-directions' ) ];
			if ( ! item || ! hasCoords( item ) ) {
				return;
			}
			e.preventDefault();
			self.directions( item );
		} );
	};

	/** Apps from Map Settings → Navigation that fit this device. */
	Mapify.prototype.directionApps = function () {
		return ( this.cfg.directions.apps || [] ).filter( function ( app ) {
			return onPlatform( app.platform );
		} );
	};

	Mapify.prototype.directions = function ( item ) {
		var d = this.cfg.directions;
		var geo = 'geo:' + item.latitude + ',' + item.longitude + '?q=' + item.latitude + ',' + item.longitude + '(' + encodeURIComponent( item.title || '' ) + ')';
		if ( d.mode === 'google' ) {
			window.open( appUrl( GOOGLE_DIR, item ), '_blank', 'noopener' );
			return;
		}
		if ( d.mode === 'direct' ) {
			// The phone's default map app, without a list.
			if ( isAndroid() ) {
				window.location.href = geo;
			} else if ( isIOS() ) {
				window.location.href = 'maps://?daddr=' + item.latitude + ',' + item.longitude + '&q=' + encodeURIComponent( item.title || '' );
			} else {
				var first = this.directionApps()[ 0 ];
				window.open( appUrl( first ? first.url : GOOGLE_DIR, item ) || appUrl( GOOGLE_DIR, item ), '_blank', 'noopener' );
			}
			return;
		}
		if ( d.mode === 'auto' && isAndroid() ) {
			// geo: makes Android list every installed map app.
			window.location.href = geo;
			return;
		}
		this.directionsSheet( item );
	};

	Mapify.prototype.directionsSheet = function ( item ) {
		var self = this;
		var d = this.cfg.directions;
		var old = document.querySelector( '.mapify-sheet' );
		if ( old ) {
			old.remove();
		}
		var apps = this.directionApps();
		if ( ! apps.length ) {
			window.open( appUrl( GOOGLE_DIR, item ), '_blank', 'noopener' );
			return;
		}
		var sheet = document.createElement( 'div' );
		sheet.className = 'mapify-sheet';
		sheet.setAttribute( 'role', 'dialog' );
		sheet.setAttribute( 'aria-modal', 'true' );
		sheet.setAttribute( 'aria-label', d.title );
		sheet.setAttribute( 'dir', this.dir );
		var html = '<div class="mapify-sheet__panel"><p class="mapify-sheet__title">' + esc( d.title ) + '<span>' + esc( item.title || '' ) + '</span></p><div class="mapify-sheet__apps">';
		apps.forEach( function ( app ) {
			var url = appUrl( app.url, item );
			if ( ! url ) {
				return;
			}
			var web = /^https?:/i.test( url );
			html += '<a class="mapify-sheet__app mapify-sheet__app--' + esc( app.id ) + '" href="' + esc( url ) + '"' + ( web ? ' target="_blank" rel="noopener"' : '' ) + '>'
				+ ( app.icon ? '<img class="mapify-sheet__icon" src="' + esc( app.icon ) + '" alt="" />' : '' )
				+ '<span>' + esc( app.label ) + '</span></a>';
		} );
		html += '</div><button type="button" class="mapify-sheet__cancel">' + esc( this.cfg.i18n.cancel ) + '</button></div>';
		sheet.innerHTML = html;
		function close() {
			sheet.remove();
			document.removeEventListener( 'keydown', onKey );
		}
		function onKey( e ) {
			if ( e.key === 'Escape' ) {
				close();
			}
		}
		sheet.addEventListener( 'click', function ( e ) {
			if ( e.target === sheet || e.target.closest( '.mapify-sheet__cancel' ) || e.target.closest( '.mapify-sheet__app' ) ) {
				setTimeout( close, 0 );
			}
		} );
		document.addEventListener( 'keydown', onKey );
		( document.fullscreenElement === self.stage ? self.stage : document.body ).appendChild( sheet );
		var first = sheet.querySelector( '.mapify-sheet__app, .mapify-sheet__cancel' );
		if ( first ) {
			first.focus();
		}
	};

	Mapify.prototype.bindFullscreen = function () {
		var self = this;
		if ( ! this.cfg.fullscreen || ! this.stage.requestFullscreen ) {
			return;
		}
		var btn = document.createElement( 'button' );
		btn.type = 'button';
		btn.className = 'mapify__fs';
		btn.setAttribute( 'aria-label', this.cfg.i18n.fullscreen );
		btn.title = this.cfg.i18n.fullscreen;
		btn.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 9V4h5v2H6v3H4Zm10-5h6v5h-2V6h-4V4ZM6 15v3h3v2H4v-5h2Zm12 0h2v5h-5v-2h3v-3Z"/></svg>';
		btn.addEventListener( 'click', function () {
			if ( document.fullscreenElement ) {
				document.exitFullscreen();
			} else {
				self.stage.requestFullscreen();
			}
		} );
		document.addEventListener( 'fullscreenchange', function () {
			self.stage.classList.toggle( 'is-fullscreen', document.fullscreenElement === self.stage );
			setTimeout( function () {
				self.engine.resize();
			}, 60 );
		} );
		this.stage.appendChild( btn );
	};

	/* ------------------------------------------------------------------ boot */

	function boot( root ) {
		( root || document ).querySelectorAll( '.mapify[data-mapify]:not(.is-init)' ).forEach( function ( el ) {
			el.classList.add( 'is-init' );
			el.mapify = new Mapify( el );
		} );
	}

	window.Mapify = { init: boot, fillTemplate: fillTemplate };

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			boot();
		} );
	} else {
		boot();
	}

	// Page builders re-render widgets in place (Elementor, WPBakery frontend editor).
	if ( window.MutationObserver ) {
		new MutationObserver( function ( list ) {
			for ( var i = 0; i < list.length; i++ ) {
				var added = list[ i ].addedNodes;
				for ( var j = 0; j < added.length; j++ ) {
					var n = added[ j ];
					if ( n.nodeType === 1 && ( n.matches( '.mapify[data-mapify]' ) || n.querySelector( '.mapify[data-mapify]' ) ) ) {
						boot();
						return;
					}
				}
			}
		} ).observe( document.documentElement, { childList: true, subtree: true } );
	}
	window.addEventListener( 'elementor/frontend/init', function () {
		if ( window.elementorFrontend && window.elementorFrontend.hooks ) {
			window.elementorFrontend.hooks.addAction( 'frontend/element_ready/mapify-map.default', function ( $scope ) {
				boot( $scope && $scope[ 0 ] );
			} );
		}
	} );
} )();
