<?php
/**
 * Map widget settings schema.
 *
 * One definition drives the shortcode attributes, the WPBakery params, the Elementor
 * content controls and the shortcode builder, so every builder exposes the same options.
 *
 * @package Mapify
 */

namespace Mapify;

defined( 'ABSPATH' ) || exit;

class Schema {

	protected static $fields = null;

	public static function engines() {
		return apply_filters(
			'mapify_engines',
			array(
				'osm'    => __( 'OpenStreetMap (free, no key)', 'mapify' ),
				'google' => __( 'Google Maps', 'mapify' ),
				'mapbox' => __( 'Mapbox', 'mapify' ),
				'mapir'    => __( 'Map.ir (Persian map)', 'mapify' ),
				'neshan'   => __( 'Neshan (Persian map)', 'mapify' ),
				'parsimap' => __( 'Parsimap (Persian map)', 'mapify' ),
				'mapup'    => __( 'Mapup (Persian map, no key)', 'mapify' ),
				'custom' => __( 'Custom tile server (XYZ)', 'mapify' ),
				'iran'   => __( 'Iran — offline SVG map', 'mapify' ),
				'svg'    => __( 'Custom SVG map', 'mapify' ),
				'image'  => __( 'Image as map', 'mapify' ),
			)
		);
	}

	/** Engines drawn on a flat plane (no tiles) where pins can use x/y positions. */
	public static function plane_engines() {
		return array( 'iran', 'svg', 'image' );
	}

	public static function tile_engines() {
		return array( 'osm', 'mapbox', 'mapir', 'neshan', 'parsimap', 'mapup', 'custom' );
	}

	public static function groups() {
		return array(
			'source'     => __( 'Branches', 'mapify' ),
			'map'        => __( 'Map', 'mapify' ),
			'google'     => __( 'Google Maps', 'mapify' ),
			'tiles'      => __( 'Tile layer', 'mapify' ),
			'plane'      => __( 'Image / SVG map', 'mapify' ),
			'pins'       => __( 'Custom pins', 'mapify' ),
			'markers'    => __( 'Markers', 'mapify' ),
			'popup'      => __( 'Popup', 'mapify' ),
			'list'       => __( 'Branches list', 'mapify' ),
			'filter'     => __( 'Category filter', 'mapify' ),
			'appearance' => __( 'Appearance', 'mapify' ),
			'advanced'   => __( 'Advanced', 'mapify' ),
		);
	}

	public static function osm_styles() {
		return apply_filters(
			'mapify_osm_styles',
			array(
				'osm'                 => __( 'OpenStreetMap standard', 'mapify' ),
				'osm-gray'            => __( 'OpenStreetMap grayscale', 'mapify' ),
				'osm-dark'            => __( 'OpenStreetMap dark', 'mapify' ),
				'osm-sepia'           => __( 'OpenStreetMap vintage', 'mapify' ),
				'osm-hot'             => __( 'OpenStreetMap Humanitarian', 'mapify' ),
				'osm-fr'              => __( 'OpenStreetMap France', 'mapify' ),
				'osm-de'              => __( 'OpenStreetMap Germany', 'mapify' ),
				'cyclosm'             => __( 'CyclOSM (roads and cycling)', 'mapify' ),
				'opentopo'            => __( 'OpenTopoMap', 'mapify' ),
				'esri-light'          => __( 'Light gray canvas (Esri)', 'mapify' ),
				'esri-dark'           => __( 'Dark gray canvas (Esri)', 'mapify' ),
				'esri-street'         => __( 'World street map (Esri)', 'mapify' ),
				'esri-topo'           => __( 'Topographic (Esri)', 'mapify' ),
				'esri-natgeo'         => __( 'National Geographic (Esri)', 'mapify' ),
				'esri-imagery'        => __( 'Satellite imagery (Esri)', 'mapify' ),
				'esri-imagery-labels' => __( 'Satellite with labels (Esri)', 'mapify' ),
				'esri-terrain'        => __( 'Terrain (Esri)', 'mapify' ),
				'esri-shaded'         => __( 'Shaded relief (Esri)', 'mapify' ),
				'esri-physical'       => __( 'Physical (Esri)', 'mapify' ),
				'esri-ocean'          => __( 'Ocean (Esri)', 'mapify' ),
			)
		);
	}

	public static function neshan_styles() {
		return array(
			'neshan'         => __( 'Neshan', 'mapify' ),
			'standard-day'   => __( 'Standard day', 'mapify' ),
			'standard-night' => __( 'Standard night', 'mapify' ),
			'dreamy'         => __( 'Dreamy', 'mapify' ),
			'dreamy-gold'    => __( 'Dreamy gold', 'mapify' ),
			'osm-bright'     => __( 'OSM bright', 'mapify' ),
		);
	}

	public static function parsimap_styles() {
		return array(
			'parsimap-streets-v11-raster' => __( 'Parsimap streets', 'mapify' ),
			'google-street-raster'        => __( 'Google-like streets', 'mapify' ),
		);
	}

	public static function mapbox_styles() {
		return array(
			'streets-v12'           => __( 'Streets', 'mapify' ),
			'outdoors-v12'          => __( 'Outdoors', 'mapify' ),
			'light-v11'             => __( 'Light', 'mapify' ),
			'dark-v11'              => __( 'Dark', 'mapify' ),
			'satellite-v9'          => __( 'Satellite', 'mapify' ),
			'satellite-streets-v12' => __( 'Satellite streets', 'mapify' ),
			'navigation-day-v1'     => __( 'Navigation day', 'mapify' ),
			'navigation-night-v1'   => __( 'Navigation night', 'mapify' ),
			'custom'                => __( 'Custom style (Mapbox Studio)', 'mapify' ),
		);
	}

	/**
	 * Built-in Google styles: slug => [label, json].
	 */
	public static function google_styles() {
		static $styles = null;
		if ( null === $styles ) {
			$styles = apply_filters( 'mapify_google_styles', include MAPIFY_DIR . 'includes/data/google-styles.php' );
		}
		return $styles;
	}

