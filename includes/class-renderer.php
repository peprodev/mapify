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
		$esri = 'https://server.arcgisonline.com/ArcGIS/rest/services/';
		$osm  = array(
			'osm'          => array( 'https://tile.openstreetmap.org/{z}/{x}/{y}.png', '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors', 19 ),
			'osm-hot'      => array( 'https://{s}.tile.openstreetmap.fr/hot/{z}/{x}/{y}.png', '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, Humanitarian OSM Team', 19 ),
			'esri-light'   => array( $esri . 'Canvas/World_Light_Gray_Base/MapServer/tile/{z}/{y}/{x}', 'Tiles © Esri — Esri, HERE, Garmin, © OpenStreetMap contributors', 16 ),
			'esri-dark'    => array( $esri . 'Canvas/World_Dark_Gray_Base/MapServer/tile/{z}/{y}/{x}', 'Tiles © Esri — Esri, HERE, Garmin, © OpenStreetMap contributors', 16 ),
			'esri-street'  => array( $esri . 'World_Street_Map/MapServer/tile/{z}/{y}/{x}', 'Tiles © Esri', 19 ),
			'esri-topo'    => array( $esri . 'World_Topo_Map/MapServer/tile/{z}/{y}/{x}', 'Tiles © Esri', 19 ),
			'opentopo'     => array( 'https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', '© OpenStreetMap contributors, SRTM | © <a href="https://opentopomap.org">OpenTopoMap</a>', 17 ),
			'esri-imagery' => array( $esri . 'World_Imagery/MapServer/tile/{z}/{y}/{x}', 'Tiles © Esri — Source: Esri, Maxar, Earthstar Geographics', 19 ),
		);
		$pick = isset( $osm[ $s['osm_style'] ] ) ? $osm[ $s['osm_style'] ] : $osm['osm'];
		return array(
			'url'         => $pick[0],
			'attribution' => $pick[1],
			'maxZoom'     => $pick[2],
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
		} elseif ( 'image' === $s['maptype'] && '' === $s['map_image'] ) {
			$missing = __( 'Choose an image for the image map. Showing the offline Iran map instead.', 'mapify' );
			$s['maptype'] = 'iran';
			return $missing;
		} elseif ( 'svg' === $s['maptype'] && '' === $s['svg_file'] ) {
			$missing = __( 'Choose an SVG file for the SVG map. Showing the offline Iran map instead.', 'mapify' );
			$s['maptype'] = 'iran';
			return $missing;
		}
		if ( $missing ) {
			$s['maptype']   = 'osm';
			$s['osm_style'] = 'osm';
		}
		return $missing;
	}

	protected static function parse_bounds( $value ) {
		$parts = array_map( 'trim', explode( ',', (string) $value ) );
		if ( 4 !== count( $parts ) ) {
			return null;
		}
		foreach ( $parts as $p ) {
			if ( ! is_numeric( $p ) ) {
				return null;
			}
		}
		return array(
			'north' => (float) $parts[0],
			'west'  => (float) $parts[1],
			'south' => (float) $parts[2],
			'east'  => (float) $parts[3],
		);
	}

	public static function config( array $s, array $branches ) {
		$center = array_map( 'floatval', array_pad( explode( ',', $s['center_coordinate'] ), 2, 0 ) );
		$config = array(
			'engine'     => $s['maptype'],
			'center'     => array( $center[0], $center[1] ),
			'zoom'       => (int) $s['default_zoom'],
			'fitBounds'  => (bool) $s['fit_bounds'],
			'scrollZoom' => (bool) $s['scroll_zoom'],
			'controls'   => ! $s['disabledefaultui'],
			'fullscreen' => (bool) $s['fullscreen'],
			'cluster'    => (bool) $s['branchascluster'],
			'clusterRadius' => $s['clustergridsize'] ? (int) $s['clustergridsize'] : 60,
			'clusterMin' => $s['clusterminsize'] ? (int) $s['clusterminsize'] : 2,
			'pinStyle'   => $s['pin_style'],
			'pinImage'   => 'image' === $s['pin_style'] ? $s['pinimage'] : '',
			'animation'  => $s['pin_animation'],
			'tooltip'    => (bool) $s['show_tooltip'],
			'action'     => $s['pinaction'],
			'target'     => $s['pinurltarget'],
			'template'   => $s['popup_markup'],
			'list'       => (bool) $s['branchlistshow'],
			'items'      => array_values( array_merge( $branches, $s['pins'] ) ),
			'i18n'       => array(
				'noResult'     => __( 'No branch found.', 'mapify' ),
				'showAll'      => __( 'Show all', 'mapify' ),
				'fullscreen'   => __( 'Fullscreen', 'mapify' ),
				'close'        => __( 'Close', 'mapify' ),
				'googleFailed' => __( 'Google Maps could not be loaded. Check your API key.', 'mapify' ),
			),
		);

		if ( 'google' === $s['maptype'] ) {
			$style = '';
			if ( 'custom' === $s['map_defined_style'] ) {
				$style = $s['googlemap_style'];
			} else {
				$styles = Schema::google_styles();
				if ( isset( $styles[ $s['map_defined_style'] ] ) ) {
					$style = $styles[ $s['map_defined_style'] ]['json'];
				} elseif ( 'default' !== $s['map_defined_style'] ) {
					$style = (string) apply_filters( 'mapify-shortcode-googlemapstyle-render-customstyle-json', '', $s['map_defined_style'], $s, '' );
				}
			}
			$decoded          = json_decode( $style, true );
			$config['google'] = array(
				'key'      => Options::get( 'google_api_key' ),
				'mapId'    => Options::get( 'google_map_id' ),
				'language' => Options::get( 'google_language' ),
				'type'     => $s['google_type'],
				'styles'   => is_array( $decoded ) ? $decoded : array(),
			);
		} elseif ( in_array( $s['maptype'], Schema::tile_engines(), true ) ) {
			$config['tiles'] = self::tile_provider( $s );
		} else {
			$plane = array(
				'regionTooltip'   => (bool) $s['region_tooltip'],
				'regionHighlight' => (bool) $s['region_highlight'],
				'regionClick'     => $s['region_click'],
				'bounds'          => self::parse_bounds( $s['geo_bounds'] ),
				'projection'      => $s['geo_projection'],
			);
			if ( 'iran' === $s['maptype'] ) {
				$lang                = 'auto' === $s['region_labels'] ? ( 0 === strpos( determine_locale(), 'fa' ) ? 'fa' : 'en' ) : $s['region_labels'];
				$plane['svg']        = MAPIFY_ASSETS . 'maps/iran.svg?ver=' . MAPIFY_VERSION;
				$plane['labels']     = $lang;
				$plane['bounds']     = null; // Read from the SVG itself.
				$plane['projection'] = 'mercator';
			} elseif ( 'svg' === $s['maptype'] ) {
				$plane['svg'] = $s['svg_file'];
			} else {
				$plane['image'] = $s['map_image'];
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

	protected static function list_markup( array $s, array $items ) {
		$html = '<div class="mapify__list mapify__list--' . esc_attr( $s['list_layout'] ) . '">';
		if ( $s['branchessearch'] ) {
			$html .= '<div class="mapify__search"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10.5 3a7.5 7.5 0 0 1 5.96 12.06l4.24 4.24-1.4 1.4-4.24-4.24A7.5 7.5 0 1 1 10.5 3Zm0 2a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11Z"/></svg>'
				. '<input type="search" class="mapify__search-input" placeholder="' . esc_attr( $s['search_placeholder'] ) . '" aria-label="' . esc_attr( $s['search_placeholder'] ) . '" /></div>';
		}
		$groups = array();
		foreach ( $items as $item ) {
			if ( ! empty( $item['custom'] ) ) {
				continue;
			}
			if ( 'category' === $s['brancheslistcat'] ) {
				$cats = empty( $item['categories'] ) ? array( 0 => __( 'Uncategorized', 'mapify' ) ) : $item['categories'];
				foreach ( $cats as $id => $name ) {
					$groups[ $id ]['name']    = $name;
					$groups[ $id ]['items'][] = $item;
				}
			} else {
				$groups[0]['name']    = '';
				$groups[0]['items'][] = $item;
			}
		}
		$html .= '<div class="mapify__groups">';
		foreach ( $groups as $group ) {
			$html .= '<div class="mapify__group">';
			if ( '' !== $group['name'] ) {
				$html .= '<div class="mapify__group-title">' . esc_html( $group['name'] ) . '</div>';
			}
			$html .= '<ul class="mapify__items">';
			foreach ( $group['items'] as $item ) {
				$search = strtolower( wp_strip_all_tags( $item['title'] . ' ' . $item['address'] . ' ' . implode( ' ', $item['categories'] ) . ' ' . $item['phone'] ) );
				$html  .= '<li><button type="button" class="mapify__item" data-id="' . esc_attr( $item['id'] ) . '" data-search="' . esc_attr( $search ) . '">';
				if ( 'cards' === $s['list_layout'] ) {
					if ( $item['image'] ) {
						$html .= '<img class="mapify__item-image" src="' . esc_url( $item['image'] ) . '" alt="" loading="lazy" />';
					}
					$html .= '<span class="mapify__item-body"><span class="mapify__item-title">' . esc_html( $item['title'] ) . '</span>';
					if ( $item['address'] ) {
						$html .= '<span class="mapify__item-meta">' . esc_html( $item['address'] ) . '</span>';
					}
					$html .= '</span>';
				} else {
					$html .= '<span class="mapify__item-title">' . esc_html( $item['title'] ) . '</span>';
				}
				$html .= '</button></li>';
			}
			$html .= '</ul></div>';
		}
		$html .= '</div><p class="mapify__empty" hidden>' . esc_html__( 'No branch found.', 'mapify' ) . '</p></div>';
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
		$s      = $settings;
		$notice = self::resolve_engine( $s );
		$items  = Branches::query( $s );
		$config = self::config( $s, $items );
		$id     = $s['el_id'] ? sanitize_html_class( $s['el_id'] ) : 'mapify-' . self::$count . '-' . wp_rand( 100, 999 );
		$list   = $s['branchlistshow'] && ! empty( $items );

		wp_enqueue_style( 'mapify-front' );
		wp_enqueue_script( 'mapify-front' );

		$classes = array(
			'mapify',
			'mapify--engine-' . $s['maptype'],
			in_array( $s['maptype'], Schema::plane_engines(), true ) ? 'mapify--plane' : 'mapify--geo',
			'mapify--pins-' . $s['pin_style'],
			'mapify--anim-' . $s['pin_animation'],
			$list ? 'mapify--list-' . $s['branchplacement'] : 'mapify--no-list',
			$s['el_class'],
			$args['classes'],
		);
		$style   = $args['style_vars'] ? self::style_attr( $s ) : '';

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
		$html .= '<div class="mapify__stage"><div class="mapify__map" role="region" aria-label="' . esc_attr__( 'Branches map', 'mapify' ) . '"></div><div class="mapify__loading" aria-hidden="true"><span></span></div></div>';
		$html .= '</div></div>';

		return apply_filters( 'mapify-shortcode-return-data', $html, $settings, $items );
	}
}
