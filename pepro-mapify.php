<?php
/**
 * Plugin Name:       PeproDev Branches Map (Mapify)
 * Plugin URI:        https://pepro.dev/mapify
 * Description:       Show your branches on Google Maps, OpenStreetMap, Mapbox, Map.ir, custom SVG/image maps or an offline map of Iran. Works as a shortcode, an Elementor widget and a WPBakery Page Builder element.
 * Version:           2.4.1
 * Requires at least: 6.5
 * Tested up to:      7.1
 * Requires PHP:      7.4
 * Author:            Pepro Dev. Group
 * Author URI:        https://pepro.dev/
 * Developer:         Amirhosseinhpv
 * Developer URI:     https://hpv.im/
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       mapify
 * Domain Path:       /languages
 * Elementor tested up to: 4.3.1
 * WPBakery tested up to:  9.0.1
 *
 * @package Mapify
 */

defined( 'ABSPATH' ) || exit;

define( 'MAPIFY_VERSION', '2.4.1' );
define( 'MAPIFY_FILE', __FILE__ );
define( 'MAPIFY_DIR', plugin_dir_path( __FILE__ ) );
define( 'MAPIFY_URL', plugin_dir_url( __FILE__ ) );
define( 'MAPIFY_ASSETS', MAPIFY_URL . 'assets/' );

require_once MAPIFY_DIR . 'includes/class-options.php';
require_once MAPIFY_DIR . 'includes/class-schema.php';
require_once MAPIFY_DIR . 'includes/class-branches.php';
require_once MAPIFY_DIR . 'includes/class-renderer.php';
require_once MAPIFY_DIR . 'includes/class-shortcode.php';
require_once MAPIFY_DIR . 'includes/class-branch-editor.php';
require_once MAPIFY_DIR . 'includes/class-admin.php';
require_once MAPIFY_DIR . 'includes/class-transfer.php';
require_once MAPIFY_DIR . 'includes/class-plugin.php';

register_activation_hook( __FILE__, array( 'Mapify\\Plugin', 'activate' ) );
register_uninstall_hook( __FILE__, array( 'Mapify\\Plugin', 'uninstall' ) );

add_action( 'plugins_loaded', array( 'Mapify\\Plugin', 'instance' ) );
