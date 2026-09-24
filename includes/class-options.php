<?php
/**
 * Global plugin options (API keys and defaults), stored in one REST-enabled option.
 *
 * @package Mapify
 */

namespace Mapify;

defined( 'ABSPATH' ) || exit;

class Options {

	const KEY = 'mapify_settings';

	/**
	 * Option schema: key => [type, default].
	 */
	public static function schema() {
		return array(
			'google_api_key'      => array( 'string', '' ),
			'google_map_id'       => array( 'string', '' ),
			'google_language'     => array( 'string', '' ),
			'mapbox_token'        => array( 'string', '' ),
			'mapir_api_key'       => array( 'string', '' ),
			'neshan_api_key'      => array( 'string', '' ),
			'parsimap_api_key'    => array( 'string', '' ),
			'default_engine'      => array( 'string', 'osm' ),
			'default_center'      => array( 'string', '32.4279,53.6880' ),
			'default_zoom'        => array( 'integer', 5 ),
			'default_height'      => array( 'string', '500px' ),
			'accent_color'        => array( 'string', '#e05a46' ),
			'branch_template'     => array( 'string', 'content' ),
			'branch_slug'         => array( 'string', 'map' ),
			'allow_svg_upload'    => array( 'boolean', false ),
			'geocoder'            => array( 'string', 'nominatim' ),
			'clear_on_uninstall'  => array( 'boolean', false ),
			'attribution_mode'    => array( 'string', 'default' ),
			'attribution_text'    => array( 'string', '' ),
		);
	}

	public static function defaults() {
		$out = array();
		foreach ( self::schema() as $key => $def ) {
			$out[ $key ] = $def[1];
		}
		return $out;
	}

	public static function all() {
		$saved = get_option( self::KEY, null );
		if ( ! is_array( $saved ) ) {
			$saved = self::migrate_legacy();
		}
		return wp_parse_args( $saved, self::defaults() );
	}

	public static function get( $key ) {
		$all = self::all();
		return isset( $all[ $key ] ) ? $all[ $key ] : null;
	}

	/**
	 * Build settings from the options used by v1.x.
	 */
	protected static function migrate_legacy() {
		$legacy = array();
		$google = get_option( 'mapify-googlemapAPI', '' );
		if ( $google ) {
			$legacy['google_api_key'] = $google;
			$legacy['default_engine'] = 'google';
		}
		$template = get_option( 'mapify-template', '' );
		if ( $template ) {
			$legacy['branch_template'] = 'post' === $template ? 'post' : 'content';
		}
		if ( 'yes' === get_option( 'mapify-clearunistall', 'no' ) ) {
			$legacy['clear_on_uninstall'] = true;
		}
		return $legacy;
	}

	public static function register() {
		$properties = array();
		foreach ( self::schema() as $key => $def ) {
			$properties[ $key ] = array( 'type' => $def[0] );
		}
		register_setting(
			'mapify',
			self::KEY,
			array(
				'type'              => 'object',
				'default'           => self::defaults(),
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'show_in_rest'      => array(
					'schema' => array(
						'type'                 => 'object',
						'properties'           => $properties,
						'additionalProperties' => false,
					),
				),
			)
		);
	}

	/** HTML allowed in a custom map attribution. */
	public static function attribution_tags() {
		return array(
			'a'      => array(
				'href'   => true,
				'target' => true,
				'rel'    => true,
				'title'  => true,
			),
			'strong' => array(),
			'em'     => array(),
			'span'   => array( 'class' => true ),
		);
	}

	public static function sanitize( $value ) {
		$value = is_array( $value ) ? $value : array();
		$out   = array();
		foreach ( self::schema() as $key => $def ) {
			$raw = isset( $value[ $key ] ) ? $value[ $key ] : $def[1];
			switch ( $def[0] ) {
				case 'boolean':
					$out[ $key ] = (bool) $raw;
					break;
				case 'integer':
					$out[ $key ] = (int) $raw;
					break;
				default:
					$out[ $key ] = 'attribution_text' === $key ? wp_kses( (string) $raw, self::attribution_tags() ) : sanitize_text_field( (string) $raw );
			}
		}
		if ( ! in_array( $out['attribution_mode'], array( 'default', 'hide', 'custom' ), true ) ) {
			$out['attribution_mode'] = 'default';
		}
		if ( ! in_array( $out['branch_template'], array( 'post', 'content' ), true ) ) {
			$out['branch_template'] = 'content';
		}
		if ( ! array_key_exists( $out['default_engine'], Schema::engines() ) ) {
			$out['default_engine'] = 'osm';
		}
		$out['branch_slug'] = sanitize_title( $out['branch_slug'] ) ?: 'map';
		if ( ! sanitize_hex_color( $out['accent_color'] ) ) {
			$out['accent_color'] = '#e05a46';
		}
		return $out;
	}
}
