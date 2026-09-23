<?php
/**
 * [pepro-mapify] shortcode (alias: [mapify]).
 *
 * Every schema field is an attribute. Enclosed content, when present, is used as the popup template:
 * [mapify maptype="iran"]<h3>{title}</h3>{address}[/mapify]
 *
 * @package Mapify
 */

namespace Mapify;

defined( 'ABSPATH' ) || exit;

class Shortcode {

	const TAG = 'pepro-mapify';

	public static function register() {
		add_shortcode( self::TAG, array( __CLASS__, 'render' ) );
		add_shortcode( 'mapify', array( __CLASS__, 'render' ) );
	}

	public static function render( $atts = array(), $content = '', $tag = '' ) {
		$atts     = is_array( $atts ) ? $atts : array();
		$settings = Schema::normalize( array_intersect_key( $atts, Schema::fields() ), (string) $content );
		$classes  = '';
		if ( ! empty( $atts['css'] ) && function_exists( 'vc_shortcode_custom_css_class' ) ) {
			$classes = apply_filters( defined( 'VC_SHORTCODE_CUSTOM_CSS_FILTER_TAG' ) ? VC_SHORTCODE_CUSTOM_CSS_FILTER_TAG : 'vc_shortcodes_css_class', vc_shortcode_custom_css_class( $atts['css'], ' ' ), self::TAG, $atts );
		}
		return Renderer::render( $settings, array( 'classes' => $classes ) );
	}

}