	public static function google_style_options() {
		$out = array(
			'default' => __( 'Default', 'mapify' ),
			'custom'  => __( 'Custom JSON style', 'mapify' ),
		);
		foreach ( self::google_styles() as $slug => $style ) {
			$out[ $slug ] = $style['label'];
		}
		return $out;
	}

	/**
	 * Thumbnail for a style option, by field: Google styles and the free tile styles have one.
	 */
	public static function style_preview( $field_key, $slug ) {
		if ( 'map_defined_style' === $field_key ) {
			return self::google_style_preview( $slug );
		}
		if ( 'osm_style' === $field_key && file_exists( MAPIFY_DIR . "assets/img/tile-style/{$slug}.jpg" ) ) {
			return MAPIFY_ASSETS . "img/tile-style/{$slug}.jpg";
		}
		return (string) apply_filters( 'mapify_style_preview', '', $field_key, $slug );
	}

	public static function google_style_preview( $slug ) {
		$legacy = array(
			'default'  => 'gmapdefault',
			'custom'   => 'gmapcustom',
			'midnight' => 'gmapmidnight',
			'desert'   => 'gmapdesert',
			'bright'   => 'gmapbright',
			'ulight'   => 'gmapulight',
			'accriv'   => 'gmapassassincreediv',
		);
		$file = isset( $legacy[ $slug ] ) ? $legacy[ $slug ] : $slug;
		if ( ! file_exists( MAPIFY_DIR . "assets/img/map-style/{$file}.jpg" ) ) {
			return '';
		}
		return MAPIFY_ASSETS . "img/map-style/{$file}.jpg";
	}

	public static function list_layouts() {
		return apply_filters(
			'mapify_list_layouts',
			array(
				'chips'    => __( 'Chips', 'mapify' ),
				'cards'    => __( 'Cards', 'mapify' ),
				'list'     => __( 'Detailed list (address and phone)', 'mapify' ),
				'grid'     => __( 'Image grid', 'mapify' ),
				'compact'  => __( 'Compact numbered list', 'mapify' ),
				'table'    => __( 'Table', 'mapify' ),
				'carousel' => __( 'Carousel (horizontal scroll)', 'mapify' ),
				'dropdown' => __( 'Dropdown', 'mapify' ),
			)
		);
	}

	public static function direction_apps() {
		return array(
			'google' => __( 'Google Maps', 'mapify' ),
			'apple'  => __( 'Apple Maps (Apple devices only)', 'mapify' ),
			'waze'   => __( 'Waze', 'mapify' ),
			'neshan' => __( 'Neshan', 'mapify' ),
			'balad'  => __( 'Balad', 'mapify' ),
		);
	}

	public static function default_popup_template() {
		$tpl = '<div class="mapify-card">
  <img class="mapify-card__image" src="{popup_image}" alt="{title}" />
  <div class="mapify-card__body">
    <h3 class="mapify-card__title">{title|' . esc_html__( 'No title', 'mapify' ) . '}</h3>
    <p class="mapify-card__row mapify-card__address">{address}</p>
    <p class="mapify-card__row mapify-card__phone"><a href="tel:{phone}">{phone}</a></p>
    <div class="mapify-card__actions">
      <a class="mapify-card__link" href="{url}">' . esc_html__( 'View branch', 'mapify' ) . '</a>
      {directions}
    </div>
  </div>
</div>';
		return apply_filters( 'mapify_default_popup_template', $tpl );
	}

	public static function popup_tags() {
		return array( 'id', 'title', 'image', 'pin_image', 'popup_image', 'url', 'latitude', 'longitude', 'address', 'phone', 'site', 'email', 'twitter', 'facebook', 'instagram', 'telegram', 'linkedin', 'additional', 'categories', 'directions' );
	}

