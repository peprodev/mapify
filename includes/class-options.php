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
			'nav_title'           => array( 'string', '' ),
			'nav_apps'            => array( 'array', self::default_nav_apps() ),
		);
	}

	/**
	 * Built-in navigation apps: id => [label, URL template, platform].
	 * URL placeholders: {lat} {lng} {title} {address}.
	 */
	public static function builtin_nav_apps() {
		return apply_filters(
			'mapify_builtin_nav_apps',
			array(
				'google' => array( __( 'Google Maps', 'mapify' ), 'https://www.google.com/maps/dir/?api=1&destination={lat},{lng}', 'all' ),
				'apple'  => array( __( 'Apple Maps', 'mapify' ), 'https://maps.apple.com/?daddr={lat},{lng}&q={title}', 'apple' ),
				'waze'   => array( __( 'Waze', 'mapify' ), 'https://waze.com/ul?ll={lat},{lng}&navigate=yes', 'all' ),
				'neshan' => array( __( 'Neshan', 'mapify' ), 'https://nshn.ir/?lat={lat}&lng={lng}', 'all' ),
				'balad'  => array( __( 'Balad', 'mapify' ), 'https://balad.ir/location?latitude={lat}&longitude={lng}', 'all' ),
				'geo'    => array( __( 'Other apps on this phone', 'mapify' ), 'geo:{lat},{lng}?q={lat},{lng}({title})', 'android' ),
			)
		);
	}

	/** Stored form of the default app list; empty label/URL/icon/platform mean "use the built-in value". */
	public static function default_nav_apps() {
		$out = array();
		foreach ( array( 'google', 'apple', 'waze', 'neshan', 'balad', 'geo' ) as $id ) {
			$out[] = array(
				'id'       => $id,
				'label'    => '',
				'url'      => '',
				'icon'     => '',
				'platform' => '',
				'enabled'  => true,
			);
		}
		return $out;
	}

	public static function nav_platforms() {
		return array(
			'all'     => __( 'All devices', 'mapify' ),
			'android' => __( 'Android only', 'mapify' ),
			'apple'   => __( 'Apple devices only', 'mapify' ),
			'mobile'  => __( 'Phones and tablets only', 'mapify' ),
			'desktop' => __( 'Desktop only', 'mapify' ),
		);
	}

	public static function nav_icon( $id ) {
		foreach ( array( 'svg', 'png' ) as $ext ) {
			if ( file_exists( MAPIFY_DIR . "assets/img/nav/{$id}.{$ext}" ) ) {
				return MAPIFY_ASSETS . "img/nav/{$id}.{$ext}";
			}
		}
		return MAPIFY_ASSETS . 'img/nav/app.svg';
	}

	/**
	 * Navigation apps with built-in values filled in.
	 *
	 * @return array[] id, label, url, icon, platform, enabled.
	 */
	public static function nav_apps() {
		$builtin = self::builtin_nav_apps();
		$saved   = self::get( 'nav_apps' );
		$out     = array();
		foreach ( is_array( $saved ) ? $saved : array() as $app ) {
			$id   = isset( $app['id'] ) ? $app['id'] : '';
			$base = isset( $builtin[ $id ] ) ? $builtin[ $id ] : array( '', '', 'all' );
			$url  = '' !== (string) $app['url'] ? $app['url'] : $base[1];
			if ( '' === $url ) {
				continue;
			}
			$out[] = array(
				'id'       => $id,
				'label'    => '' !== (string) $app['label'] ? $app['label'] : ( $base[0] ? $base[0] : $id ),
				'url'      => $url,
				'icon'     => '' !== (string) $app['icon'] ? $app['icon'] : self::nav_icon( $id ),
				'platform' => '' !== (string) $app['platform'] ? $app['platform'] : $base[2],
				'enabled'  => ! empty( $app['enabled'] ),
			);
		}
		return apply_filters( 'mapify_nav_apps', $out );
	}

	/**
	 * Navigation URL templates may use any app scheme (geo:, waze:, comgooglemaps: …) but never script schemes.
	 */
	public static function sanitize_nav_url( $url ) {
		$url = trim( preg_replace( '/[\s"\'<>`]+/', '', (string) $url ) );
		if ( ! preg_match( '/^([a-z][a-z0-9+.\-]*):/i', $url, $m ) || in_array( strtolower( $m[1] ), array( 'javascript', 'data', 'vbscript', 'file' ), true ) ) {
			return '';
		}
		return $url;
	}

	protected static function sanitize_nav_apps( $apps ) {
		$out  = array();
		$seen = array();
		foreach ( is_array( $apps ) ? array_slice( array_values( $apps ), 0, 30 ) : array() as $i => $app ) {
			if ( ! is_array( $app ) ) {
				continue;
			}
			$id = isset( $app['id'] ) ? sanitize_key( $app['id'] ) : '';
			if ( '' === $id || isset( $seen[ $id ] ) ) {
				$id = 'app-' . ( $i + 1 ) . '-' . substr( md5( wp_json_encode( $app ) ), 0, 4 );
			}
			$seen[ $id ] = true;
			$platform    = isset( $app['platform'] ) ? (string) $app['platform'] : '';
			$out[]       = array(
				'id'       => $id,
				'label'    => isset( $app['label'] ) ? sanitize_text_field( $app['label'] ) : '',
				'url'      => isset( $app['url'] ) ? self::sanitize_nav_url( $app['url'] ) : '',
				'icon'     => isset( $app['icon'] ) ? esc_url_raw( $app['icon'] ) : '',
				'platform' => array_key_exists( $platform, self::nav_platforms() ) ? $platform : '',
				'enabled'  => ! empty( $app['enabled'] ),
			);
		}
		return $out;
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
		$properties['nav_apps']['items'] = array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'properties'           => array(
				'id'       => array( 'type' => 'string' ),
				'label'    => array( 'type' => 'string' ),
				'url'      => array( 'type' => 'string' ),
				'icon'     => array( 'type' => 'string' ),
				'platform' => array( 'type' => 'string' ),
				'enabled'  => array( 'type' => 'boolean' ),
			),
		);
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
				case 'array':
					$out[ $key ] = 'nav_apps' === $key ? self::sanitize_nav_apps( $raw ) : ( is_array( $raw ) ? $raw : array() );
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
