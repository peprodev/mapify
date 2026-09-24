<?php
/**
 * Renders a map from normalized settings. Shared by the shortcode, Elementor and WPBakery.
 *
 * @package Mapify
 */

namespace Mapify;

defined( 'ABSPATH' ) || exit;

class Renderer {

	protected static $count = 0;

	public static function tile_provider( array $s ) {
		switch ( $s['maptype'] ) {
			case 'mapbox':
				$style = 'custom' === $s['mapbox_style'] ? trim( $s['mapbox_custom'] ) : 'mapbox/' . $s['mapbox_style'];
				if ( '' === $style || false === strpos( $style, '/' ) ) {
					$style = 'mapbox/streets-v12';
				}
				return array(
					'url'         => 'https://api.mapbox.com/styles/v1/' . $style . '/tiles/512/{z}/{x}/{y}@2x?access_token=' . rawurlencode( Options::get( 'mapbox_token' ) ),
					'attribution' => '© <a href="https://www.mapbox.com/about/maps/">Mapbox</a> © <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
					'tileSize'    => 512,
					'zoomOffset'  => -1,
					'maxZoom'     => 22,
				);
			case 'mapir':
				return array(
					'url'         => 'https://map.ir/shiveh/xyz/1.0.0/Shiveh:Shiveh@EPSG:3857@png/{z}/{x}/{y}.png?x-api-key=' . rawurlencode( Options::get( 'mapir_api_key' ) ),
					'attribution' => '© <a href="https://map.ir/">Map.ir</a>',
					'maxZoom'     => 19,
				);
			case 'neshan':
				// Neshan ships its own Leaflet build that draws the base layer from the map options.
				return array(
					'neshan' => array(
						'key'     => Options::get( 'neshan_api_key' ),
						'maptype' => $s['neshan_style'],
						'js'      => 'https://static.neshan.org/sdk/leaflet/v1.9.4/neshan-sdk/v1.0.8/index.js',
						'css'     => 'https://static.neshan.org/sdk/leaflet/v1.9.4/neshan-sdk/v1.0.8/index.css',
					),
					'maxZoom' => 19,
				);
			case 'parsimap':
				return array(
					'url'         => 'https://api.parsimap.ir/tile/' . rawurlencode( $s['parsimap_style'] ) . '/{z}/{x}/{y}?key=' . rawurlencode( Options::get( 'parsimap_api_key' ) ),
					'attribution' => '© <a href="https://www.parsimap.ir">Parsimap</a> © <a href="https://www.openstreetmap.org/about/">OpenStreetMap</a>',
					'maxZoom'     => 18,
				);
			case 'mapup':
				return array(
					'url'         => 'https://tiles.mapup.ir/styles/basic-preview/{z}/{x}/{y}.png',
					'attribution' => '© <a href="https://mapup.ir">Mapup</a> © <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
					'maxZoom'     => 20,
				);
			case 'custom':
				return array(
					'url'         => esc_url_raw( $s['tiles_url'] ),
					'attribution' => wp_kses_post( $s['tiles_attribution'] ),
					'maxZoom'     => 20,
				);
		}
		$esri     = 'https://server.arcgisonline.com/ArcGIS/rest/services/';
		$osm_attr = '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors';
		$osm_url  = 'https://tile.openstreetmap.org/{z}/{x}/{y}.png';
		// slug => [url, attribution, max zoom, extras (overlay url, CSS filter)].
		$osm = apply_filters(
			'mapify_osm_tile_styles',
			array(
				'osm'                 => array( $osm_url, $osm_attr, 19 ),
				'osm-gray'            => array( $osm_url, $osm_attr, 19, array( 'filter' => 'gray' ) ),
				'osm-dark'            => array( $osm_url, $osm_attr, 19, array( 'filter' => 'dark' ) ),
				'osm-sepia'           => array( $osm_url, $osm_attr, 19, array( 'filter' => 'sepia' ) ),
				'osm-hot'             => array( 'https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png', $osm_attr . ', Humanitarian OSM Team', 19 ),
				'osm-fr'              => array( 'https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png', '© OpenStreetMap France | ' . $osm_attr, 20 ),
				'osm-de'              => array( 'https://tile.openstreetmap.de/{z}/{x}/{y}.png', $osm_attr, 18 ),
				'cyclosm'             => array( 'https://{s}.tile-cyclosm.openstreetmap.fr/cyclosm/{z}/{x}/{y}.png', '<a href="https://www.cyclosm.org">CyclOSM</a> | ' . $osm_attr, 20 ),
				'opentopo'            => array( 'https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', '© OpenStreetMap contributors, SRTM | © <a href="https://opentopomap.org">OpenTopoMap</a>', 17 ),
				'esri-light'          => array( $esri . 'Canvas/World_Light_Gray_Base/MapServer/tile/{z}/{y}/{x}', 'Tiles © Esri — Esri, HERE, Garmin, © OpenStreetMap contributors', 16 ),
				'esri-dark'           => array( $esri . 'Canvas/World_Dark_Gray_Base/MapServer/tile/{z}/{y}/{x}', 'Tiles © Esri — Esri, HERE, Garmin, © OpenStreetMap contributors', 16 ),
				'esri-street'         => array( $esri . 'World_Street_Map/MapServer/tile/{z}/{y}/{x}', 'Tiles © Esri', 19 ),
				'esri-topo'           => array( $esri . 'World_Topo_Map/MapServer/tile/{z}/{y}/{x}', 'Tiles © Esri', 19 ),
				'esri-natgeo'         => array( $esri . 'NatGeo_World_Map/MapServer/tile/{z}/{y}/{x}', 'Tiles © Esri — National Geographic, Esri, DeLorme, NAVTEQ, UNEP-WCMC, USGS, NASA, ESA, METI, NRCAN, GEBCO, NOAA, iPC', 16 ),
				'esri-imagery'        => array( $esri . 'World_Imagery/MapServer/tile/{z}/{y}/{x}', 'Tiles © Esri — Source: Esri, Maxar, Earthstar Geographics', 19 ),
				'esri-imagery-labels' => array( $esri . 'World_Imagery/MapServer/tile/{z}/{y}/{x}', 'Tiles © Esri — Source: Esri, Maxar, Earthstar Geographics', 19, array( 'overlay' => $esri . 'Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}' ) ),
				'esri-terrain'        => array( $esri . 'World_Terrain_Base/MapServer/tile/{z}/{y}/{x}', 'Tiles © Esri — Source: USGS, Esri, TANA, DeLorme, and NPS', 13 ),
				'esri-shaded'         => array( $esri . 'World_Shaded_Relief/MapServer/tile/{z}/{y}/{x}', 'Tiles © Esri — Source: Esri', 13 ),
				'esri-physical'       => array( $esri . 'World_Physical_Map/MapServer/tile/{z}/{y}/{x}', 'Tiles © Esri — Source: US National Park Service', 8 ),
				'esri-ocean'          => array( $esri . 'Ocean/World_Ocean_Base/MapServer/tile/{z}/{y}/{x}', 'Tiles © Esri — Sources: GEBCO, NOAA, CHS, OSU, UNH, CSUMB, National Geographic, DeLorme, NAVTEQ, and Esri', 13 ),
			)
		);
		$pick  = isset( $osm[ $s['osm_style'] ] ) ? $osm[ $s['osm_style'] ] : $osm['osm'];
		$extra = isset( $pick[3] ) && is_array( $pick[3] ) ? $pick[3] : array();
		return array_merge(
			array(
				'url'         => $pick[0],
				'attribution' => $pick[1],
				'maxZoom'     => $pick[2],
			),
			array_intersect_key( $extra, array_flip( array( 'overlay', 'filter' ) ) )
		);
	}