	/**
	 * Field definitions.
	 *
	 * type: select|multiselect|text|textarea|code|number|toggle|color|media|repeater
	 * condition: [ field => [values] ] — all builders translate it to their own dependency syntax.
	 */
	public static function fields() {
		if ( null !== self::$fields ) {
			return self::$fields;
		}
		$plane = self::plane_engines();
		$geo   = array_merge( array( 'google' ), self::tile_engines() );

		$fields = array(
			// Branches.
			'branchtype'        => array(
				'group'   => 'source',
				'type'    => 'select',
				'label'   => __( 'Show branches', 'mapify' ),
				'default' => 'all',
				'options' => array(
					'all'  => __( 'All branches', 'mapify' ),
					'cat'  => __( 'From selected categories', 'mapify' ),
					'id'   => __( 'Handpicked branches', 'mapify' ),
					'none' => __( 'None (custom pins only)', 'mapify' ),
				),
			),
			'branchcat'         => array(
				'group'     => 'source',
				'type'      => 'multiselect',
				'label'     => __( 'Categories', 'mapify' ),
				'default'   => array(),
				'options'   => 'categories',
				'condition' => array( 'branchtype' => array( 'cat' ) ),
			),
			'branchids'         => array(
				'group'     => 'source',
				'type'      => 'multiselect',
				'label'     => __( 'Branches', 'mapify' ),
				'default'   => array(),
				'options'   => 'branches',
				'condition' => array( 'branchtype' => array( 'id' ) ),
			),
			'orderby'           => array(
				'group'   => 'source',
				'type'    => 'select',
				'label'   => __( 'Order by', 'mapify' ),
				'default' => 'title',
				'options' => array(
					'title'      => __( 'Title', 'mapify' ),
					'date'       => __( 'Date', 'mapify' ),
					'menu_order' => __( 'Menu order', 'mapify' ),
					'post__in'   => __( 'Handpicked order', 'mapify' ),
				),
			),
			'order'             => array(
				'group'   => 'source',
				'type'    => 'select',
				'label'   => __( 'Order', 'mapify' ),
				'default' => 'ASC',
				'options' => array(
					'ASC'  => __( 'Ascending', 'mapify' ),
					'DESC' => __( 'Descending', 'mapify' ),
				),
			),

			// Map.
			'maptype'           => array(
				'group'   => 'map',
				'type'    => 'select',
				'label'   => __( 'Map engine', 'mapify' ),
				'default' => '',
				'options' => array( '' => __( 'Use global default', 'mapify' ) ) + self::engines(),
			),
			'center_coordinate' => array(
				'group'       => 'map',
				'type'        => 'text',
				'label'       => __( 'Center (lat,lng)', 'mapify' ),
				'default'     => '',
				'placeholder' => '35.6997,51.3380',
				'condition'   => array( 'maptype' => array_merge( array( '' ), $geo ) ),
			),
			'default_zoom'      => array(
				'group'       => 'map',
				'type'        => 'number',
				'label'       => __( 'Zoom level', 'mapify' ),
				'default'     => '',
				'min'         => 1,
				'max'         => 22,
				'placeholder' => '5',
				'condition'   => array( 'maptype' => array_merge( array( '' ), $geo ) ),
			),
			'fit_bounds'        => array(
				'group'       => 'map',
				'type'        => 'toggle',
				'label'       => __( 'Auto-fit to pins', 'mapify' ),
				'description' => __( 'Zoom and center the map so every pin is visible.', 'mapify' ),
				'default'     => true,
				'condition'   => array( 'maptype' => array_merge( array( '' ), $geo ) ),
			),
			'zoom_control'      => array(
				'group'       => 'map',
				'type'        => 'toggle',
				'label'       => __( 'Zoom buttons (+ / −)', 'mapify' ),
				'description' => __( 'Also works on the offline Iran, SVG and image maps.', 'mapify' ),
				'default'     => true,
			),
			'zoom_position'     => array(
				'group'     => 'map',
				'type'      => 'select',
				'label'     => __( 'Zoom buttons position', 'mapify' ),
				'default'   => 'topleft',
				'options'   => array(
					'topleft'     => __( 'Top left', 'mapify' ),
					'topright'    => __( 'Top right', 'mapify' ),
					'bottomleft'  => __( 'Bottom left', 'mapify' ),
					'bottomright' => __( 'Bottom right', 'mapify' ),
				),
				'condition' => array( 'zoom_control' => array( true ) ),
			),
			'scroll_zoom'       => array(
				'group'   => 'map',
				'type'    => 'toggle',
				'label'   => __( 'Zoom with mouse wheel', 'mapify' ),
				'default' => false,
			),
			'double_click_zoom' => array(
				'group'   => 'map',
				'type'    => 'toggle',
				'label'   => __( 'Zoom on double click', 'mapify' ),
				'default' => true,
			),
			'touch_zoom'        => array(
				'group'   => 'map',
				'type'    => 'toggle',
				'label'   => __( 'Pinch to zoom on touch screens', 'mapify' ),
				'default' => true,
			),
			'min_zoom'          => array(
				'group'       => 'map',
				'type'        => 'number',
				'label'       => __( 'Minimum zoom', 'mapify' ),
				'default'     => '',
				'min'         => 1,
				'max'         => 22,
				'placeholder' => '1',
				'condition'   => array( 'maptype' => array_merge( array( '' ), $geo ) ),
			),
			'max_zoom'          => array(
				'group'       => 'map',
				'type'        => 'number',
				'label'       => __( 'Maximum zoom', 'mapify' ),
				'default'     => '',
				'min'         => 1,
				'max'         => 22,
				'placeholder' => '19',
				'condition'   => array( 'maptype' => array_merge( array( '' ), $geo ) ),
			),
			'plane_max_zoom'    => array(
				'group'       => 'map',
				'type'        => 'number',
				'label'       => __( 'Maximum zoom (×)', 'mapify' ),
				'description' => __( 'How far the offline Iran, SVG and image maps can be enlarged. 1 turns zooming off.', 'mapify' ),
				'default'     => 4,
				'min'         => 1,
				'max'         => 10,
				'condition'   => array( 'maptype' => $plane ),
			),
			'disabledefaultui'  => array(
				'group'   => 'map',
				'type'    => 'toggle',
				'label'   => __( 'Hide map controls', 'mapify' ),
				'default' => false,
			),
			'fullscreen'        => array(
				'group'   => 'map',
				'type'    => 'toggle',
				'label'   => __( 'Fullscreen button', 'mapify' ),
				'default' => true,
			),

			// Google.
			'google_type'       => array(
				'group'     => 'google',
				'type'      => 'select',
				'label'     => __( 'Map type', 'mapify' ),
				'default'   => 'roadmap',
				'options'   => array(
					'roadmap'   => __( 'Roadmap', 'mapify' ),
					'satellite' => __( 'Satellite', 'mapify' ),
					'hybrid'    => __( 'Hybrid', 'mapify' ),
					'terrain'   => __( 'Terrain', 'mapify' ),
				),
				'condition' => array( 'maptype' => array( 'google' ) ),
			),
			'map_defined_style' => array(
				'group'       => 'google',
				'type'        => 'select',
				'label'       => __( 'Map style', 'mapify' ),
				'description' => __( 'Styles are ignored when a Google Map ID is set in the plugin settings (cloud styling is used instead).', 'mapify' ),
				'default'     => 'default',
				'options'     => 'google_styles',
				'previews'    => true,
				'condition'   => array( 'maptype' => array( 'google' ) ),
			),
			'googlemap_style'   => array(
				'group'       => 'google',
				'type'        => 'code',
				'label'       => __( 'Custom style JSON', 'mapify' ),
				'description' => __( 'Paste a style array, e.g. from snazzymaps.com.', 'mapify' ),
				'default'     => '',
				'language'    => 'json',
				'condition'   => array( 'map_defined_style' => array( 'custom' ) ),
			),

			// Tiles.
			'osm_style'         => array(
				'group'     => 'tiles',
				'type'      => 'select',
				'label'     => __( 'Tile style', 'mapify' ),
				'default'   => 'osm',
				'options'   => self::osm_styles(),
				'previews'  => true,
				'condition' => array( 'maptype' => array( 'osm' ) ),
			),
			'mapbox_style'      => array(
				'group'     => 'tiles',
				'type'      => 'select',
				'label'     => __( 'Mapbox style', 'mapify' ),
				'default'   => 'streets-v12',
				'options'   => self::mapbox_styles(),
				'condition' => array( 'maptype' => array( 'mapbox' ) ),
			),
			'neshan_style'      => array(
				'group'     => 'tiles',
				'type'      => 'select',
				'label'     => __( 'Neshan style', 'mapify' ),
				'default'   => 'neshan',
				'options'   => self::neshan_styles(),
				'condition' => array( 'maptype' => array( 'neshan' ) ),
			),
			'parsimap_style'    => array(
				'group'     => 'tiles',
				'type'      => 'select',
				'label'     => __( 'Parsimap style', 'mapify' ),
				'default'   => 'parsimap-streets-v11-raster',
				'options'   => self::parsimap_styles(),
				'condition' => array( 'maptype' => array( 'parsimap' ) ),
			),
			'mapbox_custom'     => array(
				'group'       => 'tiles',
				'type'        => 'text',
				'label'       => __( 'Mapbox style ID', 'mapify' ),
				'placeholder' => 'username/style-id',
				'default'     => '',
				'condition'   => array( 'mapbox_style' => array( 'custom' ) ),
			),
			'tiles_url'         => array(
				'group'       => 'tiles',
				'type'        => 'text',
				'label'       => __( 'Tile URL template', 'mapify' ),
				'placeholder' => 'https://{s}.tile.example.com/{z}/{x}/{y}.png',
				'default'     => '',
				'condition'   => array( 'maptype' => array( 'custom' ) ),
			),
			'tiles_attribution' => array(
				'group'     => 'tiles',
				'type'      => 'text',
				'label'     => __( 'Tile attribution', 'mapify' ),
				'default'   => '',
				'condition' => array( 'maptype' => array( 'custom' ) ),
			),

			// Plane maps.
			'map_image'         => array(
				'group'     => 'plane',
				'type'      => 'media',
				'label'     => __( 'Map image', 'mapify' ),
				'default'   => '',
				'condition' => array( 'maptype' => array( 'image' ) ),
			),
			'svg_file'          => array(
				'group'       => 'plane',
				'type'        => 'media',
				'label'       => __( 'SVG file', 'mapify' ),
				'description' => __( 'Shapes with an id become hoverable regions.', 'mapify' ),
				'default'     => '',
				'condition'   => array( 'maptype' => array( 'svg' ) ),
			),
			'geo_bounds'        => array(
				'group'       => 'plane',
				'type'        => 'text',
				'label'       => __( 'Geo bounds (north,west,south,east)', 'mapify' ),
				'description' => __( 'Optional. When set, branches are placed on the image/SVG by their latitude and longitude.', 'mapify' ),
				'placeholder' => '39.8,44.0,25.0,63.3',
				'default'     => '',
				'condition'   => array( 'maptype' => array( 'image', 'svg' ) ),
			),
			'geo_projection'    => array(
				'group'     => 'plane',
				'type'      => 'select',
				'label'     => __( 'Projection', 'mapify' ),
				'default'   => 'mercator',
				'options'   => array(
					'mercator'        => __( 'Web Mercator', 'mapify' ),
					'equirectangular' => __( 'Equirectangular (plate carrée)', 'mapify' ),
				),
				'condition' => array( 'maptype' => array( 'image', 'svg' ) ),
			),
			'region_tooltip'    => array(
				'group'     => 'plane',
				'type'      => 'toggle',
				'label'     => __( 'Show region name on hover', 'mapify' ),
				'default'   => true,
				'condition' => array( 'maptype' => array( 'iran', 'svg' ) ),
			),
			'region_highlight'  => array(
				'group'     => 'plane',
				'type'      => 'toggle',
				'label'     => __( 'Highlight regions that have branches', 'mapify' ),
				'default'   => true,
				'condition' => array( 'maptype' => array( 'iran', 'svg' ) ),
			),
			'region_click'      => array(
				'group'     => 'plane',
				'type'      => 'select',
				'label'     => __( 'Region click', 'mapify' ),
				'default'   => 'filter',
				'options'   => array(
					'filter' => __( 'Filter branches in region', 'mapify' ),
					'none'   => __( 'Do nothing', 'mapify' ),
				),
				'condition' => array( 'maptype' => array( 'iran', 'svg' ) ),
			),
			'region_labels'     => array(
				'group'     => 'plane',
				'type'      => 'select',
				'label'     => __( 'Region names', 'mapify' ),
				'default'   => 'auto',
				'options'   => array(
					'auto' => __( 'Site language', 'mapify' ),
					'fa'   => __( 'Persian', 'mapify' ),
					'en'   => __( 'English', 'mapify' ),
				),
				'condition' => array( 'maptype' => array( 'iran' ) ),
			),

			// Custom pins.
			'pins'              => array(
				'group'       => 'pins',
				'type'        => 'repeater',
				'label'       => __( 'Custom pins', 'mapify' ),
				'description' => __( 'Extra pins that are not branches. On image/SVG maps use X/Y (percent = responsive, px = absolute); on geographic maps use latitude/longitude.', 'mapify' ),
				'default'     => array(),
				'title_field' => 'title',
				'fields'      => array(
					'title'   => array(
						'type'    => 'text',
						'label'   => __( 'Title', 'mapify' ),
						'default' => __( 'New pin', 'mapify' ),
					),
					'x'       => array(
						'type'    => 'text',
						'label'   => __( 'X position', 'mapify' ),
						'default' => '50',
					),
					'y'       => array(
						'type'    => 'text',
						'label'   => __( 'Y position', 'mapify' ),
						'default' => '50',
					),
					'unit'    => array(
						'type'    => 'select',
						'label'   => __( 'Position unit', 'mapify' ),
						'default' => '%',
						'options' => array(
							'%'  => __( 'Percent (relative)', 'mapify' ),
							'px' => __( 'Pixels (absolute)', 'mapify' ),
						),
					),
					'lat'     => array(
						'type'    => 'text',
						'label'   => __( 'Latitude', 'mapify' ),
						'default' => '',
					),
					'lng'     => array(
						'type'    => 'text',
						'label'   => __( 'Longitude', 'mapify' ),
						'default' => '',
					),
					'content' => array(
						'type'    => 'textarea',
						'label'   => __( 'Popup content (HTML)', 'mapify' ),
						'default' => '',
					),
					'link'    => array(
						'type'    => 'text',
						'label'   => __( 'Link URL', 'mapify' ),
						'default' => '',
					),
					'image'   => array(
						'type'    => 'media',
						'label'   => __( 'Pin image', 'mapify' ),
						'default' => '',
					),
					'color'   => array(
						'type'    => 'color',
						'label'   => __( 'Pin color', 'mapify' ),
						'default' => '',
					),
				),
			),

			// Markers.
			'pin_style'         => array(
				'group'   => 'markers',
				'type'    => 'select',
				'label'   => __( 'Pin style', 'mapify' ),
				'default' => 'marker',
				'options' => array(
					'marker' => __( 'Marker', 'mapify' ),
					'dot'    => __( 'Dot', 'mapify' ),
					'image'  => __( 'Image', 'mapify' ),
				),
			),
			'pinimage'          => array(
				'group'       => 'markers',
				'type'        => 'media',
				'label'       => __( 'Pin image', 'mapify' ),
				'description' => __( 'Overrides every branch pin image.', 'mapify' ),
				'default'     => '',
				'condition'   => array( 'pin_style' => array( 'image' ) ),
			),
			'pin_animation'     => array(
				'group'   => 'markers',
				'type'    => 'select',
				'label'   => __( 'Pin animation', 'mapify' ),
				'default' => 'drop',
				'options' => array(
					'none'   => __( 'None', 'mapify' ),
					'drop'   => __( 'Drop in', 'mapify' ),
					'pulse'  => __( 'Pulse', 'mapify' ),
					'bounce' => __( 'Bounce on hover', 'mapify' ),
				),
			),
			'show_tooltip'      => array(
				'group'   => 'markers',
				'type'    => 'toggle',
				'label'   => __( 'Show title on hover', 'mapify' ),
				'default' => true,
			),
			'pinaction'         => array(
				'group'   => 'markers',
				'type'    => 'select',
				'label'   => __( 'Pin click action', 'mapify' ),
				'default' => 'popup',
				'options' => array(
					'popup' => __( 'Open popup', 'mapify' ),
					'url'   => __( 'Open branch page', 'mapify' ),
					'none'  => __( 'Nothing', 'mapify' ),
				),
			),
			'pinurltarget'      => array(
				'group'     => 'markers',
				'type'      => 'select',
				'label'     => __( 'Link target', 'mapify' ),
				'default'   => '_self',
				'options'   => array(
					'_self'  => __( 'Same tab', 'mapify' ),
					'_blank' => __( 'New tab', 'mapify' ),
				),
				'condition' => array( 'pinaction' => array( 'url' ) ),
			),
			'branchascluster'   => array(
				'group'     => 'markers',
				'type'      => 'toggle',
				'label'     => __( 'Cluster nearby pins', 'mapify' ),
				'default'   => true,
				'condition' => array( 'maptype' => array_merge( array( '' ), $geo ) ),
			),
			'clustergridsize'   => array(
				'group'     => 'markers',
				'type'      => 'number',
				'label'     => __( 'Cluster radius (px)', 'mapify' ),
				'default'   => 60,
				'min'       => 10,
				'max'       => 300,
				'condition' => array( 'branchascluster' => array( true ) ),
			),
			'clusterminsize'    => array(
				'group'     => 'markers',
				'type'      => 'number',
				'label'     => __( 'Minimum pins per cluster', 'mapify' ),
				'default'   => 2,
				'min'       => 2,
				'max'       => 50,
				'condition' => array( 'branchascluster' => array( true ) ),
			),

			// Popup.
			'popup_markup'      => array(
				'group'       => 'popup',
				'type'        => 'code',
				'label'       => __( 'Popup template (HTML)', 'mapify' ),
				'description' => sprintf(
					/* translators: %s: list of tags */
					__( 'Tags: %s. Use {tag|fallback} for a default value. {image} is the featured image, {pin_image} the pin image and {popup_image} the image chosen in “Popup image”.', 'mapify' ),
					'{' . implode( '} {', self::popup_tags() ) . '}'
				),
				'default'     => '',
				'language'    => 'html',
				'condition'   => array( 'pinaction' => array( 'popup' ) ),
			),

			'popup_image'       => array(
				'group'       => 'popup',
				'type'        => 'select',
				'label'       => __( 'Popup image', 'mapify' ),
				'description' => __( 'Used by the {popup_image} tag. {image} is always the featured image and {pin_image} the pin image.', 'mapify' ),
				'default'     => 'featured',
				'options'     => array(
					'featured' => __( 'Featured image', 'mapify' ),
					'pin'      => __( 'Branch pin image', 'mapify' ),
					'auto'     => __( 'Featured image, else pin image', 'mapify' ),
					'none'     => __( 'No image', 'mapify' ),
				),
				'condition'   => array( 'pinaction' => array( 'popup' ) ),
			),
			'popup_image_fallback' => array(
				'group'       => 'popup',
				'type'        => 'toggle',
				'label'       => __( 'Placeholder when there is no image', 'mapify' ),
				'default'     => true,
				'condition'   => array( 'popup_image' => array( 'featured', 'pin', 'auto' ) ),
			),
			'popup_directions'  => array(
				'group'       => 'popup',
				'type'        => 'toggle',
				'label'       => __( 'Get directions button', 'mapify' ),
				'description' => __( 'Add {directions} to a custom template to choose where the button goes.', 'mapify' ),
				'default'     => true,
				'condition'   => array( 'pinaction' => array( 'popup' ) ),
			),
			'directions_label'  => array(
				'group'       => 'popup',
				'type'        => 'text',
				'label'       => __( 'Button text', 'mapify' ),
				'default'     => '',
				'placeholder' => __( 'Get directions', 'mapify' ),
				'condition'   => array( 'popup_directions' => array( true ) ),
			),
			'directions_mode'   => array(
				'group'       => 'popup',
				'type'        => 'select',
				'label'       => __( 'Button action', 'mapify' ),
				'description' => __( 'Android can list the map apps installed on the phone. iPhone and desktop browsers cannot, so they get a list of map apps to choose from.', 'mapify' ),
				'default'     => 'auto',
				'options'     => array(
					'auto'   => __( 'Installed map apps on Android, app list elsewhere', 'mapify' ),
					'sheet'  => __( 'Always show the app list', 'mapify' ),
					'google' => __( 'Open Google Maps directly', 'mapify' ),
				),
				'condition'   => array( 'popup_directions' => array( true ) ),
			),
			'directions_apps'   => array(
				'group'     => 'popup',
				'type'      => 'multiselect',
				'label'     => __( 'Apps in the list', 'mapify' ),
				'default'   => array( 'google', 'apple', 'waze', 'neshan', 'balad' ),
				'options'   => self::direction_apps(),
				'condition' => array( 'popup_directions' => array( true ) ),
			),

			// Branches list.
			'branchlistshow'    => array(
				'group'   => 'list',
				'type'    => 'toggle',
				'label'   => __( 'Show branches list', 'mapify' ),
				'default' => true,
			),
			'branchplacement'   => array(
				'group'     => 'list',
				'type'      => 'select',
				'label'     => __( 'List position', 'mapify' ),
				'default'   => 'top',
				'options'   => array(
					'top'    => __( 'Above the map', 'mapify' ),
					'bottom' => __( 'Below the map', 'mapify' ),
					'start'  => __( 'Sidebar (start)', 'mapify' ),
					'end'    => __( 'Sidebar (end)', 'mapify' ),
				),
				'condition' => array( 'branchlistshow' => array( true ) ),
			),
			'list_layout'       => array(
				'group'     => 'list',
				'type'      => 'select',
				'label'     => __( 'List layout', 'mapify' ),
				'default'   => 'chips',
				'options'   => self::list_layouts(),
				'condition' => array( 'branchlistshow' => array( true ) ),
			),
			'brancheslistcat'   => array(
				'group'     => 'list',
				'type'      => 'select',
				'label'     => __( 'Group by category', 'mapify' ),
				'default'   => 'none',
				'options'   => array(
					'none'     => __( 'No', 'mapify' ),
					'category' => __( 'Yes', 'mapify' ),
				),
				'condition' => array( 'branchlistshow' => array( true ) ),
			),
			'branchessearch'    => array(
				'group'     => 'list',
				'type'      => 'toggle',
				'label'     => __( 'Search box', 'mapify' ),
				'default'   => true,
				'condition' => array( 'branchlistshow' => array( true ) ),
			),
			'search_placeholder' => array(
				'group'     => 'list',
				'type'      => 'text',
				'label'     => __( 'Search placeholder', 'mapify' ),
				'default'   => '',
				'placeholder' => __( 'Search branches…', 'mapify' ),
				'condition' => array( 'branchessearch' => array( true ) ),
			),

			// Category filter.
			'list_cat_filter'   => array(
				'group'       => 'filter',
				'type'        => 'toggle',
				'label'       => __( 'Category chips under the search box', 'mapify' ),
				'description' => __( 'Visitors pick a category and the list and the map show only its branches.', 'mapify' ),
				'default'     => false,
				'condition'   => array( 'branchlistshow' => array( true ) ),
			),
			'map_cat_filter'    => array(
				'group'       => 'filter',
				'type'        => 'toggle',
				'label'       => __( 'Category chips on the map', 'mapify' ),
				'description' => __( 'Shows only the pins of the chosen category.', 'mapify' ),
				'default'     => false,
			),
			'map_cat_filter_position' => array(
				'group'     => 'filter',
				'type'      => 'select',
				'label'     => __( 'Position on the map', 'mapify' ),
				'default'   => 'top',
				'options'   => array(
					'top'    => __( 'Top', 'mapify' ),
					'bottom' => __( 'Bottom', 'mapify' ),
				),
				'condition' => array( 'map_cat_filter' => array( true ) ),
			),
			'cat_filter_multiple' => array(
				'group'   => 'filter',
				'type'    => 'toggle',
				'label'   => __( 'Allow choosing several categories', 'mapify' ),
				'default' => false,
			),
			'cat_filter_counts' => array(
				'group'   => 'filter',
				'type'    => 'toggle',
				'label'   => __( 'Show the number of branches', 'mapify' ),
				'default' => true,
			),
			'cat_filter_all'    => array(
				'group'       => 'filter',
				'type'        => 'text',
				'label'       => __( '“All” chip text', 'mapify' ),
				'description' => __( 'Leave empty for the default text.', 'mapify' ),
				'default'     => '',
				'placeholder' => __( 'All', 'mapify' ),
			),

			// Appearance (Elementor uses its Style tab instead).
			'el_map_width'      => array(
				'group'   => 'appearance',
				'type'    => 'text',
				'label'   => __( 'Width', 'mapify' ),
				'default' => '100%',
			),
			'el_map_height'     => array(
				'group'       => 'appearance',
				'type'        => 'text',
				'label'       => __( 'Height', 'mapify' ),
				'description' => __( 'Image and SVG maps size themselves by their aspect ratio.', 'mapify' ),
				'default'     => '',
				'placeholder' => '500px',
			),
			'accent_color'      => array(
				'group'   => 'appearance',
				'type'    => 'color',
				'label'   => __( 'Accent color', 'mapify' ),
				'default' => '',
			),
			'pin_color'         => array(
				'group'   => 'appearance',
				'type'    => 'color',
				'label'   => __( 'Pin color', 'mapify' ),
				'default' => '',
			),
			'pin_size'          => array(
				'group'   => 'appearance',
				'type'    => 'number',
				'label'   => __( 'Pin size (px)', 'mapify' ),
				'default' => '',
				'min'     => 12,
				'max'     => 96,
			),
			'radius'            => array(
				'group'   => 'appearance',
				'type'    => 'number',
				'label'   => __( 'Corner radius (px)', 'mapify' ),
				'default' => '',
				'min'     => 0,
				'max'     => 60,
			),
			'popup_bg'          => array(
				'group'   => 'appearance',
				'type'    => 'color',
				'label'   => __( 'Popup background', 'mapify' ),
				'default' => '',
			),
			'popup_color'       => array(
				'group'   => 'appearance',
				'type'    => 'color',
				'label'   => __( 'Popup text', 'mapify' ),
				'default' => '',
			),
			'popup_width'       => array(
				'group'   => 'appearance',
				'type'    => 'number',
				'label'   => __( 'Popup width (px)', 'mapify' ),
				'default' => '',
				'min'     => 160,
				'max'     => 600,
			),
			'list_bg'           => array(
				'group'   => 'appearance',
				'type'    => 'color',
				'label'   => __( 'List item background', 'mapify' ),
				'default' => '',
			),
			'list_color'        => array(
				'group'   => 'appearance',
				'type'    => 'color',
				'label'   => __( 'List item text', 'mapify' ),
				'default' => '',
			),
			'region_fill'       => array(
				'group'   => 'appearance',
				'type'    => 'color',
				'label'   => __( 'Region fill', 'mapify' ),
				'default' => '',
			),
			'region_active'     => array(
				'group'   => 'appearance',
				'type'    => 'color',
				'label'   => __( 'Region with branches', 'mapify' ),
				'default' => '',
			),
			'region_hover'      => array(
				'group'   => 'appearance',
				'type'    => 'color',
				'label'   => __( 'Region hover', 'mapify' ),
				'default' => '',
			),
			'region_stroke'     => array(
				'group'   => 'appearance',
				'type'    => 'color',
				'label'   => __( 'Region border', 'mapify' ),
				'default' => '',
			),
			'loading_color'     => array(
				'group'       => 'appearance',
				'type'        => 'text',
				'label'       => __( 'Loading background', 'mapify' ),
				'placeholder' => 'linear-gradient(120deg,#dd5542,#fd9d73)',
				'default'     => '',
			),

			// Advanced.
			'el_id'             => array(
				'group'   => 'advanced',
				'type'    => 'text',
				'label'   => __( 'HTML id', 'mapify' ),
				'default' => '',
			),
			'el_class'          => array(
				'group'   => 'advanced',
				'type'    => 'text',
				'label'   => __( 'Extra CSS classes', 'mapify' ),
				'default' => '',
			),
		);

		self::$fields = apply_filters( 'mapify_schema_fields', $fields );
		return self::$fields;
	}

