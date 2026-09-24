<?php
/**
 * Plugin bootstrap: hooks, assets and page-builder integrations.
 *
 * @package Mapify
 */

namespace Mapify;

defined( 'ABSPATH' ) || exit;

class Plugin {

	protected static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	protected function __construct() {
		add_action(
			'init',
			function () {
				load_plugin_textdomain( 'mapify', false, dirname( plugin_basename( MAPIFY_FILE ) ) . '/languages/' );
			},
			0
		);
		add_action( 'init', array( Branches::class, 'register' ) );
		add_action( 'init', array( Shortcode::class, 'register' ) );
		add_action( 'init', array( $this, 'register_assets' ), 5 );
		add_action( 'init', array( Options::class, 'register' ) );
		add_filter( 'the_content', array( Branches::class, 'filter_content' ) );
		add_filter( 'upload_mimes', array( $this, 'svg_mime' ) );
		add_filter( 'wp_handle_upload_prefilter', array( $this, 'sanitize_svg_upload' ) );

		Branches::init_limit();
		Branch_Editor::init();
		Admin::init();
		Transfer::init();

		// Elementor.
		add_action( 'elementor/elements/categories_registered', array( $this, 'elementor_category' ) );
		add_action( 'elementor/controls/register', array( $this, 'elementor_controls' ) );
		add_action( 'elementor/widgets/register', array( $this, 'elementor_widgets' ) );
		add_action( 'elementor/preview/enqueue_scripts', array( $this, 'enqueue_front' ) );

		// WPBakery Page Builder.
		add_action( 'vc_before_init', array( $this, 'wpbakery' ) );

		// Back-compat: v1 exposed the instance as a global.
		$GLOBALS['PeproMapify'] = $this;
	}

	public function register_assets() {
		$v = MAPIFY_VERSION;
		wp_register_style( 'mapify-front', MAPIFY_ASSETS . 'css/mapify-front.css', array(), $v );
		$accent = sanitize_hex_color( Options::get( 'accent_color' ) );
		$height = preg_replace( '/[^a-z0-9.%]/i', '', (string) Options::get( 'default_height' ) );
		wp_add_inline_style( 'mapify-front', ':where(.mapify){' . ( $accent ? '--mapify-accent:' . $accent . ';' : '' ) . ( $height ? '--mapify-height:' . $height . ';' : '' ) . '}' );
		wp_register_script( 'mapify-front', MAPIFY_ASSETS . 'js/mapify-front.js', array(), $v, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_add_inline_script(
			'mapify-front',
			'window.MapifyGlobals = ' . wp_json_encode(
				array(
					'vendor' => array(
						'leafletJs'  => MAPIFY_ASSETS . 'vendor/leaflet/leaflet.js?ver=1.9.4',
						'leafletCss' => MAPIFY_ASSETS . 'vendor/leaflet/leaflet.css?ver=1.9.4',
						'clusterJs'  => MAPIFY_ASSETS . 'vendor/markercluster/leaflet.markercluster.js?ver=1.5.3',
						'clusterCss' => MAPIFY_ASSETS . 'vendor/markercluster/MarkerCluster.css?ver=1.5.3',
						'gClusterJs' => MAPIFY_ASSETS . 'vendor/gmaps-markerclusterer.min.js?ver=2.6.2',
					),
					'rtl'    => is_rtl(),
				)
			) . ';',
			'before'
		);
	}

	public function enqueue_front() {
		wp_enqueue_style( 'mapify-front' );
		wp_enqueue_script( 'mapify-front' );
	}

	public function elementor_category( $manager ) {
		$manager->add_category(
			'peprodev',
			array(
				'title' => __( 'PeproDev Elements', 'mapify' ),
				'icon'  => 'eicon-map-pin',
			)
		);
	}

	public function elementor_controls( $controls_manager ) {
		require_once MAPIFY_DIR . 'includes/integrations/elementor/class-gallery-control.php';
		$controls_manager->register( new Integrations\Elementor\Gallery_Control() );
	}

	public function elementor_widgets( $widgets_manager ) {
		require_once MAPIFY_DIR . 'includes/integrations/elementor/class-map-widget.php';
		$widgets_manager->register( new Integrations\Elementor\Map_Widget() );
	}

	public function wpbakery() {
		require_once MAPIFY_DIR . 'includes/integrations/wpbakery/class-wpbakery.php';
		Integrations\WPBakery::init();
	}

	public function svg_mime( $mimes ) {
		if ( Options::get( 'allow_svg_upload' ) && current_user_can( 'upload_files' ) && current_user_can( 'unfiltered_html' ) ) {
			$mimes['svg'] = 'image/svg+xml';
		}
		return $mimes;
	}

	/**
	 * Strip scripts, event handlers and external references from uploaded SVG files.
	 */
	public function sanitize_svg_upload( $file ) {
		if ( empty( $file['tmp_name'] ) || 'svg' !== strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) ) ) {
			return $file;
		}
		$svg = file_get_contents( $file['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( false === $svg || false === stripos( $svg, '<svg' ) ) {
			$file['error'] = __( 'This SVG file is not valid.', 'mapify' );
			return $file;
		}
		$clean = self::clean_svg( $svg );
		file_put_contents( $file['tmp_name'], $clean ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		return $file;
	}

	public static function clean_svg( $svg ) {
		$svg = preg_replace( '#<(script|foreignObject|iframe|embed|object)\b[^>]*>.*?</\1>#is', '', $svg );
		$svg = preg_replace( '#<(script|foreignObject|iframe|embed|object)\b[^>]*/?>#is', '', $svg );
		$svg = preg_replace( '#\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)#i', '', $svg );
		$svg = preg_replace( '#(href|xlink:href)\s*=\s*("|\')\s*(javascript|data:text)[^"\']*\2#i', '', $svg );
		return $svg;
	}

	public static function activate() {
		Branches::register();
		flush_rewrite_rules();
	}

	public static function uninstall() {
		if ( ! Options::get( 'clear_on_uninstall' ) ) {
			return;
		}
		delete_option( Options::KEY );
		foreach ( array( 'mapify-googlemapAPI', 'mapify-openstreetAPI', 'mapify-cedarmapsAPI', 'mapify-template', 'mapify-clearunistall', 'mapify-cleardbunistall' ) as $legacy ) {
			delete_option( $legacy );
		}
	}
}