	/**
	 * Check that the chosen engine can run; otherwise fall back to OpenStreetMap.
	 *
	 * @return string|null Notice for editors when a fallback happened.
	 */
	protected static function resolve_engine( array &$s ) {
		$missing = null;
		if ( 'google' === $s['maptype'] && '' === Options::get( 'google_api_key' ) ) {
			$missing = __( 'Google Maps needs an API key (Branches → Map Settings). Showing OpenStreetMap instead.', 'mapify' );
		} elseif ( 'mapbox' === $s['maptype'] && '' === Options::get( 'mapbox_token' ) ) {
			$missing = __( 'Mapbox needs an access token (Branches → Map Settings). Showing OpenStreetMap instead.', 'mapify' );
		} elseif ( 'mapir' === $s['maptype'] && '' === Options::get( 'mapir_api_key' ) ) {
			$missing = __( 'Map.ir needs an API key (Branches → Map Settings). Showing OpenStreetMap instead.', 'mapify' );
		} elseif ( 'neshan' === $s['maptype'] && '' === Options::get( 'neshan_api_key' ) ) {
			$missing = __( 'Neshan needs a web map API key (Branches → Map Settings). Showing OpenStreetMap instead.', 'mapify' );
		} elseif ( 'parsimap' === $s['maptype'] && '' === Options::get( 'parsimap_api_key' ) ) {
			$missing = __( 'Parsimap needs an API token (Branches → Map Settings). Showing OpenStreetMap instead.', 'mapify' );
		} elseif ( 'custom' === $s['maptype'] && '' === $s['tiles_url'] ) {
			$missing = __( 'Enter a tile URL template for the custom tile server. Showing OpenStreetMap instead.', 'mapify' );
		}
		if ( $missing ) {
			$s['maptype']   = 'osm';
			$s['osm_style'] = 'osm';
		}
		return $missing;
	}