	/** Maps appearance fields to CSS custom properties (value suffix appended for numbers). */
	public static function style_vars() {
		return array(
			'el_map_width'  => array( '--mapify-width', '' ),
			'el_map_height' => array( '--mapify-height', '' ),
			'accent_color'  => array( '--mapify-accent', '' ),
			'pin_color'     => array( '--mapify-pin-color', '' ),
			'pin_size'      => array( '--mapify-pin-size', 'px' ),
			'radius'        => array( '--mapify-radius', 'px' ),
			'popup_bg'      => array( '--mapify-popup-bg', '' ),
			'popup_color'   => array( '--mapify-popup-color', '' ),
			'popup_width'   => array( '--mapify-popup-width', 'px' ),
			'list_bg'       => array( '--mapify-list-bg', '' ),
			'list_color'    => array( '--mapify-list-color', '' ),
			'region_fill'   => array( '--mapify-region-fill', '' ),
			'region_active' => array( '--mapify-region-active', '' ),
			'region_hover'  => array( '--mapify-region-hover', '' ),
			'region_stroke' => array( '--mapify-region-stroke', '' ),
			'loading_color' => array( '--mapify-loading-bg', '' ),
		);
	}

	/**
	 * Resolve dynamic option lists.
	 */
	public static function options( $field ) {
		if ( ! isset( $field['options'] ) ) {
			return array();
		}
		if ( is_array( $field['options'] ) ) {
			return $field['options'];
		}
		switch ( $field['options'] ) {
			case 'categories':
				$out   = array();
				$terms = get_terms(
					array(
						'taxonomy'   => 'mapify_category',
						'hide_empty' => false,
					)
				);
				if ( ! is_wp_error( $terms ) ) {
					foreach ( $terms as $term ) {
						$out[ $term->slug ] = $term->name;
					}
				}
				return $out;
			case 'branches':
				$out   = array();
				$posts = get_posts(
					array(
						'post_type'   => 'mapify',
						'numberposts' => 500,
						'orderby'     => 'title',
						'order'       => 'ASC',
						'post_status' => 'publish',
					)
				);
				foreach ( $posts as $post ) {
					$out[ (string) $post->ID ] = $post->post_title ? $post->post_title : '#' . $post->ID;
				}
				return $out;
			case 'google_styles':
				return self::google_style_options();
		}
		return array();
	}

