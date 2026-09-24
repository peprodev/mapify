<?php
/**
 * Multilingual support.
 *
 * - Interface texts use the "mapify" text domain; WPML String Translation and Loco Translate find them
 *   in the PHP and JavaScript files (JavaScript texts load through wp_set_script_translations()).
 * - wpml-config.xml makes branches, branch categories, branch details, the texts of the Elementor
 *   widget and the WPBakery element / shortcode, and texts entered in Map Settings translatable in WPML.
 * - Polylang gets the Map Settings texts through pll_register_string().
 *
 * @package Mapify
 */

namespace Mapify;

defined( 'ABSPATH' ) || exit;

class I18n {

	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'register_polylang' ) );
	}

	/**
	 * Texts entered in Map Settings that visitors see: name => value.
	 * Extensions add theirs with the mapify_translatable_strings filter.
	 */
	public static function strings() {
		return apply_filters( 'mapify_translatable_strings', array(), Options::all() );
	}

	/** Polylang lists the strings registered on each admin request. */
	public static function register_polylang() {
		if ( ! function_exists( 'pll_register_string' ) ) {
			return;
		}
		foreach ( self::strings() as $name => $value ) {
			if ( is_string( $value ) && '' !== trim( $value ) ) {
				pll_register_string( $name, $value, 'Mapify', false !== strpos( $value, "\n" ) );
			}
		}
	}

	/**
	 * A Map Settings text in the current language. WPML already translates them when the option is read
	 * (admin-texts in wpml-config.xml); Polylang needs pll__().
	 */
	public static function translate( $value ) {
		if ( is_string( $value ) && '' !== $value && function_exists( 'pll__' ) ) {
			return pll__( $value );
		}
		return $value;
	}
}