	/**
	 * Map attribution: the provider's copyright plus a link to Mapify on WordPress.org.
	 */
	public static function attribution() {
		return array(
			'mode'  => 'default',
			'html'  => '',
			'brand' => '<a href="' . esc_url( MAPIFY_REPO_URL ) . '" target="_blank" rel="noopener">© PeproDev Mapify</a>',
		);
	}

	public static function config( array $s, array $branches ) {
		$center = array_map( 'floatval', array_pad( explode( ',', $s['center_coordinate'] ), 2, 0 ) );
		$config = array(
			'engine'       => $s['maptype'],
			'center'       => array( $center[0], $center[1] ),
			'zoom'         => (int) $s['default_zoom'],
			'fitBounds'    => (bool) $s['fit_bounds'],
			'scrollZoom'   => (bool) $s['scroll_zoom'],
			'controls'     => ! $s['disabledefaultui'],
			'zoomControl'  => ! $s['disabledefaultui'] && $s['zoom_control'],
			'zoomPosition' => $s['zoom_position'],
			'dblClickZoom' => (bool) $s['double_click_zoom'],
			'touchZoom'    => (bool) $s['touch_zoom'],
			'minZoom'      => '' === $s['min_zoom'] ? null : (int) $s['min_zoom'],
			'maxZoom'      => '' === $s['max_zoom'] ? null : (int) $s['max_zoom'],
			'planeMaxZoom' => '' === $s['plane_max_zoom'] ? 4 : max( 1, (float) $s['plane_max_zoom'] ),
			'fullscreen'   => (bool) $s['fullscreen'],
			'cluster'      => (bool) $s['branchascluster'],
			'clusterRadius' => $s['clustergridsize'] ? (int) $s['clustergridsize'] : 60,
			'clusterMin'   => $s['clusterminsize'] ? (int) $s['clusterminsize'] : 2,
			'pinStyle'     => $s['pin_style'],
			'pinImage'     => 'image' === $s['pin_style'] ? $s['pinimage'] : '',
			'animation'    => $s['pin_animation'],
			'tooltip'      => (bool) $s['show_tooltip'],
			'action'       => $s['pinaction'],
			'target'       => $s['pinurltarget'],
			'template'     => $s['popup_markup'],
			'popupImage'   => $s['popup_image'],
			'placeholder'  => $s['popup_image_fallback'] ? MAPIFY_ASSETS . 'img/defimg.jpg' : '',
			'directions'   => $s['popup_directions'] ? array(
				'label' => $s['directions_label'],
				'mode'  => $s['directions_mode'],
			) : null,
			'attribution'  => self::attribution(),
			'list'         => (bool) $s['branchlistshow'],
			'listScroll'   => (bool) $s['list_scroll_to_map'],
			'listPopup'    => (bool) $s['list_open_popup'],
			'items'        => array_values( $branches ),
			'i18n'         => array(
				'noResult'     => __( 'No branch found.', 'mapify' ),
				'showAll'      => __( 'Show all', 'mapify' ),
				'fullscreen'   => __( 'Fullscreen', 'mapify' ),
				'close'        => __( 'Close', 'mapify' ),
				'googleFailed' => __( 'Google Maps could not be loaded. Check your API key.', 'mapify' ),
				'loadFailed'   => __( 'The map could not be loaded.', 'mapify' ),
				'zoomIn'       => __( 'Zoom in', 'mapify' ),
				'zoomOut'      => __( 'Zoom out', 'mapify' ),
				'zoomReset'    => __( 'Reset zoom', 'mapify' ),
				'cancel'       => __( 'Cancel', 'mapify' ),
			),
		);

		if ( 'google' === $s['maptype'] ) {
			$style  = '';
			$styles = Schema::google_styles();
			if ( isset( $styles[ $s['map_defined_style'] ] ) ) {
				$style = $styles[ $s['map_defined_style'] ]['json'];
			} elseif ( 'default' !== $s['map_defined_style'] ) {
				$style = (string) apply_filters( 'mapify-shortcode-googlemapstyle-render-customstyle-json', '', $s['map_defined_style'], $s, '' ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores -- v1 hook name.
			}
			$config['google'] = array(
				'key'      => Options::get( 'google_api_key' ),
				'mapId'    => Options::get( 'google_map_id' ),
				'language' => Options::get( 'google_language' ),
				'type'     => $s['google_type'],
				'styles'   => Schema::parse_google_style( $style ),
			);
		} elseif ( in_array( $s['maptype'], Schema::tile_engines(), true ) ) {
			$config['tiles'] = self::tile_provider( $s );
		} else {
			$plane = array(
				'regionTooltip'   => (bool) $s['region_tooltip'],
				'regionHighlight' => (bool) $s['region_highlight'],
				'regionClick'     => $s['region_click'],
				'bounds'          => null,
				'projection'      => 'mercator',
			);
			if ( 'iran' === $s['maptype'] ) {
				$plane['svg']    = MAPIFY_ASSETS . 'maps/iran.svg?ver=' . MAPIFY_VERSION;
				$plane['labels'] = 'auto' === $s['region_labels'] ? ( 0 === strpos( determine_locale(), 'fa' ) ? 'fa' : 'en' ) : $s['region_labels'];
			}
			$config['plane'] = $plane;
		}
		return apply_filters( 'mapify_front_config', $config, $s, $branches );
	}

	protected static function style_attr( array $s ) {
		$css = array();
		foreach ( Schema::style_vars() as $key => $var ) {
			if ( ! isset( $s[ $key ] ) || '' === $s[ $key ] ) {
				continue;
			}
			$value = (string) $s[ $key ] . ( is_numeric( $s[ $key ] ) ? $var[1] : '' );
			$value = str_replace( array( ';', '{', '}', '<', '>' ), '', $value );
			$css[] = $var[0] . ':' . $value;
		}
		return implode( ';', $css );
	}

	/** Attributes shared by every list row: id, categories and search text. */
	public static function row_attrs( array $item ) {
		$search = function_exists( 'mb_strtolower' ) ? 'mb_strtolower' : 'strtolower';
		$text   = $search( wp_strip_all_tags( $item['title'] . ' ' . $item['address'] . ' ' . implode( ' ', $item['categories'] ) . ' ' . $item['phone'] ) );
		$cats   = isset( $item['cats'] ) ? $item['cats'] : array_map( 'strval', array_keys( $item['categories'] ) );
		$attrs  = ' data-id="' . esc_attr( $item['id'] ) . '" data-cats="' . esc_attr( implode( ',', $cats ) ) . '" data-search="' . esc_attr( $text ) . '"';
		return $attrs . apply_filters( 'mapify_list_row_attrs', '', $item );
	}

	public static function item_dot( array $item ) {
		return ! empty( $item['color'] ) ? '<span class="mapify__item-dot" style="--c:' . esc_attr( $item['color'] ) . '" aria-hidden="true"></span>' : '';
	}

	/**
	 * Inner markup of one list item for the chosen layout.
	 */
	protected static function item_markup( $layout, array $item, $n, array $s ) {
		$title   = '<span class="mapify__item-title">' . esc_html( $item['title'] ) . '</span>';
		$address = $item['address'] ? '<span class="mapify__item-meta">' . esc_html( $item['address'] ) . '</span>' : '';
		$phone   = $item['phone'] ? '<span class="mapify__item-meta mapify__item-phone" dir="ltr">' . esc_html( $item['phone'] ) . '</span>' : '';
		$image   = $item['image'] ? '<img class="mapify__item-image" src="' . esc_url( $item['image'] ) . '" alt="" loading="lazy" />' : '';
		switch ( $layout ) {
			case 'cards':
				$html = $image . '<span class="mapify__item-body">' . $title . $address . '</span>';
				break;
			case 'list':
				$html = '<span class="mapify__item-icon" aria-hidden="true"' . ( ! empty( $item['color'] ) ? ' style="--c:' . esc_attr( $item['color'] ) . '"' : '' ) . '><svg viewBox="0 0 24 24"><path d="M12 2a7 7 0 0 0-7 7c0 5.25 7 13 7 13s7-7.75 7-13a7 7 0 0 0-7-7Zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5Z"/></svg></span>'
					. '<span class="mapify__item-body">' . $title . $address . $phone . '</span>';
				break;
			default:
				$html = self::item_dot( $item ) . $title;
		}
		return apply_filters( 'mapify_list_item_html', $html, $layout, $item, $n, $s );
	}

	/**
	 * Branches grouped for the list: [ [ name, items ] ].
	 */
	protected static function list_groups( array $s, array $items ) {
		$groups = array();
		foreach ( $items as $item ) {
			if ( ! empty( $item['custom'] ) ) {
				continue;
			}
			if ( 'category' === $s['brancheslistcat'] ) {
				$item_cats = empty( $item['categories'] ) ? array( 0 => __( 'Uncategorized', 'mapify' ) ) : $item['categories'];
				foreach ( $item_cats as $id => $name ) {
					$groups[ $id ]['name']    = $name;
					$groups[ $id ]['items'][] = $item;
				}
			} else {
				$groups[0]['name']    = '';
				$groups[0]['items'][] = $item;
			}
		}
		return $groups;
	}

	protected static function list_markup( array $s, array $items ) {
		$layout = $s['list_layout'];
		$html   = '<div class="mapify__list mapify__list--' . esc_attr( $layout ) . '">';
		if ( apply_filters( 'mapify_list_show_search', $s['branchessearch'], $s ) ) {
			$html .= '<div class="mapify__search"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10.5 3a7.5 7.5 0 0 1 5.96 12.06l4.24 4.24-1.4 1.4-4.24-4.24A7.5 7.5 0 1 1 10.5 3Zm0 2a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11Z"/></svg>'
				. '<input type="search" class="mapify__search-input" placeholder="' . esc_attr( $s['search_placeholder'] ) . '" aria-label="' . esc_attr( $s['search_placeholder'] ) . '" /></div>';
		}
		$html  .= apply_filters( 'mapify_list_tools', '', $s, $items );
		$groups = self::list_groups( $s, $items );
		$custom = apply_filters( 'mapify_list_groups_html', null, $groups, $s );
		if ( is_string( $custom ) ) {
			$html .= $custom;
		} else {
			$html .= '<div class="mapify__groups">';
			foreach ( $groups as $group ) {
				$html .= '<div class="mapify__group">';
				if ( '' !== $group['name'] ) {
					$html .= '<div class="mapify__group-title">' . esc_html( $group['name'] ) . '</div>';
				}
				$html .= '<ul class="mapify__items">';
				$n     = 0;
				foreach ( $group['items'] as $item ) {
					++$n;
					$html .= '<li class="mapify__row"' . self::row_attrs( $item ) . '><button type="button" class="mapify__item" data-id="' . esc_attr( $item['id'] ) . '">' . self::item_markup( $layout, $item, $n, $s ) . '</button></li>';
				}
				$html .= '</ul>' . apply_filters( 'mapify_list_group_after', '', $layout, $group, $s ) . '</div>';
			}
			$html .= '</div>';
		}
		$html .= '<p class="mapify__empty" hidden>' . esc_html__( 'No branch found.', 'mapify' ) . '</p></div>';
		return $html;
	}

	/**
	 * @param array $settings Normalized settings (Schema::normalize()).
	 * @param array $args     classes, style_vars (bool), attributes.
	 */
	public static function render( array $settings, array $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'classes'    => '',
				'style_vars' => true,
			)
		);
		++self::$count;
		$s      = apply_filters( 'mapify_render_settings', $settings );
		$notice = self::resolve_engine( $s );
		if ( ! $notice && ! empty( $s['_notice'] ) ) {
			$notice = $s['_notice'];
		}
		$items  = Branches::query( $s );
		$config = self::config( $s, $items );
		$id     = $s['el_id'] ? sanitize_html_class( $s['el_id'] ) : 'mapify-' . self::$count . '-' . wp_rand( 100, 999 );
		$list   = $s['branchlistshow'] && ! empty( $items );