	public static function defaults() {
		$out = array();
		foreach ( self::fields() as $key => $field ) {
			$out[ $key ] = $field['default'];
		}
		return $out;
	}

	public static function is_true( $value ) {
		if ( is_bool( $value ) ) {
			return $value;
		}
		return in_array( strtolower( (string) $value ), array( '1', 'yes', 'true', 'on' ), true );
	}

	/**
	 * Decode values stored by WPBakery's textarea_raw_html (base64 of rawurlencode).
	 */
	public static function maybe_decode_raw_html( $value ) {
		$value = (string) $value;
		if ( '' === $value || preg_match( '/[<>{}\s\[]/', $value ) ) {
			return $value;
		}
		$decoded = base64_decode( $value, true );
		if ( false === $decoded ) {
			return $value;
		}
		return rawurldecode( $decoded );
	}

	public static function media_url( $value ) {
		if ( is_array( $value ) ) {
			if ( ! empty( $value['url'] ) ) {
				return esc_url_raw( $value['url'] );
			}
			$value = isset( $value['id'] ) ? $value['id'] : '';
		}
		if ( is_numeric( $value ) && (int) $value > 0 ) {
			$url = wp_get_attachment_url( (int) $value );
			return $url ? $url : '';
		}
		return esc_url_raw( (string) $value );
	}

