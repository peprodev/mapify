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
				'osm'    => __( 'OpenStreetMap (free, no key)', 'pepro-mapify' ),
				'google' => __( 'Google Maps', 'pepro-mapify' ),
				'mapbox' => __( 'Mapbox', 'pepro-mapify' ),
				'mapir'    => __( 'Map.ir (Persian map)', 'pepro-mapify' ),
				'neshan'   => __( 'Neshan (Persian map)', 'pepro-mapify' ),
				'parsimap' => __( 'Parsimap (Persian map)', 'pepro-mapify' ),
				'mapup'    => __( 'Mapup (Persian map, no key)', 'pepro-mapify' ),
				'custom' => __( 'Custom tile server (XYZ)', 'pepro-mapify' ),
				'iran'   => __( 'Iran — offline SVG map', 'pepro-mapify' ),
			)
		);
	}

	/** Engines drawn on a flat plane (no tiles) where pins can use x/y positions. */
	public static function plane_engines() {
		return apply_filters( 'mapify_plane_engines', array( 'iran' ) );
	}

	public static function tile_engines() {
		return array( 'osm', 'mapbox', 'mapir', 'neshan', 'parsimap', 'mapup', 'custom' );
	}

	public static function groups() {
		return apply_filters(
			'mapify_schema_groups',
			array(
				'source'     => __( 'Branches', 'pepro-mapify' ),
				'map'        => __( 'Map', 'pepro-mapify' ),
				'google'     => __( 'Google Maps', 'pepro-mapify' ),
				'snazzy'     => __( 'Snazzy Maps style', 'pepro-mapify' ),
				'tiles'      => __( 'Tile layer', 'pepro-mapify' ),
				'plane'      => __( 'Offline Iran map', 'pepro-mapify' ),
				'markers'    => __( 'Markers', 'pepro-mapify' ),
				'popup'      => __( 'Popup', 'pepro-mapify' ),
				'list'       => __( 'Branches list', 'pepro-mapify' ),
				'appearance' => __( 'Appearance', 'pepro-mapify' ),
				'advanced'   => __( 'Advanced', 'pepro-mapify' ),
			)
		);
	}

	public static function osm_styles() {
		return apply_filters(
			'mapify_osm_styles',
			array(
				'osm'                 => __( 'OpenStreetMap standard', 'pepro-mapify' ),
				'osm-gray'            => __( 'OpenStreetMap grayscale', 'pepro-mapify' ),
				'osm-dark'            => __( 'OpenStreetMap dark', 'pepro-mapify' ),
				'osm-sepia'           => __( 'OpenStreetMap vintage', 'pepro-mapify' ),
				'osm-hot'             => __( 'OpenStreetMap Humanitarian', 'pepro-mapify' ),
				'osm-fr'              => __( 'OpenStreetMap France', 'pepro-mapify' ),
				'osm-de'              => __( 'OpenStreetMap Germany', 'pepro-mapify' ),
				'cyclosm'             => __( 'CyclOSM (roads and cycling)', 'pepro-mapify' ),
				'opentopo'            => __( 'OpenTopoMap', 'pepro-mapify' ),
				'esri-light'          => __( 'Light gray canvas (Esri)', 'pepro-mapify' ),
				'esri-dark'           => __( 'Dark gray canvas (Esri)', 'pepro-mapify' ),
				'esri-street'         => __( 'World street map (Esri)', 'pepro-mapify' ),
				'esri-topo'           => __( 'Topographic (Esri)', 'pepro-mapify' ),
				'esri-natgeo'         => __( 'National Geographic (Esri)', 'pepro-mapify' ),
				'esri-imagery'        => __( 'Satellite imagery (Esri)', 'pepro-mapify' ),
				'esri-imagery-labels' => __( 'Satellite with labels (Esri)', 'pepro-mapify' ),
				'esri-terrain'        => __( 'Terrain (Esri)', 'pepro-mapify' ),
				'esri-shaded'         => __( 'Shaded relief (Esri)', 'pepro-mapify' ),
				'esri-physical'       => __( 'Physical (Esri)', 'pepro-mapify' ),
				'esri-ocean'          => __( 'Ocean (Esri)', 'pepro-mapify' ),
			)
		);
	}

	public static function neshan_styles() {
		return array(
			'neshan'         => __( 'Neshan', 'pepro-mapify' ),
			'standard-day'   => __( 'Standard day', 'pepro-mapify' ),
			'standard-night' => __( 'Standard night', 'pepro-mapify' ),
			'dreamy'         => __( 'Dreamy', 'pepro-mapify' ),
			'dreamy-gold'    => __( 'Dreamy gold', 'pepro-mapify' ),
			'osm-bright'     => __( 'OSM bright', 'pepro-mapify' ),
		);
	}

	public static function parsimap_styles() {
		return array(
			'parsimap-streets-v11-raster' => __( 'Parsimap streets', 'pepro-mapify' ),
			'google-street-raster'        => __( 'Google-like streets', 'pepro-mapify' ),
		);
	}

	public static function mapbox_styles() {
		return array(
			'streets-v12'           => __( 'Streets', 'pepro-mapify' ),
			'outdoors-v12'          => __( 'Outdoors', 'pepro-mapify' ),
			'light-v11'             => __( 'Light', 'pepro-mapify' ),
			'dark-v11'              => __( 'Dark', 'pepro-mapify' ),
			'satellite-v9'          => __( 'Satellite', 'pepro-mapify' ),
			'satellite-streets-v12' => __( 'Satellite streets', 'pepro-mapify' ),
			'navigation-day-v1'     => __( 'Navigation day', 'pepro-mapify' ),
			'navigation-night-v1'   => __( 'Navigation night', 'pepro-mapify' ),
			'custom'                => __( 'Custom style (Mapbox Studio)', 'pepro-mapify' ),
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

	/**
	 * Read a Google style array from JSON or from a pasted Snazzy Maps JavaScript snippet (var styles = [...];).
	 */
	public static function parse_google_style( $value ) {
		// Shortcode attributes carry quotes and brackets as entities (&quot; &#91; &#93;).
		$value = trim( html_entity_decode( (string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
		$start = strpos( $value, '[' );
		$end   = strrpos( $value, ']' );
		if ( false === $start || false === $end || $end < $start ) {
			return array();
		}
		$decoded = json_decode( substr( $value, $start, $end - $start + 1 ), true );
		return is_array( $decoded ) ? $decoded : array();
	}

	public static function google_style_options() {
		$out = array(
			'default' => __( 'Default', 'pepro-mapify' ),
		);
		foreach ( self::google_styles() as $slug => $style ) {
			$out[ $slug ] = $style['label'];
		}
		return apply_filters( 'mapify_google_style_options', $out );
	}

	/**
	 * Thumbnail for a style option, by field: Google styles and the free tile styles have one.
	 */
	public static function style_preview( $field_key, $slug ) {
		$url = '';
		if ( 'map_defined_style' === $field_key ) {
			$url = self::google_style_preview( $slug );
		} elseif ( 'osm_style' === $field_key && file_exists( MAPIFY_DIR . "assets/img/tile-style/{$slug}.jpg" ) ) {
			$url = MAPIFY_ASSETS . "img/tile-style/{$slug}.jpg";
		}
		return (string) apply_filters( 'mapify_style_preview', $url, $field_key, $slug );
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
				'chips' => __( 'Chips', 'pepro-mapify' ),
				'cards' => __( 'Cards', 'pepro-mapify' ),
				'list'  => __( 'Detailed list (address and phone)', 'pepro-mapify' ),
			)
		);
	}

	public static function default_popup_template() {
		$tpl = '<div class="mapify-card">
  <img class="mapify-card__image" src="{popup_image}" alt="{title}" />
  <div class="mapify-card__body">
    <h3 class="mapify-card__title">{title|' . esc_html__( 'No title', 'pepro-mapify' ) . '}</h3>
    <p class="mapify-card__row mapify-card__address">{address}</p>
    <p class="mapify-card__row mapify-card__phone"><a href="tel:{phone}">{phone}</a></p>
    <div class="mapify-card__actions">
      <a class="mapify-card__link" href="{url}">' . esc_html__( 'View branch', 'pepro-mapify' ) . '</a>
      {directions}
    </div>
  </div>
</div>';
		return apply_filters( 'mapify_default_popup_template', $tpl );
	}

	public static function popup_tags() {
		return apply_filters( 'mapify_popup_tags', array( 'id', 'title', 'image', 'pin_image', 'popup_image', 'url', 'latitude', 'longitude', 'address', 'phone', 'site', 'email', 'twitter', 'facebook', 'instagram', 'telegram', 'linkedin', 'additional', 'categories', 'directions' ) );
	}

	/**
	 * Field definitions.
	 *
	 * type: select|multiselect|text|textarea|code|number|toggle|color|media|repeater|notice
	 * (notice: a read-only message; variant "info", or "pro" for a feature this edition does not include)
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
				'label'   => __( 'Show branches', 'pepro-mapify' ),
				'default' => 'all',
				'options' => array(
					'all'  => __( 'All branches', 'pepro-mapify' ),
					'cat'  => __( 'From selected categories', 'pepro-mapify' ),
					'id'   => __( 'Handpicked branches', 'pepro-mapify' ),
				),
			),
			'branchcat'         => array(
				'group'     => 'source',
				'type'      => 'multiselect',
				'label'     => __( 'Categories', 'pepro-mapify' ),
				'default'   => array(),
				'options'   => 'categories',
				'condition' => array( 'branchtype' => array( 'cat' ) ),
			),
			'branchids'         => array(
				'group'     => 'source',
				'type'      => 'multiselect',
				'label'     => __( 'Branches', 'pepro-mapify' ),
				'default'   => array(),
				'options'   => 'branches',
				'condition' => array( 'branchtype' => array( 'id' ) ),
			),
			'orderby'           => array(
				'group'   => 'source',
				'type'    => 'select',
				'label'   => __( 'Order by', 'pepro-mapify' ),
				'default' => 'title',
				'options' => array(
					'title'      => __( 'Title', 'pepro-mapify' ),
					'date'       => __( 'Date', 'pepro-mapify' ),
					'menu_order' => __( 'Menu order', 'pepro-mapify' ),
					'post__in'   => __( 'Handpicked order', 'pepro-mapify' ),
				),
			),
			'order'             => array(
				'group'   => 'source',
				'type'    => 'select',
				'label'   => __( 'Order', 'pepro-mapify' ),
				'default' => 'ASC',
				'options' => array(
					'ASC'  => __( 'Ascending', 'pepro-mapify' ),
					'DESC' => __( 'Descending', 'pepro-mapify' ),
				),
			),

			// Map.
			'maptype'           => array(
				'group'   => 'map',
				'type'    => 'select',
				'label'   => __( 'Map engine', 'pepro-mapify' ),
				'default' => '',
				'options' => array( '' => __( 'Use global default', 'pepro-mapify' ) ) + self::engines(),
			),
			'center_coordinate' => array(
				'group'       => 'map',
				'type'        => 'text',
				'label'       => __( 'Center (lat,lng)', 'pepro-mapify' ),
				'default'     => '',
				'placeholder' => '35.6997,51.3380',
				'condition'   => array( 'maptype' => array_merge( array( '' ), $geo ) ),
			),
			'default_zoom'      => array(
				'group'       => 'map',
				'type'        => 'number',
				'label'       => __( 'Zoom level', 'pepro-mapify' ),
				'default'     => '',
				'min'         => 1,
				'max'         => 22,
				'placeholder' => '5',
				'condition'   => array( 'maptype' => array_merge( array( '' ), $geo ) ),
			),
			'fit_bounds'        => array(
				'group'       => 'map',
				'type'        => 'toggle',
				'label'       => __( 'Auto-fit to pins', 'pepro-mapify' ),
				'description' => __( 'Zoom and center the map so every pin is visible.', 'pepro-mapify' ),
				'default'     => true,
				'condition'   => array( 'maptype' => array_merge( array( '' ), $geo ) ),
			),
			'zoom_control'      => array(
				'group'       => 'map',
				'type'        => 'toggle',
				'label'       => __( 'Zoom buttons (+ / −)', 'pepro-mapify' ),
				'description' => __( 'Also works on the offline Iran map.', 'pepro-mapify' ),
				'default'     => true,
			),
			'zoom_position'     => array(
				'group'     => 'map',
				'type'      => 'select',
				'label'     => __( 'Zoom buttons position', 'pepro-mapify' ),
				'default'   => 'topleft',
				'options'   => array(
					'topleft'     => __( 'Top left', 'pepro-mapify' ),
					'topright'    => __( 'Top right', 'pepro-mapify' ),
					'bottomleft'  => __( 'Bottom left', 'pepro-mapify' ),
					'bottomright' => __( 'Bottom right', 'pepro-mapify' ),
				),
				'condition' => array( 'zoom_control' => array( true ) ),
			),
			'scroll_zoom'       => array(
				'group'   => 'map',
				'type'    => 'toggle',
				'label'   => __( 'Zoom with mouse wheel', 'pepro-mapify' ),
				'default' => false,
			),
			'double_click_zoom' => array(
				'group'   => 'map',
				'type'    => 'toggle',
				'label'   => __( 'Zoom on double click', 'pepro-mapify' ),
				'default' => true,
			),
			'touch_zoom'        => array(
				'group'   => 'map',
				'type'    => 'toggle',
				'label'   => __( 'Pinch to zoom on touch screens', 'pepro-mapify' ),
				'default' => true,
			),
			'min_zoom'          => array(
				'group'       => 'map',
				'type'        => 'number',
				'label'       => __( 'Minimum zoom', 'pepro-mapify' ),
				'default'     => '',
				'min'         => 1,
				'max'         => 22,
				'placeholder' => '1',
				'condition'   => array( 'maptype' => array_merge( array( '' ), $geo ) ),
			),
			'max_zoom'          => array(
				'group'       => 'map',
				'type'        => 'number',
				'label'       => __( 'Maximum zoom', 'pepro-mapify' ),
				'default'     => '',
				'min'         => 1,
				'max'         => 22,
				'placeholder' => '19',
				'condition'   => array( 'maptype' => array_merge( array( '' ), $geo ) ),
			),
			'plane_max_zoom'    => array(
				'group'       => 'map',
				'type'        => 'number',
				'label'       => __( 'Maximum zoom (×)', 'pepro-mapify' ),
				'description' => __( 'How far the offline map can be enlarged. 1 turns zooming off.', 'pepro-mapify' ),
				'default'     => 4,
				'min'         => 1,
				'max'         => 10,
				'condition'   => array( 'maptype' => $plane ),
			),
			'disabledefaultui'  => array(
				'group'   => 'map',
				'type'    => 'toggle',
				'label'   => __( 'Hide map controls', 'pepro-mapify' ),
				'default' => false,
			),
			'fullscreen'        => array(
				'group'   => 'map',
				'type'    => 'toggle',
				'label'   => __( 'Fullscreen button', 'pepro-mapify' ),
				'default' => true,
			),

			// Google.
			'google_type'       => array(
				'group'     => 'google',
				'type'      => 'select',
				'label'     => __( 'Map type', 'pepro-mapify' ),
				'default'   => 'roadmap',
				'options'   => array(
					'roadmap'   => __( 'Roadmap', 'pepro-mapify' ),
					'satellite' => __( 'Satellite', 'pepro-mapify' ),
					'hybrid'    => __( 'Hybrid', 'pepro-mapify' ),
					'terrain'   => __( 'Terrain', 'pepro-mapify' ),
				),
				'condition' => array( 'maptype' => array( 'google' ) ),
			),
			'map_defined_style' => array(
				'group'       => 'google',
				'type'        => 'select',
				'label'       => __( 'Map style', 'pepro-mapify' ),
				'description' => __( 'Styles are ignored when a Google Map ID is set in the plugin settings (cloud styling is used instead).', 'pepro-mapify' ),
				'default'     => 'default',
				'options'     => 'google_styles',
				'previews'    => true,
				'note'        => __( 'More templates are available in the Pro version.', 'pepro-mapify' ),
				'condition'   => array( 'maptype' => array( 'google' ) ),
			),
			'snazzy_teaser'     => array(
				'group'     => 'snazzy',
				'type'      => 'notice',
				'variant'   => 'pro',
				'label'     => __( 'Snazzy Maps style (JavaScript style array)', 'pepro-mapify' ),
				'content'   => __( 'Style Google Maps with any design from Snazzy Maps by pasting its JavaScript style array. Available in the Pro version.', 'pepro-mapify' ),
				'default'   => '',
				'condition' => array( 'maptype' => array( 'google' ) ),
			),

			// Tiles.
			'osm_style'         => array(
				'group'     => 'tiles',
				'type'      => 'select',
				'label'     => __( 'Tile style', 'pepro-mapify' ),
				'default'   => 'osm',
				'options'   => self::osm_styles(),
				'previews'  => true,
				'condition' => array( 'maptype' => array( 'osm' ) ),
			),
			'mapbox_style'      => array(
				'group'     => 'tiles',
				'type'      => 'select',
				'label'     => __( 'Mapbox style', 'pepro-mapify' ),
				'default'   => 'streets-v12',
				'options'   => self::mapbox_styles(),
				'condition' => array( 'maptype' => array( 'mapbox' ) ),
			),
			'neshan_style'      => array(
				'group'     => 'tiles',
				'type'      => 'select',
				'label'     => __( 'Neshan style', 'pepro-mapify' ),
				'default'   => 'neshan',
				'options'   => self::neshan_styles(),
				'condition' => array( 'maptype' => array( 'neshan' ) ),
			),
			'parsimap_style'    => array(
				'group'     => 'tiles',
				'type'      => 'select',
				'label'     => __( 'Parsimap style', 'pepro-mapify' ),
				'default'   => 'parsimap-streets-v11-raster',
				'options'   => self::parsimap_styles(),
				'condition' => array( 'maptype' => array( 'parsimap' ) ),
			),
			'mapbox_custom'     => array(
				'group'       => 'tiles',
				'type'        => 'text',
				'label'       => __( 'Mapbox style ID', 'pepro-mapify' ),
				'placeholder' => 'username/style-id',
				'default'     => '',
				'condition'   => array( 'mapbox_style' => array( 'custom' ) ),
			),
			'tiles_url'         => array(
				'group'       => 'tiles',
				'type'        => 'text',
				'label'       => __( 'Tile URL template', 'pepro-mapify' ),
				'placeholder' => 'https://{s}.tile.example.com/{z}/{x}/{y}.png',
				'default'     => '',
				'condition'   => array( 'maptype' => array( 'custom' ) ),
			),
			'tiles_attribution' => array(
				'group'     => 'tiles',
				'type'      => 'text',
				'label'     => __( 'Tile attribution', 'pepro-mapify' ),
				'default'   => '',
				'condition' => array( 'maptype' => array( 'custom' ) ),
			),

			// Plane maps.
			'region_tooltip'    => array(
				'group'     => 'plane',
				'type'      => 'toggle',
				'label'     => __( 'Show region name on hover', 'pepro-mapify' ),
				'default'   => true,
				'condition' => array( 'maptype' => array( 'iran' ) ),
			),
			'region_highlight'  => array(
				'group'     => 'plane',
				'type'      => 'toggle',
				'label'     => __( 'Highlight regions that have branches', 'pepro-mapify' ),
				'default'   => true,
				'condition' => array( 'maptype' => array( 'iran' ) ),
			),
			'region_click'      => array(
				'group'     => 'plane',
				'type'      => 'select',
				'label'     => __( 'Region click', 'pepro-mapify' ),
				'default'   => 'filter',
				'options'   => array(
					'filter' => __( 'Filter branches in region', 'pepro-mapify' ),
					'none'   => __( 'Do nothing', 'pepro-mapify' ),
				),
				'condition' => array( 'maptype' => array( 'iran' ) ),
			),
			'region_labels'     => array(
				'group'     => 'plane',
				'type'      => 'select',
				'label'     => __( 'Region names', 'pepro-mapify' ),
				'default'   => 'auto',
				'options'   => array(
					'auto' => __( 'Site language', 'pepro-mapify' ),
					'fa'   => __( 'Persian', 'pepro-mapify' ),
					'en'   => __( 'English', 'pepro-mapify' ),
				),
				'condition' => array( 'maptype' => array( 'iran' ) ),
			),

			// Markers.
			'pin_style'         => array(
				'group'   => 'markers',
				'type'    => 'select',
				'label'   => __( 'Pin style', 'pepro-mapify' ),
				'default' => 'marker',
				'options' => array(
					'marker' => __( 'Marker', 'pepro-mapify' ),
					'dot'    => __( 'Dot', 'pepro-mapify' ),
					'image'  => __( 'Image', 'pepro-mapify' ),
				),
			),
			'pinimage'          => array(
				'group'       => 'markers',
				'type'        => 'media',
				'label'       => __( 'Pin image', 'pepro-mapify' ),
				'description' => __( 'Overrides every branch pin image.', 'pepro-mapify' ),
				'default'     => '',
				'condition'   => array( 'pin_style' => array( 'image' ) ),
			),
			'pin_animation'     => array(
				'group'   => 'markers',
				'type'    => 'select',
				'label'   => __( 'Pin animation', 'pepro-mapify' ),
				'default' => 'drop',
				'options' => array(
					'none'   => __( 'None', 'pepro-mapify' ),
					'drop'   => __( 'Drop in', 'pepro-mapify' ),
					'pulse'  => __( 'Pulse', 'pepro-mapify' ),
					'bounce' => __( 'Bounce on hover', 'pepro-mapify' ),
				),
			),
			'show_tooltip'      => array(
				'group'   => 'markers',
				'type'    => 'toggle',
				'label'   => __( 'Show title on hover', 'pepro-mapify' ),
				'default' => true,
			),
			'pinaction'         => array(
				'group'   => 'markers',
				'type'    => 'select',
				'label'   => __( 'Pin click action', 'pepro-mapify' ),
				'default' => 'popup',
				'options' => array(
					'popup' => __( 'Open popup', 'pepro-mapify' ),
					'url'   => __( 'Open branch page', 'pepro-mapify' ),
					'none'  => __( 'Nothing', 'pepro-mapify' ),
				),
			),
			'pinurltarget'      => array(
				'group'     => 'markers',
				'type'      => 'select',
				'label'     => __( 'Link target', 'pepro-mapify' ),
				'default'   => '_self',
				'options'   => array(
					'_self'  => __( 'Same tab', 'pepro-mapify' ),
					'_blank' => __( 'New tab', 'pepro-mapify' ),
				),
				'condition' => array( 'pinaction' => array( 'url' ) ),
			),
			'branchascluster'   => array(
				'group'     => 'markers',
				'type'      => 'toggle',
				'label'     => __( 'Cluster nearby pins', 'pepro-mapify' ),
				'default'   => true,
				'condition' => array( 'maptype' => array_merge( array( '' ), $geo ) ),
			),
			'clustergridsize'   => array(
				'group'     => 'markers',
				'type'      => 'number',
				'label'     => __( 'Cluster radius (px)', 'pepro-mapify' ),
				'default'   => 60,
				'min'       => 10,
				'max'       => 300,
				'condition' => array( 'branchascluster' => array( true ) ),
			),
			'clusterminsize'    => array(
				'group'     => 'markers',
				'type'      => 'number',
				'label'     => __( 'Minimum pins per cluster', 'pepro-mapify' ),
				'default'   => 2,
				'min'       => 2,
				'max'       => 50,
				'condition' => array( 'branchascluster' => array( true ) ),
			),

			// Popup.
			'popup_markup'      => array(
				'group'       => 'popup',
				'type'        => 'code',
				'label'       => __( 'Popup template (HTML)', 'pepro-mapify' ),
				'description' => sprintf(
					/* translators: %s: list of tags */
					__( 'Tags: %s. Use {tag|fallback} for a default value. {image} is the featured image, {pin_image} the pin image and {popup_image} the image chosen in “Popup image”.', 'pepro-mapify' ),
					'{' . implode( '} {', self::popup_tags() ) . '}'
				),
				'default'     => '',
				'language'    => 'html',
				'condition'   => array( 'pinaction' => array( 'popup' ) ),
			),

			'popup_image'       => array(
				'group'       => 'popup',
				'type'        => 'select',
				'label'       => __( 'Popup image', 'pepro-mapify' ),
				'description' => __( 'Used by the {popup_image} tag. {image} is always the featured image and {pin_image} the pin image.', 'pepro-mapify' ),
				'default'     => 'featured',
				'options'     => array(
					'featured' => __( 'Featured image', 'pepro-mapify' ),
					'pin'      => __( 'Branch pin image', 'pepro-mapify' ),
					'auto'     => __( 'Featured image, else pin image', 'pepro-mapify' ),
					'none'     => __( 'No image', 'pepro-mapify' ),
				),
				'condition'   => array( 'pinaction' => array( 'popup' ) ),
			),
			'popup_image_fallback' => array(
				'group'       => 'popup',
				'type'        => 'toggle',
				'label'       => __( 'Placeholder when there is no image', 'pepro-mapify' ),
				'default'     => true,
				'condition'   => array( 'popup_image' => array( 'featured', 'pin', 'auto' ) ),
			),
			'popup_directions'  => array(
				'group'       => 'popup',
				'type'        => 'toggle',
				'label'       => __( 'Get directions button', 'pepro-mapify' ),
				'description' => __( 'Add {directions} to a custom template to choose where the button goes.', 'pepro-mapify' ),
				'default'     => true,
				'condition'   => array( 'pinaction' => array( 'popup' ) ),
			),
			'directions_label'  => array(
				'group'       => 'popup',
				'type'        => 'text',
				'label'       => __( 'Button text', 'pepro-mapify' ),
				'default'     => '',
				'placeholder' => __( 'Get directions', 'pepro-mapify' ),
				'condition'   => array( 'popup_directions' => array( true ) ),
			),
			'directions_mode'   => array(
				'group'       => 'popup',
				'type'        => 'select',
				'label'       => __( 'Button action', 'pepro-mapify' ),
				'description' => __( 'Android phones can list the map apps installed on the phone.', 'pepro-mapify' ),
				'default'     => 'auto',
				'options'     => array(
					'auto'   => __( 'Installed map apps on Android, Google Maps elsewhere', 'pepro-mapify' ),
					'google' => __( 'Open Google Maps', 'pepro-mapify' ),
				),
				'condition'   => array( 'popup_directions' => array( true ) ),
			),

			// Branches list.
			'branchlistshow'    => array(
				'group'   => 'list',
				'type'    => 'toggle',
				'label'   => __( 'Show branches list', 'pepro-mapify' ),
				'default' => true,
			),
			'branchplacement'   => array(
				'group'     => 'list',
				'type'      => 'select',
				'label'     => __( 'List position', 'pepro-mapify' ),
				'default'   => 'top',
				'options'   => array(
					'top'    => __( 'Above the map', 'pepro-mapify' ),
					'bottom' => __( 'Below the map', 'pepro-mapify' ),
					'start'  => __( 'Sidebar (start)', 'pepro-mapify' ),
					'end'    => __( 'Sidebar (end)', 'pepro-mapify' ),
				),
				'condition' => array( 'branchlistshow' => array( true ) ),
			),
			'list_layout'       => array(
				'group'     => 'list',
				'type'      => 'select',
				'label'     => __( 'List layout', 'pepro-mapify' ),
				'default'   => 'chips',
				'options'   => self::list_layouts(),
				'condition' => array( 'branchlistshow' => array( true ) ),
			),
			'brancheslistcat'   => array(
				'group'     => 'list',
				'type'      => 'select',
				'label'     => __( 'Group by category', 'pepro-mapify' ),
				'default'   => 'none',
				'options'   => array(
					'none'     => __( 'No', 'pepro-mapify' ),
					'category' => __( 'Yes', 'pepro-mapify' ),
				),
				'condition' => array( 'branchlistshow' => array( true ) ),
			),
			'branchessearch'    => array(
				'group'     => 'list',
				'type'      => 'toggle',
				'label'     => __( 'Search box', 'pepro-mapify' ),
				'default'   => true,
				'condition' => array( 'branchlistshow' => array( true ) ),
			),
			'search_placeholder' => array(
				'group'     => 'list',
				'type'      => 'text',
				'label'     => __( 'Search placeholder', 'pepro-mapify' ),
				'default'   => '',
				'placeholder' => __( 'Search branches…', 'pepro-mapify' ),
				'condition' => array( 'branchessearch' => array( true ) ),
			),
			'list_scroll_to_map' => array(
				'group'       => 'list',
				'type'        => 'toggle',
				'label'       => __( 'Scroll to the map when a branch is picked', 'pepro-mapify' ),
				'description' => __( 'When a visitor picks a branch in the list and the map is out of view, the page scrolls to the map.', 'pepro-mapify' ),
				'default'     => true,
				'condition'   => array( 'branchlistshow' => array( true ) ),
			),
			'list_open_popup'   => array(
				'group'       => 'list',
				'type'        => 'toggle',
				'label'       => __( 'Open the branch popup when it is picked', 'pepro-mapify' ),
				'description' => __( 'Turn off to only move the map to the branch.', 'pepro-mapify' ),
				'default'     => true,
				'condition'   => array( 'branchlistshow' => array( true ) ),
			),

			// Appearance (Elementor uses its Style tab instead).
			'el_map_width'      => array(
				'group'   => 'appearance',
				'type'    => 'text',
				'label'   => __( 'Width', 'pepro-mapify' ),
				'default' => '100%',
			),
			'el_map_height'     => array(
				'group'       => 'appearance',
				'type'        => 'text',
				'label'       => __( 'Height', 'pepro-mapify' ),
				'description' => __( 'The offline map sizes itself by its aspect ratio.', 'pepro-mapify' ),
				'default'     => '',
				'placeholder' => '500px',
			),
			'accent_color'      => array(
				'group'   => 'appearance',
				'type'    => 'color',
				'label'   => __( 'Accent color', 'pepro-mapify' ),
				'default' => '',
			),
			'pin_color'         => array(
				'group'   => 'appearance',
				'type'    => 'color',
				'label'   => __( 'Pin color', 'pepro-mapify' ),
				'default' => '',
			),
			'pin_size'          => array(
				'group'   => 'appearance',
				'type'    => 'number',
				'label'   => __( 'Pin size (px)', 'pepro-mapify' ),
				'default' => '',
				'min'     => 12,
				'max'     => 96,
			),
			'radius'            => array(
				'group'   => 'appearance',
				'type'    => 'number',
				'label'   => __( 'Corner radius (px)', 'pepro-mapify' ),
				'default' => '',
				'min'     => 0,
				'max'     => 60,
			),
			'popup_bg'          => array(
				'group'   => 'appearance',
				'type'    => 'color',
				'label'   => __( 'Popup background', 'pepro-mapify' ),
				'default' => '',
			),
			'popup_color'       => array(
				'group'   => 'appearance',
				'type'    => 'color',
				'label'   => __( 'Popup text', 'pepro-mapify' ),
				'default' => '',
			),
			'popup_width'       => array(
				'group'   => 'appearance',
				'type'    => 'number',
				'label'   => __( 'Popup width (px)', 'pepro-mapify' ),
				'default' => '',
				'min'     => 160,
				'max'     => 600,
			),
			'list_bg'           => array(
				'group'   => 'appearance',
				'type'    => 'color',
				'label'   => __( 'List item background', 'pepro-mapify' ),
				'default' => '',
			),
			'list_color'        => array(
				'group'   => 'appearance',
				'type'    => 'color',
				'label'   => __( 'List item text', 'pepro-mapify' ),
				'default' => '',
			),
			'region_fill'       => array(
				'group'   => 'appearance',
				'type'    => 'color',
				'label'   => __( 'Region fill', 'pepro-mapify' ),
				'default' => '',
			),
			'region_active'     => array(
				'group'   => 'appearance',
				'type'    => 'color',
				'label'   => __( 'Region with branches', 'pepro-mapify' ),
				'default' => '',
			),
			'region_hover'      => array(
				'group'   => 'appearance',
				'type'    => 'color',
				'label'   => __( 'Region hover', 'pepro-mapify' ),
				'default' => '',
			),
			'region_stroke'     => array(
				'group'   => 'appearance',
				'type'    => 'color',
				'label'   => __( 'Region border', 'pepro-mapify' ),
				'default' => '',
			),
			'loading_color'     => array(
				'group'       => 'appearance',
				'type'        => 'text',
				'label'       => __( 'Loading background', 'pepro-mapify' ),
				'placeholder' => 'linear-gradient(120deg,#dd5542,#fd9d73)',
				'default'     => '',
			),

			// Advanced.
			'el_id'             => array(
				'group'   => 'advanced',
				'type'    => 'text',
				'label'   => __( 'HTML id', 'pepro-mapify' ),
				'default' => '',
			),
			'el_class'          => array(
				'group'   => 'advanced',
				'type'    => 'text',
				'label'   => __( 'Extra CSS classes', 'pepro-mapify' ),
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
		return (array) apply_filters( 'mapify_schema_options', array(), $field['options'], $field );
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
	/**
	 * A CSS value from a shortcode attribute or widget setting (color, gradient or length).
	 * Only characters a color/length/gradient needs; nothing that can leave the value or load a resource.
	 */
	public static function sanitize_css_value( $value ) {
		$value = trim( wp_strip_all_tags( (string) $value ) );
		if ( '' === $value || strlen( $value ) > 200 ) {
			return '';
		}
		if ( ! preg_match( '/^[a-zA-Z0-9#%.,()\s+\-\/]+$/', $value ) || preg_match( '/(url|expression|image-set|element|attr)\s*\(/i', $value ) ) {
			return '';
		}
		return $value;
	}

	/** True when a URL uses a scheme that can run script (javascript:, vbscript:, data:). */
	public static function is_unsafe_url( $value ) {
		$value = strtolower( preg_replace( '/[\x00-\x20]+/', '', html_entity_decode( (string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
		return (bool) preg_match( '/^(javascript|vbscript|data|livescript):/', $value );
	}

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
	 * Sanitize a popup template without letting KSES mangle {tag|fallback} placeholders in URLs.
	 */
	public static function kses_template( $html ) {
		$tokens = array();
		$html   = preg_replace_callback(
			'/\{[a-z0-9_]+(?:\|[^{}]*)?\}/i',
			function ( $m ) use ( &$tokens ) {
				$token = $m[0];
				$bar   = strpos( $token, '|' );
				// A fallback value must not turn a link into script.
				if ( false !== $bar && self::is_unsafe_url( substr( $token, $bar + 1, -1 ) ) ) {
					$token = substr( $token, 0, $bar ) . '}';
				}
				$key            = 'mapifytoken' . count( $tokens ) . 'x';
				$tokens[ $key ] = $token;
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
					$value = (array) apply_filters( 'mapify_normalize_repeater', array(), $value, $key, $field );
					break;
				case 'notice':
					continue 2;
				case 'code':
				case 'textarea':
					$value = self::maybe_decode_raw_html( $value );
					break;
				case 'select':
					$value   = is_scalar( $value ) ? (string) $value : '';
					$options = self::options( $field );
					if ( 'map_defined_style' === $key ) {
						// Version 1 style names are mapped later, so only the format is checked here.
						$value = preg_match( '/^[a-z0-9_-]{1,64}$/', $value ) ? $value : (string) $field['default'];
					} elseif ( ! empty( $options ) && ! array_key_exists( $value, $options ) ) {
						$value = (string) $field['default'];
					}
					break;
				case 'color':
					$value = is_scalar( $value ) ? self::sanitize_css_value( $value ) : '';
					break;
				default:
					$value = is_scalar( $value ) ? sanitize_text_field( (string) $value ) : '';
			}
			$out[ $key ] = $value;
		}

		// Values that end up in HTML attributes, CSS or URLs.
		foreach ( array_keys( self::style_vars() ) as $key ) {
			if ( isset( $out[ $key ] ) && is_string( $out[ $key ] ) ) {
				$out[ $key ] = self::sanitize_css_value( $out[ $key ] );
			}
		}
		if ( isset( $out['el_id'] ) ) {
			$out['el_id'] = sanitize_html_class( $out['el_id'] );
		}
		if ( isset( $out['el_class'] ) ) {
			$out['el_class'] = implode( ' ', array_filter( array_map( 'sanitize_html_class', preg_split( '/\s+/', (string) $out['el_class'] ) ) ) );
		}
		if ( isset( $out['center_coordinate'] ) ) {
			$out['center_coordinate'] = preg_match( '/^\s*-?\d+(\.\d+)?\s*,\s*-?\d+(\.\d+)?\s*$/', $out['center_coordinate'] ) ? preg_replace( '/\s+/', '', $out['center_coordinate'] ) : '';
		}
		if ( isset( $out['tiles_url'] ) ) {
			$out['tiles_url'] = esc_url_raw( $out['tiles_url'], array( 'https', 'http' ) );
		}
		if ( isset( $out['mapbox_custom'] ) ) {
			$out['mapbox_custom'] = preg_match( '#^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$#', trim( $out['mapbox_custom'] ) ) ? trim( $out['mapbox_custom'] ) : '';
		}
		if ( isset( $out['tiles_attribution'] ) ) {
			$out['tiles_attribution'] = wp_kses(
				$out['tiles_attribution'],
				array(
					'a' => array(
						'href'   => true,
						'target' => true,
						'rel'    => true,
					),
				)
			);
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
			$out['directions_label'] = __( 'Get directions', 'pepro-mapify' );
		}
		if ( '' === $out['search_placeholder'] ) {
			$out['search_placeholder'] = __( 'Search branches…', 'pepro-mapify' );
		}
		return apply_filters( 'mapify_normalized_settings', $out, $raw );
	}
}