		wp_enqueue_style( 'mapify-front' );
		wp_enqueue_script( 'mapify-front' );
		do_action( 'mapify_enqueue_front', $s );

		$classes = apply_filters(
			'mapify_wrapper_classes',
			array(
				'mapify',
				'mapify--engine-' . $s['maptype'],
				in_array( $s['maptype'], Schema::plane_engines(), true ) ? 'mapify--plane' : 'mapify--geo',
				'mapify--pins-' . $s['pin_style'],
				'mapify--anim-' . $s['pin_animation'],
				$list ? 'mapify--list-' . $s['branchplacement'] : 'mapify--no-list',
				$list ? 'mapify--layout-' . $s['list_layout'] : '',
				'mapify--zoom-' . $s['zoom_position'],
				$s['el_class'],
				$args['classes'],
			),
			$s
		);
		$style = $args['style_vars'] ? self::style_attr( $s ) : '';

		$html  = '<div id="' . esc_attr( $id ) . '" class="' . esc_attr( trim( implode( ' ', array_filter( $classes ) ) ) ) . '"';
		$html .= $style ? ' style="' . esc_attr( $style ) . '"' : '';
		$html .= ' data-mapify="' . esc_attr( wp_json_encode( $config ) ) . '">';
		if ( $notice && current_user_can( 'edit_posts' ) ) {
			$html .= '<div class="mapify__notice" role="status">' . esc_html( $notice ) . '</div>';
		}
		$html .= '<div class="mapify__layout">';
		if ( $list ) {
			$html .= self::list_markup( $s, $items );
		}
		$html .= '<div class="mapify__stage"><div class="mapify__map" role="region" aria-label="' . esc_attr__( 'Branches map', 'mapify' ) . '"></div>';
		$html .= apply_filters( 'mapify_stage_html', '', $s, $items );
		$html .= '<div class="mapify__loading" aria-hidden="true"><span></span></div></div>';
		$html .= '</div></div>';

		return apply_filters( 'mapify-shortcode-return-data', $html, $settings, $items ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores -- v1 hook name.
	}
}