	protected static function to_list( $value ) {
		if ( is_array( $value ) ) {
			return array_values( array_filter( array_map( 'trim', array_map( 'strval', $value ) ), 'strlen' ) );
		}
		return array_values( array_filter( array_map( 'trim', explode( ',', (string) $value ) ), 'strlen' ) );
	}

	/**
	 * Parse pins given as an array (Elementor), urlencoded JSON (WPBakery param_group / shortcode) or JSON.
	 */
	public static function parse_pins( $value ) {
		if ( is_string( $value ) && '' !== $value ) {
			$decoded = json_decode( rawurldecode( $value ), true );
			if ( ! is_array( $decoded ) ) {
				$decoded = json_decode( $value, true );
			}
			$value = is_array( $decoded ) ? $decoded : array();
		}
		if ( ! is_array( $value ) ) {
			return array();
		}
		$out = array();
		foreach ( $value as $i => $pin ) {
			if ( ! is_array( $pin ) ) {
				continue;
			}
			$unit  = isset( $pin['unit'] ) && 'px' === $pin['unit'] ? 'px' : '%';
			$out[] = array(
				'id'      => 'pin-' . ( isset( $pin['_id'] ) ? sanitize_key( $pin['_id'] ) : $i ),
				'custom'  => true,
				'title'   => isset( $pin['title'] ) ? sanitize_text_field( $pin['title'] ) : '',
				'x'       => isset( $pin['x'] ) && '' !== $pin['x'] ? (float) $pin['x'] : null,
				'y'       => isset( $pin['y'] ) && '' !== $pin['y'] ? (float) $pin['y'] : null,
				'unit'    => $unit,
				'latitude'  => isset( $pin['lat'] ) && is_numeric( $pin['lat'] ) ? (float) $pin['lat'] : null,
				'longitude' => isset( $pin['lng'] ) && is_numeric( $pin['lng'] ) ? (float) $pin['lng'] : null,
				'content' => isset( $pin['content'] ) ? wp_kses_post( $pin['content'] ) : '',
				'url'     => isset( $pin['link'] ) ? esc_url_raw( is_array( $pin['link'] ) ? ( isset( $pin['link']['url'] ) ? $pin['link']['url'] : '' ) : $pin['link'] ) : '',
				'pin_img' => isset( $pin['image'] ) ? self::media_url( $pin['image'] ) : '',
				'color'   => isset( $pin['color'] ) ? sanitize_text_field( $pin['color'] ) : '',
			);
		}
		return $out;
	}

