<?php
/**
 * Admin screens: settings page and shortcode builder (both built with @wordpress/components).
 *
 * @package Mapify
 */

namespace Mapify;

defined( 'ABSPATH' ) || exit;

class Admin {

	const SETTINGS_SLUG = 'mapify';
	const BUILDER_SLUG  = 'mapify-shortcode';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'template_redirect', array( __CLASS__, 'preview' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( MAPIFY_FILE ), array( __CLASS__, 'action_links' ) );
	}

	public static function parent_slug() {
		return 'edit.php?post_type=' . Branches::POST_TYPE;
	}

	public static function menu() {
		add_submenu_page( self::parent_slug(), __( 'Map Settings', 'mapify' ), __( 'Map Settings', 'mapify' ), 'manage_options', self::SETTINGS_SLUG, array( __CLASS__, 'render_root' ) );
		add_submenu_page( self::parent_slug(), __( 'Shortcode Builder', 'mapify' ), __( 'Shortcode Builder', 'mapify' ), 'edit_posts', self::BUILDER_SLUG, array( __CLASS__, 'render_root' ) );
	}

	public static function action_links( $links ) {
		array_unshift(
			$links,
			'<a href="' . esc_url( admin_url( self::parent_slug() . '&page=' . self::SETTINGS_SLUG ) ) . '">' . esc_html__( 'Settings', 'mapify' ) . '</a>',
			'<a href="' . esc_url( admin_url( self::parent_slug() . '&page=' . self::BUILDER_SLUG ) ) . '">' . esc_html__( 'Shortcode Builder', 'mapify' ) . '</a>'
		);
		return $links;
	}

	public static function render_root() {
		echo '<div class="wrap mapify-admin-wrap"><div id="mapify-admin-root" class="mapify-admin"><div class="mapify-admin__boot"><span class="spinner is-active"></span></div></div></div>';
	}

	/**
	 * Schema for JS: resolve dynamic options and add previews.
	 */
	public static function schema_for_js() {
		$fields = array();
		foreach ( Schema::fields() as $key => $field ) {
			$f = $field;
			if ( isset( $field['options'] ) ) {
				$opts         = Schema::options( $field );
				$f['options'] = array();
				foreach ( $opts as $value => $label ) {
					$row = array(
						'value' => (string) $value,
						'label' => $label,
					);
					if ( ! empty( $field['previews'] ) ) {
						$row['image'] = Schema::style_preview( $key, $value );
					}
					$f['options'][] = $row;
				}
			}
			if ( 'repeater' === $field['type'] ) {
				foreach ( $f['fields'] as $sub_key => $sub ) {
					if ( isset( $sub['options'] ) ) {
						$rows = array();
						foreach ( $sub['options'] as $value => $label ) {
							$rows[] = array(
								'value' => (string) $value,
								'label' => $label,
							);
						}
						$f['fields'][ $sub_key ]['options'] = $rows;
					}
				}
			}
			$f['key'] = $key;
			$fields[] = $f;
		}
		$groups = array();
		foreach ( Schema::groups() as $key => $label ) {
			$groups[] = array(
				'key'   => $key,
				'label' => $label,
			);
		}
		return array(
			'fields' => $fields,
			'groups' => $groups,
		);
	}

	public static function assets( $hook ) {
		$page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! in_array( $page, array( self::SETTINGS_SLUG, self::BUILDER_SLUG ), true ) || false === strpos( $hook, $page ) ) {
			return;
		}
		wp_enqueue_style( 'wp-components' );
		wp_enqueue_style( 'mapify-admin', MAPIFY_ASSETS . 'css/admin.css', array( 'wp-components' ), MAPIFY_VERSION );
		wp_enqueue_script( 'mapify-admin', MAPIFY_ASSETS . 'js/admin.js', array( 'wp-element', 'wp-components', 'wp-api-fetch', 'wp-i18n', 'wp-dom-ready' ), MAPIFY_VERSION, true );
		wp_set_script_translations( 'mapify-admin', 'mapify', MAPIFY_DIR . 'languages' );

		$data = array(
			'screen'     => self::SETTINGS_SLUG === $page ? 'settings' : 'builder',
			'version'    => MAPIFY_VERSION,
			'logo'       => MAPIFY_ASSETS . 'img/peprodev.svg',
			'optionKey'  => Options::KEY,
			'settings'   => Options::all(),
			'engines'    => Schema::engines(),
			'canManage'  => current_user_can( 'manage_options' ),
			'links'      => array(
				'settings' => admin_url( self::parent_slug() . '&page=' . self::SETTINGS_SLUG ),
				'builder'  => admin_url( self::parent_slug() . '&page=' . self::BUILDER_SLUG ),
				'branches' => admin_url( self::parent_slug() ),
				'newBranch' => admin_url( 'post-new.php?post_type=' . Branches::POST_TYPE ),
			),
			'builders'   => array(
				'elementor' => defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '',
				'wpbakery'  => defined( 'WPB_VC_VERSION' ) ? WPB_VC_VERSION : '',
			),
			'env'        => array(
				'php' => PHP_VERSION,
				'wp'  => get_bloginfo( 'version' ),
			),
			'branchCount' => (int) wp_count_posts( Branches::POST_TYPE )->publish,
		);
		if ( 'builder' === $data['screen'] ) {
			wp_enqueue_media();
			$data['schema']        = self::schema_for_js();
			$data['defaults']      = Schema::defaults();
			$data['popupTemplate'] = Schema::default_popup_template();
			$data['previewUrl']    = home_url( '/' );
			$data['previewNonce']  = wp_create_nonce( 'mapify_preview' );
			$data['tag']           = Shortcode::TAG;
		}
		wp_add_inline_script( 'mapify-admin', 'window.MapifyAdmin = ' . wp_json_encode( $data ) . ';', 'before' );
	}

	/**
	 * Live preview used by the shortcode builder iframe.
	 */
	public static function preview() {
		if ( empty( $_POST['mapify_preview'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return;
		}
		if ( ! current_user_can( 'edit_posts' ) || ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_wpnonce'] ) ), 'mapify_preview' ) ) {
			wp_die( esc_html__( 'Preview link expired. Reload the builder.', 'mapify' ), 403 );
		}
		$settings = json_decode( wp_unslash( $_POST['settings'] ?? '{}' ), true ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$content  = isset( $_POST['content'] ) ? wp_kses_post( wp_unslash( $_POST['content'] ) ) : '';
		$settings = Schema::normalize( is_array( $settings ) ? $settings : array(), $content );
		show_admin_bar( false );
		$html = Renderer::render( $settings );
		?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<meta name="robots" content="noindex" />
<?php wp_head(); ?>
<style>html,body{margin:0!important;padding:0;background:#fff}body.mapify-preview{padding:24px!important}</style>
</head>
<body class="mapify-preview">
		<?php
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in Renderer.
		wp_footer();
		?>
</body>
</html>
		<?php
		exit;
	}
}