	/**
	 * Sanitize a popup template without letting KSES mangle {tag|fallback} placeholders in URLs.
	 */
	public static function kses_template( $html ) {
		$tokens = array();
		$html   = preg_replace_callback(
			'/\{[a-z0-9_]+(?:\|[^{}]*)?\}/i',
			function ( $m ) use ( &$tokens ) {
				$key            = 'mapifytoken' . count( $tokens ) . 'x';
				$tokens[ $key ] = $m[0];
				return 'https://' . $key;
			},
			(string) $html
		);
		$html = wp_kses_post( $html );
		foreach ( $tokens as $key => $token ) {
			$html = str_replace( array( 'https://' . $key, $key ), esc_attr( $token ), $html );
		}
		return $html;
	}

	/**
	 * Turn raw builder values into a clean, typed settings array.
	 *
	 * @param array  $raw     Raw attributes / settings.
	 * @param string $content Enclosed shortcode content (used as popup template).
	 */
	public static function normalize( $raw, $content = '' ) {
		$raw    = is_array( $raw ) ? $raw : array();
		$fields = self::fields();
		$out    = array();

		// Legacy v1 values.
		if ( isset( $raw['maptype'] ) && 'googlemap' === $raw['maptype'] ) {
			$raw['maptype'] = 'google';
		}
		if ( isset( $raw['branchtype'] ) && 'ids' === $raw['branchtype'] ) {
			$raw['branchtype'] = 'id';
		}
		if ( empty( $raw['branchtype'] ) ) {
			if ( ! empty( $raw['branchcat'] ) ) {
				$raw['branchtype'] = 'cat';
			} elseif ( ! empty( $raw['branchids'] ) ) {
				$raw['branchtype'] = 'id';
			}
		}
		if ( ! empty( $raw['pinimage'] ) && empty( $raw['pin_style'] ) ) {
			$raw['pin_style'] = 'image';
		}

		foreach ( $fields as $key => $field ) {
			$value = array_key_exists( $key, $raw ) ? $raw[ $key ] : $field['default'];
			switch ( $field['type'] ) {
				case 'toggle':
					$value = ( '' === $value || null === $value ) && array_key_exists( $key, $raw ) ? false : self::is_true( $value );
					break;
				case 'multiselect':
					$value = self::to_list( $value );
					break;
				case 'number':
					if ( is_array( $value ) ) {
						$value = isset( $value['size'] ) ? $value['size'] : '';
					}
					$value = is_numeric( $value ) ? $value + 0 : '';
					break;
				case 'media':
					$value = self::media_url( $value );
					break;
				case 'repeater':
					$value = self::parse_pins( $value );
					break;
				case 'code':
				case 'textarea':
					$value = self::maybe_decode_raw_html( $value );
					break;
				case 'select':
					$value    = (string) $value;
					$options  = self::options( $field );
					if ( ! empty( $options ) && ! array_key_exists( $value, $options ) && ! in_array( $key, array( 'map_defined_style' ), true ) ) {
						$value = (string) $field['default'];
					}
					break;
				case 'color':
					$value = sanitize_text_field( (string) $value );
					break;
				default:
					$value = is_scalar( $value ) ? sanitize_text_field( (string) $value ) : '';
			}
			$out[ $key ] = $value;
		}

		$content = trim( (string) $content );
		if ( '' !== $content ) {
			$out['popup_markup'] = $content;
		}
		if ( '' === trim( $out['popup_markup'] ) ) {
			$out['popup_markup'] = self::default_popup_template();
		}
		$out['popup_markup'] = self::kses_template( $out['popup_markup'] );

		if ( '' === $out['maptype'] || ! array_key_exists( $out['maptype'], self::engines() ) ) {
			$out['maptype'] = Options::get( 'default_engine' );
		}
		if ( '' === $out['center_coordinate'] ) {
			$out['center_coordinate'] = Options::get( 'default_center' );
		}
		if ( '' === $out['default_zoom'] ) {
			$out['default_zoom'] = (int) Options::get( 'default_zoom' );
		}
		if ( '' === $out['directions_label'] ) {
			$out['directions_label'] = __( 'Get directions', 'mapify' );
		}
		if ( '' === $out['cat_filter_all'] ) {
			$out['cat_filter_all'] = __( 'All', 'mapify' );
		}
		if ( '' === $out['search_placeholder'] ) {
			$out['search_placeholder'] = __( 'Search branches…', 'mapify' );
		}
		return apply_filters( 'mapify_normalized_settings', $out, $raw );
	}
}
