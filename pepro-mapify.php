<?php
/*
Plugin Name: Pepro Branches Map (Mapify)
Description: List your branches on a beautiful map with clickable hotspots, supporting 70+ Google Maps custom styles, and integrates into WPBakery Page Builder
Contributors: amirhosseinhpv,peprodev
Tags: functionality, map, googlemaps, svg map, show branches on map, pin on map, popup, branch
Author: Pepro Dev. Group
Developer: Amirhosseinhpv
Author URI: https://pepro.dev/
Developer URI: https://hpv.im/
Plugin URI: https://pepro.dev/mapify
Version: 1.3.4
Stable tag: 1.3.4
Requires at least: 5.0
Tested up to: 5.5
Requires PHP: 5.6
Text Domain: mapify
Domain Path: /languages
Copyright: (c) 2020 Pepro Dev. Group, All rights reserved.
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/
defined("ABSPATH") or	die("Pepro Branches Map (Mapify) :: Unauthorized Access!");
if (!class_exists("PeproBranchesMap_AKA_Mapify")){
  class PeproBranchesMap_AKA_Mapify
  {
    private static $_instance = null;
    private $td;
    private $plugin_dir;
    private $plugin_url;
    private $assets_url;
    private $plugin_basename;
    private $plugin_file;
    private $version;
    private $db_slug;
    private $title;
    private $title_w;
    private $db_table = null;
    private $manage_links = array();
    private $meta_links = array();
    public function __construct()
    {
      global $wpdb;
      $this->td = "mapify";
      self::$_instance = $this;
      $this->plugin_dir = plugin_dir_path(__FILE__);
      $this->plugin_url = plugins_url("", __FILE__);
      $this->assets_url = plugins_url("/assets/", __FILE__);
      $this->plugin_basename = plugin_basename(__FILE__);
      $this->plugin_file = __FILE__;
      $this->version = "1.3.4";
      $this->db_slug = $this->td;
      $this->db_table = $wpdb->prefix . $this->db_slug;
      $this->deactivateURI = null;
      $this->deactivateICON = '<style>.dashicons-small::before { font-size: 1rem !important; box-shadow: none !important; background-color: transparent !important; color: inherit !important; }</style><span style="font-size: larger; line-height: 1rem; display: inline; vertical-align: text-top;" class="dashicons dashicons-dismiss" aria-hidden="true"></span> ';
      $this->versionICON = '<span style="font-size: larger; line-height: 1rem; display: inline; vertical-align: text-top;" class="dashicons dashicons-admin-plugins" aria-hidden="true"></span> ';
      $this->authorICON = '<span style="font-size: larger; line-height: 1rem; display: inline; vertical-align: text-top;" class="dashicons dashicons-admin-users" aria-hidden="true"></span> ';
      $this->settingURL = '<span style="display: inline;float: none;padding: 0;" class="dashicons dashicons-admin-settings dashicons-small" aria-hidden="true"></span> ';
      $this->submitionURL = '<span style="display: inline;float: none;padding: 0;" class="dashicons dashicons-images-alt dashicons-small" aria-hidden="true"></span> ';
      $this->url = admin_url("admin.php?page={$this->db_slug}");
      $this->title = __("Pepro Mapify", $this->td);
      $this->title_w = sprintf(__("%2\$s ver. %1\$s", $this->td), $this->version, $this->title);
      add_action("init", array($this, 'init_plugin'));
    }
    public function init_plugin()
    {
        if ($this->_vc_activated()){
          add_action("vc_before_init", array($this,"integrate_visual_composer"));
          if ( function_exists('vc_add_shortcode_param')){
              $jsfile = "{$this->assets_url}js/vc.init.js?" . current_time("timestamp"); // we've added timestamp to end of file url to prevent caching
              vc_add_shortcode_param( "pepro_about", array($this,"vc_add_pepro_about"), $jsfile);
              vc_add_shortcode_param( "dropdown_multi", array($this,"vc_add_pepro_dropdown_multi") );
              vc_add_shortcode_param( "radio_image", array($this,"vc_add_pepro_radio_image") );
              vc_add_shortcode_param( "pinmarkermaker", "__return_empty_string", "{$this->assets_url}js/pin.maker.js?" . current_time("timestamp"));
          }
        }
        add_filter("plugin_action_links_{$this->plugin_basename}", array($this, 'plugins_row_links'));
        add_action("plugin_row_meta", array( $this, 'plugin_row_meta' ), 10, 2);
        add_action("admin_menu", array($this, 'admin_menu'));
        add_action("admin_init", array($this, 'admin_init'));
        add_action("admin_enqueue_scripts", array($this, 'admin_enqueue_scripts'));
        add_action("admin_print_footer_scripts", array($this, 'admin_print_footer_scripts'));
        add_shortcode("pepro-mapify", array($this,'mapify_shortcode'));
        $this->add_mapify_cpt();
        add_filter("the_content", array($this,"mapify_branch_template"));
        include_once "$this->plugin_dir/assets/app/metabx.php";
      }
    public function mapify_branch_template($content)
    {
        global $post;
        if ( is_singular() && in_the_loop() && is_main_query() && "mapify" == $post->post_type){
          $contentTemplate = get_post_meta(get_the_ID() ,"place_details_content_template", true);
          if (( "content" == $contentTemplate) || ("default" == $contentTemplate && "content" == get_option("{$this->db_slug}-template","post"))){
            return apply_filters( "mapify-branches-single-post-template", $this->get_branches_template(), $post, get_the_ID() );
          }
        }
        return $content;
    }
    public function get_branches_template()
    {
      ob_start();
      $post_data = extract(array(
        'title'       => get_the_title(get_the_ID()),
        'image'       => get_the_post_thumbnail_url(get_the_ID(),'thumbnail') || false,
        'imagel'       => get_the_post_thumbnail_url(get_the_ID(),'large') || false,
        'address'     => get_post_meta( get_the_ID(), "place_details_address", true ),
        'phone'       => get_post_meta( get_the_ID(), "place_details_phone", true ),
        'site'        => get_post_meta( get_the_ID(), "place_details_site", true ),
        'email'       => get_post_meta( get_the_ID(), "place_details_email", true ),
        'socailtw'    => get_post_meta( get_the_ID(), "place_details_socailtw", true ),
        'socailfb'    => get_post_meta( get_the_ID(), "place_details_socailfb", true ),
        'socailig'    => get_post_meta( get_the_ID(), "place_details_socailig", true ),
        'socailtg'    => get_post_meta( get_the_ID(), "place_details_socailtg", true ),
        'socailli'    => get_post_meta( get_the_ID(), "place_details_socailli", true ),
        'additional'  => get_post_meta( get_the_ID(), "place_details_additional", true ),
        'map_data'    => get_post_meta( get_the_ID(), "place_details_map_data", true ),
        'featured_img_url' => get_the_post_thumbnail_url(get_the_ID(),'full'),
      ));
      $social = "<i class='fas fa-users icon-social'></i> <span class='label social'>" . __("On Social: ",$this->td) . "</span>";
      $social .= empty($socailtw) ?: "<a href='$socailtw' title='".esc_attr__("Twitter",$this->td)."'><i class='icon-social fab fa-twitter'></i></a> ";
      $social .= empty($socailfb) ?: "<a href='$socailfb' title='".esc_attr__("Facebook",$this->td)."'><i class='icon-social fab fa-facebook'></i></a> ";
      $social .= empty($socailig) ?: "<a href='$socailig' title='".esc_attr__("Instagram",$this->td)."'><i class='icon-social fab fa-instagram'></i></a> ";
      $social .= empty($socailtg) ?: "<a href='$socailtg' title='".esc_attr__("Telegram",$this->td)."'><i class='icon-social fab fa-telegram'></i></a> ";
      $social .= empty($socailli) ?: "<a href='$socailli' title='".esc_attr__("LinkedIn",$this->td)."'><i class='icon-social fab fa-linkedin'></i></a> ";

      wp_enqueue_style( "fontawesome", "//use.fontawesome.com/releases/v5.2.0/css/all.css" );
      wp_enqueue_style( "branches-cpt", "{$this->assets_url}css/branches-single.css");

      $map_data = json_decode( $map_data);
      $latitude = $map_data->latitude;
      $longitude = $map_data->longitude;

      $getdirection = "<i class='fas fa-directions icon-direction'></i> <span class='label direction'>" . __("Get Direction: ",$this->td) . "</span>
      <a class='mapify-btn-find-route google_map' href='https://www.google.com/maps/place/$latitude,$longitude/@$latitude,$longitude,12.0z'><i class='fas fa-map-marker-alt icon-direction'></i> ".__("Google Map",$this->td)."</a>
      <a class='mapify-btn-find-route waze' href='https://www.waze.com/ul?ll=$latitude,$longitude&navigate=yes&zoom=12'><i class='fas fa-route icon-direction'></i> ".__("Waze",$this->td)."</a>
      ";
      echo apply_filters( "pepro-mapify-branchestemplate_return", '
        <div class="mapfiy-branch-details-container-parent">
          <div class="mapfiy-branch-details-container">
            <div class="mapfiy-branch-detail-item-container image">
            <a href="'.esc_url($featured_img_url).'" rel="lightbox">'.get_the_post_thumbnail( null, 'medium', '' ).'</a>
          </div>
          <div class="mapfiy-branch-detail-item-container details">
          <div class="mapify-title"><h2>'.$title.'</h2></div>
          <div class="mapify-address"><i class="fas fa-map icon-map"></i> ' . __("Address: ",$this->td) . $address."<br/>".$getdirection.'</div>
          <div class="mapify-contact">
          <div class="mapify-phone">'.(!empty($phone)?"<i class='icon-contact fas fa-phone'></i> <span class='label phone'>".__("Phone: ",$this->td)."</span><a href='tel:$phone'>$phone</a>":"").'</div>
          <div class="mapify-site">'.(!empty($site)?"<i class='icon-contact fas fa-globe'></i> <span class='label site'>".__("Site: ",$this->td)."</span><a href='$site'>$site</a>":"").'</div>
          <div class="mapify-email">'.(!empty($email)?"<i class='icon-contact fas fa-envelope'></i> <span class='label email'>".__("Email: ",$this->td)."</span><a href='mailto:$email'>$email</a>":"").'</div>
          </div>
          <div class="mapify-social">'.(!empty($email)?$social:"").'</div>
          </div>
          </div>
          <div class="mapfiy-branch-detail-item-container extras">
            <div class="mapify-extras">'.$additional.'</div>
          </div>
        </div>
        ');

      $tcona = ob_get_contents();
      ob_end_clean();
      return $tcona;
    }
    public function integrate_visual_composer()
    {
      wp_enqueue_style(   "chosen", "{$this->assets_url}/css/chosen.min.css");
      wp_enqueue_script(  "chosen", "{$this->assets_url}/js/chosen.min.js", array("jquery"));
      wp_register_script( "markermaker", "{$this->assets_url}/js/pin.maker.js", array("jquery"));
      wp_localize_script( "markermaker", "markermaker", array(
        "vc_pinmarkermaker_dirfolder" => "{$this->assets_url}img/markers",
        "vc_pinmarkermaker_clipboard" => __("Click To Set as your Overwritten Pin image", $this->td),
        "vc_pinmarkermaker_numbers"   => __("Numbers", $this->td),
        "vc_pinmarkermaker_character" => __("Character", $this->td),
        "vc_pinmarkermaker_symbols"   => __("Symbols", $this->td),
        "default_template"   => $this->get_default_popup_markup(),
      ) );
      wp_enqueue_script( "markermaker");

      $peproMapifyMaptypes = apply_filters( "pepro-mapify-vc-maptypes",array(
          esc_html__("Google Maps", $this->td)   => 'googlemap'  ,
          // esc_html__("OpenStreet", $this->td)  => 'openstreet' ,
          // esc_html__("CedarMaps", $this->td)   => 'cedarmaps'  ,
        ));
      $list = get_posts(apply_filters( "pepro-mapify-vc-brancheslist-opt",array(
          'numberposts'      => -1,
          'orderby'          => 'date',
          'order'            => 'DESC',
          'post_type'        => 'mapify',)));
      $lists = array();foreach ($list as $key) { $lists[$key->post_title] = $key->ID;}
      $peproMapifyMapIDs = apply_filters( "pepro-mapify-vc-branches-ids",$lists);
      $categories = get_categories(apply_filters( "pepro-mapify-vc-branchescats-opt",
        array(
          'taxonomy'    => 'mapify_category',
          'orderby'     => 'name',
          'order'       => 'ASC',
          'hide_empty'  => false,)));
      $lists = array();foreach ($categories as $cat) { $lists[esc_html( $cat->name )] = $cat->slug; }
      $peproMapifyMapCategories = apply_filters( "pepro-mapify-vc-branches-cats",$lists);
      $lists = array(
        esc_html_x( "Default", "googl-map-style-name", $this->td )                => "default",
        esc_html_x( "Custom Style", "googl-map-style-name", $this->td )           => "custom",
        esc_html_x( "Midnight", "googl-map-style-name", $this->td )               => "midnight",
        esc_html_x( "Desert", "googl-map-style-name", $this->td )                 => "desert",
        esc_html_x( "Bright Colors", "googl-map-style-name", $this->td )          => "bright",
        esc_html_x( "Ultra Light", "googl-map-style-name", $this->td )            => "ulight",
        esc_html_x( "Assassin's Creed IV", "googl-map-style-name", $this->td )    => "accriv",
        esc_html_x( "Grass is greener", "googl-map-style-name", $this->td)        => "grass-is-greener",
        esc_html_x( "Sin City", "googl-map-style-name", $this->td)                => "sin-city",
        esc_html_x( "The Propia Effect", "googl-map-style-name", $this->td)       => "the-propia-effect",
        esc_html_x( "Snazzy Maps", "googl-map-style-name", $this->td)             => "snazzy-maps",
        esc_html_x( "Light Green", "googl-map-style-name", $this->td)             => "light-green",
        esc_html_x( "Flat green", "googl-map-style-name", $this->td)              => "flat-green",
        esc_html_x( "Dark Electric", "googl-map-style-name", $this->td)           => "dark-electric",
        esc_html_x( "Two Tone", "googl-map-style-name", $this->td)                => "two-tone",
        esc_html_x( "Modest", "googl-map-style-name", $this->td)                  => "modest",
        esc_html_x( "Flat Colors", "googl-map-style-name", $this->td)             => "flat-colors",
        esc_html_x( "Red Alert", "googl-map-style-name", $this->td)               => "red-alert",
        esc_html_x( "Creamy Red", "googl-map-style-name", $this->td)              => "creamy-red",
        esc_html_x( "Light and dark", "googl-map-style-name", $this->td)          => "light-and-dark",
        esc_html_x( "Uber 2017", "googl-map-style-name", $this->td)               => "uber-2017",
        esc_html_x( "Hints of Gold", "googl-map-style-name", $this->td)           => "hints-of-gold",
        esc_html_x( "Transport for London", "googl-map-style-name", $this->td)    => "transport-for-london",
        esc_html_x( "Old Dry Mud", "googl-map-style-name", $this->td)             => "old-dry-mud",
        esc_html_x( "Neon World", "googl-map-style-name", $this->td)              => "neon-world",
        esc_html_x( "Printable Map", "googl-map-style-name", $this->td)           => "printable-map",
        esc_html_x( "Captor", "googl-map-style-name", $this->td)                  => "captor",
        esc_html_x( "Zombie Survival Map", "googl-map-style-name", $this->td)     => "zombie-survival-map",
        esc_html_x( "Wyborcza 2018", "googl-map-style-name", $this->td)           => "wyborcza-2018",
        esc_html_x( "Hot Pink", "googl-map-style-name", $this->td)                => "hot-pink",
        esc_html_x( "Dark Yellow", "googl-map-style-name", $this->td)             => "dark-yellow",
        esc_html_x( "Light Blue Water", "googl-map-style-name", $this->td)        => "light-blue-water",
        esc_html_x( "Chilled", "googl-map-style-name", $this->td)                 => "chilled",
        esc_html_x( "Purple", "googl-map-style-name", $this->td)                  => "purple",
        esc_html_x( "Night vision", "googl-map-style-name", $this->td)            => "night-vision",
        esc_html_x( "50 shades of blue", "googl-map-style-name", $this->td)       => "50-shades-of-blue",
        esc_html_x( "Carte Vierge", "googl-map-style-name", $this->td)            => "carte-vierge",
        esc_html_x( "Simplified Map", "googl-map-style-name", $this->td)          => "simplified-map",
        esc_html_x( "Inturlam Style 2", "googl-map-style-name", $this->td)        => "inturlam-style-2",
        esc_html_x( "Esperanto", "googl-map-style-name", $this->td)               => "esperanto",
        esc_html_x( "Nothing but roads", "googl-map-style-name", $this->td)       => "nothing-but-roads",
        esc_html_x( "Veins", "googl-map-style-name", $this->td)                   => "veins",
        esc_html_x( "Blueprint", "googl-map-style-name", $this->td)               => "blueprint",
        esc_html_x( "PipBoy Maps", "googl-map-style-name", $this->td)             => "pipboy-maps",
        esc_html_x( "Tinia", "googl-map-style-name", $this->td)                   => "tinia",
        esc_html_x( "BehanceHK", "googl-map-style-name", $this->td)               => "behancehk",
        esc_html_x( "St. Martin", "googl-map-style-name", $this->td)              => "st-martin",
        esc_html_x( "AutoMax", "googl-map-style-name", $this->td)                 => "automax",
        esc_html_x( "Colorblind-friendly", "googl-map-style-name", $this->td)     => "colorblind-friendly",
        esc_html_x( "NightRider", "googl-map-style-name", $this->td)              => "nightrider",
        esc_html_x( "HCRE", "googl-map-style-name", $this->td)                    => "hcre",
        esc_html_x( "Celestial Blue", "googl-map-style-name", $this->td)          => "celestial-blue",
        esc_html_x( "Best Ski Pros", "googl-map-style-name", $this->td)           => "best-ski-pros",
        esc_html_x( "Pokemon Go", "googl-map-style-name", $this->td)              => "pokemon-go",
        esc_html_x( "Vintage Brown", "googl-map-style-name", $this->td)           => "vintage-old-golden-brown",
        esc_html_x( "Apple Maps", "googl-map-style-name", $this->td)              => "apple-maps-esque",
        esc_html_x( "Unsaturated Browns", "googl-map-style-name", $this->td)      => "unsaturated-browns",
        esc_html_x( "Flat Map", "googl-map-style-name", $this->td)                => "flat-map",
        esc_html_x( "Multi Brand Net.", "googl-map-style-name", $this->td)        => "multi-brand-network",
        esc_html_x( "Retro", "googl-map-style-name", $this->td)                   => "retro",
        esc_html_x( "Muted Blue", "googl-map-style-name", $this->td)              => "muted-blue",
        esc_html_x( "Neutral Blue", "googl-map-style-name", $this->td)            => "neutral-blue",
        esc_html_x( "Black & white", "googl-map-style-name", $this->td)           => "black-and-white-without-labels",
        esc_html_x( "Icy Blue", "googl-map-style-name", $this->td)                => "icy-blue",
        esc_html_x( "Hopper", "googl-map-style-name", $this->td)                  => "hopper",
        esc_html_x( "Cobalt", "googl-map-style-name", $this->td)                  => "cobalt",
        esc_html_x( "Night Vision", "googl-map-style-name", $this->td)            => "night-visions",
        esc_html_x( "Red Hues", "googl-map-style-name", $this->td)                => "red-hues",
        esc_html_x( "Roads only", "googl-map-style-name", $this->td)              => "roads-only",
        esc_html_x( "Flat Map with Labels", "googl-map-style-name", $this->td)    => "flat-map-with-labels",
        esc_html_x( "Mondrian", "googl-map-style-name", $this->td)                => "mondrian",
        esc_html_x( "Bright & Bubbly", "googl-map-style-name", $this->td)         => "bright-and-bubbly",
        esc_html_x( "Shades of Grey", "googl-map-style-name", $this->td)          => "shades-of-grey",
      );
      $googlemapDesignsArray = apply_filters( "pepro-mapify-vc-googlemap-styles",$lists);
      $lists = array(
        "default" => "{$this->assets_url}img/map-style/gmapdefault.jpg",
        "custom" => "{$this->assets_url}img/map-style/gmapcustom.jpg",
        "midnight" => "{$this->assets_url}img/map-style/gmapmidnight.jpg",
        "desert" => "{$this->assets_url}img/map-style/gmapdesert.jpg",
        "bright" => "{$this->assets_url}img/map-style/gmapbright.jpg",
        "ulight" => "{$this->assets_url}img/map-style/gmapulight.jpg",
        "accriv" => "{$this->assets_url}img/map-style/gmapassassincreediv.jpg",
        "grass-is-greener" => "{$this->assets_url}img/map-style/grass-is-greener.jpg",
        "sin-city" => "{$this->assets_url}img/map-style/sin-city.jpg",
        "the-propia-effect" => "{$this->assets_url}img/map-style/the-propia-effect.jpg",
        "snazzy-maps" => "{$this->assets_url}img/map-style/snazzy-maps.jpg",
        "light-green" => "{$this->assets_url}img/map-style/light-green.jpg",
        "flat-green" => "{$this->assets_url}img/map-style/flat-green.jpg",
        "dark-electric" => "{$this->assets_url}img/map-style/dark-electric.jpg",
        "two-tone" => "{$this->assets_url}img/map-style/two-tone.jpg",
        "modest" => "{$this->assets_url}img/map-style/modest.jpg",
        "flat-colors" => "{$this->assets_url}img/map-style/flat-colors.jpg",
        "red-alert" => "{$this->assets_url}img/map-style/red-alert.jpg",
        "creamy-red" => "{$this->assets_url}img/map-style/creamy-red.jpg",
        "light-and-dark" => "{$this->assets_url}img/map-style/light-and-dark.jpg",
        "uber-2017" => "{$this->assets_url}img/map-style/uber-2017.jpg",
        "hints-of-gold" => "{$this->assets_url}img/map-style/hints-of-gold.jpg",
        "transport-for-london" => "{$this->assets_url}img/map-style/transport-for-london.jpg",
        "old-dry-mud" => "{$this->assets_url}img/map-style/old-dry-mud.jpg",
        "neon-world" => "{$this->assets_url}img/map-style/neon-world.jpg",
        "printable-map" => "{$this->assets_url}img/map-style/printable-map.jpg",
        "captor" => "{$this->assets_url}img/map-style/captor.jpg",
        "zombie-survival-map" => "{$this->assets_url}img/map-style/zombie-survival-map.jpg",
        "wyborcza-2018" => "{$this->assets_url}img/map-style/wyborcza-2018.jpg",
        "hot-pink" => "{$this->assets_url}img/map-style/hot-pink.jpg",
        "dark-yellow" => "{$this->assets_url}img/map-style/dark-yellow.jpg",
        "light-blue-water" => "{$this->assets_url}img/map-style/light-blue-water.jpg",
        "chilled" => "{$this->assets_url}img/map-style/chilled.jpg",
        "purple" => "{$this->assets_url}img/map-style/purple.jpg",
        "night-vision" => "{$this->assets_url}img/map-style/night-vision.jpg",
        "50-shades-of-blue" => "{$this->assets_url}img/map-style/50-shades-of-blue.jpg",
        "carte-vierge" => "{$this->assets_url}img/map-style/carte-vierge.jpg",
        "simplified-map" => "{$this->assets_url}img/map-style/simplified-map.jpg",
        "inturlam-style-2" => "{$this->assets_url}img/map-style/inturlam-style-2.jpg",
        "esperanto" => "{$this->assets_url}img/map-style/esperanto.jpg",
        "nothing-but-roads" => "{$this->assets_url}img/map-style/nothing-but-roads.jpg",
        "veins" => "{$this->assets_url}img/map-style/veins.jpg",
        "blueprint" => "{$this->assets_url}img/map-style/blueprint.jpg",
        "pipboy-maps" => "{$this->assets_url}img/map-style/pipboy-maps.jpg",
        "tinia" => "{$this->assets_url}img/map-style/tinia.jpg",
        "behancehk" => "{$this->assets_url}img/map-style/behancehk.jpg",
        "st-martin" => "{$this->assets_url}img/map-style/st-martin.jpg",
        "automax" => "{$this->assets_url}img/map-style/automax.jpg",
        "colorblind-friendly" => "{$this->assets_url}img/map-style/colorblind-friendly.jpg",
        "nightrider" => "{$this->assets_url}img/map-style/nightrider.jpg",
        "hcre" => "{$this->assets_url}img/map-style/hcre.jpg",
        "celestial-blue" => "{$this->assets_url}img/map-style/celestial-blue.jpg",
        "best-ski-pros" => "{$this->assets_url}img/map-style/best-ski-pros.jpg",
        "pokemon-go" => "{$this->assets_url}img/map-style/pokemon-go.jpg",
        "vintage-old-golden-brown" => "{$this->assets_url}img/map-style/vintage-old-golden-brown.jpg",
        "apple-maps-esque" => "{$this->assets_url}img/map-style/apple-maps-esque.jpg",
        "unsaturated-browns" => "{$this->assets_url}img/map-style/unsaturated-browns.jpg",
        "flat-map" => "{$this->assets_url}img/map-style/flat-map.jpg",
        "multi-brand-network" => "{$this->assets_url}img/map-style/multi-brand-network.jpg",
        "retro" => "{$this->assets_url}img/map-style/retro.jpg",
        "muted-blue" => "{$this->assets_url}img/map-style/muted-blue.jpg",
        "neutral-blue" => "{$this->assets_url}img/map-style/neutral-blue.jpg",
        "black-and-white-without-labels" => "{$this->assets_url}img/map-style/black-and-white-without-labels.jpg",
        "icy-blue" => "{$this->assets_url}img/map-style/icy-blue.jpg",
        "hopper" => "{$this->assets_url}img/map-style/hopper.jpg",
        "cobalt" => "{$this->assets_url}img/map-style/cobalt.jpg",
        "night-visions" => "{$this->assets_url}img/map-style/night-visions.jpg",
        "red-hues" => "{$this->assets_url}img/map-style/red-hues.jpg",
        "roads-only" => "{$this->assets_url}img/map-style/roads-only.jpg",
        "flat-map-with-labels" => "{$this->assets_url}img/map-style/flat-map-with-labels.jpg",
        "mondrian" => "{$this->assets_url}img/map-style/mondrian.jpg",
        "bright-and-bubbly" => "{$this->assets_url}img/map-style/bright-and-bubbly.jpg",
        "shades-of-grey" => "{$this->assets_url}img/map-style/shades-of-grey.jpg",
      );
      $googlemapDesignsPicArray = apply_filters( "pepro-mapify-vc-googlemap-styles-pics",$lists);

      vc_map(
          array(
              "base" => "pepro-mapify",
              "name" => esc_html__("Mapify", $this->td),
              "category" => esc_html__("Pepro Elements", "$this->td"),
              "description" => esc_html__("List your branches on a beautiful svg map.", $this->td ),
              "class" => "{$this->td}__class",
              "icon" => "{$this->assets_url}img/peprodev.svg",
              "show_settings_on_create" => true,
              "admin_enqueue_css" => array("{$this->assets_url}/css/vc.init.css"),
              "admin_enqueue_js" => array(),
              "params" => array(
                array(
                  "group"               => esc_html_x("General","vc-tab", "$this->td" ),
                  "heading"             => esc_html__("Select Branches", $this->td),
                  "description"         => esc_html__("Select Branches to show on the map", $this->td),
                  "type"                => "dropdown",
                  "param_name"          => "branchtype",
                  "edit_field_class"    => "vc_column vc_col-sm-6",
                  "admin_label"         => false,
                  "save_always"         => true,
                  "std"                 => "cat",
                  "value"               => array(
                    esc_html__( "Select Branches by ID", $this->td ) => "id",
                    esc_html__( "Select Branches by Category", $this->td ) => "cat",
                  ),
                ),
                array(
                  "group"               => esc_html_x("General","vc-tab", "$this->td" ),
                  "heading"             => esc_html__("Handpick Branches ID", $this->td),
                  "param_name"          => "branchids",
                  "type"                => "dropdown_multi",
                  "class"               => "chosen-select",
                  "edit_field_class"    => "vc_column vc_col-sm-6",
                  "placeholder"         => esc_html__("Select Categories", $this->td),
                  "admin_label"         => false,
                  "description"         => esc_html__("Handpick branches to show on map", $this->td),
                  "value"               => $peproMapifyMapIDs,
                  'dependency'          => array( 'element' => 'branchtype', 'value' => array( 'id' ), ),
                ),
                array(
                  "group"               => esc_html_x("General","vc-tab", "$this->td" ),
                  "heading"             => esc_html__("Select Branches Category", $this->td),
                  "param_name"          => "branchcat",
                  "type"                => "dropdown_multi",
                  "edit_field_class"    => "vc_column vc_col-sm-6",
                  "class"               => "chosen-select",
                  "placeholder"         => esc_html__("Select Categories", $this->td),
                  "admin_label"         => false,
                  "description"         => esc_html__("Select branch categories to show on map", $this->td),
                  "value"               => $peproMapifyMapCategories,
                  'dependency'          => array( 'element' => 'branchtype', 'value' => array( 'cat' ), ),
                ),
                array(
                  "group"               => esc_html_x("Design","vc-tab", "$this->td" ),
                  "heading"             => esc_html__("Map Engine", $this->td),
                  "type"                => "dropdown",
                  "edit_field_class"    => "vc_column vc_col-sm-12",
                  "param_name"          => "maptype",
                  "save_always"         => true,
                  "admin_label"         => false,
                  "description"         => esc_html__("Select your desired map type", $this->td),
                  "std"                 => apply_filters( "pepro-mapify-maptypes-default","googlemap"),
                  "value"               => $peproMapifyMaptypes,
                ),
                array(
                  "group"               => esc_html_x("Branches List","vc-tab", "$this->td" ),
                  "heading"             => esc_html__("Show Branches List?", $this->td),
                  "type"                => "checkbox",
                  "param_name"          => "branchlistshow",
                  "edit_field_class"    => "vc_column vc_col-sm-6",
                  "admin_label"         => false,
                ),
                array(
                  "group"               => esc_html_x("Branches List","vc-tab", "$this->td" ),
                  "heading"             => esc_html__("Show Branches Search?", $this->td),
                  "type"                => "checkbox",
                  "edit_field_class"    => "vc_column vc_col-sm-6",
                  "param_name"          => "branchessearch",
                  "admin_label"         => false,
                  'dependency'          => array( 'element' => 'branchlistshow', 'value' => array( 'true' ), ),
                ),
                array(
                  "group"               => esc_html_x("Branches List","vc-tab", "$this->td" ),
                  "heading"             => esc_html__("Branches List placement", $this->td),
                  "type"                => "dropdown",
                  "edit_field_class"    => "vc_column vc_col-sm-6",
                  "param_name"          => "branchplacement",
                  "admin_label"         => false,
                  "std"                 => "top",
                  "value"               => array(
                    esc_html__( "Show at Top of Map", $this->td )    => "top"    ,
                    esc_html__( "Show at Bottom of Map", $this->td ) => "bottom" ,
                  ),
                  'dependency'          => array( 'element' => 'branchlistshow', 'value' => array( 'true' ), ),
                ),
                array(
                  "group"               => esc_html_x("Branches List","vc-tab", "$this->td" ),
                  "heading"             => esc_html__("Categorize Branches In List", $this->td),
                  "type"                => "dropdown",
                  "edit_field_class"    => "vc_column vc_col-sm-6",
                  "param_name"          => "brancheslistcat",
                  "admin_label"         => false,
                  "std"                 => "none",
                  "value"               => array(
                    esc_html__( "Do not categorize branches in list", $this->td )             => "none"    ,
                    esc_html__( "Categorize based on Branches Categories", $this->td )        => "category" ,
                    // esc_html__( "Categorize based on Branches Country", $this->td )           => "country" ,
                    // esc_html__( "Categorize based on Branches Provinces/States", $this->td )  => "states" ,
                  ),
                  'dependency'          => array( 'element' => 'branchlistshow', 'value' => array( 'true' ), ),
                ),
                array(
                  "group"               => esc_html_x("Marker Clusters","vc-tab", "$this->td" ),
                  "heading"             => esc_html__("Cluster Branches Marker Pins on Map?", $this->td),
                  "description"         => esc_html__("Create per-zoom-level clusters for large amounts of markers", $this->td ),
                  "type"                => "checkbox",
                  "param_name"          => "branchascluster",
                  "edit_field_class"    => "vc_column vc_col-sm-4",
                  "std"                 => "true",
                  "admin_label"         => false,
                ),
                array(
                  "group"               => esc_html_x("Marker Clusters","vc-tab", "$this->td" ),
                  "heading"             => esc_html__("Clusters Grid Size", $this->td),
                  "type"                => "textfield",
                  "param_name"          => "clustergridsize",
                  "edit_field_class"    => "vc_column vc_col-sm-4",
                  "std"                 => "100",
                  "admin_label"         => false,
                  "description"         => esc_html__( "The grid size of a cluster in pixels", $this->td ),
                  'dependency'          => array( 'element' => 'branchascluster', 'value' => array( 'true' ), ),
                ),
                array(
                  "group"               => esc_html_x("Marker Clusters","vc-tab", "$this->td" ),
                  "heading"             => esc_html__("Clusters Minimum Size", $this->td),
                  "type"                => "textfield",
                  "param_name"          => "clusterminsize",
                  "edit_field_class"    => "vc_column vc_col-sm-4",
                  "std"                 => "2",
                  "description"         => esc_html__( "The maximum number of markers can be part of a cluster", $this->td ),
                  "admin_label"         => false,
                  'dependency'          => array( 'element' => 'branchascluster', 'value' => array( 'true' ), ),
                ),
                array(
                  "group"               => esc_html_x("Marker Pin","vc-tab", "$this->td" ),
                  "heading"             => esc_html__("Overwrite Marker Pin Images", $this->td),
                  "type"                => "textfield",
                  "param_name"          => "pinimage",
                  "description"         => esc_html__( "Enter URL or use form below to generate your Pin image. This image will be overwritten to all branches marker", $this->td ),
                  "value"               => "",
                  "admin_label"         => false,
                  "edit_field_class"    => "vc_column vc_col-sm-12",
                  "holder"              => "div",
                ),
                array(
                  "group"               => esc_html_x("Marker Pin","vc-tab", "$this->td" ),
                  "heading"             => esc_html__("Marker Pin Image Generator", $this->td),
                  "type"                => "pinmarkermaker",
                  "param_name"          => "pinimagehelper",
                  "edit_field_class"    => "vc_column vc_col-sm-12 peprovc_pinimagegenerator",
                  "admin_label"         => false,
                ),
                array(
                  "group"               => esc_html_x("Marker Pin","vc-tab", "$this->td" ),
                  "heading"             => esc_html__("Marker Pin Click Action", $this->td),
                  "type"                => "dropdown",
                  "param_name"          => "pinaction",
                  "save_always"         => true,
                  "admin_label"         => false,
                  "edit_field_class"    => "vc_column vc_col-sm-12",
                  "std"                 => "url",
                  "value"               => array(
                    esc_html__( "Open URL", $this->td )     => "url"  ,
                    esc_html__( "Open Popup", $this->td )   => "popup"   ,
                    esc_html__( "Do Nothing", $this->td )   => "null"    ,
                  ),
                ),
                array(
                  "group"               => esc_html_x("Marker Pin","vc-tab", "$this->td" ),
                  "heading"             => esc_html__("URL Target", $this->td),
                  "type"                => "dropdown",
                  "param_name"          => "pinurltarget",
                  "edit_field_class"    => "vc_column vc_col-sm-12",
                  "save_always"         => true,
                  "admin_label"         => false,
                  "std"                 => "_blank",
                  "value"               => array(
                    esc_html__( 'Same window', 'js_composer' ) => '_self',
                    esc_html__( 'New window', 'js_composer' ) => '_blank',
                  ),
                  'dependency'          => array( 'element' => 'pinaction', 'value' => array( 'url' ), ),
                ),
                array(
                    "group"             => esc_html_x("Marker Pin", "vc-tab", "$this->td" ),
                    "heading"           => esc_html__("Marker Pin Popup Template (HTML)", "$this->td" ),
                    "edit_field_class"  => "vc_column vc_col-sm-12",
                    "type"              => "textarea_raw_html",
                    "holder"            => "div",
                    "save_always"       => true,
                    "admin_label"       => false,
                    "param_name"        => "popup_markup",
                    "value"             => base64_encode($this->get_default_popup_markup()),
                    "description"       => "<a id='mapify_load_default_markup' href='#'>" . __("Load Default Template", $this->td) . "</a><p class='mapify_markup_guide_container'>" . $this->get_default_popup_markup_help() . "</p>",
                    'dependency'        => array( 'element' => 'pinaction', 'value' => array( 'popup' ), ),
                ),
                array(
                    "group"             => esc_html_x("Design","vc-tab", "$this->td" ),
                    "heading"           => esc_html__("Default Center Coordinate", "$this->td" ),
                    "type"              => "textfield",
                    "class"             => "",
                    "edit_field_class"  => "vc_column vc_col-sm-6",
                    "holder"            => "div",
                    "admin_label"       => false,
                    "param_name"        => "center_coordinate",
                    "value"             => "32.1001646,54.4637493",
                    "description"       => __("Enter Default Center Coordinate in <i>latitude,longitude</i> format", $this->td),
                ),
                array(
                    "group"             => esc_html_x("Design","vc-tab", "$this->td" ),
                    "heading"           => esc_html__("Default Zoom Level", "$this->td" ),
                    "type"              => "textfield",
                    "class"             => "",
                    "edit_field_class"  => "vc_column vc_col-sm-6",
                    "holder"            => "div",
                    "admin_label"       => false,
                    "param_name"        => "default_zoom",
                    "value"             => "5",

                ),
                array(
                    "group"             => esc_html_x("Design","vc-tab", "$this->td" ),
                    "heading"           => esc_html__("Default Loading Image", "$this->td" ),
                    "type"              => "textfield",
                    "edit_field_class"  => "vc_column vc_col-sm-6",
                    "holder"            => "div",
                    "admin_label"       => false,
                    "param_name"        => "loading_image",
                    "value"             => "{$this->assets_url}img/peprodev.svg",
                    "dependency"        => array( 'element' => 'maptype', 'value' => array( 'googlemap' ), ),
                ),
                array(
                    "group"             => esc_html_x("Design", "vc-tab", "$this->td" ),
                    "heading"           => esc_html__("Default Loading Color", "$this->td" ),
                    "type"              => "textfield",
                    "edit_field_class"  => "vc_column vc_col-sm-6",
                    "holder"            => "div",
                    "admin_label"       => false,
                    "param_name"        => "loading_color",
                    "value"             => "linear-gradient(120deg,#dd5542,#fd9d73)",
                    "dependency"        => array( 'element' => 'maptype', 'value' => array( 'googlemap' ), ),

                ),
                array(
                    "group"             => esc_html_x("CSS", "vc-tab", "$this->td" ),
                    "heading"           => esc_html__("Map Container's HTML ID", "$this->td" ),
                    "type"              => "textfield",
                    "class"             => "",
                    "edit_field_class"  => "vc_column vc_col-sm-6",
                    "holder"            => "div",
                    "admin_label"       => false,
                    "param_name"        => "el_id",
                    "value"             => "",

                  ),
                array(
                    "group"             => esc_html_x("CSS", "vc-tab", "$this->td" ),
                    "heading"           => esc_html__("Map Container's HTML Class", "$this->td" ),
                    "type"              => "textfield",
                    "class"             => "",
                    "edit_field_class"  => "vc_column vc_col-sm-6",
                    "holder"            => "div",
                    "admin_label"       => false,
                    "param_name"        => "el_class",
                    "value"             => "",

                ),
                array(
                    "group"             => esc_html_x("CSS","vc-tab","$this->td" ),
                    "heading"           => esc_html__("Map Container's Width", "$this->td" ),
                    "type"              => "textfield",
                    "class"             => "",
                    "edit_field_class"  => "vc_column vc_col-sm-6",
                    "holder"            => "div",
                    "admin_label"       => false,
                    "param_name"        => "el_map_width",
                    "value"             => "100%",

                  ),
                array(
                    "group"             => esc_html_x("CSS","vc-tab", "$this->td" ),
                    "heading"           => esc_html__("Map Container's Height", "$this->td" ),
                    "type"              => "textfield",
                    "class"             => "",
                    "edit_field_class"  => "vc_column vc_col-sm-6",
                    "holder"            => "div",
                    "admin_label"       => false,
                    "param_name"        => "el_map_height",
                    "value"             => "500px",

                ),
                array(
                    "group"             => esc_html_x("Design", "vc-tab", "$this->td" ),
                    "heading"           => esc_html__("G-Map Custom Logo", "$this->td" ),
                    "type"              => "textfield",
                    "edit_field_class"  => "vc_column vc_col-sm-6",
                    "holder"            => "div",
                    "admin_label"       => false,
                    "description"       => __('Change G-Map default trademark logo at footer', $this->td),
                    "param_name"        => "mapfooterimage",
                    "value"             => "{$this->assets_url}img/peprodev.svg",
                    "dependency"        => array( 'element' => 'maptype', 'value' => array( 'googlemap' ), ),
                ),
                array(
                    "group"             => esc_html_x("Design","vc-tab", "$this->td" ),
                    "heading"           => esc_html__("Development Mode", "$this->td" ),
                    "type"              => "checkbox",
                    "edit_field_class"  => "vc_column vc_col-sm-3",
                    "holder"            => "div",
                    "admin_label"       => false,
                    "param_name"        => "usegmapcopyright",
                    "description"       => __('In development mode, API is skipped but "FOR DEVELOPMENT PURPOSES ONLY" watermark is added to map, And also styles will not work properly.', $this->td),
                    "dependency"        => array( 'element' => 'maptype', 'value' => array( 'googlemap' ), ),
                ),
                array(
                    "group"             => esc_html_x("Design", "vc-tab","$this->td" ),
                    "heading"           => esc_html__("Disable UI", "$this->td" ),
                    "type"              => "checkbox",
                    "edit_field_class"  => "vc_column vc_col-sm-3",
                    "holder"            => "div",
                    "admin_label"       => false,
                    "param_name"        => "disabledefaultui",
                    "description"       => __('No UI will be shown on Map, No Zoom Buttons, No Fullscreen Button and ...', $this->td),
                    "dependency"        => array( 'element' => 'maptype', 'value' => array( 'googlemap' ), ),
                ),
                array(
                    "group"             => esc_html_x("Design","vc-tab", "$this->td" ),
                    "heading"           => esc_html__("Select Style", "$this->td" ),
                    "type"              => "radio_image",
                    "edit_field_class"  => "vc_column vc_col-sm-6",
                    "holder"            => "div",
                    "admin_label"       => false,
                    "std"               => "default",
                    "value"             => $googlemapDesignsArray,
                    "images"            => $googlemapDesignsPicArray,
                    "image_height"      => "100px",
                    "image_width"       => "100px",
                    "image_label"       => true,
                    "param_name"        => "map_defined_style",
                    "dependency"        => array('element' => 'maptype','value' => array( 'googlemap' ),),
                ),
                array(
                    "group"             => esc_html_x("Design","vc-tab", "$this->td" ),
                    "heading"           => esc_html__("Style Preview", "$this->td" ),
                    "type"              => "textfield",
                    "edit_field_class"  => "vc_column vc_col-sm-6 vc_sticky",
                    "holder"            => "div",
                    "admin_label"       => false,
                    "param_name"        => "googlemap_style__preview",
                    "dependency"        => array('element' => 'maptype','value' => array( 'googlemap' ),),
                ),
                array(
                  "group"             => esc_html_x("Design","vc-tab", "$this->td" ),
                  "heading"           => esc_html__("Google Maps Custom Style", "$this->td" ),
                  "type"              => "textarea_raw_html",
                  "edit_field_class"  => "vc_column vc_col-sm-6 vc_sticky",
                  "holder"            => "div",
                  "admin_label"       => false,
                  "param_name"        => "googlemap_style",
                  "description"       => sprintf(__('Browse %s for beautiful pre-made styles.', $this->td),'<a target="_blank" href="https://snazzymaps.com">snazzymaps.com</a>'),
                  "dependency"        => array('element' => 'maptype','value' => array( 'googlemap' ),),
                ),
                array(
                  "group"               => esc_html_x("CSS","vc-tab", "$this->td"),
                  "type"                => "css_editor",
                  "param_name"          => "css",
                  "save_always"         => true,
                  "admin_label"         => false,
                ),
                array(
                  "group"               => esc_html_x("Import / Export","vc-tab", "$this->td" ),
                  "type"                => "pepro_about",
                  "param_name"          => "maptype",
                  "save_always"         => true,
                  "admin_label"         => false,
                ),
            ),
          )
        );
    }
    public function vc_add_pepro_about($settings, $value)
    {
      // __("Import Done Successfully",$this->td)
      ob_start();
      $ct = current_time("timestamp");
      ?>
      <script type="text/javascript">
        var GLOBAL_MAPIFY_VERSION = "<?=$this->version;?>";
        var GLOBAL_VC_VERSION = "<?=WPB_VC_VERSION;?>";
        var GLOBAL_PHP_VERSION = "<?=phpversion();?>";
        var GLOBAL_WP_VERSION = "<?=get_bloginfo('version');?>";
        var GLOBAL_IMPORT_DONE = "<?=__('Import Done Successfully',$this->td);?>";
      </script>
      <style media="screen">
        div#mapify<?=$ct;?>{
          	display: block;
          	background: url('<?=plugins_url( "/assets/img/peprodev.svg",__FILE__ );?>');
          	min-height: 200px;
          	background-size: 53px;
          	background-position: bottom right;
          	background-repeat: no-repeat;
        }
        [dir=rtl] div#mapify<?=$ct;?>{
          	background-position: bottom left;
        }
      </style>
      <div id="mapify<?=$ct;?>">
        <textarea id="mapify-importexport" rows="8" cols="80" placeholder="<?=esc_attr__("Press 'Export Shortcode Configurations' to generate data",$this->td);?>"></textarea>
        <p>
          <button type="button" style="min-width: 40%;"
          class="mapify-export vc_general vc_ui-button vc_ui-button-action vc_ui-button-shape-rounded"
          data-caption="<?=__("Export Shortcode Configurations",$this->td);?>"
          data-copied="<?=__("Successfully copied to clipboard !",$this->td);?>"><?=__("Export Shortcode Configurations",$this->td);?></button>
          <button type="button" style="min-width: 40%;"
          class="mapify-import vc_general vc_ui-button vc_ui-button-action vc_ui-button-shape-rounded"
          data-empty="<?=__("Empty data! Paste your data in field above.",$this->td);?>"><?=__("Import Shortcode Configurations",$this->td);?></button>
        </p>
      </div>
      <?php
      $tcona = ob_get_contents();
      ob_end_clean();
      return $tcona;
    }
    public function vc_add_pepro_dropdown_multi( $param, $value )
    {
       $param_line = '';
       $param_class = isset($param['class']) ? $param['class'] : "";
       $param_line .= '<select multiple data-placeholder="'.esc_attr( $param['placeholder'] ).'" name="'. esc_attr( $param['param_name'] ).'" class="wpb_vc_param_value wpb-input wpb-select '. esc_attr( $param['param_name'] ).' '.esc_attr($param_class).' '. esc_attr($param['type']).'">';
       foreach ( $param['value'] as $text_val => $val ) {
           if ( is_numeric($text_val) && (is_string($val) || is_numeric($val)) ) {
                        $text_val = $val;
                    }
                    $text_val = $text_val;$selected = '';
                    if(!is_array($value)) {
                        $param_value_arr = explode(',',$value);
                    } else {
                        $param_value_arr = $value;
                    }
                    if ($value!=='' && in_array($val, $param_value_arr)) {
                        $selected = ' selected="selected"';
                    }
                    $param_line .= '<option class="'.$val.'" value="'.$val.'"'.$selected.'>'.$text_val.'</option>';
                }
       $param_line .= '</select>';

       return  $param_line;
    }
    public function vc_add_pepro_radio_image( $param, $value )
    {
       $image_label = empty($param['image_label']) ? false : ((true == $param['image_label']) ? true : false);
       $imgwidth = empty($param['image_width']) ? "100px" : esc_attr($param['image_width']);
       $show_input = empty($param['show_input']) ? "hidden" : "text";
       $input_pos = empty($param['input_pos']) ? "bottom" : (("top" == $param['input_pos'])?"top":"bottom");
       $imgheight = empty($param['image_height']) ? "100px" : esc_attr($param['image_height']);
       $param_class = isset($param['class']) ? $param['class'] : "";
       $param_line  = '<style>.peprodevvcradiolabl span { margin-top: 0.7rem; }.peprodevvcradiolabl {margin-bottom: .5rem; display: inline-flex; flex-direction: column; place-content: center; place-items: center; }.peprodevvcinputforradio:checked + label > img { box-shadow: 0 0 0 3px white,0 0 0 6px #e05a46; } .peprodevvcinputforradio + label > img { border-radius: 5px; }.wpb_vc_param_value.radio_image{ display: flex; flex-wrap: wrap; justify-content: flex-start; }
       .vcpepro_radio_item_container.'.$param['param_name'].' { flex: 0 1 calc('.$imgwidth.' + 1.2rem); margin-bottom: 5px; width: auto;text-align: center;}</style>';
       if ("bottom" !== $input_pos){$param_line  .=  '<input type="'.$show_input.'" value="'.$value.'" id="'.$param['param_name'].'" name="'.$param['param_name'].'" class="wpb_vc_param_value wpb-input">';}
       $param_line .= '<div class="wpb_vc_param_value '. esc_attr( $param['param_name'] ).' '.esc_attr( $param_class ).' '. esc_attr($param['type']).'">';
       foreach ( $param['value'] as $text_val => $val ) {
         if ( is_numeric($text_val) && (is_string($val) || is_numeric($val)) ) { $text_val = $val; }
         $text_val = $text_val; $selected = ''; $img = "";
         if(!is_array($value)) {
           $param_value_arr = explode(',',$value);
         } else {
           $param_value_arr = $value;
         }
         if ($value !== '' && in_array($val, $param_value_arr)) { $selected = ' checked'; }
         $dnoneinpuit = "";
         if (!empty($param['images'][$val])){
           $img = $param['images'][$val];
           $text_val = "<img id='{$param['param_name']}_$val' title='$text_val' style='width:$imgwidth; height:$imgheight;' alt='$text_val' src='$img' />" . (true===$image_label?"<span>$text_val</span>":"");
           $dnoneinpuit = "display: none;";
         }
         $param_line .=
         '<div class="vcpepro_radio_item_container '.esc_attr( $param['param_name'] )." $val ".' ">
         <input style="width: auto;'.$dnoneinpuit.'" type="radio" name="peprodev_'. esc_attr( $param['param_name'] ).'"
         class="wpb_vc_param_value peprodevvcinputforradio '."{$param['param_name']} $val".'" id="peprodev_'.$param['param_name']."_".$val.'"
         value="'.$val.'" '.$selected.' />
         <label class="vc_radio-label peprodevvcradiolabl" for="peprodev_'.esc_attr( $param['param_name'] )."_".$val.'">'.$text_val.'</label>
         </div>';
       }
       $param_line .= '</div>';
       if ("bottom" === $input_pos){$param_line  .=  '<input type="'.$show_input.'" value="'.$value.'" id="'.$param['param_name'].'" name="'.$param['param_name'].'" class="wpb_vc_param_value wpb-input">';}
       $param_line  .=  '<script>jQuery("input.wpb_vc_param_value.'.$param['param_name'].'").change(function(){var s = jQuery(this).val();jQuery("#'.$param['param_name'].'").val(s);});</script>';
       return  $param_line;
    }
    public function plugins_row_links($links)
    {
        foreach ($this->get_manage_links() as $title => $href) {
            array_unshift($links, "<a href='$href' target='_self'>$title</a>");
        }
        $a = new SimpleXMLElement($links["deactivate"]);
        $this->deactivateURI = "<a href='".$a['href']."'>".$this->deactivateICON.$a[0]."</a>";
        unset($links["deactivate"]);
        return $links;
    }
    public function plugin_row_meta($links, $file)
    {
        if ($this->plugin_basename === $file) {
            // unset($links[1]);
            unset($links[2]);
            $icon_attr = array(
              'style' => array(
              'font-size: larger;',
              'line-height: 1rem;',
              'display: inline;',
              'vertical-align: text-top;',
              ),
            );
            foreach ($this->get_meta_links() as $id => $link) {
                $title = (!empty($link['icon'])) ? self::do_icon($link['icon'], $icon_attr) . ' ' . esc_html($link['title']) : esc_html($link['title']);
                $links[ $id ] = '<a href="' . esc_url($link['url']) . '" title="'.esc_attr($link['description']).'" target="'.(empty($link['target'])?"_blank":$link['target']).'">' . $title . '</a>';
            }
            $links[0] = $this->versionICON . $links[0];
            $links[1] = $this->authorICON . $links[1];
            $links["deactivate"] = $this->deactivateURI;
        }
        return $links;
    }
    public static function do_icon($icon, $attr = array(), $content = '')
    {
        $class = '';
        if (false === strpos($icon, '/') && 0 !== strpos($icon, 'data:') && 0 !== strpos($icon, 'http')) {
            // It's an icon class.
            $class .= ' dashicons ' . $icon;
        } else {
            // It's a Base64 encoded string or file URL.
            $class .= ' vaa-icon-image';
            $attr   = self::merge_attr($attr, array(
                'style' => array( 'background-image: url("' . $icon . '") !important' ),
            ));
        }

        if (! empty($attr['class'])) {
            $class .= ' ' . (string) $attr['class'];
        }
        $attr['class']       = $class;
        $attr['aria-hidden'] = 'true';

        $attr = self::parse_to_html_attr($attr);
        return '<span ' . $attr . '>' . $content . '</span>';
    }
    public static function parse_to_html_attr($array)
    {
        $str = '';
        if (is_array($array) && ! empty($array)) {
            foreach ($array as $attr => $value) {
                if (is_array($value)) {
                    $value = implode(' ', $value);
                }
                $array[ $attr ] = esc_attr($attr) . '="' . esc_attr($value) . '"';
            }
            $str = implode(' ', $array);
        }
        return $str;
    }
    public function get_meta_links()
    {
        if (!empty($this->meta_links)) {return $this->meta_links;}
        $this->meta_links = array(
              'support'      => array(
                  'title'       => __('Support', $this->td),
                  'description' => __('Support', $this->td),
                  'icon'        => 'dashicons-admin-site',
                  'target'      => '_blank',
                  'url'         => "mailto:support@pepro.dev?subject={$this->title}",
              ),
          );
        return $this->meta_links;
    }
    public function get_manage_links()
    {
        if (!empty($this->manage_links)) {return $this->manage_links; }
        $this->manage_links = array(
          $this->settingURL .   __("Settings", $this->td)       => "$this->url",
        );
        return $this->manage_links;
    }
    public static function activation_hook()
    {
    }
    public static function deactivation_hook()
    {
    }
    public static function uninstall_hook()
    {
      $ppa = new PeproBranchesMap_AKA_Mapify;
      if (get_option("{$ppa->db_slug}-clearunistall", "no") === "yes") {
          $cf7Database_class_options = $ppa->get_setting_options();
          foreach ($cf7Database_class_options as $options) {
              $opparent = $options["name"];
              foreach ($options["data"] as $optname => $optvalue) {
                  unregister_setting($opparent, $optname);
                  delete_option($optname);
              }
          }
      }
    }
    public function plugins_loaded()
    {
        load_plugin_textdomain($this->td, false, dirname(plugin_basename(__FILE__))."/languages/");
    }
    public function get_setting_options()
    {
      return array(
        array(
          "name" => "{$this->db_slug}_general",
          "data" => array(
            "{$this->db_slug}-clearunistall" => "no",
            "{$this->db_slug}-cleardbunistall" => "no",

            "{$this->db_slug}-googlemapAPI" => "",
            "{$this->db_slug}-openstreetAPI" => "",
            "{$this->db_slug}-cedarmapsAPI" => "",
            "{$this->db_slug}-template" => "content",

          )
        ),
      );
    }
    protected function add_mapify_cpt()
    {
      $labels = array(
        'name'                  => _x( 'Branch', 'Post Type General Name', $this->td),
        'singular_name'         => _x( 'Branch', 'Post Type Singular Name', $this->td),
        'menu_name'             => __( 'Mapify', $this->td),
        'name_admin_bar'        => __( 'Branch', $this->td),
        'archives'              => __( 'Branch Archives', $this->td),
        'attributes'            => __( 'Branch Attributes', $this->td),
        'parent_item_colon'     => __( 'Parent Branch:', $this->td),
        'all_items'             => __( 'All Branches', $this->td),
        'add_new_item'          => __( 'Add New Branch', $this->td),
        'add_new'               => __( 'Add New', $this->td),
        'new_item'              => __( 'New Branch', $this->td),
        'edit_item'             => __( 'Edit Branch', $this->td),
        'update_item'           => __( 'Update Branch', $this->td),
        'view_item'             => __( 'View Branch', $this->td),
        'view_items'            => __( 'View Branches', $this->td),
        'search_items'          => __( 'Search Branches', $this->td),
        'not_found'             => __( 'Not Branch found', $this->td),
        'not_found_in_trash'    => __( 'Not Branch found in Trash', $this->td),
        'featured_image'        => __( 'Branch Image', $this->td),
        'set_featured_image'    => __( 'Set branch image', $this->td),
        'remove_featured_image' => __( 'Remove branch image', $this->td),
        'use_featured_image'    => __( 'Use as branch image', $this->td),
        'insert_into_item'      => __( 'Insert into Branch', $this->td),
        'uploaded_to_this_item' => __( 'Uploaded to this Branch', $this->td),
        'items_list'            => __( 'Branches list', $this->td),
        'items_list_navigation' => __( 'Branches list navigation', $this->td),
        'filter_items_list'     => __( 'Filter Branches list', $this->td),
      );
      $rewrite = array(
        'slug'                  => 'map',
        'with_front'            => true,
        'pages'                 => true,
        'feeds'                 => true,
      );
      $args = array(
        'label'                 => __( 'Mapify', $this->td),
        'description'           => __( 'Add branches to show on map', $this->td),
        'labels'                => $labels,
        'supports'              => array( 'title', 'thumbnail', 'revisions', "editor" ),
        'hierarchical'          => false,
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_position'         => 5,
        'menu_icon'             => 'dashicons-location',
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => true,
        'can_export'            => true,
        'has_archive'           => 'map',
        'exclude_from_search'   => false,
        'publicly_queryable'    => true,
        'rewrite'               => $rewrite,
        'capability_type'       => 'post',
      );
      register_post_type( 'mapify', $args );
      $labels = array(
        'name' => _x( 'Category', 'taxonomy general name' , $this->td),
        'singular_name' => _x( 'Category', 'taxonomy singular name', $this->td),
        'search_items' =>  __( 'Search Categories', $this->td),
        'all_items' => __( 'All Categories', $this->td),
        'parent_item' => __( 'Parent Category' , $this->td),
        'parent_item_colon' => __( 'Parent Category:', $this->td),
        'edit_item' => __( 'Edit Category' , $this->td),
        'update_item' => __( 'Update Category', $this->td),
        'add_new_item' => __( 'Add New Category', $this->td),
        'new_item_name' => __( 'New Category Name' , $this->td),
        'menu_name' => __( 'Categories' , $this->td),
      );
      register_taxonomy(
        'mapify_category',
        array('mapify'),
        array(
          'hierarchical' => true,
          'labels' => $labels,
          'show_ui' => true,
          'show_admin_column' => true,
          'query_var' => true,
          'rewrite' => array( 'slug' => 'branches' ),
        )
      );
      add_filter( "post_updated_messages", function($messages){

        $post = get_post();
        $permalink = get_permalink( $post->ID );
        $preview_url = get_preview_post_link( $post );
        if ( ! $permalink ) { $permalink = ''; }

        $preview_post_link_html   = '';
        $scheduled_post_link_html = '';
        $view_post_link_html      = '';

        $view_post_link_html = sprintf( ' <a href="%1$s">%2$s</a>', esc_url( $permalink ), __( 'View branch', $this->td ) );
        $preview_post_link_html = sprintf( ' <a target="_blank" href="%1$s">%2$s</a>', esc_url( $preview_url ), __( 'Preview branch', $this->td ) );
        $scheduled_date = sprintf(
          /* translators: Publish box date string. 1: Date, 2: Time. */
          __( '%1$s at %2$s' , $this->td),
          /* translators: Publish box date format, see https://www.php.net/date */
          date_i18n( _x( 'M j, Y', 'publish box date format', $this->td ), strtotime( $post->post_date ) ),
          /* translators: Publish box time format, see https://www.php.net/date */
          date_i18n( _x( 'H:i', 'publish box time format', $this->td ), strtotime( $post->post_date ) )
        );
        $scheduled_post_link_html = sprintf( ' <a target="_blank" href="%1$s">%2$s</a>', esc_url( $permalink ), __( 'Preview post', $this->td ) );

      	$messages['mapify'] = array(
          0  => '', // Unused. Messages start at index 1.
          1  => __( 'Branch updated.', $this->td ) . $view_post_link_html,
          2  => __( 'Branch Custom field updated.', $this->td ),
          3  => __( 'Branch Custom field deleted.', $this->td ),
          4  => __( 'Branch updated.', $this->td ),
          /* translators: %s: date and time of the revision */
          5  => isset( $_GET['revision'] ) ? sprintf( __( 'Branch restored to revision from %s.', $this->td ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
          6  => __( 'Branch published.', $this->td ) . $view_post_link_html,
          7  => __( 'Branch saved.', $this->td ),
          8  => __( 'Branch submitted.', $this->td ) . $preview_post_link_html,
          9  => sprintf( __( 'Branch scheduled for: %s.', $this->td ), '<strong>' . $scheduled_date . '</strong>' ) . $scheduled_post_link_html,
          10 => __( 'Branch draft updated.', $this->td ) . $preview_post_link_html,
      	);
        return $messages;
      });
    }
    /**
     * Update Footer Info to Developer info
     *
     * @method update_footer_info
     * @version 1.0.0
     * @since 1.0.0
     * @license https://pepro.dev/license Pepro.dev License
     */
    private function update_footer_info()
    {
        add_filter(
            'admin_footer_text', function () {
                return sprintf(_x("Thanks for using %s products", "footer-copyright", $this->td), "<b><a href='https://pepro.dev/' target='_blank' >".__("Pepro Dev", $this->td)."</a></b>");
            }, 11
        );
        add_filter(
            'update_footer', function () {
                return sprintf(_x("%s — Version %s", "footer-copyright", $this->td), $this->title, $this->version);
            }, 11
        );
        echo "<style>
        #footer-left b a::before {
          content: '';
          background: url('{$this->assets_url}img/peprodev.svg') no-repeat;
          background-position-x: center;
          background-position-y: center;
          background-size: contain;
          width: 60px;
          height: 40px;
          display: inline-block;
          pointer-events: none;
          position: absolute;
          -webkit-margin-before: calc(-60px + 1rem);
                  margin-block-start: calc(-60px + 1rem);
          -webkit-filter: opacity(0.0);
          filter: opacity(0.0);
          transition: all 0.3s ease-in-out;
        }
        #footer-left b a:hover::before {
          -webkit-filter: opacity(1.0);
          filter: opacity(1.0);
          transition: all 0.3s ease-in-out;
        }
        </style>";
    }
    public function help_container($hook)
    {
        ob_start();
        $this->update_footer_info();
        $input_number = ' dir="ltr" lang="en-US" min="0" step="1" ';
        $input_english = ' dir="ltr" lang="en-US" ';
        $input_required = ' required ';
        wp_enqueue_style("fontawesome","//use.fontawesome.com/releases/v5.13.1/css/all.css", array(), '5.13.1', 'all');
        wp_enqueue_style("{$this->db_slug}", "{$this->assets_url}css/backend.css", array("wp-color-picker") , current_time( "timestamp" ));
        wp_enqueue_script("{$this->db_slug}", "{$this->assets_url}js/backend.js", array("jquery","wp-color-picker") , current_time( "timestamp" ));
        is_rtl() AND wp_add_inline_style("{$this->db_slug}", ".form-table th {}#wpfooter, #wpbody-content *:not(.dashicons ), #wpbody-content input:not([dir=ltr]), #wpbody-content textarea:not([dir=ltr]), h1.had, .caqpde>b.fa{ font-family: bodyfont, roboto, Tahoma; }");
        echo "<h1 class='had'>".$this->title_w."</h1><div class=\"wrap\">";
        echo '<form method="post" action="options.php">';
        settings_fields("{$this->db_slug}_general");
        if (isset($_REQUEST["settings-updated"]) && $_REQUEST["settings-updated"] == "true") { echo '<div id="message" class="updated notice is-dismissible"><p>' . _x("Settings saved successfully.", "setting-general", $this->td) . "</p></div>"; }
        echo "<br><table class='form-table'><tbody>
        <p style='text-align:center;'>" . __("Use WPBakery Page Builder widget to show the map.", $this->td) . "</p>";

        $this->print_setting_input("{$this->db_slug}-googlemapAPI",           _x("Google Maps API","setting-general", $this->td),         'dir="ltr" lang="en-US"', "text","", "<a target='_blank' href='https://console.cloud.google.com/projectselector2/billing/enable'><span class='dashicons dashicons-external'></span></div>");
        // $this->print_setting_input("{$this->db_slug}-cedarmapsAPI",           _x("CedarMaps API","setting-general", $this->td),         'dir="ltr" lang="en-US"', "text");
        $this->print_setting_select("{$this->db_slug}-template",              _x("Default Branches Template","setting-general", $this->td),array("post" =>_x("Use Post Template","settings-general",$this->td), "content" => _x("Use Mapify Template","settings-general",$this->td)));
        $this->print_setting_select("{$this->db_slug}-clearunistall",         _x("Clear Configurations on Unistall","setting-general", $this->td),array("yes" =>_x("Yes","settings-general",$this->td), "no" => _x("No","settings-general",$this->td)));

        echo "</tbody></table>



        <div class='submtCC'>";
        submit_button(__("Save setting", $this->td), "primary submt", "submit", false);
        echo "</form></div></div>";
        $tcona = ob_get_contents();
        ob_end_clean();
        print $tcona;
    }
    public function get_default_popup_markup()
    {
      $def =
      '<div class="image">
        <img style="width: 100%;"
        src="{img|'.$this->assets_url."img/defimg.jpg".'}"/>
      </div>
      <h3 class="title">{title|'.__("No Title",$this->td).'}</h3>
      <div class="address">
        <img
          style="width:18px !important; height:18px !important;"
          src="https://img.icons8.com/material-rounded/18/e15d47/address.png"/>
          {address}
      </div>
      <div class="phone">
        <img
        style="width:16px !important; height:16px !important;"
        src="https://img.icons8.com/android/16/e15d47/phone.png"/>
        {phone}
      </div>
      <div class="insta">
        <img
        style="width:18px !important; height:18px !important;"
        src="https://img.icons8.com/material-rounded/18/e15d47/instagram-new.png"/>
        {instagram}
      </div>';
      return apply_filters( "mapify_vc_get_default_popup_markup", $def, $def);
    }
    public function get_default_popup_markup_help()
    {
      $params = "{id}
      {title|default_value}
      {img/image|default_value}
      {url}
      {latitude|default_value}
      {longitude|default_value}
      {pin_img|default_value}
      {address|default_value}
      {phone|default_value}
      {site|default_value}
      {email|default_value}
      {twitter|default_value}
      {facebook|default_value}
      {instagram|default_value}
      {telegram|default_value}
      {linkedin|default_value}
      {additional|default_value}
      ";
      $def = "<div><strong>".__("To make your own template, you can use following tags with the syntax of {parameter|default_value}.",$this->td)."</strong><br>$params</div>";
      return apply_filters( "mapify_vc_get_default_popup_markup_help", $def, $def);
    }
    public function shortcode_wapper($content,$el_class,$el_id)
    {
      return "
        <!--$el_id // Pepro Branches Map (Mapify) widget for WPBakery Page Builder by Pepro.dev // https://pepro.dev //-->
          <div class='mapify-branches-list-container top $el_id' data-ref-id='$el_id'></div>
          <div class='mapify-container $el_class' id='$el_id'>$content</div>
          <div class='mapify-branches-list-container bottom $el_id' data-ref-id='$el_id'></div>
        <!--$el_id // Pepro Branches Map (Mapify) widget for WPBakery Page Builder by Pepro.dev // https://pepro.dev //-->";
    }
    public function mapify_shortcode($atts = array(),$content)
    {
      $atts_filter = shortcode_atts(
        array(
          "branchtype"          => "cat",
          "branchcat"           => "",
          "branchids"           => "",
          "branchlistshow"      => "true",
          "branchplacement"     => "top",
          "brancheslistcat"     => "none",
          "branchessearch"      => "false",
          "branchascluster"     => "true",
          "clustergridsize"     => "100",
          "el_map_width"        => "100%",
          "el_map_height"       => "500px",
          "clusterminsize"      => "2",
          "default_zoom"        => "5",
          "el_class"            => "",
          "usegmapcopyright"    => "false",
          "dev"                 => "false",
          "disabledefaultui"    => "false",
          "map_defined_style"   => "default",
          "el_id"               => "",
          "pinaction"           => "url",
          "css"                 => "",
          "googlemap_style"     => "",
          "pinurltarget"        => "_blank",
          "map_style"           => "",
          "center_coordinate"   => "32.1001646,54.4637493",
          "maptype"             => apply_filters( "pepro-mapify-maptypes-default","googlemap"),
          "popup_markup"        => base64_encode($this->get_default_popup_markup()),
          "pinimage"            => "",
          "loading_image"       => "{$this->assets_url}img/peprodev.svg",
          "loading_color"       => "linear-gradient(120deg,#dd5542,#fd9d73)",
          "loading_color"       => "linear-gradient(120deg,#dd5542,#fd9d73)",
          "mapfooterimage"      => "{$this->assets_url}img/peprodev.svg",
        ),$atts);
      $custom_css_code = apply_filters( "pepro-mapify-vc-styles-default-css",'
        .googlemapinfo_div {
          font-family: iranyekan;
          max-height: unset !important;
          min-width: 350px !important;
          max-width: 350px !important;
          font-size: 14px !important;
          font-weight: 400 !important;
          background-color: rgb(255, 255, 255) !important;
          border-radius: 8px !important;
          box-shadow: rgba(0, 0, 0, 0.15) 0 7px 12px, rgba(0, 0, 0, 0.05) 0 0 1px !important;
          overflow: hidden !important;
          text-align: center !important;
          border-width: 0 !important;
          margin: 0 !important;
          padding: 0 0 0.5rem !important;
        }'.
        '.googlemapinfo_div .title {
          border-color: rgb(201, 201, 201);
          border-style: solid;
          border-width: 0 0 1px;
          margin: 0;
          direction: rtl;
          line-height: 2.3;
          padding: 0 0 5px;
          display: inline-block;
          min-width: calc(100% - 28px);
        }'.
        '.googlemapinfo_div .address {
          margin: 5px;
          text-align: start;
          direction: rtl;
          padding: 7px 7px 0;
        }'.
        '.googlemapinfo_div .insta,'.
        '.googlemapinfo_div .phone {
          line-height: 1;
          padding: 5px 14px;
          text-align: start;
        }'.
        '.googlemapinfo_div>div:first-of-type{
          max-height: unset !important;
          overflow: hidden !important;
        }'.
        '.googlemapinfo_div>button:last-of-type {
          white-space: nowrap;
          font-size: 20px;
          line-height: 1rem;
          color: white;
          letter-spacing: 0;
          background-color: rgb(255, 255, 255) !important;
          border-radius: 0 8px 0 8px !important;
          cursor: pointer !important;
          visibility: inherit;
          transition: none 0s ease 0s;
          border-width: 0 !important;
          margin: 0 5px !important;
          height: 25px !important;
          opacity: 1;
          text-align: center;
        }'.
        '.mapify-branches-item {
          color: white !important;
          font-family: iranyekan;
          background-color: #703192;
          border-radius: 30px 6px 15px 15px;
          box-sizing: border-box;
          transition: none 0s ease 0s;
          text-align: center;
          padding: .2rem 1rem;
          display: inline-block;
          margin: 4px 5px;
          font-size: 0.8rem;
          font-weight: 300;
          min-width: 176px;
        }
        .mapify-branches-list-container {
          margin: 1rem 0;
        }
        '
      );
      $atts_filter["popup_markup"] = rawurldecode(base64_decode($atts_filter["popup_markup"]));
      $atts_filter["googlemap_style"] = rawurldecode(base64_decode($atts_filter["googlemap_style"]));
      extract($atts_filter);
      $usegmapcopyright = ($dev == "true") ? "true" : $usegmapcopyright;

      switch ($map_defined_style) {
        case "default":
          $map_style = "";
          break;

        case "custom":
          $map_style = "custom";
          break;

        case "midnight":
          $map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcy5lOmwudC5mfHAuYzojZmZmZmZmZmYscy5lOmwudC5zfHAuYzojZmYwMDAwMDB8cC5sOjEzLHMudDoxfHMuZTpnLmZ8cC5jOiNmZjAwMDAwMCxzLnQ6MXxzLmU6Zy5zfHAuYzojZmYxNDRiNTN8cC5sOjE0fHAudzoxLjQscy50OjV8cC5jOiNmZjA4MzA0YixzLnQ6MnxzLmU6Z3xwLmM6I2ZmMGM0MTUyfHAubDo1LHMudDo0OXxzLmU6Zy5mfHAuYzojZmYwMDAwMDAscy50OjQ5fHMuZTpnLnN8cC5jOiNmZjBiNDM0ZnxwLmw6MjUscy50OjUwfHMuZTpnLmZ8cC5jOiNmZjAwMDAwMCxzLnQ6NTB8cy5lOmcuc3xwLmM6I2ZmMGIzZDUxfHAubDoxNixzLnQ6NTF8cy5lOmd8cC5jOiNmZjAwMDAwMCxzLnQ6NHxwLmM6I2ZmMTQ2NDc0LHMudDo2fHAuYzojZmYwMjEwMTk';
          $googlemap_style = '[
            {
              "featureType": "all",
              "elementType": "labels.text.fill",
              "stylers": [{
                "color": "#ffffff"
              }]
            }, {
              "featureType": "all",
              "elementType": "labels.text.stroke",
              "stylers": [{
                "color": "#000000"
              }, {
                "lightness": 13
              }]
            }, {
              "featureType": "administrative",
              "elementType": "geometry.fill",
              "stylers": [{
                "color": "#000000"
              }]
            }, {
              "featureType": "administrative",
              "elementType": "geometry.stroke",
              "stylers": [{
                "color": "#144b53"
              }, {
                "lightness": 14
              }, {
                "weight": 1.4
              }]
            }, {
              "featureType": "landscape",
              "elementType": "all",
              "stylers": [{
                "color": "#08304b"
              }]
            }, {
              "featureType": "poi",
              "elementType": "geometry",
              "stylers": [{
                "color": "#0c4152"
              }, {
                "lightness": 5
              }]
            }, {
              "featureType": "road.highway",
              "elementType": "geometry.fill",
              "stylers": [{
                "color": "#000000"
              }]
            }, {
              "featureType": "road.highway",
              "elementType": "geometry.stroke",
              "stylers": [{
                "color": "#0b434f"
              }, {
                "lightness": 25
              }]
            }, {
              "featureType": "road.arterial",
              "elementType": "geometry.fill",
              "stylers": [{
                "color": "#000000"
              }]
            }, {
              "featureType": "road.arterial",
              "elementType": "geometry.stroke",
              "stylers": [{
                "color": "#0b3d51"
              }, {
                "lightness": 16
              }]
            }, {
              "featureType": "road.local",
              "elementType": "geometry",
              "stylers": [{
                "color": "#000000"
              }]
            }, {
              "featureType": "transit",
              "elementType": "all",
              "stylers": [{
                "color": "#146474"
              }]
            }, {
              "featureType": "water",
              "elementType": "all",
              "stylers": [{
                "color": "#021019"
              }]
            }]';
          break;

        case "desert":
          $map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcy5lOmx8cC52Om9mZnxwLmM6I2ZmZjQ5ZjUzLHMudDo1fHAuYzojZmZmOWRkYzV8cC5sOi03LHMudDozfHAuYzojZmY4MTMwMzN8cC5sOjQzLHMudDozM3xwLmM6I2ZmNjQ1YzIwfHAubDozOCxzLnQ6NnxwLmM6I2ZmMTk5NGJmfHAuczotNjl8cC5nOjAuOTl8cC5sOjQzLHMudDo1MXxzLmU6Zy5mfHAuYzojZmZmMTlmNTN8cC53OjEuM3xwLnY6b258cC5sOjE2LHMudDozMyxzLnQ6NDB8cC5jOiNmZjY0NWMyMHxwLmw6Mzkscy50OjM1fHAuYzojZmZhOTU1MjF8cC5sOjM1LHMudDozNnxzLmU6Zy5mfHAuYzojZmY4MTMwMzN8cC5sOjM4fHAudjpvZmYscy5lOmwscy50OjM5fHAuYzojZmY5ZTU5MTZ8cC5sOjMyLHMudDozNHxwLmM6I2ZmOWU1OTE2fHAubDo0NixzLnQ6NjZ8cC52Om9mZixzLnQ6NjV8cC5jOiNmZjgxMzAzM3xwLmw6MjIscy50OjR8cC5sOjM4LHMudDo1MXxzLmU6Zy5zfHAuYzojZmZmMTlmNTN8cC5sOi0xMA';
          $googlemap_style = '[
            {
              "elementType": "labels",
              "stylers": [
                {
                  "visibility": "off"
                },
                {
                  "color": "#f49f53"
                }
              ]
            },
            {
              "featureType": "landscape",
              "stylers": [
                {
                  "color": "#f9ddc5"
                },
                {
                  "lightness": -7
                }
              ]
            },
            {
              "featureType": "road",
              "stylers": [
                {
                  "color": "#813033"
                },
                {
                  "lightness": 43
                }
              ]
            },
            {
              "featureType": "poi.business",
              "stylers": [
                {
                  "color": "#645c20"
                },
                {
                  "lightness": 38
                }
              ]
            },
            {
              "featureType": "water",
              "stylers": [
                {
                  "color": "#1994bf"
                },
                {
                  "saturation": -69
                },
                {
                  "gamma": 0.99
                },
                {
                  "lightness": 43
                }
              ]
            },
            {
              "featureType": "road.local",
              "elementType": "geometry.fill",
              "stylers": [
                {
                  "color": "#f19f53"
                },
                {
                  "weight": 1.3
                },
                {
                  "visibility": "on"
                },
                {
                  "lightness": 16
                }
              ]
            },
            {
              "featureType": "poi.business"
            },
            {
              "featureType": "poi.park",
              "stylers": [
                {
                  "color": "#645c20"
                },
                {
                  "lightness": 39
                }
              ]
            },
            {
              "featureType": "poi.school",
              "stylers": [
                {
                  "color": "#a95521"
                },
                {
                  "lightness": 35
                }
              ]
            },
            {},
              {
                "featureType": "poi.medical",
                "elementType": "geometry.fill",
                "stylers": [
                  {
                    "color": "#813033"
                  },
                  {
                    "lightness": 38
                  },
                  {
                    "visibility": "off"
                  }
                ]
              },
              {},
                {},
                  {},
                    {},
                      {},
                        {},
                          {},
                            {},
                              {},
                                {},
                                  {},
                                    {
                                      "elementType": "labels"
                                    },
                                    {
                                      "featureType": "poi.sports_complex",
                                      "stylers": [
                                        {
                                          "color": "#9e5916"
                                        },
                                        {
                                          "lightness": 32
                                        }
                                      ]
                                    },
                                    {},
                                      {
                                        "featureType": "poi.government",
                                        "stylers": [
                                          {
                                            "color": "#9e5916"
                                          },
                                          {
                                            "lightness": 46
                                          }
                                        ]
                                      },
                                      {
                                        "featureType": "transit.station",
                                        "stylers": [
                                          {
                                            "visibility": "off"
                                          }
                                        ]
                                      },
                                      {
                                        "featureType": "transit.line",
                                        "stylers": [
                                          {
                                            "color": "#813033"
                                          },
                                          {
                                            "lightness": 22
                                          }
                                        ]
                                      },
                                      {
                                        "featureType": "transit",
                                        "stylers": [
                                          {
                                            "lightness": 38
                                          }
                                        ]
                                      },
                                      {
                                        "featureType": "road.local",
                                        "elementType": "geometry.stroke",
                                        "stylers": [
                                          {
                                            "color": "#f19f53"
                                          },
                                          {
                                            "lightness": -10
                                          }
                                        ]
                                      },
                                      {},
                                        {},
                                          {}]';
          break;

        case "bright":
          $map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcC5zOjMyfHAubDotM3xwLnY6b258cC53OjEuMTgscy50OjF8cy5lOmx8cC52Om9mZixzLnQ6NXxzLmU6bHxwLnY6b2ZmLHMudDo4MXxwLnM6LTcwfHAubDoxNCxzLnQ6MnxzLmU6bHxwLnY6b2ZmLHMudDozfHMuZTpsfHAudjpvZmYscy50OjR8cy5lOmx8cC52Om9mZixzLnQ6NnxwLnM6MTAwfHAubDotMTQscy50OjZ8cy5lOmx8cC52Om9mZnxwLmw6MTI';
          $googlemap_style = '[
              {
                "featureType": "all",
                "elementType": "all",
                "stylers": [
                  {
                    "saturation": "32"
                  },
                  {
                    "lightness": "-3"
                  },
                  {
                    "visibility": "on"
                  },
                  {
                    "weight": "1.18"
                  }
                ]
              },
              {
                "featureType": "administrative",
                "elementType": "labels",
                "stylers": [
                  {
                    "visibility": "off"
                  }
                ]
              },
              {
                "featureType": "landscape",
                "elementType": "labels",
                "stylers": [
                  {
                    "visibility": "off"
                  }
                ]
              },
              {
                "featureType": "landscape.man_made",
                "elementType": "all",
                "stylers": [
                  {
                    "saturation": "-70"
                  },
                  {
                    "lightness": "14"
                  }
                ]
              },
              {
                "featureType": "poi",
                "elementType": "labels",
                "stylers": [
                  {
                    "visibility": "off"
                  }
                ]
              },
              {
                "featureType": "road",
                "elementType": "labels",
                "stylers": [
                  {
                    "visibility": "off"
                  }
                ]
              },
              {
                "featureType": "transit",
                "elementType": "labels",
                "stylers": [
                  {
                    "visibility": "off"
                  }
                ]
              },
              {
                "featureType": "water",
                "elementType": "all",
                "stylers": [
                  {
                    "saturation": "100"
                  },
                  {
                    "lightness": "-14"
                  }
                ]
              },
              {
                "featureType": "water",
                "elementType": "labels",
                "stylers": [
                  {
                    "visibility": "off"
                  },
                  {
                    "lightness": "12"
                  }
                ]
              }]';
          break;

        case "ulight":
          $map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcy50OjZ8cy5lOmd8cC5jOiNmZmU5ZTllOXxwLmw6MTcscy50OjV8cy5lOmd8cC5jOiNmZmY1ZjVmNXxwLmw6MjAscy50OjQ5fHMuZTpnLmZ8cC5jOiNmZmZmZmZmZnxwLmw6MTcscy50OjQ5fHMuZTpnLnN8cC5jOiNmZmZmZmZmZnxwLmw6Mjl8cC53OjAuMixzLnQ6NTB8cy5lOmd8cC5jOiNmZmZmZmZmZnxwLmw6MTgscy50OjUxfHMuZTpnfHAuYzojZmZmZmZmZmZ8cC5sOjE2LHMudDoyfHMuZTpnfHAuYzojZmZmNWY1ZjV8cC5sOjIxLHMudDo0MHxzLmU6Z3xwLmM6I2ZmZGVkZWRlfHAubDoyMSxzLmU6bC50LnN8cC52Om9ufHAuYzojZmZmZmZmZmZ8cC5sOjE2LHMuZTpsLnQuZnxwLnM6MzZ8cC5jOiNmZjMzMzMzM3xwLmw6NDAscy5lOmwuaXxwLnY6b2ZmLHMudDo0fHMuZTpnfHAuYzojZmZmMmYyZjJ8cC5sOjE5LHMudDoxfHMuZTpnLmZ8cC5jOiNmZmZlZmVmZXxwLmw6MjAscy50OjF8cy5lOmcuc3xwLmM6I2ZmZmVmZWZlfHAubDoxN3xwLnc6MS4y';
          $googlemap_style = '[
            {
              "featureType": "water",
              "elementType": "geometry",
              "stylers": [
                {
                  "color": "#e9e9e9"
                },
                {
                  "lightness": 17
                }
              ]
            },
            {
              "featureType": "landscape",
              "elementType": "geometry",
              "stylers": [
                {
                  "color": "#f5f5f5"
                },
                {
                  "lightness": 20
                }
              ]
            },
            {
              "featureType": "road.highway",
              "elementType": "geometry.fill",
              "stylers": [
                {
                  "color": "#ffffff"
                },
                {
                  "lightness": 17
                }
              ]
            },
            {
              "featureType": "road.highway",
              "elementType": "geometry.stroke",
              "stylers": [
                {
                  "color": "#ffffff"
                },
                {
                  "lightness": 29
                },
                {
                  "weight": 0.2
                }
              ]
            },
            {
              "featureType": "road.arterial",
              "elementType": "geometry",
              "stylers": [
                {
                  "color": "#ffffff"
                },
                {
                  "lightness": 18
                }
              ]
            },
            {
              "featureType": "road.local",
              "elementType": "geometry",
              "stylers": [
                {
                  "color": "#ffffff"
                },
                {
                  "lightness": 16
                }
              ]
            },
            {
              "featureType": "poi",
              "elementType": "geometry",
              "stylers": [
                {
                  "color": "#f5f5f5"
                },
                {
                  "lightness": 21
                }
              ]
            },
            {
              "featureType": "poi.park",
              "elementType": "geometry",
              "stylers": [
                {
                  "color": "#dedede"
                },
                {
                  "lightness": 21
                }
              ]
            },
            {
              "elementType": "labels.text.stroke",
              "stylers": [
                {
                  "visibility": "on"
                },
                {
                  "color": "#ffffff"
                },
                {
                  "lightness": 16
                }
              ]
            },
            {
              "elementType": "labels.text.fill",
              "stylers": [
                {
                  "saturation": 36
                },
                {
                  "color": "#333333"
                },
                {
                  "lightness": 40
                }
              ]
            },
            {
              "elementType": "labels.icon",
              "stylers": [
                {
                  "visibility": "off"
                }
              ]
            },
            {
              "featureType": "transit",
              "elementType": "geometry",
              "stylers": [
                {
                  "color": "#f2f2f2"
                },
                {
                  "lightness": 19
                }
              ]
            },
            {
              "featureType": "administrative",
              "elementType": "geometry.fill",
              "stylers": [
                {
                  "color": "#fefefe"
                },
                {
                  "lightness": 20
                }
              ]
            },
            {
              "featureType": "administrative",
              "elementType": "geometry.stroke",
              "stylers": [
                {
                  "color": "#fefefe"
                },
                {
                  "lightness": 17
                },
                {
                  "weight": 1.2
                }
              ]
            }]';
          break;

        case "accriv":
          $map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcC52Om9uLHMuZTpsfHAudjpvZmZ8cC5zOi0xMDAscy5lOmwudC5mfHAuczozNnxwLmM6I2ZmMDAwMDAwfHAubDo0MHxwLnY6b2ZmLHMuZTpsLnQuc3xwLnY6b2ZmfHAuYzojZmYwMDAwMDB8cC5sOjE2LHMuZTpsLml8cC52Om9mZixzLnQ6MXxzLmU6Zy5mfHAuYzojZmYwMDAwMDB8cC5sOjIwLHMudDoxfHMuZTpnLnN8cC5jOiNmZjAwMDAwMHxwLmw6MTd8cC53OjEuMixzLnQ6NXxzLmU6Z3xwLmM6I2ZmMDAwMDAwfHAubDoyMCxzLnQ6NXxzLmU6Zy5mfHAuYzojZmY0ZDYwNTkscy50OjV8cy5lOmcuc3xwLmM6I2ZmNGQ2MDU5LHMudDo4MnxzLmU6Zy5mfHAuYzojZmY0ZDYwNTkscy50OjJ8cy5lOmd8cC5sOjIxLHMudDoyfHMuZTpnLmZ8cC5jOiNmZjRkNjA1OSxzLnQ6MnxzLmU6Zy5zfHAuYzojZmY0ZDYwNTkscy50OjN8cy5lOmd8cC52Om9ufHAuYzojZmY3ZjhkODkscy50OjN8cy5lOmcuZnxwLmM6I2ZmN2Y4ZDg5LHMudDo0OXxzLmU6Zy5mfHAuYzojZmY3ZjhkODl8cC5sOjE3LHMudDo0OXxzLmU6Zy5zfHAuYzojZmY3ZjhkODl8cC5sOjI5fHAudzowLjIscy50OjUwfHMuZTpnfHAuYzojZmYwMDAwMDB8cC5sOjE4LHMudDo1MHxzLmU6Zy5mfHAuYzojZmY3ZjhkODkscy50OjUwfHMuZTpnLnN8cC5jOiNmZjdmOGQ4OSxzLnQ6NTF8cy5lOmd8cC5jOiNmZjAwMDAwMHxwLmw6MTYscy50OjUxfHMuZTpnLmZ8cC5jOiNmZjdmOGQ4OSxzLnQ6NTF8cy5lOmcuc3xwLmM6I2ZmN2Y4ZDg5LHMudDo0fHMuZTpnfHAuYzojZmYwMDAwMDB8cC5sOjE5LHMudDo2fHAuYzojZmYyYjM2Mzh8cC52Om9uLHMudDo2fHMuZTpnfHAuYzojZmYyYjM2Mzh8cC5sOjE3LHMudDo2fHMuZTpnLmZ8cC5jOiNmZjI0MjgyYixzLnQ6NnxzLmU6Zy5zfHAuYzojZmYyNDI4MmIscy50OjZ8cy5lOmx8cC52Om9mZixzLnQ6NnxzLmU6bC50fHAudjpvZmYscy50OjZ8cy5lOmwudC5mfHAudjpvZmYscy50OjZ8cy5lOmwudC5zfHAudjpvZmYscy50OjZ8cy5lOmwuaXxwLnY6b2Zm';
          $googlemap_style = '[
              {
                  "featureType": "all",
                  "elementType": "all",
                  "stylers": [
                      {
                          "visibility": "on"
                      }
                  ]
              },
              {
                  "featureType": "all",
                  "elementType": "labels",
                  "stylers": [
                      {
                          "visibility": "off"
                      },
                      {
                          "saturation": "-100"
                      }
                  ]
              },
              {
                  "featureType": "all",
                  "elementType": "labels.text.fill",
                  "stylers": [
                      {
                          "saturation": 36
                      },
                      {
                          "color": "#000000"
                      },
                      {
                          "lightness": 40
                      },
                      {
                          "visibility": "off"
                      }
                  ]
              },
              {
                  "featureType": "all",
                  "elementType": "labels.text.stroke",
                  "stylers": [
                      {
                          "visibility": "off"
                      },
                      {
                          "color": "#000000"
                      },
                      {
                          "lightness": 16
                      }
                  ]
              },
              {
                  "featureType": "all",
                  "elementType": "labels.icon",
                  "stylers": [
                      {
                          "visibility": "off"
                      }
                  ]
              },
              {
                  "featureType": "administrative",
                  "elementType": "geometry.fill",
                  "stylers": [
                      {
                          "color": "#000000"
                      },
                      {
                          "lightness": 20
                      }
                  ]
              },
              {
                  "featureType": "administrative",
                  "elementType": "geometry.stroke",
                  "stylers": [
                      {
                          "color": "#000000"
                      },
                      {
                          "lightness": 17
                      },
                      {
                          "weight": 1.2
                      }
                  ]
              },
              {
                  "featureType": "landscape",
                  "elementType": "geometry",
                  "stylers": [
                      {
                          "color": "#000000"
                      },
                      {
                          "lightness": 20
                      }
                  ]
              },
              {
                  "featureType": "landscape",
                  "elementType": "geometry.fill",
                  "stylers": [
                      {
                          "color": "#4d6059"
                      }
                  ]
              },
              {
                  "featureType": "landscape",
                  "elementType": "geometry.stroke",
                  "stylers": [
                      {
                          "color": "#4d6059"
                      }
                  ]
              },
              {
                  "featureType": "landscape.natural",
                  "elementType": "geometry.fill",
                  "stylers": [
                      {
                          "color": "#4d6059"
                      }
                  ]
              },
              {
                  "featureType": "poi",
                  "elementType": "geometry",
                  "stylers": [
                      {
                          "lightness": 21
                      }
                  ]
              },
              {
                  "featureType": "poi",
                  "elementType": "geometry.fill",
                  "stylers": [
                      {
                          "color": "#4d6059"
                      }
                  ]
              },
              {
                  "featureType": "poi",
                  "elementType": "geometry.stroke",
                  "stylers": [
                      {
                          "color": "#4d6059"
                      }
                  ]
              },
              {
                  "featureType": "road",
                  "elementType": "geometry",
                  "stylers": [
                      {
                          "visibility": "on"
                      },
                      {
                          "color": "#7f8d89"
                      }
                  ]
              },
              {
                  "featureType": "road",
                  "elementType": "geometry.fill",
                  "stylers": [
                      {
                          "color": "#7f8d89"
                      }
                  ]
              },
              {
                  "featureType": "road.highway",
                  "elementType": "geometry.fill",
                  "stylers": [
                      {
                          "color": "#7f8d89"
                      },
                      {
                          "lightness": 17
                      }
                  ]
              },
              {
                  "featureType": "road.highway",
                  "elementType": "geometry.stroke",
                  "stylers": [
                      {
                          "color": "#7f8d89"
                      },
                      {
                          "lightness": 29
                      },
                      {
                          "weight": 0.2
                      }
                  ]
              },
              {
                  "featureType": "road.arterial",
                  "elementType": "geometry",
                  "stylers": [
                      {
                          "color": "#000000"
                      },
                      {
                          "lightness": 18
                      }
                  ]
              },
              {
                  "featureType": "road.arterial",
                  "elementType": "geometry.fill",
                  "stylers": [
                      {
                          "color": "#7f8d89"
                      }
                  ]
              },
              {
                  "featureType": "road.arterial",
                  "elementType": "geometry.stroke",
                  "stylers": [
                      {
                          "color": "#7f8d89"
                      }
                  ]
              },
              {
                  "featureType": "road.local",
                  "elementType": "geometry",
                  "stylers": [
                      {
                          "color": "#000000"
                      },
                      {
                          "lightness": 16
                      }
                  ]
              },
              {
                  "featureType": "road.local",
                  "elementType": "geometry.fill",
                  "stylers": [
                      {
                          "color": "#7f8d89"
                      }
                  ]
              },
              {
                  "featureType": "road.local",
                  "elementType": "geometry.stroke",
                  "stylers": [
                      {
                          "color": "#7f8d89"
                      }
                  ]
              },
              {
                  "featureType": "transit",
                  "elementType": "geometry",
                  "stylers": [
                      {
                          "color": "#000000"
                      },
                      {
                          "lightness": 19
                      }
                  ]
              },
              {
                  "featureType": "water",
                  "elementType": "all",
                  "stylers": [
                      {
                          "color": "#2b3638"
                      },
                      {
                          "visibility": "on"
                      }
                  ]
              },
              {
                  "featureType": "water",
                  "elementType": "geometry",
                  "stylers": [
                      {
                          "color": "#2b3638"
                      },
                      {
                          "lightness": 17
                      }
                  ]
              },
              {
                  "featureType": "water",
                  "elementType": "geometry.fill",
                  "stylers": [
                      {
                          "color": "#24282b"
                      }
                  ]
              },
              {
                  "featureType": "water",
                  "elementType": "geometry.stroke",
                  "stylers": [
                      {
                          "color": "#24282b"
                      }
                  ]
              },
              {
                  "featureType": "water",
                  "elementType": "labels",
                  "stylers": [
                      {
                          "visibility": "off"
                      }
                  ]
              },
              {
                  "featureType": "water",
                  "elementType": "labels.text",
                  "stylers": [
                      {
                          "visibility": "off"
                      }
                  ]
              },
              {
                  "featureType": "water",
                  "elementType": "labels.text.fill",
                  "stylers": [
                      {
                          "visibility": "off"
                      }
                  ]
              },
              {
                  "featureType": "water",
                  "elementType": "labels.text.stroke",
                  "stylers": [
                      {
                          "visibility": "off"
                      }
                  ]
              },
              {
                  "featureType": "water",
                  "elementType": "labels.icon",
                  "stylers": [
                      {
                          "visibility": "off"
                      }
                  ]
              }]';
          break;

        case "grass-is-greener": //Grass is greener
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcC5zOi0xMDAscy50OjZ8cy5lOmcuZnxwLmM6I2ZmMDA5OWRkLHMuZTpsfHAudjpvZmYscy50OjQwfHMuZTpnLmZ8cC5jOiNmZmFhZGQ1NSxzLnQ6NDl8cy5lOmx8cC52Om9uLHMudDo1MHxzLmU6bC50fHAudjpvbixzLnQ6NTF8cy5lOmwudHxwLnY6b24';
        	$googlemap_style = '[{"stylers":[{"saturation":-100}]},{"featureType":"water","elementType":"geometry.fill","stylers":[{"color":"#0099dd"}]},{"elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"poi.park","elementType":"geometry.fill","stylers":[{"color":"#aadd55"}]},{"featureType":"road.highway","elementType":"labels","stylers":[{"visibility":"on"}]},{"featureType":"road.arterial","elementType":"labels.text","stylers":[{"visibility":"on"}]},{"featureType":"road.local","elementType":"labels.text","stylers":[{"visibility":"on"}]},{}]';
        	break;

        case "sin-city": //Sin City
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcy5lOmx8cC52Om9uLHMuZTpsLnQuZnxwLnM6MzZ8cC5jOiNmZjAwMDAwMHxwLmw6NDAscy5lOmwudC5zfHAudjpvbnxwLmM6I2ZmMDAwMDAwfHAubDoxNixzLmU6bC5pfHAudjpvZmYscy50OjF8cy5lOmcuZnxwLmM6I2ZmMDAwMDAwfHAubDoyMCxzLnQ6MXxzLmU6Zy5zfHAuYzojZmYwMDAwMDB8cC5sOjE3fHAudzoxLjIscy50OjE5fHMuZTpsLnQuZnxwLmM6I2ZmYzRjNGM0LHMudDoyMHxzLmU6bC50LmZ8cC5jOiNmZjcwNzA3MCxzLnQ6NXxzLmU6Z3xwLmM6I2ZmMDAwMDAwfHAubDoyMCxzLnQ6MnxzLmU6Z3xwLmM6I2ZmMDAwMDAwfHAubDoyMXxwLnY6b24scy50OjMzfHMuZTpnfHAudjpvbixzLnQ6NDl8cy5lOmcuZnxwLmM6I2ZmYmUyMDI2fHAubDowfHAudjpvbixzLnQ6NDl8cy5lOmcuc3xwLnY6b2ZmLHMudDo0OXxzLmU6bC50LmZ8cC52Om9mZixzLnQ6NDl8cy5lOmwudC5zfHAudjpvZmZ8cC5oOiNmZjAwMGEscy50OjUwfHMuZTpnfHAuYzojZmYwMDAwMDB8cC5sOjE4LHMudDo1MHxzLmU6Zy5mfHAuYzojZmY1NzU3NTcscy50OjUwfHMuZTpsLnQuZnxwLmM6I2ZmZmZmZmZmLHMudDo1MHxzLmU6bC50LnN8cC5jOiNmZjJjMmMyYyxzLnQ6NTF8cy5lOmd8cC5jOiNmZjAwMDAwMHxwLmw6MTYscy50OjUxfHMuZTpsLnQuZnxwLmM6I2ZmOTk5OTk5LHMudDo1MXxzLmU6bC50LnN8cC5zOi01MixzLnQ6NHxzLmU6Z3xwLmM6I2ZmMDAwMDAwfHAubDoxOSxzLnQ6NnxzLmU6Z3xwLmM6I2ZmMDAwMDAwfHAubDoxNw';
        	$googlemap_style = '[{"featureType":"all","elementType":"labels","stylers":[{"visibility":"on"}]},{"featureType":"all","elementType":"labels.text.fill","stylers":[{"saturation":36},{"color":"#000000"},{"lightness":40}]},{"featureType":"all","elementType":"labels.text.stroke","stylers":[{"visibility":"on"},{"color":"#000000"},{"lightness":16}]},{"featureType":"all","elementType":"labels.icon","stylers":[{"visibility":"off"}]},{"featureType":"administrative","elementType":"geometry.fill","stylers":[{"color":"#000000"},{"lightness":20}]},{"featureType":"administrative","elementType":"geometry.stroke","stylers":[{"color":"#000000"},{"lightness":17},{"weight":1.2}]},{"featureType":"administrative.locality","elementType":"labels.text.fill","stylers":[{"color":"#c4c4c4"}]},{"featureType":"administrative.neighborhood","elementType":"labels.text.fill","stylers":[{"color":"#707070"}]},{"featureType":"landscape","elementType":"geometry","stylers":[{"color":"#000000"},{"lightness":20}]},{"featureType":"poi","elementType":"geometry","stylers":[{"color":"#000000"},{"lightness":21},{"visibility":"on"}]},{"featureType":"poi.business","elementType":"geometry","stylers":[{"visibility":"on"}]},{"featureType":"road.highway","elementType":"geometry.fill","stylers":[{"color":"#be2026"},{"lightness":"0"},{"visibility":"on"}]},{"featureType":"road.highway","elementType":"geometry.stroke","stylers":[{"visibility":"off"}]},{"featureType":"road.highway","elementType":"labels.text.fill","stylers":[{"visibility":"off"}]},{"featureType":"road.highway","elementType":"labels.text.stroke","stylers":[{"visibility":"off"},{"hue":"#ff000a"}]},{"featureType":"road.arterial","elementType":"geometry","stylers":[{"color":"#000000"},{"lightness":18}]},{"featureType":"road.arterial","elementType":"geometry.fill","stylers":[{"color":"#575757"}]},{"featureType":"road.arterial","elementType":"labels.text.fill","stylers":[{"color":"#ffffff"}]},{"featureType":"road.arterial","elementType":"labels.text.stroke","stylers":[{"color":"#2c2c2c"}]},{"featureType":"road.local","elementType":"geometry","stylers":[{"color":"#000000"},{"lightness":16}]},{"featureType":"road.local","elementType":"labels.text.fill","stylers":[{"color":"#999999"}]},{"featureType":"road.local","elementType":"labels.text.stroke","stylers":[{"saturation":"-52"}]},{"featureType":"transit","elementType":"geometry","stylers":[{"color":"#000000"},{"lightness":19}]},{"featureType":"water","elementType":"geometry","stylers":[{"color":"#000000"},{"lightness":17}]}]';
        	break;

        case "the-propia-effect": //The Propia Effect
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcy50OjV8cC52OnNpbXBsaWZpZWR8cC5jOiNmZjJiM2Y1N3xwLnc6MC4xLHMudDoxfHAudjpvbnxwLmg6I2ZmMDAwMHxwLnc6MC40fHAuYzojZmZmZmZmZmYscy50OjQ5fHMuZTpsLnR8cC53OjEuM3xwLmM6I2ZmRkZGRkZGLHMudDo0OXxzLmU6Z3xwLmM6I2ZmZjU1Zjc3fHAudzozLHMudDo1MHxzLmU6Z3xwLmM6I2ZmZjU1Zjc3fHAudzoxLjEscy50OjUxfHMuZTpnfHAuYzojZmZmNTVmNzd8cC53OjAuNCxzLnQ6NDl8cy5lOmx8cC53OjAuOHxwLmM6I2ZmZmZmZmZmfHAudjpvbixzLnQ6NTF8cy5lOmx8cC52Om9mZixzLnQ6NTB8cy5lOmx8cC5jOiNmZmZmZmZmZnxwLnc6MC43LHMudDoyfHMuZTpsfHAudjpvZmYscy50OjJ8cC5jOiNmZjZjNWI3YixzLnQ6NnxwLmM6I2ZmZjNiMTkxLHMudDo2NXxwLnY6b24';
        	$googlemap_style = '[{"featureType":"landscape","stylers":[{"visibility":"simplified"},{"color":"#2b3f57"},{"weight":0.1}]},{"featureType":"administrative","stylers":[{"visibility":"on"},{"hue":"#ff0000"},{"weight":0.4},{"color":"#ffffff"}]},{"featureType":"road.highway","elementType":"labels.text","stylers":[{"weight":1.3},{"color":"#FFFFFF"}]},{"featureType":"road.highway","elementType":"geometry","stylers":[{"color":"#f55f77"},{"weight":3}]},{"featureType":"road.arterial","elementType":"geometry","stylers":[{"color":"#f55f77"},{"weight":1.1}]},{"featureType":"road.local","elementType":"geometry","stylers":[{"color":"#f55f77"},{"weight":0.4}]},{},{"featureType":"road.highway","elementType":"labels","stylers":[{"weight":0.8},{"color":"#ffffff"},{"visibility":"on"}]},{"featureType":"road.local","elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"road.arterial","elementType":"labels","stylers":[{"color":"#ffffff"},{"weight":0.7}]},{"featureType":"poi","elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"poi","stylers":[{"color":"#6c5b7b"}]},{"featureType":"water","stylers":[{"color":"#f3b191"}]},{"featureType":"transit.line","stylers":[{"visibility":"on"}]}]';
        	break;

        case "snazzy-maps": //Snazzy Maps
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcy50OjZ8cy5lOmd8cC5jOiNmZjMzMzczOSxzLnQ6NXxzLmU6Z3xwLmM6I2ZmMmVjYzcxLHMudDoyfHAuYzojZmYyZWNjNzF8cC5sOi03LHMudDo0OXxzLmU6Z3xwLmM6I2ZmMmVjYzcxfHAubDotMjgscy50OjUwfHMuZTpnfHAuYzojZmYyZWNjNzF8cC52Om9ufHAubDotMTUscy50OjUxfHMuZTpnfHAuYzojZmYyZWNjNzF8cC5sOi0xOCxzLmU6bC50LmZ8cC5jOiNmZmZmZmZmZixzLmU6bC50LnN8cC52Om9mZixzLnQ6NHxzLmU6Z3xwLmM6I2ZmMmVjYzcxfHAubDotMzQscy50OjF8cy5lOmd8cC52Om9ufHAuYzojZmYzMzM3Mzl8cC53OjAuOCxzLnQ6NDB8cC5jOiNmZjJlY2M3MSxzLnQ6M3xzLmU6Zy5zfHAuYzojZmYzMzM3Mzl8cC53OjAuM3xwLmw6MTA';
        	$googlemap_style = '[{"featureType":"water","elementType":"geometry","stylers":[{"color":"#333739"}]},{"featureType":"landscape","elementType":"geometry","stylers":[{"color":"#2ecc71"}]},{"featureType":"poi","stylers":[{"color":"#2ecc71"},{"lightness":-7}]},{"featureType":"road.highway","elementType":"geometry","stylers":[{"color":"#2ecc71"},{"lightness":-28}]},{"featureType":"road.arterial","elementType":"geometry","stylers":[{"color":"#2ecc71"},{"visibility":"on"},{"lightness":-15}]},{"featureType":"road.local","elementType":"geometry","stylers":[{"color":"#2ecc71"},{"lightness":-18}]},{"elementType":"labels.text.fill","stylers":[{"color":"#ffffff"}]},{"elementType":"labels.text.stroke","stylers":[{"visibility":"off"}]},{"featureType":"transit","elementType":"geometry","stylers":[{"color":"#2ecc71"},{"lightness":-34}]},{"featureType":"administrative","elementType":"geometry","stylers":[{"visibility":"on"},{"color":"#333739"},{"weight":0.8}]},{"featureType":"poi.park","stylers":[{"color":"#2ecc71"}]},{"featureType":"road","elementType":"geometry.stroke","stylers":[{"color":"#333739"},{"weight":0.3},{"lightness":10}]}]';
        	break;

        case "light-green": //Light Green
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcC5oOiNiYWY0YzR8cC5zOjEwLHMudDo2fHAuYzojZmZlZmZlZmQscy5lOmx8cC52Om9mZixzLnQ6MXxzLmU6bHxwLnY6b24scy50OjN8cC52Om9mZixzLnQ6NHxwLnY6b2Zm';
        	$googlemap_style = '[{"stylers":[{"hue":"#baf4c4"},{"saturation":10}]},{"featureType":"water","stylers":[{"color":"#effefd"}]},{"featureType":"all","elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"administrative","elementType":"labels","stylers":[{"visibility":"on"}]},{"featureType":"road","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"transit","elementType":"all","stylers":[{"visibility":"off"}]}]';
        	break;

        case "flat-green": //Flat green
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcC5oOiNiYmZmMDB8cC53OjAuNXxwLmc6MC41LHMuZTpsfHAudjpvZmYscy50OjgyfHAuYzojZmZhNGNjNDgscy50OjN8cy5lOmd8cC5jOiNmZmZmZmZmZnxwLnY6b258cC53OjEscy50OjF8cy5lOmx8cC52Om9uLHMudDo0OXxzLmU6bHxwLnY6c2ltcGxpZmllZHxwLmc6MS4xNHxwLnM6LTE4LHMudDo3ODV8cy5lOmx8cC5zOjMwfHAuZzowLjc2LHMudDo1MXxwLnY6c2ltcGxpZmllZHxwLnc6MC40fHAubDotOCxzLnQ6NnxwLmM6I2ZmNGFhZWNjLHMudDo4MXxwLmM6I2ZmNzE4ZTMyLHMudDozM3xwLnM6Njh8cC5sOi02MSxzLnQ6MTl8cy5lOmwudC5zfHAudzoyLjd8cC5jOiNmZmY0ZjllOCxzLnQ6Nzg1fHMuZTpnLnN8cC53OjEuNXxwLmM6I2ZmZTUzMDEzfHAuczotNDJ8cC5sOjI4';
        	$googlemap_style = '[{"stylers":[{"hue":"#bbff00"},{"weight":0.5},{"gamma":0.5}]},{"elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"landscape.natural","stylers":[{"color":"#a4cc48"}]},{"featureType":"road","elementType":"geometry","stylers":[{"color":"#ffffff"},{"visibility":"on"},{"weight":1}]},{"featureType":"administrative","elementType":"labels","stylers":[{"visibility":"on"}]},{"featureType":"road.highway","elementType":"labels","stylers":[{"visibility":"simplified"},{"gamma":1.14},{"saturation":-18}]},{"featureType":"road.highway.controlled_access","elementType":"labels","stylers":[{"saturation":30},{"gamma":0.76}]},{"featureType":"road.local","stylers":[{"visibility":"simplified"},{"weight":0.4},{"lightness":-8}]},{"featureType":"water","stylers":[{"color":"#4aaecc"}]},{"featureType":"landscape.man_made","stylers":[{"color":"#718e32"}]},{"featureType":"poi.business","stylers":[{"saturation":68},{"lightness":-61}]},{"featureType":"administrative.locality","elementType":"labels.text.stroke","stylers":[{"weight":2.7},{"color":"#f4f9e8"}]},{"featureType":"road.highway.controlled_access","elementType":"geometry.stroke","stylers":[{"weight":1.5},{"color":"#e53013"},{"saturation":-42},{"lightness":28}]}]';
        	break;

        case "dark-electric": //Dark Electric
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcy5lOmwudC5mfHAuczozNnxwLmM6I2ZmMDAwMDAwfHAubDo0MCxzLmU6bC50LnN8cC52Om9ufHAuYzojZmYwMDAwMDB8cC5sOjE2LHMuZTpsLml8cC52Om9mZixzLnQ6MXxzLmU6Zy5mfHAuYzojZmYwMDAwMDB8cC5sOjIwLHMudDoxfHMuZTpnLnN8cC5jOiNmZjAwMDAwMHxwLmw6MTd8cC53OjEuMixzLnQ6MTl8cy5lOmx8cC52Om9mZixzLnQ6MjB8cy5lOmx8cC52OnNpbXBsaWZpZWQscy50OjIwfHMuZTpsLnQuZnxwLmw6MTcscy50OjIxfHMuZTpsfHAudjpvZmYscy50OjV8cy5lOmd8cC5jOiNmZjAwMDAwMHxwLmw6MjAscy50OjV8cy5lOmx8cC52Om9uLHMudDo4MXxzLmU6bHxwLnY6b2ZmLHMudDo4MXxzLmU6bC50fHAudjpvZmYscy50OjgyfHMuZTpsfHAudjpvbixzLnQ6MnxzLmU6Z3xwLmM6I2ZmMDAwMDAwfHAubDoyMSxzLnQ6MnxzLmU6bHxwLnY6b2ZmLHMudDozfHMuZTpsfHAudjpzaW1wbGlmaWVkLHMudDo0OXxzLmU6Z3xwLnY6b258cC5jOiNmZmZmNDcwMCxzLnQ6NDl8cy5lOmcuZnxwLmw6MTcscy50OjQ5fHMuZTpnLnN8cC5jOiNmZjAwMDAwMHxwLmw6Mjl8cC53OjAuMixzLnQ6NDl8cy5lOmx8cC5pbDp0cnVlfHAudjpvZmYscy50Ojc4NXxzLmU6Zy5mfHAuYzojZmYzYjNiM2Iscy50OjUwfHMuZTpnfHAuYzojZmYwMDAwMDB8cC5sOjE4LHMudDo1MHxzLmU6Zy5mfHAuYzojZmZmZjQ3MDB8cC5sOjM5fHAuZzowLjQzfHAuczotNDcscy50OjUwfHMuZTpsfHAudjpvZmYscy50OjUxfHMuZTpnfHAuYzojZmYwMDAwMDB8cC5sOjE2LHMudDo1MXxzLmU6Zy5zfHAuYzojZmY1NTU1NTUscy50OjUxfHMuZTpsfHAudjpvZmYscy50OjR8cy5lOmd8cC5jOiNmZjAwMDAwMHxwLmw6MTkscy50OjZ8cy5lOmd8cC5jOiNmZjAwMDAwMHxwLmw6MTc';
        	$googlemap_style = '[{"featureType":"all","elementType":"labels.text.fill","stylers":[{"saturation":36},{"color":"#000000"},{"lightness":40}]},{"featureType":"all","elementType":"labels.text.stroke","stylers":[{"visibility":"on"},{"color":"#000000"},{"lightness":16}]},{"featureType":"all","elementType":"labels.icon","stylers":[{"visibility":"off"}]},{"featureType":"administrative","elementType":"geometry.fill","stylers":[{"color":"#000000"},{"lightness":20}]},{"featureType":"administrative","elementType":"geometry.stroke","stylers":[{"color":"#000000"},{"lightness":17},{"weight":1.2}]},{"featureType":"administrative.locality","elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"administrative.neighborhood","elementType":"labels","stylers":[{"visibility":"simplified"}]},{"featureType":"administrative.neighborhood","elementType":"labels.text.fill","stylers":[{"lightness":"17"}]},{"featureType":"administrative.land_parcel","elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"landscape","elementType":"geometry","stylers":[{"color":"#000000"},{"lightness":20}]},{"featureType":"landscape","elementType":"labels","stylers":[{"visibility":"on"}]},{"featureType":"landscape.man_made","elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"landscape.man_made","elementType":"labels.text","stylers":[{"visibility":"off"}]},{"featureType":"landscape.natural","elementType":"labels","stylers":[{"visibility":"on"}]},{"featureType":"poi","elementType":"geometry","stylers":[{"color":"#000000"},{"lightness":21}]},{"featureType":"poi","elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"road","elementType":"labels","stylers":[{"visibility":"simplified"}]},{"featureType":"road.highway","elementType":"geometry","stylers":[{"visibility":"on"},{"color":"#ff4700"}]},{"featureType":"road.highway","elementType":"geometry.fill","stylers":[{"lightness":17}]},{"featureType":"road.highway","elementType":"geometry.stroke","stylers":[{"color":"#000000"},{"lightness":29},{"weight":0.2}]},{"featureType":"road.highway","elementType":"labels","stylers":[{"invert_lightness":true},{"visibility":"off"}]},{"featureType":"road.highway.controlled_access","elementType":"geometry.fill","stylers":[{"color":"#3b3b3b"}]},{"featureType":"road.arterial","elementType":"geometry","stylers":[{"color":"#000000"},{"lightness":18}]},{"featureType":"road.arterial","elementType":"geometry.fill","stylers":[{"color":"#ff4700"},{"lightness":"39"},{"gamma":"0.43"},{"saturation":"-47"}]},{"featureType":"road.arterial","elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"road.local","elementType":"geometry","stylers":[{"color":"#000000"},{"lightness":16}]},{"featureType":"road.local","elementType":"geometry.stroke","stylers":[{"color":"#555555"}]},{"featureType":"road.local","elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"transit","elementType":"geometry","stylers":[{"color":"#000000"},{"lightness":19}]},{"featureType":"water","elementType":"geometry","stylers":[{"color":"#000000"},{"lightness":17}]}]';
        	break;

        case "two-tone": //Two Tone
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcy5lOmwudC5mfHAuYzojZmZmZmZmZmYscy5lOmwudC5zfHAudjpvZmYscy5lOmwuaXxwLnY6b2ZmLHMudDoxfHMuZTpnLmZ8cC5jOiNmZmM5MzIzYixzLnQ6MXxzLmU6Zy5zfHAuYzojZmZjOTMyM2J8cC53OjEuMixzLnQ6MTl8cy5lOmcuZnxwLmw6LTEscy50OjIwfHMuZTpsLnQuZnxwLmw6MHxwLnM6MCxzLnQ6MjB8cy5lOmwudC5zfHAudzowLjAxLHMudDoyMXxzLmU6bC50LnN8cC53OjAuMDEscy50OjV8cy5lOmd8cC5jOiNmZmM5MzIzYixzLnQ6MnxzLmU6Z3xwLmM6I2ZmOTkyODJmLHMudDozfHMuZTpnLnN8cC52Om9mZixzLnQ6NDl8cy5lOmcuZnxwLmM6I2ZmOTkyODJmLHMudDo3ODV8cy5lOmcuc3xwLmM6I2ZmOTkyODJmLHMudDo1MHxzLmU6Z3xwLmM6I2ZmOTkyODJmLHMudDo1MXxzLmU6Z3xwLmM6I2ZmOTkyODJmLHMudDo0fHMuZTpnfHAuYzojZmY5OTI4MmYscy50OjZ8cy5lOmd8cC5jOiNmZjA5MDIyOA';
        	$googlemap_style = '[{"featureType":"all","elementType":"labels.text.fill","stylers":[{"color":"#ffffff"}]},{"featureType":"all","elementType":"labels.text.stroke","stylers":[{"visibility":"off"}]},{"featureType":"all","elementType":"labels.icon","stylers":[{"visibility":"off"}]},{"featureType":"administrative","elementType":"geometry.fill","stylers":[{"color":"#c9323b"}]},{"featureType":"administrative","elementType":"geometry.stroke","stylers":[{"color":"#c9323b"},{"weight":1.2}]},{"featureType":"administrative.locality","elementType":"geometry.fill","stylers":[{"lightness":"-1"}]},{"featureType":"administrative.neighborhood","elementType":"labels.text.fill","stylers":[{"lightness":"0"},{"saturation":"0"}]},{"featureType":"administrative.neighborhood","elementType":"labels.text.stroke","stylers":[{"weight":"0.01"}]},{"featureType":"administrative.land_parcel","elementType":"labels.text.stroke","stylers":[{"weight":"0.01"}]},{"featureType":"landscape","elementType":"geometry","stylers":[{"color":"#c9323b"}]},{"featureType":"poi","elementType":"geometry","stylers":[{"color":"#99282f"}]},{"featureType":"road","elementType":"geometry.stroke","stylers":[{"visibility":"off"}]},{"featureType":"road.highway","elementType":"geometry.fill","stylers":[{"color":"#99282f"}]},{"featureType":"road.highway.controlled_access","elementType":"geometry.stroke","stylers":[{"color":"#99282f"}]},{"featureType":"road.arterial","elementType":"geometry","stylers":[{"color":"#99282f"}]},{"featureType":"road.local","elementType":"geometry","stylers":[{"color":"#99282f"}]},{"featureType":"transit","elementType":"geometry","stylers":[{"color":"#99282f"}]},{"featureType":"water","elementType":"geometry","stylers":[{"color":"#090228"}]}]';
        	break;

        case "modest": //Modest
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcy5lOmd8cC5jOiNmZjIwMmMzZSxzLmU6bC50LmZ8cC5nOjAuMDF8cC5sOjIwfHAudzoxLjM5fHAuYzojZmZmZmZmZmYscy5lOmwudC5zfHAudzowLjk2fHAuczo5fHAudjpvbnxwLmM6I2ZmMDAwMDAwLHMuZTpsLml8cC52Om9mZixzLnQ6NXxzLmU6Z3xwLmw6MzB8cC5zOjl8cC5jOiNmZjI5NDQ2YixzLnQ6MnxzLmU6Z3xwLnM6MjAscy50OjQwfHMuZTpnfHAubDoyMHxwLnM6LTIwLHMudDozfHMuZTpnfHAubDoxMHxwLnM6LTMwLHMudDozfHMuZTpnLmZ8cC5jOiNmZjE5M2E1NSxzLnQ6M3xzLmU6Zy5zfHAuczoyNXxwLmw6MjV8cC53OjAuMDEscy50OjZ8cC5sOi0yMA';
        	$googlemap_style = '[{"featureType":"all","elementType":"geometry","stylers":[{"color":"#202c3e"}]},{"featureType":"all","elementType":"labels.text.fill","stylers":[{"gamma":0.01},{"lightness":20},{"weight":"1.39"},{"color":"#ffffff"}]},{"featureType":"all","elementType":"labels.text.stroke","stylers":[{"weight":"0.96"},{"saturation":"9"},{"visibility":"on"},{"color":"#000000"}]},{"featureType":"all","elementType":"labels.icon","stylers":[{"visibility":"off"}]},{"featureType":"landscape","elementType":"geometry","stylers":[{"lightness":30},{"saturation":"9"},{"color":"#29446b"}]},{"featureType":"poi","elementType":"geometry","stylers":[{"saturation":20}]},{"featureType":"poi.park","elementType":"geometry","stylers":[{"lightness":20},{"saturation":-20}]},{"featureType":"road","elementType":"geometry","stylers":[{"lightness":10},{"saturation":-30}]},{"featureType":"road","elementType":"geometry.fill","stylers":[{"color":"#193a55"}]},{"featureType":"road","elementType":"geometry.stroke","stylers":[{"saturation":25},{"lightness":25},{"weight":"0.01"}]},{"featureType":"water","elementType":"all","stylers":[{"lightness":-20}]}]';
        	break;

        case "flat-colors": //Flat Colors
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcy50OjJ8cy5lOmwudC5mfHAuYzojZmY3NDc0NzR8cC5sOjIzLHMudDozN3xzLmU6Zy5mfHAuYzojZmZmMzhlYjAscy50OjM0fHMuZTpnLmZ8cC5jOiNmZmNlZDdkYixzLnQ6MzZ8cy5lOmcuZnxwLmM6I2ZmZmZhNWE4LHMudDo0MHxzLmU6Zy5mfHAuYzojZmZjN2U1Yzgscy50OjM4fHMuZTpnLmZ8cC5jOiNmZmQ2Y2JjNyxzLnQ6MzV8cy5lOmcuZnxwLmM6I2ZmYzRjOWU4LHMudDozOXxzLmU6Zy5mfHAuYzojZmZiMWVhZjEscy50OjN8cy5lOmd8cC5sOjEwMCxzLnQ6M3xzLmU6bHxwLnY6b2ZmfHAubDoxMDAscy50OjQ5fHMuZTpnLmZ8cC5jOiNmZmZmZDRhNSxzLnQ6NTB8cy5lOmcuZnxwLmM6I2ZmZmZlOWQyLHMudDo1MXxwLnY6c2ltcGxpZmllZCxzLnQ6NTF8cy5lOmcuZnxwLnc6My4wMCxzLnQ6NTF8cy5lOmcuc3xwLnc6MC4zMCxzLnQ6NTF8cy5lOmwudHxwLnY6b24scy50OjUxfHMuZTpsLnQuZnxwLmM6I2ZmNzQ3NDc0fHAubDozNixzLnQ6NTF8cy5lOmwudC5zfHAuYzojZmZlOWU1ZGN8cC5sOjMwLHMudDo2NXxzLmU6Z3xwLnY6b258cC5sOjEwMCxzLnQ6NnxwLmM6I2ZmZDJlN2Y3';
        	$googlemap_style = '[{"featureType":"poi","elementType":"labels.text.fill","stylers":[{"color":"#747474"},{"lightness":"23"}]},{"featureType":"poi.attraction","elementType":"geometry.fill","stylers":[{"color":"#f38eb0"}]},{"featureType":"poi.government","elementType":"geometry.fill","stylers":[{"color":"#ced7db"}]},{"featureType":"poi.medical","elementType":"geometry.fill","stylers":[{"color":"#ffa5a8"}]},{"featureType":"poi.park","elementType":"geometry.fill","stylers":[{"color":"#c7e5c8"}]},{"featureType":"poi.place_of_worship","elementType":"geometry.fill","stylers":[{"color":"#d6cbc7"}]},{"featureType":"poi.school","elementType":"geometry.fill","stylers":[{"color":"#c4c9e8"}]},{"featureType":"poi.sports_complex","elementType":"geometry.fill","stylers":[{"color":"#b1eaf1"}]},{"featureType":"road","elementType":"geometry","stylers":[{"lightness":"100"}]},{"featureType":"road","elementType":"labels","stylers":[{"visibility":"off"},{"lightness":"100"}]},{"featureType":"road.highway","elementType":"geometry.fill","stylers":[{"color":"#ffd4a5"}]},{"featureType":"road.arterial","elementType":"geometry.fill","stylers":[{"color":"#ffe9d2"}]},{"featureType":"road.local","elementType":"all","stylers":[{"visibility":"simplified"}]},{"featureType":"road.local","elementType":"geometry.fill","stylers":[{"weight":"3.00"}]},{"featureType":"road.local","elementType":"geometry.stroke","stylers":[{"weight":"0.30"}]},{"featureType":"road.local","elementType":"labels.text","stylers":[{"visibility":"on"}]},{"featureType":"road.local","elementType":"labels.text.fill","stylers":[{"color":"#747474"},{"lightness":"36"}]},{"featureType":"road.local","elementType":"labels.text.stroke","stylers":[{"color":"#e9e5dc"},{"lightness":"30"}]},{"featureType":"transit.line","elementType":"geometry","stylers":[{"visibility":"on"},{"lightness":"100"}]},{"featureType":"water","elementType":"all","stylers":[{"color":"#d2e7f7"}]}]';
        	break;

        case "red-alert": //Red Alert
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcy50OjZ8cy5lOmd8cC5jOiNmZmZmZGZhNixzLnQ6NXxzLmU6Z3xwLmM6I2ZmYjUyMTI3LHMudDoyfHMuZTpnfHAuYzojZmZjNTUzMWIscy50OjQ5fHMuZTpnLmZ8cC5jOiNmZjc0MDAxYnxwLmw6LTEwLHMudDo0OXxzLmU6Zy5zfHAuYzojZmZkYTNjM2Mscy50OjUwfHMuZTpnLmZ8cC5jOiNmZjc0MDAxYixzLnQ6NTB8cy5lOmcuc3xwLmM6I2ZmZGEzYzNjLHMudDo1MXxzLmU6Zy5mfHAuYzojZmY5OTBjMTkscy5lOmwudC5mfHAuYzojZmZmZmZmZmYscy5lOmwudC5zfHAuYzojZmY3NDAwMWJ8cC5sOi04LHMudDo0fHMuZTpnfHAuYzojZmY2YTBkMTB8cC52Om9uLHMudDoxfHMuZTpnfHAuYzojZmZmZmRmYTZ8cC53OjAuNCxzLnQ6NTF8cy5lOmcuc3xwLnY6b2Zm';
        	$googlemap_style = '[{"featureType":"water","elementType":"geometry","stylers":[{"color":"#ffdfa6"}]},{"featureType":"landscape","elementType":"geometry","stylers":[{"color":"#b52127"}]},{"featureType":"poi","elementType":"geometry","stylers":[{"color":"#c5531b"}]},{"featureType":"road.highway","elementType":"geometry.fill","stylers":[{"color":"#74001b"},{"lightness":-10}]},{"featureType":"road.highway","elementType":"geometry.stroke","stylers":[{"color":"#da3c3c"}]},{"featureType":"road.arterial","elementType":"geometry.fill","stylers":[{"color":"#74001b"}]},{"featureType":"road.arterial","elementType":"geometry.stroke","stylers":[{"color":"#da3c3c"}]},{"featureType":"road.local","elementType":"geometry.fill","stylers":[{"color":"#990c19"}]},{"elementType":"labels.text.fill","stylers":[{"color":"#ffffff"}]},{"elementType":"labels.text.stroke","stylers":[{"color":"#74001b"},{"lightness":-8}]},{"featureType":"transit","elementType":"geometry","stylers":[{"color":"#6a0d10"},{"visibility":"on"}]},{"featureType":"administrative","elementType":"geometry","stylers":[{"color":"#ffdfa6"},{"weight":0.4}]},{"featureType":"road.local","elementType":"geometry.stroke","stylers":[{"visibility":"off"}]}]';
        	break;

        case "creamy-red": //Creamy Red
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcy5lOmx8cC52OnNpbXBsaWZpZWQscy5lOmwudHxwLmM6I2ZmNDQ0NDQ0LHMudDoxN3xwLnY6c2ltcGxpZmllZCxzLnQ6MTd8cy5lOmd8cC52OnNpbXBsaWZpZWQscy50OjE4fHAudjpvZmYscy50OjE5fHAudjpzaW1wbGlmaWVkfHAuczotMTAwfHAubDozMCxzLnQ6MjB8cC52Om9mZixzLnQ6MjF8cC52Om9mZixzLnQ6NXxwLnY6c2ltcGxpZmllZHxwLmc6MC4wMHxwLmw6NzQscy50OjV8cy5lOmd8cC5jOiNmZmZmZmZmZixzLnQ6MnxwLnY6b2ZmLHMudDozfHMuZTpnfHAudjpzaW1wbGlmaWVkfHAuYzojZmZmZjAwMDB8cC5zOi0xNXxwLmw6NDB8cC5nOjEuMjUscy50OjN8cy5lOmx8cC52Om9mZixzLnQ6NHxzLmU6bHxwLnY6c2ltcGxpZmllZCxzLnQ6NHxzLmU6bC5pfHAudjpvZmYscy50OjY1fHMuZTpnfHAuYzojZmZmZjAwMDB8cC5sOjgwLHMudDo2NnxzLmU6Z3xwLmM6I2ZmZTVlNWU1LHMudDo2fHMuZTpnfHAuYzojZmZlZmVmZWYscy50OjZ8cy5lOmx8cC52Om9mZg';
        	$googlemap_style = '[{"featureType":"all","elementType":"labels","stylers":[{"visibility":"simplified"}]},{"featureType":"all","elementType":"labels.text","stylers":[{"color":"#444444"}]},{"featureType":"administrative.country","elementType":"all","stylers":[{"visibility":"simplified"}]},{"featureType":"administrative.country","elementType":"geometry","stylers":[{"visibility":"simplified"}]},{"featureType":"administrative.province","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"administrative.locality","elementType":"all","stylers":[{"visibility":"simplified"},{"saturation":"-100"},{"lightness":"30"}]},{"featureType":"administrative.neighborhood","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"administrative.land_parcel","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"landscape","elementType":"all","stylers":[{"visibility":"simplified"},{"gamma":"0.00"},{"lightness":"74"}]},{"featureType":"landscape","elementType":"geometry","stylers":[{"color":"#ffffff"}]},{"featureType":"poi","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"road","elementType":"geometry","stylers":[{"visibility":"simplified"},{"color":"#ff0000"},{"saturation":"-15"},{"lightness":"40"},{"gamma":"1.25"}]},{"featureType":"road","elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"transit","elementType":"labels","stylers":[{"visibility":"simplified"}]},{"featureType":"transit","elementType":"labels.icon","stylers":[{"visibility":"off"}]},{"featureType":"transit.line","elementType":"geometry","stylers":[{"color":"#ff0000"},{"lightness":"80"}]},{"featureType":"transit.station","elementType":"geometry","stylers":[{"color":"#e5e5e5"}]},{"featureType":"water","elementType":"geometry","stylers":[{"color":"#efefef"}]},{"featureType":"water","elementType":"labels","stylers":[{"visibility":"off"}]}]';
        	break;

        case "light-and-dark": //Light and dark
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcy50OjF8cy5lOmwudC5mfHAuYzojZmY0NDQ0NDQscy50OjIxfHAudjpvZmYscy50OjV8cC5jOiNmZmYyZjJmMixzLnQ6ODJ8cC52Om9mZixzLnQ6MnxwLnY6b258cC5jOiNmZjA1MjM2NnxwLnM6LTcwfHAubDo4NSxzLnQ6MnxzLmU6bHxwLnY6c2ltcGxpZmllZHxwLmw6LTUzfHAudzoxLjAwfHAuZzowLjk4LHMudDoyfHMuZTpsLml8cC52OnNpbXBsaWZpZWQscy50OjN8cC5zOi0xMDB8cC5sOjQ1fHAudjpvbixzLnQ6M3xzLmU6Z3xwLnM6LTE4LHMudDozfHMuZTpsfHAudjpvZmYscy50OjQ5fHAudjpvbixzLnQ6NTB8cC52Om9uLHMudDo1MHxzLmU6bC5pfHAudjpvZmYscy50OjUxfHAudjpvbixzLnQ6NHxwLnY6b2ZmLHMudDo2fHAuYzojZmY1NzY3N2F8cC52Om9u';
        	$googlemap_style = '[{"featureType":"administrative","elementType":"labels.text.fill","stylers":[{"color":"#444444"}]},{"featureType":"administrative.land_parcel","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"landscape","elementType":"all","stylers":[{"color":"#f2f2f2"}]},{"featureType":"landscape.natural","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"poi","elementType":"all","stylers":[{"visibility":"on"},{"color":"#052366"},{"saturation":"-70"},{"lightness":"85"}]},{"featureType":"poi","elementType":"labels","stylers":[{"visibility":"simplified"},{"lightness":"-53"},{"weight":"1.00"},{"gamma":"0.98"}]},{"featureType":"poi","elementType":"labels.icon","stylers":[{"visibility":"simplified"}]},{"featureType":"road","elementType":"all","stylers":[{"saturation":-100},{"lightness":45},{"visibility":"on"}]},{"featureType":"road","elementType":"geometry","stylers":[{"saturation":"-18"}]},{"featureType":"road","elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"road.highway","elementType":"all","stylers":[{"visibility":"on"}]},{"featureType":"road.arterial","elementType":"all","stylers":[{"visibility":"on"}]},{"featureType":"road.arterial","elementType":"labels.icon","stylers":[{"visibility":"off"}]},{"featureType":"road.local","elementType":"all","stylers":[{"visibility":"on"}]},{"featureType":"transit","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"water","elementType":"all","stylers":[{"color":"#57677a"},{"visibility":"on"}]}]';
        	break;

        case "uber-2017": //Uber 2017
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcy5lOmwudC5mfHAuYzojZmY3YzkzYTN8cC5sOi0xMCxzLnQ6MTd8cy5lOmd8cC52Om9uLHMudDoxN3xzLmU6Zy5zfHAuYzojZmZhMGE0YTUscy50OjE4fHMuZTpnLnN8cC5jOiNmZjYyODM4ZSxzLnQ6NXxzLmU6Zy5mfHAuYzojZmZkZGUzZTMscy50OjgxfHMuZTpnLnN8cC5jOiNmZjNmNGE1MXxwLnc6MC4zMCxzLnQ6MnxwLnY6c2ltcGxpZmllZCxzLnQ6Mzd8cC52Om9uLHMudDozM3xwLnY6b2ZmLHMudDozNHxwLnY6b2ZmLHMudDo0MHxwLnY6b24scy50OjM4fHAudjpvZmYscy50OjM1fHAudjpvZmYscy50OjM5fHAudjpvZmYscy50OjN8cC5zOi0xMDB8cC52Om9uLHMudDozfHMuZTpnLnN8cC52Om9uLHMudDo0OXxzLmU6Zy5mfHAuYzojZmZiYmNhY2Yscy50OjQ5fHMuZTpnLnN8cC5sOjB8cC5jOiNmZmJiY2FjZnxwLnc6MC41MCxzLnQ6NDl8cy5lOmx8cC52Om9uLHMudDo0OXxzLmU6bC50fHAudjpvbixzLnQ6Nzg1fHMuZTpnLmZ8cC5jOiNmZmZmZmZmZixzLnQ6Nzg1fHMuZTpnLnN8cC5jOiNmZmE5YjRiOCxzLnQ6NTB8cy5lOmwuaXxwLmlsOnRydWV8cC5zOi03fHAubDozfHAuZzoxLjgwfHAudzowLjAxLHMudDo0fHAudjpvZmYscy50OjZ8cy5lOmcuZnxwLmM6I2ZmYTNjN2Rm';
        	$googlemap_style = '[{"featureType":"all","elementType":"labels.text.fill","stylers":[{"color":"#7c93a3"},{"lightness":"-10"}]},{"featureType":"administrative.country","elementType":"geometry","stylers":[{"visibility":"on"}]},{"featureType":"administrative.country","elementType":"geometry.stroke","stylers":[{"color":"#a0a4a5"}]},{"featureType":"administrative.province","elementType":"geometry.stroke","stylers":[{"color":"#62838e"}]},{"featureType":"landscape","elementType":"geometry.fill","stylers":[{"color":"#dde3e3"}]},{"featureType":"landscape.man_made","elementType":"geometry.stroke","stylers":[{"color":"#3f4a51"},{"weight":"0.30"}]},{"featureType":"poi","elementType":"all","stylers":[{"visibility":"simplified"}]},{"featureType":"poi.attraction","elementType":"all","stylers":[{"visibility":"on"}]},{"featureType":"poi.business","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"poi.government","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"poi.park","elementType":"all","stylers":[{"visibility":"on"}]},{"featureType":"poi.place_of_worship","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"poi.school","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"poi.sports_complex","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"road","elementType":"all","stylers":[{"saturation":"-100"},{"visibility":"on"}]},{"featureType":"road","elementType":"geometry.stroke","stylers":[{"visibility":"on"}]},{"featureType":"road.highway","elementType":"geometry.fill","stylers":[{"color":"#bbcacf"}]},{"featureType":"road.highway","elementType":"geometry.stroke","stylers":[{"lightness":"0"},{"color":"#bbcacf"},{"weight":"0.50"}]},{"featureType":"road.highway","elementType":"labels","stylers":[{"visibility":"on"}]},{"featureType":"road.highway","elementType":"labels.text","stylers":[{"visibility":"on"}]},{"featureType":"road.highway.controlled_access","elementType":"geometry.fill","stylers":[{"color":"#ffffff"}]},{"featureType":"road.highway.controlled_access","elementType":"geometry.stroke","stylers":[{"color":"#a9b4b8"}]},{"featureType":"road.arterial","elementType":"labels.icon","stylers":[{"invert_lightness":true},{"saturation":"-7"},{"lightness":"3"},{"gamma":"1.80"},{"weight":"0.01"}]},{"featureType":"transit","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"water","elementType":"geometry.fill","stylers":[{"color":"#a3c7df"}]}]';
        	break;

        case "hints-of-gold": //Hints of Gold
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcy50OjZ8cC5oOiMyNTI1MjV8cC5zOi0xMDB8cC5sOi04MXxwLnY6b24scy50OjV8cC5oOiM2NjY2NjZ8cC5zOi0xMDB8cC5sOi01NXxwLnY6b24scy50OjJ8cy5lOmd8cC5oOiM1NTU1NTV8cC5zOi0xMDB8cC5sOi01N3xwLnY6b24scy50OjN8cC5oOiM3Nzc3Nzd8cC5zOi0xMDB8cC5sOi02fHAudjpvbixzLnQ6MXxwLmg6I2NjOTkwMHxwLnM6MTAwfHAubDotMjJ8cC52Om9uLHMudDo0fHAuaDojNDQ0NDQ0fHAubDotNjR8cC52Om9mZixzLnQ6MnxzLmU6bHxwLmg6IzU1NTU1NXxwLnM6LTEwMHxwLmw6LTU3fHAudjpvZmY';
        	$googlemap_style = '[{"featureType":"water","elementType":"all","stylers":[{"hue":"#252525"},{"saturation":-100},{"lightness":-81},{"visibility":"on"}]},{"featureType":"landscape","elementType":"all","stylers":[{"hue":"#666666"},{"saturation":-100},{"lightness":-55},{"visibility":"on"}]},{"featureType":"poi","elementType":"geometry","stylers":[{"hue":"#555555"},{"saturation":-100},{"lightness":-57},{"visibility":"on"}]},{"featureType":"road","elementType":"all","stylers":[{"hue":"#777777"},{"saturation":-100},{"lightness":-6},{"visibility":"on"}]},{"featureType":"administrative","elementType":"all","stylers":[{"hue":"#cc9900"},{"saturation":100},{"lightness":-22},{"visibility":"on"}]},{"featureType":"transit","elementType":"all","stylers":[{"hue":"#444444"},{"saturation":0},{"lightness":-64},{"visibility":"off"}]},{"featureType":"poi","elementType":"labels","stylers":[{"hue":"#555555"},{"saturation":-100},{"lightness":-57},{"visibility":"off"}]}]';
        	break;

        case "transport-for-london": //Transport for London
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcy5lOmwudHxwLnY6b2ZmLHMuZTpsLml8cC52Om9mZixzLmU6Zy5zfHAudjpvZmYscy50OjZ8cy5lOmcuZnxwLmM6I2ZmMDA5OWNjLHMudDozfHMuZTpnLmZ8cC5jOiNmZjAwMzE0ZSxzLnQ6NjV8cy5lOmcuZnxwLnY6b258cC5jOiNmZmYwZjBmMCxzLnQ6ODF8cC5jOiNmZmFkYmFjOSxzLnQ6ODJ8cC5jOiNmZmFkYjg2NixzLnQ6MnxwLmM6I2ZmZjdjNzQyLHMudDo0MHxwLmM6I2ZmYWRiODY2LHMudDo2NnxzLmU6Zy5mfHAuYzojZmZmZjhkZDMscy50OjY2fHAuYzojZmZmZjhkZDMscy50OjY1fHMuZTpnLmZ8cC52Om9ufHAuYzojZmY4MDgwODA';
        	$googlemap_style = '[{"elementType":"labels.text","stylers":[{"visibility":"off"}]},{"elementType":"labels.icon","stylers":[{"visibility":"off"}]},{"elementType":"geometry.stroke","stylers":[{"visibility":"off"}]},{"featureType":"water","elementType":"geometry.fill","stylers":[{"color":"#0099cc"}]},{"featureType":"road","elementType":"geometry.fill","stylers":[{"color":"#00314e"}]},{"featureType":"transit.line","elementType":"geometry.fill","stylers":[{"visibility":"on"},{"color":"#f0f0f0"}]},{"featureType":"landscape.man_made","stylers":[{"color":"#adbac9"}]},{"featureType":"landscape.natural","stylers":[{"color":"#adb866"}]},{"featureType":"poi","stylers":[{"color":"#f7c742"}]},{"featureType":"poi.park","stylers":[{"color":"#adb866"}]},{"featureType":"transit.station","elementType":"geometry.fill","stylers":[{"color":"#ff8dd3"}]},{"featureType":"transit.station","stylers":[{"color":"#ff8dd3"}]},{"featureType":"transit.line","elementType":"geometry.fill","stylers":[{"visibility":"on"},{"color":"#808080"}]},{}]';
        	break;

        case "old-dry-mud": //Old Dry Mud
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcy50OjV8cC5oOiNGRkFEMDB8cC5zOjUwLjJ8cC5sOi0zNC44fHAuZzoxLHMudDo0OXxwLmg6I0ZGQUQwMHxwLnM6LTE5Ljh8cC5sOi0xLjh8cC5nOjEscy50OjUwfHAuaDojRkZBRDAwfHAuczo3Mi40fHAubDotMzIuNnxwLmc6MSxzLnQ6NTF8cC5oOiNGRkFEMDB8cC5zOjc0LjR8cC5sOi0xOHxwLmc6MSxzLnQ6NnxwLmg6IzAwRkZBNnxwLnM6LTYzLjJ8cC5sOjM4fHAuZzoxLHMudDoyfHAuaDojRkZDMzAwfHAuczo1NC4yfHAubDotMTQuNHxwLmc6MQ';
        	$googlemap_style = '[{"featureType":"landscape","stylers":[{"hue":"#FFAD00"},{"saturation":50.2},{"lightness":-34.8},{"gamma":1}]},{"featureType":"road.highway","stylers":[{"hue":"#FFAD00"},{"saturation":-19.8},{"lightness":-1.8},{"gamma":1}]},{"featureType":"road.arterial","stylers":[{"hue":"#FFAD00"},{"saturation":72.4},{"lightness":-32.6},{"gamma":1}]},{"featureType":"road.local","stylers":[{"hue":"#FFAD00"},{"saturation":74.4},{"lightness":-18},{"gamma":1}]},{"featureType":"water","stylers":[{"hue":"#00FFA6"},{"saturation":-63.2},{"lightness":38},{"gamma":1}]},{"featureType":"poi","stylers":[{"hue":"#FFC300"},{"saturation":54.2},{"lightness":-14.4},{"gamma":1}]}]';
        	break;

        case "neon-world": //Neon World
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcC5zOjEwMHxwLmc6MC42';
        	$googlemap_style = '[{"stylers":[{"saturation":100},{"gamma":0.6}]}]';
        	break;

        case "printable-map": //Printable Map
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcy50OjE3fHMuZTpnfHAudjpzaW1wbGlmaWVkLHMudDo1fHMuZTpnfHAuYzojZmZhYmE0YTQscy50OjJ8cC5jOiNmZjZjNjY2NnxwLnc6MC41MCxzLnQ6MnxzLmU6bHxwLmM6I2ZmMDAwMDAwfHAudzowLjY5LHMudDo1MHxzLmU6bC50fHAuYzojZmYwMDAwMDB8cC53OjAuNTYscy50OjUxfHMuZTpsfHAuYzojZmYwMDAwMDB8cC53OjAuNzU';
        	$googlemap_style = '[{"featureType":"administrative.country","elementType":"geometry","stylers":[{"visibility":"simplified"}]},{"featureType":"landscape","elementType":"geometry","stylers":[{"color":"#aba4a4"}]},{"featureType":"poi","elementType":"all","stylers":[{"color":"#6c6666"},{"weight":"0.50"}]},{"featureType":"poi","elementType":"labels","stylers":[{"color":"#000000"},{"weight":"0.69"}]},{"featureType":"road.arterial","elementType":"labels.text","stylers":[{"color":"#000000"},{"weight":"0.56"}]},{"featureType":"road.local","elementType":"labels","stylers":[{"color":"#000000"},{"weight":"0.75"}]}]';
        	break;

        case "captor": //Captor
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcy50OjZ8cC5jOiNmZjBlMTcxZCxzLnQ6NXxwLmM6I2ZmMWUzMDNkLHMudDozfHAuYzojZmYxZTMwM2Qscy50OjQwfHAuYzojZmYxZTMwM2Qscy50OjR8cC5jOiNmZjE4MjczMXxwLnY6c2ltcGxpZmllZCxzLnQ6MnxzLmU6bC5pfHAuYzojZmZmMGM1MTR8cC52Om9mZixzLnQ6MnxzLmU6bC50LnN8cC5jOiNmZjFlMzAzZHxwLnY6b2ZmLHMudDo0fHMuZTpsLnQuZnxwLmM6I2ZmZTc3ZTI0fHAudjpvZmYscy50OjN8cy5lOmwudC5mfHAuYzojZmY5NGE1YTYscy50OjF8cy5lOmx8cC52OnNpbXBsaWZpZWR8cC5jOiNmZmU4NGMzYyxzLnQ6MnxwLmM6I2ZmZTg0YzNjfHAudjpvZmY';
        	$googlemap_style = '[{"featureType":"water","stylers":[{"color":"#0e171d"}]},{"featureType":"landscape","stylers":[{"color":"#1e303d"}]},{"featureType":"road","stylers":[{"color":"#1e303d"}]},{"featureType":"poi.park","stylers":[{"color":"#1e303d"}]},{"featureType":"transit","stylers":[{"color":"#182731"},{"visibility":"simplified"}]},{"featureType":"poi","elementType":"labels.icon","stylers":[{"color":"#f0c514"},{"visibility":"off"}]},{"featureType":"poi","elementType":"labels.text.stroke","stylers":[{"color":"#1e303d"},{"visibility":"off"}]},{"featureType":"transit","elementType":"labels.text.fill","stylers":[{"color":"#e77e24"},{"visibility":"off"}]},{"featureType":"road","elementType":"labels.text.fill","stylers":[{"color":"#94a5a6"}]},{"featureType":"administrative","elementType":"labels","stylers":[{"visibility":"simplified"},{"color":"#e84c3c"}]},{"featureType":"poi","stylers":[{"color":"#e84c3c"},{"visibility":"off"}]}]';
        	break;

        case "zombie-survival-map": //Zombie Survival Map
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcC52OnNpbXBsaWZpZWR8cC5zOi0xMDB8cC5pbDp0cnVlfHAubDoxMXxwLmc6MS4yNyxzLnQ6MTl8cC52Om9mZixzLnQ6ODF8cC5oOiNmZjAwMDB8cC52OnNpbXBsaWZpZWR8cC5pbDp0cnVlfHAubDotMTB8cC5nOjAuNTR8cC5zOjQ1LHMudDozM3xwLnY6c2ltcGxpZmllZHxwLmg6I2ZmMDAwMHxwLnM6NzV8cC5sOjI0fHAuZzowLjcwfHAuaWw6dHJ1ZSxzLnQ6MzR8cC5oOiNmZjAwMDB8cC52OnNpbXBsaWZpZWR8cC5pbDp0cnVlfHAubDotMjR8cC5nOjAuNTl8cC5zOjU5LHMudDozNnxwLnY6c2ltcGxpZmllZHxwLmlsOnRydWV8cC5oOiNmZjAwMDB8cC5zOjczfHAubDotMjR8cC5nOjAuNTkscy50OjQwfHAubDotNDEscy50OjM1fHAudjpzaW1wbGlmaWVkfHAuaDojZmYwMDAwfHAuaWw6dHJ1ZXxwLnM6NDN8cC5sOi0xNnxwLmc6MC43MyxzLnQ6Mzl8cC5oOiNmZjAwMDB8cC5zOjQzfHAubDotMTF8cC5nOjAuNzN8cC5pbDp0cnVlLHMudDozfHAuczo0NXxwLmw6NTN8cC5nOjAuNjd8cC5pbDp0cnVlfHAuaDojZmYwMDAwfHAudjpzaW1wbGlmaWVkLHMudDozfHMuZTpsfHAudjpvZmYscy50OjR8cC52OnNpbXBsaWZpZWR8cC5oOiNmZjAwMDB8cC5zOjM4fHAubDotMTZ8cC5nOjAuODY';
        	$googlemap_style = '[{"featureType":"all","elementType":"all","stylers":[{"visibility":"simplified"},{"saturation":"-100"},{"invert_lightness":true},{"lightness":"11"},{"gamma":"1.27"}]},{"featureType":"administrative.locality","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"landscape.man_made","elementType":"all","stylers":[{"hue":"#ff0000"},{"visibility":"simplified"},{"invert_lightness":true},{"lightness":"-10"},{"gamma":"0.54"},{"saturation":"45"}]},{"featureType":"poi.business","elementType":"all","stylers":[{"visibility":"simplified"},{"hue":"#ff0000"},{"saturation":"75"},{"lightness":"24"},{"gamma":"0.70"},{"invert_lightness":true}]},{"featureType":"poi.government","elementType":"all","stylers":[{"hue":"#ff0000"},{"visibility":"simplified"},{"invert_lightness":true},{"lightness":"-24"},{"gamma":"0.59"},{"saturation":"59"}]},{"featureType":"poi.medical","elementType":"all","stylers":[{"visibility":"simplified"},{"invert_lightness":true},{"hue":"#ff0000"},{"saturation":"73"},{"lightness":"-24"},{"gamma":"0.59"}]},{"featureType":"poi.park","elementType":"all","stylers":[{"lightness":"-41"}]},{"featureType":"poi.school","elementType":"all","stylers":[{"visibility":"simplified"},{"hue":"#ff0000"},{"invert_lightness":true},{"saturation":"43"},{"lightness":"-16"},{"gamma":"0.73"}]},{"featureType":"poi.sports_complex","elementType":"all","stylers":[{"hue":"#ff0000"},{"saturation":"43"},{"lightness":"-11"},{"gamma":"0.73"},{"invert_lightness":true}]},{"featureType":"road","elementType":"all","stylers":[{"saturation":"45"},{"lightness":"53"},{"gamma":"0.67"},{"invert_lightness":true},{"hue":"#ff0000"},{"visibility":"simplified"}]},{"featureType":"road","elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"transit","elementType":"all","stylers":[{"visibility":"simplified"},{"hue":"#ff0000"},{"saturation":"38"},{"lightness":"-16"},{"gamma":"0.86"}]}]';
        	break;

        case "wyborcza-2018": //Wyborcza 2018
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcy5lOmwudC5mfHAuYzojZmZhOGFmYmYscy5lOmwudC5zfHAudjpvbnxwLmM6I2ZmMzczZDQ4fHAudzoyfHAuZzoxLHMuZTpsLml8cC52Om9mZixzLnQ6MXxzLmU6Z3xwLnc6MC42fHAuYzojZmY0YzU3NmZ8cC5nOjAscy50OjV8cy5lOmd8cC5jOiNmZjQyNGM2NXxwLmc6MXxwLnc6MTAscy50OjJ8cy5lOmd8cC5jOiNmZjRjNTc2ZixzLnQ6NDB8cy5lOmd8cC5jOiNmZjQyNGQ2NixzLnQ6M3xzLmU6Z3xwLmM6I2ZmMzc0MjVjfHAubDowLHMudDo0fHMuZTpnfHAuYzojZmY0YzU3NmYscy50OjZ8cy5lOmd8cC5jOiNmZjJiMzY0Zg';
        	$googlemap_style = '[{"featureType":"all","elementType":"labels.text.fill","stylers":[{"color":"#a8afbf"}]},{"featureType":"all","elementType":"labels.text.stroke","stylers":[{"visibility":"on"},{"color":"#373d48"},{"weight":2},{"gamma":"1"}]},{"featureType":"all","elementType":"labels.icon","stylers":[{"visibility":"off"}]},{"featureType":"administrative","elementType":"geometry","stylers":[{"weight":0.6},{"color":"#4c576f"},{"gamma":"0"}]},{"featureType":"landscape","elementType":"geometry","stylers":[{"color":"#424c65"},{"gamma":"1"},{"weight":"10"}]},{"featureType":"poi","elementType":"geometry","stylers":[{"color":"#4c576f"}]},{"featureType":"poi.park","elementType":"geometry","stylers":[{"color":"#424d66"}]},{"featureType":"road","elementType":"geometry","stylers":[{"color":"#37425c"},{"lightness":"0"}]},{"featureType":"transit","elementType":"geometry","stylers":[{"color":"#4c576f"}]},{"featureType":"water","elementType":"geometry","stylers":[{"color":"#2b364f"}]}]';
        	break;

        case "hot-pink": //Hot Pink
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcC5oOiNmZjYxYTZ8cC52Om9ufHAuaWw6dHJ1ZXxwLnM6NDB8cC5sOjEw';
        	$googlemap_style = '[{"stylers":[{"hue":"#ff61a6"},{"visibility":"on"},{"invert_lightness":true},{"saturation":40},{"lightness":10}]}]';
        	break;

        case "dark-yellow": //Dark Yellow
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcy5lOmwudC5mfHAuczozNnxwLmM6I2ZmMDAwMDAwfHAubDo0MCxzLmU6bC50LnN8cC52Om9ufHAuYzojZmYwMDAwMDB8cC5sOjE2LHMuZTpsLml8cC52Om9mZixzLnQ6MXxzLmU6Zy5mfHAubDoyMCxzLnQ6MXxzLmU6Zy5zfHAuYzojZmYwMDAwMDB8cC5sOjE3fHAudzoxLjIscy50OjE4fHMuZTpsLnQuZnxwLmM6I2ZmZTNiMTQxLHMudDoxOXxzLmU6bC50LmZ8cC5jOiNmZmUwYTY0YixzLnQ6MTl8cy5lOmwudC5zfHAuYzojZmYwZTBkMGEscy50OjIwfHMuZTpsLnQuZnxwLmM6I2ZmZDFiOTk1LHMudDo1fHMuZTpnfHAuYzojZmYwMDAwMDB8cC5sOjIwLHMudDoyfHMuZTpnfHAuYzojZmYwMDAwMDB8cC5sOjIxLHMudDozfHMuZTpsLnQuc3xwLmM6I2ZmMTIxMjBmLHMudDo0OXxzLmU6Zy5mfHAubDotNzd8cC5nOjQuNDh8cC5zOjI0fHAudzowLjY1LHMudDo0OXxzLmU6Zy5zfHAubDoyOXxwLnc6MC4yLHMudDo3ODV8cy5lOmcuZnxwLmM6I2ZmZjZiMDQ0LHMudDo1MHxzLmU6Z3xwLmM6I2ZmNGY0ZTQ5fHAudzowLjM2LHMudDo1MHxzLmU6bC50LmZ8cC5jOiNmZmM0YWM4NyxzLnQ6NTB8cy5lOmwudC5zfHAuYzojZmYyNjIzMDcscy50OjUxfHMuZTpnfHAuYzojZmZhNDg3NWF8cC5sOjE2fHAudzowLjE2LHMudDo1MXxzLmU6bC50LmZ8cC5jOiNmZmRlYjQ4MyxzLnQ6NHxzLmU6Z3xwLmM6I2ZmMDAwMDAwfHAubDoxOSxzLnQ6NnxzLmU6Z3xwLmM6I2ZmMGYyNTJlfHAubDoxNyxzLnQ6NnxzLmU6Zy5mfHAuYzojZmYwODA4MDh8cC5nOjMuMTR8cC53OjEuMDc';
        	$googlemap_style = '[{"featureType":"all","elementType":"labels.text.fill","stylers":[{"saturation":36},{"color":"#000000"},{"lightness":40}]},{"featureType":"all","elementType":"labels.text.stroke","stylers":[{"visibility":"on"},{"color":"#000000"},{"lightness":16}]},{"featureType":"all","elementType":"labels.icon","stylers":[{"visibility":"off"}]},{"featureType":"administrative","elementType":"geometry.fill","stylers":[{"lightness":20}]},{"featureType":"administrative","elementType":"geometry.stroke","stylers":[{"color":"#000000"},{"lightness":17},{"weight":1.2}]},{"featureType":"administrative.province","elementType":"labels.text.fill","stylers":[{"color":"#e3b141"}]},{"featureType":"administrative.locality","elementType":"labels.text.fill","stylers":[{"color":"#e0a64b"}]},{"featureType":"administrative.locality","elementType":"labels.text.stroke","stylers":[{"color":"#0e0d0a"}]},{"featureType":"administrative.neighborhood","elementType":"labels.text.fill","stylers":[{"color":"#d1b995"}]},{"featureType":"landscape","elementType":"geometry","stylers":[{"color":"#000000"},{"lightness":20}]},{"featureType":"poi","elementType":"geometry","stylers":[{"color":"#000000"},{"lightness":21}]},{"featureType":"road","elementType":"labels.text.stroke","stylers":[{"color":"#12120f"}]},{"featureType":"road.highway","elementType":"geometry.fill","stylers":[{"lightness":"-77"},{"gamma":"4.48"},{"saturation":"24"},{"weight":"0.65"}]},{"featureType":"road.highway","elementType":"geometry.stroke","stylers":[{"lightness":29},{"weight":0.2}]},{"featureType":"road.highway.controlled_access","elementType":"geometry.fill","stylers":[{"color":"#f6b044"}]},{"featureType":"road.arterial","elementType":"geometry","stylers":[{"color":"#4f4e49"},{"weight":"0.36"}]},{"featureType":"road.arterial","elementType":"labels.text.fill","stylers":[{"color":"#c4ac87"}]},{"featureType":"road.arterial","elementType":"labels.text.stroke","stylers":[{"color":"#262307"}]},{"featureType":"road.local","elementType":"geometry","stylers":[{"color":"#a4875a"},{"lightness":16},{"weight":"0.16"}]},{"featureType":"road.local","elementType":"labels.text.fill","stylers":[{"color":"#deb483"}]},{"featureType":"transit","elementType":"geometry","stylers":[{"color":"#000000"},{"lightness":19}]},{"featureType":"water","elementType":"geometry","stylers":[{"color":"#0f252e"},{"lightness":17}]},{"featureType":"water","elementType":"geometry.fill","stylers":[{"color":"#080808"},{"gamma":"3.14"},{"weight":"1.07"}]}]';
        	break;

        case "light-blue-water": //Light Blue Water
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcy50OjZ8cC5oOiM3MWQ2ZmZ8cC5zOjEwMHxwLmw6LTV8cC52Om9uLHMudDoyfHAuaDojZmZmZmZmfHAuczotMTAwfHAubDoxMDB8cC52Om9mZixzLnQ6NHxwLmg6I2ZmZmZmZnxwLmw6MTAwfHAudjpvZmYscy50OjQ5fHMuZTpnfHAuaDojZGVlY2VjfHAuczotNzN8cC5sOjcyfHAudjpvbixzLnQ6NDl8cy5lOmx8cC5oOiNiYWJhYmF8cC5zOi0xMDB8cC5sOjI1fHAudjpvbixzLnQ6NXxzLmU6Z3xwLmg6I2UzZTNlM3xwLnM6LTEwMHxwLnY6b24scy50OjN8cy5lOmd8cC5oOiNmZmZmZmZ8cC5zOi0xMDB8cC5sOjEwMHxwLnY6c2ltcGxpZmllZCxzLnQ6MXxzLmU6bHxwLmg6IzU5Y2ZmZnxwLnM6MTAwfHAubDozNHxwLnY6b24';
        	$googlemap_style = '[{"featureType":"water","elementType":"all","stylers":[{"hue":"#71d6ff"},{"saturation":100},{"lightness":-5},{"visibility":"on"}]},{"featureType":"poi","elementType":"all","stylers":[{"hue":"#ffffff"},{"saturation":-100},{"lightness":100},{"visibility":"off"}]},{"featureType":"transit","elementType":"all","stylers":[{"hue":"#ffffff"},{"saturation":0},{"lightness":100},{"visibility":"off"}]},{"featureType":"road.highway","elementType":"geometry","stylers":[{"hue":"#deecec"},{"saturation":-73},{"lightness":72},{"visibility":"on"}]},{"featureType":"road.highway","elementType":"labels","stylers":[{"hue":"#bababa"},{"saturation":-100},{"lightness":25},{"visibility":"on"}]},{"featureType":"landscape","elementType":"geometry","stylers":[{"hue":"#e3e3e3"},{"saturation":-100},{"lightness":0},{"visibility":"on"}]},{"featureType":"road","elementType":"geometry","stylers":[{"hue":"#ffffff"},{"saturation":-100},{"lightness":100},{"visibility":"simplified"}]},{"featureType":"administrative","elementType":"labels","stylers":[{"hue":"#59cfff"},{"saturation":100},{"lightness":34},{"visibility":"on"}]}]';
        	break;

        case "chilled": //Chilled
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcy50OjN8cy5lOmd8cC52OnNpbXBsaWZpZWQscy50OjUwfHAuaDoxNDl8cC5zOi03OCxzLnQ6NDl8cC5oOi0zMXxwLnM6LTQwfHAubDoyLjgscy50OjJ8cC52Om9mZixzLnQ6NXxwLmg6MTYzfHAuczotMjZ8cC5sOi0xLjEscy50OjR8cC52Om9mZixzLnQ6NnxwLmg6M3xwLnM6LTI0LjI0fHAubDotMzguNTc';
        	$googlemap_style = '[{"featureType":"road","elementType":"geometry","stylers":[{"visibility":"simplified"}]},{"featureType":"road.arterial","stylers":[{"hue":149},{"saturation":-78},{"lightness":0}]},{"featureType":"road.highway","stylers":[{"hue":-31},{"saturation":-40},{"lightness":2.8}]},{"featureType":"poi","elementType":"label","stylers":[{"visibility":"off"}]},{"featureType":"landscape","stylers":[{"hue":163},{"saturation":-26},{"lightness":-1.1}]},{"featureType":"transit","stylers":[{"visibility":"off"}]},{"featureType":"water","stylers":[{"hue":3},{"saturation":-24.24},{"lightness":-38.57}]}]';
        	break;

        case "purple": //Purple
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcC52OnNpbXBsaWZpZWR8cC5oOiNiYzAwZmZ8cC5zOjAscy50OjF8cC52OnNpbXBsaWZpZWQscy50OjF8cy5lOmwudC5mfHAuYzojZmZlOGI4Zjkscy50OjE3fHMuZTpsfHAuYzojZmZmZjAwMDAscy50OjIxfHMuZTpsLnQuZnxwLnY6c2ltcGxpZmllZCxzLnQ6NXxwLmM6I2ZmM2UxMTRlfHAudjpzaW1wbGlmaWVkLHMudDo1fHMuZTpsfHAudjpvZmZ8cC5jOiNmZmEwMmFjYSxzLnQ6ODJ8cC52OnNpbXBsaWZpZWR8cC5jOiNmZjJlMDkzYixzLnQ6ODJ8cy5lOmwudHxwLmM6I2ZmOWUxMDEwfHAudjpvZmYscy50OjgyfHMuZTpsLnQuZnxwLmM6I2ZmZmYwMDAwLHMudDoxMzEzfHAudjpzaW1wbGlmaWVkfHAuYzojZmY1ODE3NmUscy50OjEzMTN8cy5lOmwudC5mfHAudjpzaW1wbGlmaWVkLHMudDoyfHAudjpvZmYscy50OjMzfHAudjpvZmYscy50OjN8cC5zOi0xMDB8cC5sOjQ1LHMudDozfHMuZTpnfHAudjpzaW1wbGlmaWVkfHAuYzojZmZhMDJhY2Escy50OjN8cy5lOmx8cC52OnNpbXBsaWZpZWQscy50OjN8cy5lOmwudC5mfHAuYzojZmZkMTgwZWUscy50OjN8cy5lOmwudC5zfHAudjpzaW1wbGlmaWVkLHMudDo0OXxwLnY6c2ltcGxpZmllZCxzLnQ6NDl8cy5lOmd8cC52OnNpbXBsaWZpZWR8cC5jOiNmZmEwMmFjYSxzLnQ6NDl8cy5lOmx8cC52Om9mZnxwLmM6I2ZmZmYwMDAwLHMudDo0OXxzLmU6bC50fHAuYzojZmZhMDJhY2F8cC52OnNpbXBsaWZpZWQscy50OjQ5fHMuZTpsLnQuZnxwLmM6I2ZmY2M4MWU3fHAudjpzaW1wbGlmaWVkLHMudDo0OXxzLmU6bC50LnN8cC52OnNpbXBsaWZpZWR8cC5oOiNiYzAwZmYscy50OjUwfHMuZTpnfHAuYzojZmY2ZDIzODgscy50OjUwfHMuZTpsLnQuZnxwLmM6I2ZmYzQ2Y2UzLHMudDo1MHxzLmU6bC5pfHAudjpvZmYscy50OjR8cC52Om9mZixzLnQ6NnxwLmM6I2ZmYjc5MThmfHAudjpvbixzLnQ6NnxzLmU6Z3xwLmM6I2ZmMjgwYjMzLHMudDo2fHMuZTpsfHAudjpzaW1wbGlmaWVkfHAuYzojZmZhMDJhY2E';
        	$googlemap_style = '[{"featureType":"all","elementType":"all","stylers":[{"visibility":"simplified"},{"hue":"#bc00ff"},{"saturation":"0"}]},{"featureType":"administrative","elementType":"all","stylers":[{"visibility":"simplified"}]},{"featureType":"administrative","elementType":"labels.text.fill","stylers":[{"color":"#e8b8f9"}]},{"featureType":"administrative.country","elementType":"labels","stylers":[{"color":"#ff0000"}]},{"featureType":"administrative.land_parcel","elementType":"labels.text.fill","stylers":[{"visibility":"simplified"}]},{"featureType":"landscape","elementType":"all","stylers":[{"color":"#3e114e"},{"visibility":"simplified"}]},{"featureType":"landscape","elementType":"labels","stylers":[{"visibility":"off"},{"color":"#a02aca"}]},{"featureType":"landscape.natural","elementType":"all","stylers":[{"visibility":"simplified"},{"color":"#2e093b"}]},{"featureType":"landscape.natural","elementType":"labels.text","stylers":[{"color":"#9e1010"},{"visibility":"off"}]},{"featureType":"landscape.natural","elementType":"labels.text.fill","stylers":[{"color":"#ff0000"}]},{"featureType":"landscape.natural.landcover","elementType":"all","stylers":[{"visibility":"simplified"},{"color":"#58176e"}]},{"featureType":"landscape.natural.landcover","elementType":"labels.text.fill","stylers":[{"visibility":"simplified"}]},{"featureType":"poi","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"poi.business","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"road","elementType":"all","stylers":[{"saturation":-100},{"lightness":45}]},{"featureType":"road","elementType":"geometry","stylers":[{"visibility":"simplified"},{"color":"#a02aca"}]},{"featureType":"road","elementType":"labels","stylers":[{"visibility":"simplified"}]},{"featureType":"road","elementType":"labels.text.fill","stylers":[{"color":"#d180ee"}]},{"featureType":"road","elementType":"labels.text.stroke","stylers":[{"visibility":"simplified"}]},{"featureType":"road.highway","elementType":"all","stylers":[{"visibility":"simplified"}]},{"featureType":"road.highway","elementType":"geometry","stylers":[{"visibility":"simplified"},{"color":"#a02aca"}]},{"featureType":"road.highway","elementType":"labels","stylers":[{"visibility":"off"},{"color":"#ff0000"}]},{"featureType":"road.highway","elementType":"labels.text","stylers":[{"color":"#a02aca"},{"visibility":"simplified"}]},{"featureType":"road.highway","elementType":"labels.text.fill","stylers":[{"color":"#cc81e7"},{"visibility":"simplified"}]},{"featureType":"road.highway","elementType":"labels.text.stroke","stylers":[{"visibility":"simplified"},{"hue":"#bc00ff"}]},{"featureType":"road.arterial","elementType":"geometry","stylers":[{"color":"#6d2388"}]},{"featureType":"road.arterial","elementType":"labels.text.fill","stylers":[{"color":"#c46ce3"}]},{"featureType":"road.arterial","elementType":"labels.icon","stylers":[{"visibility":"off"}]},{"featureType":"transit","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"water","elementType":"all","stylers":[{"color":"#b7918f"},{"visibility":"on"}]},{"featureType":"water","elementType":"geometry","stylers":[{"color":"#280b33"}]},{"featureType":"water","elementType":"labels","stylers":[{"visibility":"simplified"},{"color":"#a02aca"}]}]';
        	break;

        case "night-vision": //Night vision
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcy50OjZ8cC5oOiMwMDEyMDR8cC5zOjEwMHxwLmw6LTk1fHAudjpvbixzLnQ6ODF8cC5oOiMwMDdGMUV8cC5zOjEwMHxwLmw6LTcyfHAudjpvbixzLnQ6ODJ8cC5oOiMwMEM3MkV8cC5zOjEwMHxwLmw6LTU5fHAudjpvbixzLnQ6M3xwLmg6IzAwMkMwQXxwLnM6MTAwfHAubDotODd8cC52Om9uLHMudDoyfHAuaDojMDBBOTI3fHAuczoxMDB8cC5sOi01OHxwLnY6b24';
        	$googlemap_style = '[{"featureType":"water","elementType":"all","stylers":[{"hue":"#001204"},{"saturation":100},{"lightness":-95},{"visibility":"on"}]},{"featureType":"landscape.man_made","elementType":"all","stylers":[{"hue":"#007F1E"},{"saturation":100},{"lightness":-72},{"visibility":"on"}]},{"featureType":"landscape.natural","elementType":"all","stylers":[{"hue":"#00C72E"},{"saturation":100},{"lightness":-59},{"visibility":"on"}]},{"featureType":"road","elementType":"all","stylers":[{"hue":"#002C0A"},{"saturation":100},{"lightness":-87},{"visibility":"on"}]},{"featureType":"poi","elementType":"all","stylers":[{"hue":"#00A927"},{"saturation":100},{"lightness":-58},{"visibility":"on"}]}]';
        	break;

        case "50-shades-of-blue": //50 shades of blue
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcy50OjgyfHAuYzojZmZiY2RkZmYscy50OjQ5fHMuZTpnLmZ8cC5jOiNmZjVmYjNmZixzLnQ6NTB8cC5jOiNmZmViZjRmZixzLnQ6NTF8cy5lOmcuZnxwLmM6I2ZmZWJmNGZmLHMudDo1MXxzLmU6Zy5zfHAudjpvbnxwLmM6I2ZmOTNjOGZmLHMudDo4MXxzLmU6Z3xwLmM6I2ZmYzdlMmZmLHMudDoxMDU5fHMuZTpnfHAuczoxMDB8cC5nOjAuODJ8cC5oOiMwMDg4ZmYscy5lOmwudC5mfHAuYzojZmYxNjczY2Iscy50OjQ5fHMuZTpsLml8cC5zOjU4fHAuaDojMDA2ZWZmLHMudDoyfHMuZTpnfHAuYzojZmY0Nzk3ZTAscy50OjQwfHMuZTpnfHAuYzojZmYyMDllZTF8cC5sOjQ5LHMudDo2NXxzLmU6Zy5mfHAuYzojZmY4M2JlZmMscy50OjQ5fHMuZTpnLnN8cC5jOiNmZjNlYTNmZixzLnQ6MXxzLmU6Zy5zfHAuczo4NnxwLmg6IzAwNzdmZnxwLnc6MC44LHMuZTpsLml8cC5oOiMwMDY2ZmZ8cC53OjEuOSxzLnQ6MnxzLmU6Zy5mfHAuaDojMDA3N2ZmfHAuczotN3xwLmw6MjQ';
        	$googlemap_style = '[{"featureType":"landscape.natural","stylers":[{"color":"#bcddff"}]},{"featureType":"road.highway","elementType":"geometry.fill","stylers":[{"color":"#5fb3ff"}]},{"featureType":"road.arterial","stylers":[{"color":"#ebf4ff"}]},{"featureType":"road.local","elementType":"geometry.fill","stylers":[{"color":"#ebf4ff"}]},{"featureType":"road.local","elementType":"geometry.stroke","stylers":[{"visibility":"on"},{"color":"#93c8ff"}]},{"featureType":"landscape.man_made","elementType":"geometry","stylers":[{"color":"#c7e2ff"}]},{"featureType":"transit.station.airport","elementType":"geometry","stylers":[{"saturation":100},{"gamma":0.82},{"hue":"#0088ff"}]},{"elementType":"labels.text.fill","stylers":[{"color":"#1673cb"}]},{"featureType":"road.highway","elementType":"labels.icon","stylers":[{"saturation":58},{"hue":"#006eff"}]},{"featureType":"poi","elementType":"geometry","stylers":[{"color":"#4797e0"}]},{"featureType":"poi.park","elementType":"geometry","stylers":[{"color":"#209ee1"},{"lightness":49}]},{"featureType":"transit.line","elementType":"geometry.fill","stylers":[{"color":"#83befc"}]},{"featureType":"road.highway","elementType":"geometry.stroke","stylers":[{"color":"#3ea3ff"}]},{"featureType":"administrative","elementType":"geometry.stroke","stylers":[{"saturation":86},{"hue":"#0077ff"},{"weight":0.8}]},{"elementType":"labels.icon","stylers":[{"hue":"#0066ff"},{"weight":1.9}]},{"featureType":"poi","elementType":"geometry.fill","stylers":[{"hue":"#0077ff"},{"saturation":-7},{"lightness":24}]}]';
        	break;

        case "carte-vierge": //Carte Vierge
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcy50OjF8cy5lOmx8cC52Om9mZixzLnQ6MXxzLmU6bC50fHAudjpvZmYscy50OjE3fHMuZTpnfHAudjpzaW1wbGlmaWVkfHAuaDojZmYwMDAwLHMudDoxN3xzLmU6bC50fHAudjpvZmYscy50OjV8cy5lOmwudHxwLnY6b2ZmLHMudDoyfHMuZTpsfHAudjpvZmYscy50OjJ8cy5lOmwudHxwLnY6b2ZmLHMudDozfHAudjpvbixzLnQ6M3xzLmU6bHxwLnY6b2ZmLHMudDozfHMuZTpsLnR8cC52Om9mZixzLnQ6NHxzLmU6bHxwLnY6b2Zm';
        	$googlemap_style = '[{"featureType":"administrative","elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"administrative","elementType":"labels.text","stylers":[{"visibility":"off"}]},{"featureType":"administrative.country","elementType":"geometry","stylers":[{"visibility":"simplified"},{"hue":"#ff0000"}]},{"featureType":"administrative.country","elementType":"labels.text","stylers":[{"visibility":"off"}]},{"featureType":"landscape","elementType":"labels.text","stylers":[{"visibility":"off"}]},{"featureType":"poi","elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"poi","elementType":"labels.text","stylers":[{"visibility":"off"}]},{"featureType":"road","elementType":"all","stylers":[{"visibility":"on"}]},{"featureType":"road","elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"road","elementType":"labels.text","stylers":[{"visibility":"off"}]},{"featureType":"transit","elementType":"labels","stylers":[{"visibility":"off"}]}]';
        	break;

        case "simplified-map": //Simplified Map
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcy50OjF8cy5lOmx8cC52Om9mZixzLnQ6MXxzLmU6bC50LmZ8cC5jOiNmZjQ0NDQ0NCxzLnQ6NXxwLmM6I2ZmZTllNmRlLHMudDoyfHAudjpzaW1wbGlmaWVkLHMudDozN3xwLnY6b2ZmLHMudDozM3xwLnY6b2ZmLHMudDozNHxwLnY6b2ZmLHMudDozNnxwLnY6b2ZmLHMudDo0MHxwLmM6I2ZmOTVkYTU5LHMudDozOHxwLnY6b2ZmLHMudDozNXxwLnY6b2ZmLHMudDozOXxwLnY6b2ZmLHMudDozfHAuczotMTAwfHAubDo0NSxzLnQ6M3xzLmU6bHxwLnY6b2ZmLHMudDo0OXxwLnY6c2ltcGxpZmllZCxzLnQ6NDl8cy5lOmx8cC52Om9mZixzLnQ6Nzg1fHMuZTpsfHAudjpvZmYscy50OjUwfHAudjpzaW1wbGlmaWVkLHMudDo1MHxzLmU6bC5pfHAudjpvZmYscy50OjUxfHAudjpzaW1wbGlmaWVkLHMudDo1MXxzLmU6bHxwLnY6b2ZmLHMudDo0fHAudjpvZmYscy50OjZ8cC5jOiNmZjdkZGRlNnxwLnY6b24';
        	$googlemap_style = '[{"featureType":"administrative","elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"administrative","elementType":"labels.text.fill","stylers":[{"color":"#444444"}]},{"featureType":"landscape","elementType":"all","stylers":[{"color":"#e9e6de"}]},{"featureType":"poi","elementType":"all","stylers":[{"visibility":"simplified"}]},{"featureType":"poi.attraction","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"poi.business","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"poi.government","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"poi.medical","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"poi.park","elementType":"all","stylers":[{"color":"#95da59"}]},{"featureType":"poi.place_of_worship","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"poi.school","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"poi.sports_complex","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"road","elementType":"all","stylers":[{"saturation":-100},{"lightness":45}]},{"featureType":"road","elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"road.highway","elementType":"all","stylers":[{"visibility":"simplified"}]},{"featureType":"road.highway","elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"road.highway.controlled_access","elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"road.arterial","elementType":"all","stylers":[{"visibility":"simplified"}]},{"featureType":"road.arterial","elementType":"labels.icon","stylers":[{"visibility":"off"}]},{"featureType":"road.local","elementType":"all","stylers":[{"visibility":"simplified"}]},{"featureType":"road.local","elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"transit","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"water","elementType":"all","stylers":[{"color":"#7ddde6"},{"visibility":"on"}]}]';
        	break;

        case "inturlam-style-2": //Inturlam Style 2
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcC5pbDp0cnVlfHAuczoyMHxwLmw6NTB8cC5nOjAuNHxwLmg6IzAwZmZlZSxzLmU6Z3xwLnY6c2ltcGxpZmllZCxzLmU6bHxwLnY6b24scy50OjF8cC5jOiNmZmZmZmZmZnxwLnY6c2ltcGxpZmllZCxzLnQ6MjF8cy5lOmcuc3xwLnY6c2ltcGxpZmllZCxzLnQ6NXxwLmM6I2ZmNDA1NzY5LHMudDo2fHMuZTpnLmZ8cC5jOiNmZjIzMmYzYQ';
        	$googlemap_style = '[{"featureType":"all","elementType":"all","stylers":[{"invert_lightness":true},{"saturation":20},{"lightness":50},{"gamma":0.4},{"hue":"#00ffee"}]},{"featureType":"all","elementType":"geometry","stylers":[{"visibility":"simplified"}]},{"featureType":"all","elementType":"labels","stylers":[{"visibility":"on"}]},{"featureType":"administrative","elementType":"all","stylers":[{"color":"#ffffff"},{"visibility":"simplified"}]},{"featureType":"administrative.land_parcel","elementType":"geometry.stroke","stylers":[{"visibility":"simplified"}]},{"featureType":"landscape","elementType":"all","stylers":[{"color":"#405769"}]},{"featureType":"water","elementType":"geometry.fill","stylers":[{"color":"#232f3a"}]}]';
        	break;

        case "esperanto": //Esperanto
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcy5lOmwudC5zfHAuYzojZmZmZmZmZmYscy5lOmwudC5mfHAuYzojZmYwMDAwMDAscy50OjZ8cy5lOmd8cC5jOiNmZjAwMDBmZixzLnQ6NDl8cy5lOmcuZnxwLmM6I2ZmZmYwMDAwLHMudDo0OXxzLmU6Zy5zfHAuYzojZmYwMDAxMDAscy50Ojc4NXxzLmU6Zy5mfHAuYzojZmZmZmZmMDAscy50Ojc4NXxzLmU6Zy5zfHAuYzojZmZmZjAwMDAscy50OjUwfHMuZTpnLmZ8cC5jOiNmZmZmYTkxYSxzLnQ6NTB8cy5lOmcuc3xwLmM6I2ZmMDAwMDAwLHMudDo4MnxwLnM6MzZ8cC5nOjAuNTUscy50OjUxfHMuZTpnLnN8cC5jOiNmZjAwMDAwMCxzLnQ6NTF8cy5lOmcuZnxwLmM6I2ZmZmZmZmZmLHMudDo4MXxzLmU6Zy5zfHAubDotMTAwfHAudzoyLjEscy50OjgxfHMuZTpnLmZ8cC5pbDp0cnVlfHAuaDojZmYwMDAwfHAuZzozLjAyfHAubDoyMHxwLnM6NDAscy50OjM3fHAuczoxMDB8cC5oOiNmZjAwZWV8cC5sOi0xMyxzLnQ6MzR8cC5zOjEwMHxwLmg6I2VlZmYwMHxwLmc6MC42N3xwLmw6LTI2LHMudDozNnxzLmU6Zy5mfHAuaDojZmYwMDAwfHAuczoxMDB8cC5sOi0zNyxzLnQ6MzZ8cy5lOmwudC5mfHAuYzojZmZmZjAwMDAscy50OjM1fHAuaDojZmY3NzAwfHAuczo5N3xwLmw6LTQxLHMudDozOXxwLnM6MTAwfHAuaDojMDBmZmIzfHAubDotNzEscy50OjQwfHAuczo4NHxwLmw6LTU3fHAuaDojYTFmZjAwLHMudDoxMDU5fHMuZTpnLmZ8cC5nOjAuMTEscy50OjY2fHMuZTpsLnQuc3xwLmM6I2ZmZmZjMzVlLHMudDo2NXxzLmU6Z3xwLmw6LTEwMCxzLnQ6MXxwLnM6MTAwfHAuZzowLjM1fHAubDoyMCxzLnQ6MzN8cy5lOmcuZnxwLnM6LTEwMHxwLmc6MC4zNSxzLnQ6MzN8cy5lOmwudC5zfHAuYzojZmY2OWZmZmYscy50OjM4fHMuZTpsLnQuc3xwLmM6I2ZmYzNmZmMz';
        	$googlemap_style = '[{"elementType":"labels.text.stroke","stylers":[{"color":"#ffffff"}]},{"elementType":"labels.text.fill","stylers":[{"color":"#000000"}]},{"featureType":"water","elementType":"geometry","stylers":[{"color":"#0000ff"}]},{"featureType":"road.highway","elementType":"geometry.fill","stylers":[{"color":"#ff0000"}]},{"featureType":"road.highway","elementType":"geometry.stroke","stylers":[{"color":"#000100"}]},{"featureType":"road.highway.controlled_access","elementType":"geometry.fill","stylers":[{"color":"#ffff00"}]},{"featureType":"road.highway.controlled_access","elementType":"geometry.stroke","stylers":[{"color":"#ff0000"}]},{"featureType":"road.arterial","elementType":"geometry.fill","stylers":[{"color":"#ffa91a"}]},{"featureType":"road.arterial","elementType":"geometry.stroke","stylers":[{"color":"#000000"}]},{"featureType":"landscape.natural","stylers":[{"saturation":36},{"gamma":0.55}]},{"featureType":"road.local","elementType":"geometry.stroke","stylers":[{"color":"#000000"}]},{"featureType":"road.local","elementType":"geometry.fill","stylers":[{"color":"#ffffff"}]},{"featureType":"landscape.man_made","elementType":"geometry.stroke","stylers":[{"lightness":-100},{"weight":2.1}]},{"featureType":"landscape.man_made","elementType":"geometry.fill","stylers":[{"invert_lightness":true},{"hue":"#ff0000"},{"gamma":3.02},{"lightness":20},{"saturation":40}]},{"featureType":"poi.attraction","stylers":[{"saturation":100},{"hue":"#ff00ee"},{"lightness":-13}]},{"featureType":"poi.government","stylers":[{"saturation":100},{"hue":"#eeff00"},{"gamma":0.67},{"lightness":-26}]},{"featureType":"poi.medical","elementType":"geometry.fill","stylers":[{"hue":"#ff0000"},{"saturation":100},{"lightness":-37}]},{"featureType":"poi.medical","elementType":"labels.text.fill","stylers":[{"color":"#ff0000"}]},{"featureType":"poi.school","stylers":[{"hue":"#ff7700"},{"saturation":97},{"lightness":-41}]},{"featureType":"poi.sports_complex","stylers":[{"saturation":100},{"hue":"#00ffb3"},{"lightness":-71}]},{"featureType":"poi.park","stylers":[{"saturation":84},{"lightness":-57},{"hue":"#a1ff00"}]},{"featureType":"transit.station.airport","elementType":"geometry.fill","stylers":[{"gamma":0.11}]},{"featureType":"transit.station","elementType":"labels.text.stroke","stylers":[{"color":"#ffc35e"}]},{"featureType":"transit.line","elementType":"geometry","stylers":[{"lightness":-100}]},{"featureType":"administrative","stylers":[{"saturation":100},{"gamma":0.35},{"lightness":20}]},{"featureType":"poi.business","elementType":"geometry.fill","stylers":[{"saturation":-100},{"gamma":0.35}]},{"featureType":"poi.business","elementType":"labels.text.stroke","stylers":[{"color":"#69ffff"}]},{"featureType":"poi.place_of_worship","elementType":"labels.text.stroke","stylers":[{"color":"#c3ffc3"}]}]';
        	break;

        case "nothing-but-roads": //Nothing but roads
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcy5lOmx8cC52Om9uLHMuZTpsLnQuZnxwLnM6MzZ8cC5jOiNmZjAwMDAwMHxwLmw6NDAscy5lOmwudC5zfHAudjpvbnxwLmM6I2ZmMDAwMDAwfHAubDoxNixzLmU6bC5pfHAudjpvZmYscy50OjF8cy5lOmcuZnxwLmM6I2ZmMDAwMDAwfHAubDoyMCxzLnQ6MXxzLmU6Zy5zfHAuYzojZmYwMDAwMDB8cC5sOjE3fHAudzoxLjIscy50OjF8cy5lOmx8cC52Om9mZixzLnQ6MXxzLmU6bC50fHAudjpvZmYscy50OjE5fHMuZTpsLnQuZnxwLmM6I2ZmYzRjNGM0LHMudDoyMHxzLmU6bC50LmZ8cC5jOiNmZjcwNzA3MCxzLnQ6NXxzLmU6Z3xwLmM6I2ZmMjUwMDQ2fHAubDotMjB8cC52Om9ufHAuczo1NSxzLnQ6NXxzLmU6Zy5mfHAudjpvbixzLnQ6NXxzLmU6bHxwLnY6b2ZmLHMudDoyfHMuZTpnfHAuYzojZmYwMDAwMDB8cC5sOjIxfHAudjpvZmYscy50OjMzfHMuZTpnfHAudjpvZmYscy50OjN8cy5lOmx8cC52Om9mZixzLnQ6NDl8cy5lOmcuZnxwLmM6I2ZmZWUyMzQ0fHAubDowfHAudjpvbixzLnQ6NDl8cy5lOmcuc3xwLnY6b2ZmLHMudDo0OXxzLmU6bC50LmZ8cC52Om9mZixzLnQ6NDl8cy5lOmwudC5zfHAudjpvZmZ8cC5oOiNmZjAwMGEscy50OjUwfHMuZTpnfHAuYzojZmYwMDAwMDB8cC5sOjE4LHMudDo1MHxzLmU6Zy5mfHAuYzojZmZlZTIzNDR8cC5sOjAscy50OjUwfHMuZTpnLnN8cC52Om9mZnxwLnc6Mi4wMCxzLnQ6NTB8cy5lOmwudC5mfHAuYzojZmZmZmZmZmYscy50OjUwfHMuZTpsLnQuc3xwLmM6I2ZmMmMyYzJjLHMudDo1MXxzLmU6Z3xwLmM6I2ZmZWUyMzQ0fHAubDowfHAuczowLHMudDo1MXxzLmU6Zy5zfHAubDowLHMudDo1MXxzLmU6bC50LmZ8cC5jOiNmZjk5OTk5OSxzLnQ6NTF8cy5lOmwudC5zfHAuczotNTIscy50OjR8cy5lOmd8cC5jOiNmZjAwMDAwMHxwLmw6MTl8cC52Om9mZixzLnQ6NnxzLmU6Z3xwLmM6I2ZmODcyM2VlfHAubDotNzgscy50OjZ8cy5lOmx8cC52Om9mZg';
        	$googlemap_style = '[{"featureType":"all","elementType":"labels","stylers":[{"visibility":"on"}]},{"featureType":"all","elementType":"labels.text.fill","stylers":[{"saturation":36},{"color":"#000000"},{"lightness":40}]},{"featureType":"all","elementType":"labels.text.stroke","stylers":[{"visibility":"on"},{"color":"#000000"},{"lightness":16}]},{"featureType":"all","elementType":"labels.icon","stylers":[{"visibility":"off"}]},{"featureType":"administrative","elementType":"geometry.fill","stylers":[{"color":"#000000"},{"lightness":20}]},{"featureType":"administrative","elementType":"geometry.stroke","stylers":[{"color":"#000000"},{"lightness":17},{"weight":1.2}]},{"featureType":"administrative","elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"administrative","elementType":"labels.text","stylers":[{"visibility":"off"}]},{"featureType":"administrative.locality","elementType":"labels.text.fill","stylers":[{"color":"#c4c4c4"}]},{"featureType":"administrative.neighborhood","elementType":"labels.text.fill","stylers":[{"color":"#707070"}]},{"featureType":"landscape","elementType":"geometry","stylers":[{"color":"#250046"},{"lightness":"-20"},{"visibility":"on"},{"saturation":"55"}]},{"featureType":"landscape","elementType":"geometry.fill","stylers":[{"visibility":"on"}]},{"featureType":"landscape","elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"poi","elementType":"geometry","stylers":[{"color":"#000000"},{"lightness":21},{"visibility":"off"}]},{"featureType":"poi.business","elementType":"geometry","stylers":[{"visibility":"off"}]},{"featureType":"road","elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"road.highway","elementType":"geometry.fill","stylers":[{"color":"#ee2344"},{"lightness":"0"},{"visibility":"on"}]},{"featureType":"road.highway","elementType":"geometry.stroke","stylers":[{"visibility":"off"}]},{"featureType":"road.highway","elementType":"labels.text.fill","stylers":[{"visibility":"off"}]},{"featureType":"road.highway","elementType":"labels.text.stroke","stylers":[{"visibility":"off"},{"hue":"#ff000a"}]},{"featureType":"road.arterial","elementType":"geometry","stylers":[{"color":"#000000"},{"lightness":18}]},{"featureType":"road.arterial","elementType":"geometry.fill","stylers":[{"color":"#ee2344"},{"lightness":"0"}]},{"featureType":"road.arterial","elementType":"geometry.stroke","stylers":[{"visibility":"off"},{"weight":"2.00"}]},{"featureType":"road.arterial","elementType":"labels.text.fill","stylers":[{"color":"#ffffff"}]},{"featureType":"road.arterial","elementType":"labels.text.stroke","stylers":[{"color":"#2c2c2c"}]},{"featureType":"road.local","elementType":"geometry","stylers":[{"color":"#ee2344"},{"lightness":"0"},{"saturation":"0"}]},{"featureType":"road.local","elementType":"geometry.stroke","stylers":[{"lightness":"0"}]},{"featureType":"road.local","elementType":"labels.text.fill","stylers":[{"color":"#999999"}]},{"featureType":"road.local","elementType":"labels.text.stroke","stylers":[{"saturation":"-52"}]},{"featureType":"transit","elementType":"geometry","stylers":[{"color":"#000000"},{"lightness":19},{"visibility":"off"}]},{"featureType":"water","elementType":"geometry","stylers":[{"color":"#8723ee"},{"lightness":"-78"}]},{"featureType":"water","elementType":"labels","stylers":[{"visibility":"off"}]}]';
        	break;

        case "veins": //Veins
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcC5oOiNCNjE1MzB8cC5zOjYwfHAubDotNDAscy5lOmwudC5mfHAuYzojZmZmZmZmZmYscy50OjZ8cC5jOiNmZkI2MTUzMCxzLnQ6M3xwLmM6I2ZmQjYxNTMwLHMudDo1MXxwLmM6I2ZmQjYxNTMwfHAubDo2LHMudDo0OXxwLmM6I2ZmQjYxNTMwfHAubDotMjUscy50OjUwfHAuYzojZmZCNjE1MzB8cC5sOi0xMCxzLnQ6NHxwLmM6I2ZmQjYxNTMwfHAubDo3MCxzLnQ6NjV8cC5jOiNmZkI2MTUzMHxwLmw6OTAscy50OjE3fHMuZTpsfHAudjpvZmYscy50OjY2fHMuZTpsLnQuc3xwLnY6b2ZmLHMudDo2NnxzLmU6bC50LmZ8cC5jOiNmZmZmZmZmZg';
        	$googlemap_style = '[{"stylers":[{"hue":"#B61530"},{"saturation":60},{"lightness":-40}]},{"elementType":"labels.text.fill","stylers":[{"color":"#ffffff"}]},{"featureType":"water","stylers":[{"color":"#B61530"}]},{"featureType":"road","stylers":[{"color":"#B61530"},{}]},{"featureType":"road.local","stylers":[{"color":"#B61530"},{"lightness":6}]},{"featureType":"road.highway","stylers":[{"color":"#B61530"},{"lightness":-25}]},{"featureType":"road.arterial","stylers":[{"color":"#B61530"},{"lightness":-10}]},{"featureType":"transit","stylers":[{"color":"#B61530"},{"lightness":70}]},{"featureType":"transit.line","stylers":[{"color":"#B61530"},{"lightness":90}]},{"featureType":"administrative.country","elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"transit.station","elementType":"labels.text.stroke","stylers":[{"visibility":"off"}]},{"featureType":"transit.station","elementType":"labels.text.fill","stylers":[{"color":"#ffffff"}]}]';
        	break;

        case "blueprint": //Blueprint
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcy50OjZ8cy5lOmd8cC5jOiNmZjAwMDA0NXxwLmw6MTcscy50OjV8cy5lOmd8cC5jOiNmZjAwMDA0NXxwLmw6MjAscy50OjQ5fHMuZTpnLmZ8cC5jOiNmZjAwMDA0NXxwLmw6MTcscy50OjN8cy5lOmcuc3xwLnY6b2ZmLHMudDo3ODV8cy5lOmcuc3xwLmM6I2ZmMDAwMDQ1fHAubDoyMCxzLnQ6NTB8cy5lOmd8cC5jOiNmZjAwMDA0NXxwLmw6MjUscy50OjUxfHMuZTpnfHAuYzojZmYwMDAwNDV8cC5sOjI1LHMudDoyfHMuZTpnfHAuYzojZmYwMDAwNDV8cC5sOjIxLHMuZTpsLnQuc3xwLnY6b2ZmLHMuZTpsLnQuZnxwLmM6I2ZmNGQ4OGVhLHMuZTpsLml8cC52Om9mZixzLnQ6NHxzLmU6Z3xwLmM6I2ZmMDAwMDQ1fHAubDoxOSxzLnQ6MXxzLmU6Zy5mfHAuYzojZmYwMDAwNDV8cC5sOjIwLHMudDoxfHMuZTpnLnN8cC5jOiNmZjAwMDA0NXxwLmw6MTd8cC53OjEuMg';
        	$googlemap_style = '[{"featureType":"water","elementType":"geometry","stylers":[{"color":"#000045"},{"lightness":17}]},{"featureType":"landscape","elementType":"geometry","stylers":[{"color":"#000045"},{"lightness":20}]},{"featureType":"road.highway","elementType":"geometry.fill","stylers":[{"color":"#000045"},{"lightness":17}]},{"featureType":"road","elementType":"geometry.stroke","stylers":[{"visibility":"off"}]},{"featureType":"road.highway.controlled_access","elementType":"geometry.stroke","stylers":[{"color":"#000045"},{"lightness":20}]},{"featureType":"road.arterial","elementType":"geometry","stylers":[{"color":"#000045"},{"lightness":25}]},{"featureType":"road.local","elementType":"geometry","stylers":[{"color":"#000045"},{"lightness":25}]},{"featureType":"poi","elementType":"geometry","stylers":[{"color":"#000045"},{"lightness":21}]},{"elementType":"labels.text.stroke","stylers":[{"visibility":"off"}]},{"elementType":"labels.text.fill","stylers":[{"saturation":0},{"color":"#4d88ea"},{"lightness":0}]},{"elementType":"labels.icon","stylers":[{"visibility":"off"}]},{"featureType":"transit","elementType":"geometry","stylers":[{"color":"#000045"},{"lightness":19}]},{"featureType":"administrative","elementType":"geometry.fill","stylers":[{"color":"#000045"},{"lightness":20}]},{"featureType":"administrative","elementType":"geometry.stroke","stylers":[{"color":"#000045"},{"lightness":17},{"weight":1.2}]}]';
        	break;

        case "pipboy-maps": //PipBoy Maps
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcy50OjZ8cC5oOiMwQjFFMEN8cC5zOjJ8cC5sOi04OXxwLnY6c2ltcGxpZmllZCxzLnQ6ODF8cC5oOiMwQjFFMEN8cC5zOjI2fHAubDotOTF8cC52Om9uLHMudDo4MnxwLmg6IzBCMUUwQ3xwLnM6Mzd8cC5sOi05MnxwLnY6b24scy50OjQwfHAuaDojMEIxRTBDfHAuczo2fHAubDotOTB8cC52Om9mZixzLnQ6ODF8cC5oOiMwQjFFMEN8cC5zOjI2fHAubDotOTF8cC52Om9mZixzLnQ6M3xwLmg6IzAwRkYwMHxwLnM6MTAwfHAubDotMjJ8cC52Om9uLHMudDo0OXxwLmg6IzAwRkYwMHxwLnM6MTAwfHAubDotMjJ8cC52Om9uLHMudDo1MHxwLmg6IzAwRkYwMHxwLnM6MTAwfHAubDotMzV8cC52Om9uLHMudDo1MXxwLmg6IzAwRkYwMHxwLnM6MTAwfHAubDotNTB8cC52Om9uLHMudDoxfHAuaDojMDBGRjAwfHAuczoxMDB8cC5sOi0yfHAudjpvZmYscy50OjQwfHAuaDojMDBGRjAwfHAuczoxMDB8cC5sOi0zNnxwLnY6b2ZmLHMudDoxOXxwLmg6IzAwRkYwMHxwLnM6MTAwfHAubDo1MHxwLnY6c2ltcGxpZmllZCxzLnQ6MnxwLmg6IzAwRkYwMHxwLnM6MTAwfHAubDotMzZ8cC52Om9mZixzLnQ6Mzh8cC5oOiMwMEZGMDB8cC5zOjEwMHxwLmw6LTQxfHAudjpvZmYscy50OjY';
        	$googlemap_style = '[{"featureType":"water","elementType":"all","stylers":[{"hue":"#0B1E0C"},{"saturation":2},{"lightness":-89},{"visibility":"simplified"}]},{"featureType":"landscape.man_made","elementType":"all","stylers":[{"hue":"#0B1E0C"},{"saturation":26},{"lightness":-91},{"visibility":"on"}]},{"featureType":"landscape.natural","elementType":"all","stylers":[{"hue":"#0B1E0C"},{"saturation":37},{"lightness":-92},{"visibility":"on"}]},{"featureType":"poi.park","elementType":"all","stylers":[{"hue":"#0B1E0C"},{"saturation":6},{"lightness":-90},{"visibility":"off"}]},{"featureType":"landscape.man_made","elementType":"all","stylers":[{"hue":"#0B1E0C"},{"saturation":26},{"lightness":-91},{"visibility":"off"}]},{"featureType":"road","elementType":"all","stylers":[{"hue":"#00FF00"},{"saturation":100},{"lightness":-22},{"visibility":"on"}]},{"featureType":"road.highway","elementType":"all","stylers":[{"hue":"#00FF00"},{"saturation":100},{"lightness":-22},{"visibility":"on"}]},{"featureType":"road.arterial","elementType":"all","stylers":[{"hue":"#00FF00"},{"saturation":100},{"lightness":-35},{"visibility":"on"}]},{"featureType":"road.local","elementType":"all","stylers":[{"hue":"#00FF00"},{"saturation":100},{"lightness":-50},{"visibility":"on"}]},{"featureType":"administrative","elementType":"all","stylers":[{"hue":"#00FF00"},{"saturation":100},{"lightness":-2},{"visibility":"off"}]},{"featureType":"poi.park","elementType":"all","stylers":[{"hue":"#00FF00"},{"saturation":100},{"lightness":-36},{"visibility":"off"}]},{"featureType":"administrative.locality","elementType":"all","stylers":[{"hue":"#00FF00"},{"saturation":100},{"lightness":50},{"visibility":"simplified"}]},{"featureType":"poi","elementType":"all","stylers":[{"hue":"#00FF00"},{"saturation":100},{"lightness":-36},{"visibility":"off"}]},{"featureType":"poi.place_of_worship","elementType":"all","stylers":[{"hue":"#00FF00"},{"saturation":100},{"lightness":-41},{"visibility":"off"}]},{"featureType":"water","elementType":"all","stylers":[]}]';
        	break;

        case "tinia": //Tinia
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcy5lOmwudC5mfHAuczozNnxwLmM6I2ZmMDAwMDAwfHAubDo0MCxzLmU6bC50LnN8cC52Om9ufHAuYzojZmYwMDAwMDB8cC5sOjE2LHMuZTpsLml8cC52Om9mZixzLnQ6MXxzLmU6Zy5mfHAuYzojZmYwMDAwMDB8cC5sOjIwLHMudDoxfHMuZTpnLnN8cC5jOiNmZjAwMDAwMHxwLmw6MTd8cC53OjEuMixzLnQ6MXxzLmU6bHxwLnY6b2ZmLHMudDoxN3xwLnY6c2ltcGxpZmllZCxzLnQ6MTd8cy5lOmd8cC52OnNpbXBsaWZpZWQscy50OjE3fHMuZTpsLnR8cC52OnNpbXBsaWZpZWQscy50OjE4fHAudjpvZmYscy50OjE5fHAudjpzaW1wbGlmaWVkfHAuczotMTAwfHAubDozMCxzLnQ6MjB8cC52Om9mZixzLnQ6MjF8cC52Om9mZixzLnQ6NXxwLnY6c2ltcGxpZmllZHxwLmc6MC4wMHxwLmw6NzQscy50OjV8cy5lOmd8cC5jOiNmZjM0MzM0ZnxwLmw6LTM3LHMudDo4MXxwLmw6MyxzLnQ6MnxwLnY6b2ZmLHMudDoyfHMuZTpnfHAuYzojZmYwMDAwMDB8cC5sOjIxLHMudDozfHMuZTpnfHAudjpzaW1wbGlmaWVkLHMudDo0OXxzLmU6Zy5mfHAuYzojZmYyZDJjNDV8cC5sOjAscy50OjQ5fHMuZTpnLnN8cC5jOiNmZjAwMDAwMHxwLmw6Mjl8cC53OjAuMixzLnQ6NDl8cy5lOmwudC5mfHAuYzojZmY3ZDdjOWJ8cC5sOjQzLHMudDo0OXxzLmU6bC50LnN8cC52Om9mZixzLnQ6NTB8cy5lOmd8cC5jOiNmZjJkMmM0NXxwLmw6MSxzLnQ6NTB8cy5lOmwudHxwLnY6b24scy50OjUwfHMuZTpsLnQuZnxwLmM6I2ZmN2Q3YzliLHMudDo1MHxzLmU6bC50LnN8cC52Om9mZixzLnQ6NTF8cy5lOmd8cC5jOiNmZjJkMmM0NXxwLmw6LTF8cC5nOjEscy50OjUxfHMuZTpsLnR8cC52Om9ufHAuaDojZmYwMDAwLHMudDo1MXxzLmU6bC50LmZ8cC5jOiNmZjdkN2M5YnxwLmw6LTMxLHMudDo1MXxzLmU6bC50LnN8cC52Om9mZixzLnQ6NHxzLmU6Z3xwLmM6I2ZmMmQyYzQ1fHAubDotMzYscy50OjZ8cy5lOmd8cC5jOiNmZjJkMmM0NXxwLmw6MHxwLmc6MSxzLnQ6NnxzLmU6bC50LnN8cC52Om9mZg';
        	$googlemap_style = '[{"featureType":"all","elementType":"labels.text.fill","stylers":[{"saturation":36},{"color":"#000000"},{"lightness":40}]},{"featureType":"all","elementType":"labels.text.stroke","stylers":[{"visibility":"on"},{"color":"#000000"},{"lightness":16}]},{"featureType":"all","elementType":"labels.icon","stylers":[{"visibility":"off"}]},{"featureType":"administrative","elementType":"geometry.fill","stylers":[{"color":"#000000"},{"lightness":20}]},{"featureType":"administrative","elementType":"geometry.stroke","stylers":[{"color":"#000000"},{"lightness":17},{"weight":1.2}]},{"featureType":"administrative","elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"administrative.country","elementType":"all","stylers":[{"visibility":"simplified"}]},{"featureType":"administrative.country","elementType":"geometry","stylers":[{"visibility":"simplified"}]},{"featureType":"administrative.country","elementType":"labels.text","stylers":[{"visibility":"simplified"}]},{"featureType":"administrative.province","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"administrative.locality","elementType":"all","stylers":[{"visibility":"simplified"},{"saturation":"-100"},{"lightness":"30"}]},{"featureType":"administrative.neighborhood","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"administrative.land_parcel","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"landscape","elementType":"all","stylers":[{"visibility":"simplified"},{"gamma":"0.00"},{"lightness":"74"}]},{"featureType":"landscape","elementType":"geometry","stylers":[{"color":"#34334f"},{"lightness":"-37"}]},{"featureType":"landscape.man_made","elementType":"all","stylers":[{"lightness":"3"}]},{"featureType":"poi","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"poi","elementType":"geometry","stylers":[{"color":"#000000"},{"lightness":21}]},{"featureType":"road","elementType":"geometry","stylers":[{"visibility":"simplified"}]},{"featureType":"road.highway","elementType":"geometry.fill","stylers":[{"color":"#2d2c45"},{"lightness":"0"}]},{"featureType":"road.highway","elementType":"geometry.stroke","stylers":[{"color":"#000000"},{"lightness":29},{"weight":0.2}]},{"featureType":"road.highway","elementType":"labels.text.fill","stylers":[{"color":"#7d7c9b"},{"lightness":"43"}]},{"featureType":"road.highway","elementType":"labels.text.stroke","stylers":[{"visibility":"off"}]},{"featureType":"road.arterial","elementType":"geometry","stylers":[{"color":"#2d2c45"},{"lightness":"1"}]},{"featureType":"road.arterial","elementType":"labels.text","stylers":[{"visibility":"on"}]},{"featureType":"road.arterial","elementType":"labels.text.fill","stylers":[{"color":"#7d7c9b"}]},{"featureType":"road.arterial","elementType":"labels.text.stroke","stylers":[{"visibility":"off"}]},{"featureType":"road.local","elementType":"geometry","stylers":[{"color":"#2d2c45"},{"lightness":"-1"},{"gamma":"1"}]},{"featureType":"road.local","elementType":"labels.text","stylers":[{"visibility":"on"},{"hue":"#ff0000"}]},{"featureType":"road.local","elementType":"labels.text.fill","stylers":[{"color":"#7d7c9b"},{"lightness":"-31"}]},{"featureType":"road.local","elementType":"labels.text.stroke","stylers":[{"visibility":"off"}]},{"featureType":"transit","elementType":"geometry","stylers":[{"color":"#2d2c45"},{"lightness":"-36"}]},{"featureType":"water","elementType":"geometry","stylers":[{"color":"#2d2c45"},{"lightness":"0"},{"gamma":"1"}]},{"featureType":"water","elementType":"labels.text.stroke","stylers":[{"visibility":"off"}]}]';
        	break;

        case "behancehk": //BehanceHK
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcy5lOmwudC5mfHAuYzojZmYwMDU3ZmYscy5lOmwudC5zfHAuYzojZmZmZmZmZmYscy5lOmwuaXxwLnY6b2ZmLHMudDoxfHMuZTpnLmZ8cC5jOiNmZmZmZmZmZixzLnQ6MXxzLmU6Zy5zfHAuYzojZmYwMDU3ZmYscy50OjV8cy5lOmcuZnxwLmM6I2ZmZmZmZmZmLHMudDoyfHMuZTpnLmZ8cC5jOiNmZmZmZmZmZixzLnQ6M3xzLmU6Zy5mfHAuYzojZmZmZmZmZmYscy50OjN8cy5lOmcuc3xwLmM6I2ZmMDA1N2ZmLHMudDo0fHAudjpvZmYscy50OjZ8cy5lOmcuZnxwLmM6I2ZmZmZmZmZmLHMudDo2fHMuZTpsLml8cC52Om9mZg';
        	$googlemap_style = '[{"featureType":"all","elementType":"labels.text.fill","stylers":[{"color":"#0057ff"}]},{"featureType":"all","elementType":"labels.text.stroke","stylers":[{"color":"#ffffff"}]},{"featureType":"all","elementType":"labels.icon","stylers":[{"visibility":"off"}]},{"featureType":"administrative","elementType":"geometry.fill","stylers":[{"color":"#ffffff"}]},{"featureType":"administrative","elementType":"geometry.stroke","stylers":[{"color":"#0057ff"}]},{"featureType":"landscape","elementType":"geometry.fill","stylers":[{"color":"#ffffff"}]},{"featureType":"poi","elementType":"geometry.fill","stylers":[{"color":"#ffffff"}]},{"featureType":"road","elementType":"geometry.fill","stylers":[{"color":"#ffffff"}]},{"featureType":"road","elementType":"geometry.stroke","stylers":[{"color":"#0057ff"}]},{"featureType":"transit","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"water","elementType":"geometry.fill","stylers":[{"color":"#ffffff"}]},{"featureType":"water","elementType":"labels.icon","stylers":[{"visibility":"off"}]}]';
        	break;

        case "st-martin": //St. Martin, Paris (brighter)
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcC53OjEscy5lOmd8cC5jOiNmZjA3YTFiMyxzLmU6Zy5zfHAudjpvbnxwLnc6LjUscy5lOmx8cC52Om9mZixzLmU6bC50LmZ8cC5jOiNmZmZmZmZmZnxwLnY6b2ZmLHMuZTpsLnQuc3xwLnY6b2ZmfHAuYzojZmYzZTYwNmZ8cC53OjJ8cC5nOjAuODQscy5lOmwuaXxwLnY6b2ZmLHMudDoxfHMuZTpnfHAudzowLjZ8cC5jOiNmZjAzOGI5ZSxzLnQ6NXxzLmU6Z3xwLmM6I2ZmMDA5N2E5LHMudDozN3xzLmU6Zy5mfHAuYzojZmYwM2EyYjYscy50OjMzfHMuZTpnfHAuYzojZmYwNGFjYzAscy50OjM0fHMuZTpnfHAuYzojZmYwM2EyYjYscy50OjM2fHMuZTpnfHAuYzojZmYwM2EyYjYscy50OjQwfHMuZTpnfHAudzoxLjAwfHAuYzojZmYwNGIyYzYscy50OjM4fHMuZTpnfHAuYzojZmYwM2EyYjYscy50OjM1fHMuZTpnfHAuYzojZmYwM2EyYjYscy50OjM5fHMuZTpnLmZ8cC5jOiNmZjAzYTJiNixzLnQ6M3xwLmM6I2ZmMDA4YjlkfHAudjpvbixzLnQ6M3xzLmU6bHxwLnY6b2ZmLHMudDo0fHAudjpvbixzLnQ6NHxzLmU6Z3xwLmM6I2ZmMDI3MTg0LHMudDo0fHMuZTpsfHAudjpvZmYscy50OjZ8cy5lOmcuZnxwLmM6I2ZmMDM3Nzhh';
        	$googlemap_style = '[{"featureType":"all","elementType":"all","stylers":[{"weight":"1"}]},{"featureType":"all","elementType":"geometry","stylers":[{"color":"#07a1b3"}]},{"featureType":"all","elementType":"geometry.stroke","stylers":[{"visibility":"on"},{"weight":".5"}]},{"featureType":"all","elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"all","elementType":"labels.text.fill","stylers":[{"color":"#ffffff"},{"visibility":"off"}]},{"featureType":"all","elementType":"labels.text.stroke","stylers":[{"visibility":"off"},{"color":"#3e606f"},{"weight":2},{"gamma":0.84}]},{"featureType":"all","elementType":"labels.icon","stylers":[{"visibility":"off"}]},{"featureType":"administrative","elementType":"geometry","stylers":[{"weight":0.6},{"color":"#038b9e"}]},{"featureType":"landscape","elementType":"geometry","stylers":[{"color":"#0097a9"}]},{"featureType":"poi.attraction","elementType":"geometry.fill","stylers":[{"color":"#03a2b6"}]},{"featureType":"poi.business","elementType":"geometry","stylers":[{"color":"#04acc0"}]},{"featureType":"poi.government","elementType":"geometry","stylers":[{"color":"#03a2b6"}]},{"featureType":"poi.medical","elementType":"geometry","stylers":[{"color":"#03a2b6"}]},{"featureType":"poi.park","elementType":"geometry","stylers":[{"weight":"1.00"},{"color":"#04b2c6"}]},{"featureType":"poi.place_of_worship","elementType":"geometry","stylers":[{"color":"#03a2b6"}]},{"featureType":"poi.school","elementType":"geometry","stylers":[{"color":"#03a2b6"}]},{"featureType":"poi.sports_complex","elementType":"geometry.fill","stylers":[{"color":"#03a2b6"}]},{"featureType":"road","elementType":"all","stylers":[{"color":"#008b9d"},{"visibility":"on"}]},{"featureType":"road","elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"transit","elementType":"all","stylers":[{"visibility":"on"}]},{"featureType":"transit","elementType":"geometry","stylers":[{"color":"#027184"}]},{"featureType":"transit","elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"water","elementType":"geometry.fill","stylers":[{"color":"#03778a"}]}]';
        	break;

        case "automax": //AutoMax
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcy50OjF8cy5lOmwudC5mfHAuYzojZmY0NDQ0NDQscy50OjV8cC5jOiNmZmYyZjJmMixzLnQ6MnxwLnY6b2ZmLHMudDozfHAuczotMTAwfHAubDo0NSxzLnQ6NDl8cC52OnNpbXBsaWZpZWQscy50OjUwfHMuZTpsLml8cC52Om9mZixzLnQ6NHxwLnY6b2ZmLHMudDo2fHAuYzojZmZlNzRjM2N8cC52Om9u';
        	$googlemap_style = '[{"featureType":"administrative","elementType":"labels.text.fill","stylers":[{"color":"#444444"}]},{"featureType":"landscape","elementType":"all","stylers":[{"color":"#f2f2f2"}]},{"featureType":"poi","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"road","elementType":"all","stylers":[{"saturation":-100},{"lightness":45}]},{"featureType":"road.highway","elementType":"all","stylers":[{"visibility":"simplified"}]},{"featureType":"road.arterial","elementType":"labels.icon","stylers":[{"visibility":"off"}]},{"featureType":"transit","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"water","elementType":"all","stylers":[{"color":"#e74c3c"},{"visibility":"on"}]}]';
        	break;

        case "colorblind-friendly": //Colorblind-friendly
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcy50OjZ8cC5oOiMwMDcyQjJ8cC5zOjEwMHxwLmw6LTU0fHAudjpvbixzLnQ6NXxwLmg6I0U2OUYwMHxwLnM6MTAwfHAubDotNDl8cC52Om9uLHMudDoyfHAuaDojRDU1RTAwfHAuczoxMDB8cC5sOi00NnxwLnY6b24scy50OjUxfHAuaDojQ0M3OUE3fHAuczotNTV8cC5sOi0zNnxwLnY6b24scy50OjUwfHAuaDojRjBFNDQyfHAuczotMTV8cC5sOi0yMnxwLnY6b24scy50OjQ5fHAuaDojNTZCNEU5fHAuczotMjN8cC5sOi0yfHAudjpvbixzLnQ6MXxzLmU6Z3xwLmg6IzAwMDAwMHxwLmw6LTEwMHxwLnY6b24scy50OjR8cC5oOiMwMDlFNzN8cC5zOjEwMHxwLmw6LTU5fHAudjpvbg';
        	$googlemap_style = '[{"featureType":"water","elementType":"all","stylers":[{"hue":"#0072B2"},{"saturation":100},{"lightness":-54},{"visibility":"on"}]},{"featureType":"landscape","elementType":"all","stylers":[{"hue":"#E69F00"},{"saturation":100},{"lightness":-49},{"visibility":"on"}]},{"featureType":"poi","elementType":"all","stylers":[{"hue":"#D55E00"},{"saturation":100},{"lightness":-46},{"visibility":"on"}]},{"featureType":"road.local","elementType":"all","stylers":[{"hue":"#CC79A7"},{"saturation":-55},{"lightness":-36},{"visibility":"on"}]},{"featureType":"road.arterial","elementType":"all","stylers":[{"hue":"#F0E442"},{"saturation":-15},{"lightness":-22},{"visibility":"on"}]},{"featureType":"road.highway","elementType":"all","stylers":[{"hue":"#56B4E9"},{"saturation":-23},{"lightness":-2},{"visibility":"on"}]},{"featureType":"administrative","elementType":"geometry","stylers":[{"hue":"#000000"},{"saturation":0},{"lightness":-100},{"visibility":"on"}]},{"featureType":"transit","elementType":"all","stylers":[{"hue":"#009E73"},{"saturation":100},{"lightness":-59},{"visibility":"on"}]}]';
        	break;

        case "nightrider": //NightRider
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcy50OjF8cy5lOmcuZnxwLmM6I2ZmMWUyNDJifHAubDo1LHMudDoxfHMuZTpnLnN8cC5jOiNmZjFlMjQyYnxwLnM6MHxwLmw6MzAscy50OjF8cy5lOmx8cC5jOiNmZjFlMjQyYnxwLmw6MzAscy50OjF8cy5lOmwudC5zfHAudjpvZmYscy50OjE4fHMuZTpnLnN8cC5jOiNmZjFlMjQyYnxwLmw6MjB8cC53OjEuMDAscy50OjIwfHMuZTpsLnQuZnxwLmw6LTIwLHMudDoyMXxzLmU6bC50LmZ8cC5sOi0yMCxzLnQ6NXxzLmU6Z3xwLmM6I2ZmMWUyNDJiLHMudDo1fHMuZTpsfHAuYzojZmYxZTI0MmJ8cC5sOjMwLHMudDo1fHMuZTpsLnQuc3xwLnY6b2ZmLHMudDoyfHMuZTpnfHAuYzojZmYxZTI0MmJ8cC5sOjUscy50OjJ8cy5lOmx8cC5jOiNmZjFlMjQyYnxwLmw6MzAscy50OjJ8cy5lOmwudC5zfHAudjpvZmYscy50OjN8cy5lOmd8cC52OnNpbXBsaWZpZWR8cC5jOiNmZjFlMjQyYnxwLmw6MTUscy50OjN8cy5lOmx8cC52Om9mZixzLnQ6NHxzLmU6Z3xwLmM6I2ZmMWUyNDJifHAubDo2LHMudDo0fHMuZTpsfHAuYzojZmYxZTI0MmJ8cC5sOjMwLHMudDo0fHMuZTpsLnQuc3xwLnY6b2ZmLHMudDo2fHMuZTpnfHAuYzojZmYwMTAzMDYscy50OjZ8cy5lOmwudC5zfHAudjpvZmY';
        	$googlemap_style = '[{"featureType":"administrative","elementType":"geometry.fill","stylers":[{"color":"#1e242b"},{"lightness":"5"}]},{"featureType":"administrative","elementType":"geometry.stroke","stylers":[{"color":"#1e242b"},{"saturation":"0"},{"lightness":"30"}]},{"featureType":"administrative","elementType":"labels","stylers":[{"color":"#1e242b"},{"lightness":"30"}]},{"featureType":"administrative","elementType":"labels.text.stroke","stylers":[{"visibility":"off"}]},{"featureType":"administrative.province","elementType":"geometry.stroke","stylers":[{"color":"#1e242b"},{"lightness":"20"},{"weight":"1.00"}]},{"featureType":"administrative.neighborhood","elementType":"labels.text.fill","stylers":[{"lightness":"-20"}]},{"featureType":"administrative.land_parcel","elementType":"labels.text.fill","stylers":[{"lightness":"-20"}]},{"featureType":"landscape","elementType":"geometry","stylers":[{"color":"#1e242b"}]},{"featureType":"landscape","elementType":"labels","stylers":[{"color":"#1e242b"},{"lightness":"30"}]},{"featureType":"landscape","elementType":"labels.text.stroke","stylers":[{"visibility":"off"}]},{"featureType":"poi","elementType":"geometry","stylers":[{"color":"#1e242b"},{"lightness":"5"}]},{"featureType":"poi","elementType":"labels","stylers":[{"color":"#1e242b"},{"lightness":"30"}]},{"featureType":"poi","elementType":"labels.text.stroke","stylers":[{"visibility":"off"}]},{"featureType":"road","elementType":"geometry","stylers":[{"visibility":"simplified"},{"color":"#1e242b"},{"lightness":"15"}]},{"featureType":"road","elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"transit","elementType":"geometry","stylers":[{"color":"#1e242b"},{"lightness":"6"}]},{"featureType":"transit","elementType":"labels","stylers":[{"color":"#1e242b"},{"lightness":"30"}]},{"featureType":"transit","elementType":"labels.text.stroke","stylers":[{"visibility":"off"}]},{"featureType":"water","elementType":"geometry","stylers":[{"color":"#010306"}]},{"featureType":"water","elementType":"labels.text.stroke","stylers":[{"visibility":"off"}]}]';
        	break;

        case "hcre": //HCRE
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcy5lOmcuZnxwLmM6I2ZmZWJlYmViLHMudDo4MXxzLmU6Zy5mfHAuYzojZmZkNmQyY2Mscy50OjJ8cy5lOmcuZnxwLmM6I2ZmOGQ4NjdjLHMudDo0OXxzLmU6Zy5mfHAuYzojZmY4YjFiNDEscy50OjQ5fHMuZTpnLnN8cC5jOiNmZjhiMWI0MXxwLmw6NTAscy50OjUwfHMuZTpnLmZ8cC5jOiNmZmZjZDI3ZixzLnQ6NTB8cy5lOmcuc3xwLmM6I2ZmZmNkMjdmfHAubDo1MCxzLnQ6NnxzLmU6Zy5mfHAuYzojZmYxMjIwMmZ8cC5nOjIuMDAscy50OjZ8cy5lOmwudC5mfHAubDoxMDA';
        	$googlemap_style = '[{"featureType":"all","elementType":"geometry.fill","stylers":[{"color":"#ebebeb"}]},{"featureType":"landscape.man_made","elementType":"geometry.fill","stylers":[{"color":"#d6d2cc"}]},{"featureType":"poi","elementType":"geometry.fill","stylers":[{"color":"#8d867c"}]},{"featureType":"road.highway","elementType":"geometry.fill","stylers":[{"color":"#8b1b41"}]},{"featureType":"road.highway","elementType":"geometry.stroke","stylers":[{"color":"#8b1b41"},{"lightness":"50"}]},{"featureType":"road.arterial","elementType":"geometry.fill","stylers":[{"color":"#fcd27f"}]},{"featureType":"road.arterial","elementType":"geometry.stroke","stylers":[{"color":"#fcd27f"},{"lightness":"50"}]},{"featureType":"water","elementType":"geometry.fill","stylers":[{"color":"#12202f"},{"gamma":"2.00"}]},{"featureType":"water","elementType":"labels.text.fill","stylers":[{"lightness":"100"}]}]';
        	break;

        case "celestial-blue": //Celestial Blue
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcy5lOmcuc3xwLnY6b2ZmLHMuZTpsfHAudjpvZmYscy50OjE5fHMuZTpsLnQuZnxwLnY6b2ZmLHMudDoyMHxzLmU6bC50LmZ8cC52Om9mZixzLnQ6NXxzLmU6Zy5mfHAuYzojZmYwMUM1RkYscy50OjM3fHMuZTpnLmZ8cC5jOiNmZjAwMjU3MyxzLnQ6MzN8cy5lOmcuZnxwLmM6I2ZmRkZFRDAwLHMudDozNHxzLmU6Zy5mfHAuYzojZmZENDFDMUQscy50OjQwfHMuZTpnLmZ8cC5jOiNmZjAwMkZBNyxzLnQ6MzV8cy5lOmcuZnxwLmM6I2ZmQkYwMDAwLHMudDozfHMuZTpnLmZ8cC5jOiNmZkZDRkZGNixzLnQ6NjV8cy5lOmcuZnxwLnM6LTEwMCxzLnQ6NnxzLmU6Zy5mfHAuYzojZmZCQ0YyRjQ';
        	$googlemap_style = '[{"featureType":"all","elementType":"geometry.stroke","stylers":[{"visibility":"off"}]},{"featureType":"all","elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"administrative.locality","elementType":"labels.text.fill","stylers":[{"visibility":"off"}]},{"featureType":"administrative.neighborhood","elementType":"labels.text.fill","stylers":[{"visibility":"off"}]},{"featureType":"landscape","elementType":"geometry.fill","stylers":[{"color":"#01C5FF"}]},{"featureType":"poi.attraction","elementType":"geometry.fill","stylers":[{"color":"#002573"}]},{"featureType":"poi.business","elementType":"geometry.fill","stylers":[{"color":"#FFED00"}]},{"featureType":"poi.government","elementType":"geometry.fill","stylers":[{"color":"#D41C1D"}]},{"featureType":"poi.park","elementType":"geometry.fill","stylers":[{"color":"#002FA7"}]},{"featureType":"poi.school","elementType":"geometry.fill","stylers":[{"color":"#BF0000"}]},{"featureType":"road","elementType":"geometry.fill","stylers":[{"color":"#FCFFF6"}]},{"featureType":"transit.line","elementType":"geometry.fill","stylers":[{"saturation":-100}]},{"featureType":"water","elementType":"geometry.fill","stylers":[{"color":"#BCF2F4"}]}]';
        	break;

        case "best-ski-pros": //Best Ski Pros
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcy50OjF8cy5lOmwudC5mfHAuYzojZmYyYzM2NDUscy50OjF8cy5lOmwudC5zfHAudjpvbixzLnQ6NXxwLmM6I2ZmZGNkY2RjLHMudDo4MXxzLmU6Zy5zfHAuYzojZmY0NzY2NTMscy50OjEzMTN8cy5lOmcuZnxwLnY6b258cC5jOiNmZjkzZDA5ZSxzLnQ6MTMxNHxzLmU6bHxwLnY6b258cC5jOiNmZjBkNmYzMixzLnQ6MTMxNHxzLmU6bC50LnN8cC52Om9uLHMudDoyfHAudjpvbixzLnQ6MnxzLmU6Zy5mfHAudjpvbnxwLmM6I2ZmNjJiZjg1LHMudDozfHAuczotMTAwfHAubDo0NSxzLnQ6M3xzLmU6Zy5zfHAudjpvbnxwLmM6I2ZmOTVjNGE3LHMudDozfHMuZTpsLnR8cC5jOiNmZjMzNDc2NyxzLnQ6M3xzLmU6bC50LmZ8cC52Om9ufHAuYzojZmYzMzQ3Njcscy50OjQ5fHAudjpzaW1wbGlmaWVkLHMudDo1MHxzLmU6bC5pfHAudjpvZmYscy50OjUxfHMuZTpnLnN8cC52Om9ufHAuYzojZmZiN2I3Yjcscy50OjUxfHMuZTpsLnR8cC52Om9uLHMudDo0fHAudjpvbnxwLmM6I2ZmMzY0YTZhLHMudDo0fHMuZTpsLnQuZnxwLnY6b258cC5jOiNmZmZmZmZmZixzLnQ6NHxzLmU6bC50LnN8cC52Om9uLHMudDoxMDU3fHMuZTpnLnN8cC52Om9ufHAuYzojZmY1MzUzNTMscy50OjZ8cC5jOiNmZjNmYzY3MnxwLnY6b24scy50OjZ8cy5lOmcuZnxwLnY6b258cC5jOiNmZjRkNjQ4OSxzLnQ6NnxzLmU6bC50LnN8cC52Om9mZg';
        	$googlemap_style = '[{"featureType":"administrative","elementType":"labels.text.fill","stylers":[{"color":"#2c3645"}]},{"featureType":"administrative","elementType":"labels.text.stroke","stylers":[{"visibility":"on"}]},{"featureType":"landscape","elementType":"all","stylers":[{"color":"#dcdcdc"}]},{"featureType":"landscape.man_made","elementType":"geometry.stroke","stylers":[{"color":"#476653"}]},{"featureType":"landscape.natural.landcover","elementType":"geometry.fill","stylers":[{"visibility":"on"},{"color":"#93d09e"}]},{"featureType":"landscape.natural.terrain","elementType":"labels","stylers":[{"visibility":"on"},{"color":"#0d6f32"}]},{"featureType":"landscape.natural.terrain","elementType":"labels.text.stroke","stylers":[{"visibility":"on"}]},{"featureType":"poi","elementType":"all","stylers":[{"visibility":"on"}]},{"featureType":"poi","elementType":"geometry.fill","stylers":[{"visibility":"on"},{"color":"#62bf85"}]},{"featureType":"road","elementType":"all","stylers":[{"saturation":-100},{"lightness":45}]},{"featureType":"road","elementType":"geometry.stroke","stylers":[{"visibility":"on"},{"color":"#95c4a7"}]},{"featureType":"road","elementType":"labels.text","stylers":[{"color":"#334767"}]},{"featureType":"road","elementType":"labels.text.fill","stylers":[{"visibility":"on"},{"color":"#334767"}]},{"featureType":"road.highway","elementType":"all","stylers":[{"visibility":"simplified"}]},{"featureType":"road.arterial","elementType":"labels.icon","stylers":[{"visibility":"off"}]},{"featureType":"road.local","elementType":"geometry.stroke","stylers":[{"visibility":"on"},{"color":"#b7b7b7"}]},{"featureType":"road.local","elementType":"labels.text","stylers":[{"visibility":"on"}]},{"featureType":"transit","elementType":"all","stylers":[{"visibility":"on"},{"color":"#364a6a"}]},{"featureType":"transit","elementType":"labels.text.fill","stylers":[{"visibility":"on"},{"color":"#ffffff"}]},{"featureType":"transit","elementType":"labels.text.stroke","stylers":[{"visibility":"on"}]},{"featureType":"transit.station.rail","elementType":"geometry.stroke","stylers":[{"visibility":"on"},{"color":"#535353"}]},{"featureType":"water","elementType":"all","stylers":[{"color":"#3fc672"},{"visibility":"on"}]},{"featureType":"water","elementType":"geometry.fill","stylers":[{"visibility":"on"},{"color":"#4d6489"}]},{"featureType":"water","elementType":"labels.text.stroke","stylers":[{"visibility":"off"}]}]';
        	break;

        case "pokemon-go": //Pokemon Go
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcy50OjF8cy5lOmwudC5zfHAudjpvbnxwLmM6I2ZmZjFmZmI4fHAudzoyLjI5LHMudDoyMXxwLnY6b24scy50OjgxfHMuZTpnLmZ8cC5jOiNmZmExZjE5OSxzLnQ6ODF8cy5lOmwudHxwLnY6b258cC5oOiNmZjAwMDAscy50OjEzMTN8cy5lOmcuZnxwLmM6I2ZmMzdiZGEyLHMudDoxMzE0fHMuZTpnLmZ8cC5jOiNmZjM3YmRhMixzLnQ6MnxzLmU6bHxwLnY6b258cC5jOiNmZmFmYTBhMCxzLnQ6MnxzLmU6bC50LnN8cC52Om9ufHAuYzojZmZmMWZmYjgscy50OjM3fHMuZTpnLmZ8cC52Om9uLHMudDozM3xwLnY6b2ZmLHMudDozM3xzLmU6Zy5mfHAuYzojZmZlNGRmZDkscy50OjMzfHMuZTpsLml8cC52Om9mZixzLnQ6MzR8cC52Om9mZixzLnQ6MzZ8cC52Om9mZixzLnQ6NDB8cy5lOmcuZnxwLmM6I2ZmMzdiZGEyLHMudDozOHxwLnY6b2ZmLHMudDozNXxwLnY6b2ZmLHMudDozOXxwLnY6b2ZmLHMudDozfHMuZTpnLmZ8cC5jOiNmZjg0YjA5ZSxzLnQ6M3xzLmU6Zy5zfHAuYzojZmZmYWZlYjh8cC53OjEuMjV8cC52Om9uLHMudDozfHMuZTpsLnQuc3xwLnY6b258cC5jOiNmZmYxZmZiOCxzLnQ6NDl8cy5lOmwuaXxwLnY6b2ZmLHMudDo1MHxzLmU6Zy5zfHAudjpvbnxwLmM6I2ZmZjFmZmI4LHMudDo1MHxzLmU6bC50LnN8cC52Om9ufHAuYzojZmZmMWZmYjgscy50OjUxfHMuZTpnLnN8cC52Om9ufHAuYzojZmZmMWZmYjh8cC53OjEuNDgscy50OjUxfHMuZTpsfHAudjpvZmYscy50OjR8cC52Om9mZixzLnQ6NnxzLmU6Zy5mfHAuYzojZmY1ZGRhZDY';
        	$googlemap_style = '[{"featureType":"administrative","elementType":"labels.text.stroke","stylers":[{"visibility":"on"},{"color":"#f1ffb8"},{"weight":"2.29"}]},{"featureType":"administrative.land_parcel","elementType":"all","stylers":[{"visibility":"on"}]},{"featureType":"landscape.man_made","elementType":"geometry.fill","stylers":[{"color":"#a1f199"}]},{"featureType":"landscape.man_made","elementType":"labels.text","stylers":[{"visibility":"on"},{"hue":"#ff0000"}]},{"featureType":"landscape.natural.landcover","elementType":"geometry.fill","stylers":[{"color":"#37bda2"}]},{"featureType":"landscape.natural.terrain","elementType":"geometry.fill","stylers":[{"color":"#37bda2"}]},{"featureType":"poi","elementType":"labels","stylers":[{"visibility":"on"},{"color":"#afa0a0"}]},{"featureType":"poi","elementType":"labels.text.stroke","stylers":[{"visibility":"on"},{"color":"#f1ffb8"}]},{"featureType":"poi.attraction","elementType":"geometry.fill","stylers":[{"visibility":"on"}]},{"featureType":"poi.business","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"poi.business","elementType":"geometry.fill","stylers":[{"color":"#e4dfd9"}]},{"featureType":"poi.business","elementType":"labels.icon","stylers":[{"visibility":"off"}]},{"featureType":"poi.government","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"poi.medical","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"poi.park","elementType":"geometry.fill","stylers":[{"color":"#37bda2"}]},{"featureType":"poi.place_of_worship","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"poi.school","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"poi.sports_complex","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"road","elementType":"geometry.fill","stylers":[{"color":"#84b09e"}]},{"featureType":"road","elementType":"geometry.stroke","stylers":[{"color":"#fafeb8"},{"weight":"1.25"},{"visibility":"on"}]},{"featureType":"road","elementType":"labels.text.stroke","stylers":[{"visibility":"on"},{"color":"#f1ffb8"}]},{"featureType":"road.highway","elementType":"labels.icon","stylers":[{"visibility":"off"}]},{"featureType":"road.arterial","elementType":"geometry.stroke","stylers":[{"visibility":"on"},{"color":"#f1ffb8"}]},{"featureType":"road.arterial","elementType":"labels.text.stroke","stylers":[{"visibility":"on"},{"color":"#f1ffb8"}]},{"featureType":"road.local","elementType":"geometry.stroke","stylers":[{"visibility":"on"},{"color":"#f1ffb8"},{"weight":"1.48"}]},{"featureType":"road.local","elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"transit","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"water","elementType":"geometry.fill","stylers":[{"color":"#5ddad6"}]}]';
        	break;

        case "vintage-old-golden-brown": //Vintage Old Golden Brown
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcC5jOiNmZmZmNzAwMHxwLmw6Njl8cC5zOjEwMHxwLnc6MS4xN3xwLmc6Mi4wNCxzLmU6Z3xwLmM6I2ZmY2I4NTM2LHMuZTpsfHAuYzojZmZmZmI0NzF8cC5sOjY2fHAuczoxMDAscy5lOmwudC5mfHAuZzowLjAxfHAubDoyMCxzLmU6bC50LnN8cC5zOi0zMXxwLmw6LTMzfHAudzoyfHAuZzowLjgscy5lOmwuaXxwLnY6b2ZmLHMudDo1fHAubDotOHxwLmc6MC45OHxwLnc6Mi40NXxwLnM6MjYscy50OjV8cy5lOmd8cC5sOjMwfHAuczozMCxzLnQ6MnxzLmU6Z3xwLnM6MjAscy50OjQwfHMuZTpnfHAubDoyMHxwLnM6LTIwLHMudDozfHMuZTpnfHAubDoxMHxwLnM6LTMwLHMudDozfHMuZTpnLnN8cC5zOjI1fHAubDoyNSxzLnQ6NnxwLmw6LTIwfHAuYzojZmZlY2MwODA';
        	$googlemap_style = '[{"featureType":"all","elementType":"all","stylers":[{"color":"#ff7000"},{"lightness":"69"},{"saturation":"100"},{"weight":"1.17"},{"gamma":"2.04"}]},{"featureType":"all","elementType":"geometry","stylers":[{"color":"#cb8536"}]},{"featureType":"all","elementType":"labels","stylers":[{"color":"#ffb471"},{"lightness":"66"},{"saturation":"100"}]},{"featureType":"all","elementType":"labels.text.fill","stylers":[{"gamma":0.01},{"lightness":20}]},{"featureType":"all","elementType":"labels.text.stroke","stylers":[{"saturation":-31},{"lightness":-33},{"weight":2},{"gamma":0.8}]},{"featureType":"all","elementType":"labels.icon","stylers":[{"visibility":"off"}]},{"featureType":"landscape","elementType":"all","stylers":[{"lightness":"-8"},{"gamma":"0.98"},{"weight":"2.45"},{"saturation":"26"}]},{"featureType":"landscape","elementType":"geometry","stylers":[{"lightness":30},{"saturation":30}]},{"featureType":"poi","elementType":"geometry","stylers":[{"saturation":20}]},{"featureType":"poi.park","elementType":"geometry","stylers":[{"lightness":20},{"saturation":-20}]},{"featureType":"road","elementType":"geometry","stylers":[{"lightness":10},{"saturation":-30}]},{"featureType":"road","elementType":"geometry.stroke","stylers":[{"saturation":25},{"lightness":25}]},{"featureType":"water","elementType":"all","stylers":[{"lightness":-20},{"color":"#ecc080"}]}]';
        	break;

        case "apple-maps-esque": //Apple Maps-esque
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcy50OjgxfHMuZTpnfHAuYzojZmZmN2YxZGYscy50OjgyfHMuZTpnfHAuYzojZmZkMGUzYjQscy50OjEzMTR8cy5lOmd8cC52Om9mZixzLnQ6MnxzLmU6bHxwLnY6b2ZmLHMudDozM3xwLnY6b2ZmLHMudDozNnxzLmU6Z3xwLmM6I2ZmZmJkM2RhLHMudDo0MHxzLmU6Z3xwLmM6I2ZmYmRlNmFiLHMudDozfHMuZTpnLnN8cC52Om9mZixzLnQ6M3xzLmU6bHxwLnY6b2ZmLHMudDo0OXxzLmU6Zy5mfHAuYzojZmZmZmUxNWYscy50OjQ5fHMuZTpnLnN8cC5jOiNmZmVmZDE1MSxzLnQ6NTB8cy5lOmcuZnxwLmM6I2ZmZmZmZmZmLHMudDo1MXxzLmU6Zy5mfHAuYzpibGFjayxzLnQ6MTA1OXxzLmU6Zy5mfHAuYzojZmZjZmIyZGIscy50OjZ8cy5lOmd8cC5jOiNmZmEyZGFmMg';
        	$googlemap_style = '[{"featureType":"landscape.man_made","elementType":"geometry","stylers":[{"color":"#f7f1df"}]},{"featureType":"landscape.natural","elementType":"geometry","stylers":[{"color":"#d0e3b4"}]},{"featureType":"landscape.natural.terrain","elementType":"geometry","stylers":[{"visibility":"off"}]},{"featureType":"poi","elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"poi.business","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"poi.medical","elementType":"geometry","stylers":[{"color":"#fbd3da"}]},{"featureType":"poi.park","elementType":"geometry","stylers":[{"color":"#bde6ab"}]},{"featureType":"road","elementType":"geometry.stroke","stylers":[{"visibility":"off"}]},{"featureType":"road","elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"road.highway","elementType":"geometry.fill","stylers":[{"color":"#ffe15f"}]},{"featureType":"road.highway","elementType":"geometry.stroke","stylers":[{"color":"#efd151"}]},{"featureType":"road.arterial","elementType":"geometry.fill","stylers":[{"color":"#ffffff"}]},{"featureType":"road.local","elementType":"geometry.fill","stylers":[{"color":"black"}]},{"featureType":"transit.station.airport","elementType":"geometry.fill","stylers":[{"color":"#cfb2db"}]},{"featureType":"water","elementType":"geometry","stylers":[{"color":"#a2daf2"}]}]';
        	break;

        case "unsaturated-browns": //Unsaturated Browns
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcy5lOmd8cC5oOiNmZjQ0MDB8cC5zOi02OHxwLmw6LTR8cC5nOjAuNzIscy50OjN8cy5lOmwuaSxzLnQ6ODF8cy5lOmd8cC5oOiMwMDc3ZmZ8cC5nOjMuMSxzLnQ6NnxwLmg6IzAwY2NmZnxwLmc6MC40NHxwLnM6LTMzLHMudDo0MHxwLmg6IzQ0ZmYwMHxwLnM6LTIzLHMudDo2fHMuZTpsLnQuZnxwLmg6IzAwN2ZmZnxwLmc6MC43N3xwLnM6NjV8cC5sOjk5LHMudDo2fHMuZTpsLnQuc3xwLmc6MC4xMXxwLnc6NS42fHAuczo5OXxwLmg6IzAwOTFmZnxwLmw6LTg2LHMudDo2NXxzLmU6Z3xwLmw6LTQ4fHAuaDojZmY1ZTAwfHAuZzoxLjJ8cC5zOi0yMyxzLnQ6NHxzLmU6bC50LnN8cC5zOi02NHxwLmg6I2ZmOTEwMHxwLmw6MTZ8cC5nOjAuNDd8cC53OjIuNw';
        	$googlemap_style = '[{"elementType":"geometry","stylers":[{"hue":"#ff4400"},{"saturation":-68},{"lightness":-4},{"gamma":0.72}]},{"featureType":"road","elementType":"labels.icon"},{"featureType":"landscape.man_made","elementType":"geometry","stylers":[{"hue":"#0077ff"},{"gamma":3.1}]},{"featureType":"water","stylers":[{"hue":"#00ccff"},{"gamma":0.44},{"saturation":-33}]},{"featureType":"poi.park","stylers":[{"hue":"#44ff00"},{"saturation":-23}]},{"featureType":"water","elementType":"labels.text.fill","stylers":[{"hue":"#007fff"},{"gamma":0.77},{"saturation":65},{"lightness":99}]},{"featureType":"water","elementType":"labels.text.stroke","stylers":[{"gamma":0.11},{"weight":5.6},{"saturation":99},{"hue":"#0091ff"},{"lightness":-86}]},{"featureType":"transit.line","elementType":"geometry","stylers":[{"lightness":-48},{"hue":"#ff5e00"},{"gamma":1.2},{"saturation":-23}]},{"featureType":"transit","elementType":"labels.text.stroke","stylers":[{"saturation":-64},{"hue":"#ff9100"},{"lightness":16},{"gamma":0.47},{"weight":2.7}]}]';
        	break;

        case "flat-map": //Flat Map
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcy5lOmx8cC52Om9mZixzLnQ6NXxwLnY6b258cC5jOiNmZmYzZjRmNCxzLnQ6ODF8cy5lOmd8cC53OjAuOXxwLnY6b2ZmLHMudDo0MHxzLmU6Zy5mfHAudjpvbnxwLmM6I2ZmODNjZWFkLHMudDozfHAudjpvbnxwLmM6I2ZmZmZmZmZmLHMudDozfHMuZTpsfHAudjpvZmYscy50OjQ5fHAudjpvbnxwLmM6I2ZmZmVlMzc5LHMudDo1MHxwLnY6b258cC5jOiNmZmZlZTM3OSxzLnQ6NnxwLnY6b258cC5jOiNmZjdmYzhlZA';
        	$googlemap_style = '[{"featureType":"all","elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"landscape","elementType":"all","stylers":[{"visibility":"on"},{"color":"#f3f4f4"}]},{"featureType":"landscape.man_made","elementType":"geometry","stylers":[{"weight":0.9},{"visibility":"off"}]},{"featureType":"poi.park","elementType":"geometry.fill","stylers":[{"visibility":"on"},{"color":"#83cead"}]},{"featureType":"road","elementType":"all","stylers":[{"visibility":"on"},{"color":"#ffffff"}]},{"featureType":"road","elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"road.highway","elementType":"all","stylers":[{"visibility":"on"},{"color":"#fee379"}]},{"featureType":"road.arterial","elementType":"all","stylers":[{"visibility":"on"},{"color":"#fee379"}]},{"featureType":"water","elementType":"all","stylers":[{"visibility":"on"},{"color":"#7fc8ed"}]}]';
        	break;

        case "multi-brand-network": //Multi Brand Network
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcy5lOmx8cC52Om9uLHMuZTpsLnQuZnxwLnM6MzZ8cC5jOiNmZjAwMDAwMHxwLmw6NDAscy5lOmwudC5zfHAudjpvbnxwLmM6I2ZmMDAwMDAwfHAubDoxNixzLmU6bC5pfHAudjpvZmYscy50OjF8cy5lOmcuZnxwLmM6I2ZmMDAwMDAwfHAubDoyMCxzLnQ6MXxzLmU6Zy5zfHAuYzojZmYwMDAwMDB8cC5sOjE3fHAudzoxLjIscy50OjE3fHMuZTpsLnQuZnxwLmM6I2ZmZTVjMTYzLHMudDoxOXxzLmU6bC50LmZ8cC5jOiNmZmM0YzRjNCxzLnQ6MjB8cy5lOmwudC5mfHAuYzojZmZlNWMxNjMscy50OjV8cy5lOmd8cC5jOiNmZjAwMDAwMHxwLmw6MjAscy50OjJ8cy5lOmd8cC5jOiNmZjAwMDAwMHxwLmw6MjF8cC52Om9uLHMudDozM3xzLmU6Z3xwLnY6b24scy50OjQ5fHMuZTpnLmZ8cC5jOiNmZmU1YzE2M3xwLmw6MCxzLnQ6NDl8cy5lOmcuc3xwLnY6b2ZmLHMudDo0OXxzLmU6bC50LmZ8cC5jOiNmZmZmZmZmZixzLnQ6NDl8cy5lOmwudC5zfHAuYzojZmZlNWMxNjMscy50OjUwfHMuZTpnfHAuYzojZmYwMDAwMDB8cC5sOjE4LHMudDo1MHxzLmU6Zy5mfHAuYzojZmY1NzU3NTcscy50OjUwfHMuZTpsLnQuZnxwLmM6I2ZmZmZmZmZmLHMudDo1MHxzLmU6bC50LnN8cC5jOiNmZjJjMmMyYyxzLnQ6NTF8cy5lOmd8cC5jOiNmZjAwMDAwMHxwLmw6MTYscy50OjUxfHMuZTpsLnQuZnxwLmM6I2ZmOTk5OTk5LHMudDo0fHMuZTpnfHAuYzojZmYwMDAwMDB8cC5sOjE5LHMudDo2fHMuZTpnfHAuYzojZmYwMDAwMDB8cC5sOjE3';
        	$googlemap_style = '[{"featureType":"all","elementType":"labels","stylers":[{"visibility":"on"}]},{"featureType":"all","elementType":"labels.text.fill","stylers":[{"saturation":36},{"color":"#000000"},{"lightness":40}]},{"featureType":"all","elementType":"labels.text.stroke","stylers":[{"visibility":"on"},{"color":"#000000"},{"lightness":16}]},{"featureType":"all","elementType":"labels.icon","stylers":[{"visibility":"off"}]},{"featureType":"administrative","elementType":"geometry.fill","stylers":[{"color":"#000000"},{"lightness":20}]},{"featureType":"administrative","elementType":"geometry.stroke","stylers":[{"color":"#000000"},{"lightness":17},{"weight":1.2}]},{"featureType":"administrative.country","elementType":"labels.text.fill","stylers":[{"color":"#e5c163"}]},{"featureType":"administrative.locality","elementType":"labels.text.fill","stylers":[{"color":"#c4c4c4"}]},{"featureType":"administrative.neighborhood","elementType":"labels.text.fill","stylers":[{"color":"#e5c163"}]},{"featureType":"landscape","elementType":"geometry","stylers":[{"color":"#000000"},{"lightness":20}]},{"featureType":"poi","elementType":"geometry","stylers":[{"color":"#000000"},{"lightness":21},{"visibility":"on"}]},{"featureType":"poi.business","elementType":"geometry","stylers":[{"visibility":"on"}]},{"featureType":"road.highway","elementType":"geometry.fill","stylers":[{"color":"#e5c163"},{"lightness":"0"}]},{"featureType":"road.highway","elementType":"geometry.stroke","stylers":[{"visibility":"off"}]},{"featureType":"road.highway","elementType":"labels.text.fill","stylers":[{"color":"#ffffff"}]},{"featureType":"road.highway","elementType":"labels.text.stroke","stylers":[{"color":"#e5c163"}]},{"featureType":"road.arterial","elementType":"geometry","stylers":[{"color":"#000000"},{"lightness":18}]},{"featureType":"road.arterial","elementType":"geometry.fill","stylers":[{"color":"#575757"}]},{"featureType":"road.arterial","elementType":"labels.text.fill","stylers":[{"color":"#ffffff"}]},{"featureType":"road.arterial","elementType":"labels.text.stroke","stylers":[{"color":"#2c2c2c"}]},{"featureType":"road.local","elementType":"geometry","stylers":[{"color":"#000000"},{"lightness":16}]},{"featureType":"road.local","elementType":"labels.text.fill","stylers":[{"color":"#999999"}]},{"featureType":"transit","elementType":"geometry","stylers":[{"color":"#000000"},{"lightness":19}]},{"featureType":"water","elementType":"geometry","stylers":[{"color":"#000000"},{"lightness":17}]}]';
        	break;

        case "retro": //Retro
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcy50OjF8cC52Om9mZixzLnQ6MnxwLnY6c2ltcGxpZmllZCxzLnQ6M3xzLmU6bHxwLnY6c2ltcGxpZmllZCxzLnQ6NnxwLnY6c2ltcGxpZmllZCxzLnQ6NHxwLnY6c2ltcGxpZmllZCxzLnQ6NXxwLnY6c2ltcGxpZmllZCxzLnQ6NDl8cC52Om9mZixzLnQ6NTF8cC52Om9uLHMudDo0OXxzLmU6Z3xwLnY6b24scy50OjZ8cC5jOiNmZjg0YWZhM3xwLmw6NTIscC5zOi0xN3xwLmc6MC4zNixzLnQ6NjV8cy5lOmd8cC5jOiNmZjNmNTE4Yw';
        	$googlemap_style = '[{"featureType":"administrative","stylers":[{"visibility":"off"}]},{"featureType":"poi","stylers":[{"visibility":"simplified"}]},{"featureType":"road","elementType":"labels","stylers":[{"visibility":"simplified"}]},{"featureType":"water","stylers":[{"visibility":"simplified"}]},{"featureType":"transit","stylers":[{"visibility":"simplified"}]},{"featureType":"landscape","stylers":[{"visibility":"simplified"}]},{"featureType":"road.highway","stylers":[{"visibility":"off"}]},{"featureType":"road.local","stylers":[{"visibility":"on"}]},{"featureType":"road.highway","elementType":"geometry","stylers":[{"visibility":"on"}]},{"featureType":"water","stylers":[{"color":"#84afa3"},{"lightness":52}]},{"stylers":[{"saturation":-17},{"gamma":0.36}]},{"featureType":"transit.line","elementType":"geometry","stylers":[{"color":"#3f518c"}]}]';
        	break;

        case "muted-blue": //Muted Blue
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcC5oOiNlN2VjZjAscy50OjN8cC5zOi03MCxzLnQ6NHxwLnY6b2ZmLHMudDoyfHAudjpvZmYscy50OjZ8cC52OnNpbXBsaWZpZWR8cC5zOi02MA';
        	$googlemap_style = '[{"featureType":"all","stylers":[{"saturation":0},{"hue":"#e7ecf0"}]},{"featureType":"road","stylers":[{"saturation":-70}]},{"featureType":"transit","stylers":[{"visibility":"off"}]},{"featureType":"poi","stylers":[{"visibility":"off"}]},{"featureType":"water","stylers":[{"visibility":"simplified"},{"saturation":-60}]}]';
        	break;

        case "neutral-blue": //Neutral Blue
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcy50OjZ8cy5lOmd8cC5jOiNmZjE5MzM0MSxzLnQ6NXxzLmU6Z3xwLmM6I2ZmMmM1YTcxLHMudDozfHMuZTpnfHAuYzojZmYyOTc2OGF8cC5sOi0zNyxzLnQ6MnxzLmU6Z3xwLmM6I2ZmNDA2ZDgwLHMudDo0fHMuZTpnfHAuYzojZmY0MDZkODAscy5lOmwudC5zfHAudjpvbnxwLmM6I2ZmM2U2MDZmfHAudzoyfHAuZzowLjg0LHMuZTpsLnQuZnxwLmM6I2ZmZmZmZmZmLHMudDoxfHMuZTpnfHAudzowLjZ8cC5jOiNmZjFhMzU0MSxzLmU6bC5pfHAudjpvZmYscy50OjQwfHMuZTpnfHAuYzojZmYyYzVhNzE';
        	$googlemap_style = '[{"featureType":"water","elementType":"geometry","stylers":[{"color":"#193341"}]},{"featureType":"landscape","elementType":"geometry","stylers":[{"color":"#2c5a71"}]},{"featureType":"road","elementType":"geometry","stylers":[{"color":"#29768a"},{"lightness":-37}]},{"featureType":"poi","elementType":"geometry","stylers":[{"color":"#406d80"}]},{"featureType":"transit","elementType":"geometry","stylers":[{"color":"#406d80"}]},{"elementType":"labels.text.stroke","stylers":[{"visibility":"on"},{"color":"#3e606f"},{"weight":2},{"gamma":0.84}]},{"elementType":"labels.text.fill","stylers":[{"color":"#ffffff"}]},{"featureType":"administrative","elementType":"geometry","stylers":[{"weight":0.6},{"color":"#1a3541"}]},{"elementType":"labels.icon","stylers":[{"visibility":"off"}]},{"featureType":"poi.park","elementType":"geometry","stylers":[{"color":"#2c5a71"}]}]';
        	break;

        case "black-and-white-without-labels": //Black & white without labels
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcy5lOmx8cC52Om9mZixzLnQ6MXxzLmU6Z3xwLnY6b2ZmLHMudDoxN3xzLmU6Z3xwLnY6b2ZmLHMudDoxOHxzLmU6Z3xwLnY6b2ZmLHMudDoxOXxzLmU6Z3xwLnY6b2ZmLHMudDoyMHxzLmU6Z3xwLnY6b2ZmLHMudDoyMXxzLmU6Z3xwLnY6b2ZmLHMudDo1fHAudjpvbixzLnQ6NXxzLmU6Z3xwLnY6b2ZmfHAuaDojZmYwMDAwLHMudDo1fHMuZTpsfHAudjpvZmYscy50OjgxfHMuZTpnfHAudjpvbnxwLmM6I2ZmOTQ0MjQyLHMudDo4MXxzLmU6Zy5mfHAuYzojZmZmZmZmZmYscy50OjgyfHMuZTpnfHAudjpvbnxwLmM6I2ZmZmZmZmZmLHMudDoxMzEzfHMuZTpnfHAudjpvZmYscy50OjEzMTR8cy5lOmd8cC52Om9mZnxwLnM6LTEscy50OjJ8cC52Om9mZixzLnQ6MnxzLmU6Z3xwLnY6b2ZmLHMudDozN3xzLmU6Z3xwLnY6b2ZmLHMudDozfHMuZTpnLnN8cC52Om9mZixzLnQ6NDl8cy5lOmcuZnxwLmM6I2ZmMjkyOTI5LHMudDo0OXxzLmU6Zy5zfHAudjpvZmZ8cC5jOiNmZjQ5NDk0OXxwLnM6LTg1LHMudDo1MHxzLmU6Zy5mfHAuYzojZmY4ODg4ODh8cC52Om9uLHMudDo1MXxzLmU6Z3xwLnY6b2ZmLHMudDo1MXxzLmU6Zy5mfHAuYzojZmY3ZjdmN2Yscy50OjR8cC52Om9mZixzLnQ6NHxzLmU6Z3xwLnY6b2ZmLHMudDo2NXxzLmU6Z3xwLnY6b2ZmLHMudDo2NnxzLmU6Z3xwLnY6b2ZmLHMudDoxMDU5fHMuZTpnfHAudjpvZmYscy50OjEwNTh8cy5lOmd8cC52Om9mZixzLnQ6MTA1N3xzLmU6Z3xwLnY6b2ZmLHMudDo2fHMuZTpnfHAuYzojZmZkZGRkZGQscy50OjZ8cy5lOmcuZnxwLmM6I2ZmZWVlZWVlLHMudDo2fHMuZTpnLnN8cC52Om9mZg';
        	$googlemap_style = '[{"featureType":"all","elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"administrative","elementType":"geometry","stylers":[{"visibility":"off"}]},{"featureType":"administrative.country","elementType":"geometry","stylers":[{"visibility":"off"}]},{"featureType":"administrative.province","elementType":"geometry","stylers":[{"visibility":"off"}]},{"featureType":"administrative.locality","elementType":"geometry","stylers":[{"visibility":"off"}]},{"featureType":"administrative.neighborhood","elementType":"geometry","stylers":[{"visibility":"off"}]},{"featureType":"administrative.land_parcel","elementType":"geometry","stylers":[{"visibility":"off"}]},{"featureType":"landscape","elementType":"all","stylers":[{"visibility":"on"}]},{"featureType":"landscape","elementType":"geometry","stylers":[{"visibility":"off"},{"hue":"#ff0000"}]},{"featureType":"landscape","elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"landscape.man_made","elementType":"geometry","stylers":[{"visibility":"on"},{"color":"#944242"}]},{"featureType":"landscape.man_made","elementType":"geometry.fill","stylers":[{"color":"#ffffff"}]},{"featureType":"landscape.natural","elementType":"geometry","stylers":[{"visibility":"on"},{"color":"#ffffff"}]},{"featureType":"landscape.natural.landcover","elementType":"geometry","stylers":[{"visibility":"off"}]},{"featureType":"landscape.natural.terrain","elementType":"geometry","stylers":[{"visibility":"off"},{"saturation":"-1"}]},{"featureType":"poi","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"poi","elementType":"geometry","stylers":[{"visibility":"off"}]},{"featureType":"poi.attraction","elementType":"geometry","stylers":[{"visibility":"off"}]},{"featureType":"road","elementType":"geometry.stroke","stylers":[{"visibility":"off"}]},{"featureType":"road.highway","elementType":"geometry.fill","stylers":[{"color":"#292929"}]},{"featureType":"road.highway","elementType":"geometry.stroke","stylers":[{"visibility":"off"},{"color":"#494949"},{"saturation":"-85"}]},{"featureType":"road.arterial","elementType":"geometry.fill","stylers":[{"color":"#888888"},{"visibility":"on"}]},{"featureType":"road.local","elementType":"geometry","stylers":[{"visibility":"off"}]},{"featureType":"road.local","elementType":"geometry.fill","stylers":[{"color":"#7f7f7f"}]},{"featureType":"transit","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"transit","elementType":"geometry","stylers":[{"visibility":"off"}]},{"featureType":"transit.line","elementType":"geometry","stylers":[{"visibility":"off"}]},{"featureType":"transit.station","elementType":"geometry","stylers":[{"visibility":"off"}]},{"featureType":"transit.station.airport","elementType":"geometry","stylers":[{"visibility":"off"}]},{"featureType":"transit.station.bus","elementType":"geometry","stylers":[{"visibility":"off"}]},{"featureType":"transit.station.rail","elementType":"geometry","stylers":[{"visibility":"off"}]},{"featureType":"water","elementType":"geometry","stylers":[{"color":"#dddddd"}]},{"featureType":"water","elementType":"geometry.fill","stylers":[{"color":"#eeeeee"}]},{"featureType":"water","elementType":"geometry.stroke","stylers":[{"visibility":"off"}]}]';
        	break;

        case "icy-blue": //Icy Blue
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcC5oOiMyYzNlNTB8cC5zOjI1MCxzLnQ6M3xzLmU6Z3xwLmw6NTB8cC52OnNpbXBsaWZpZWQscy50OjN8cy5lOmx8cC52Om9mZg';
        	$googlemap_style = '[{"stylers":[{"hue":"#2c3e50"},{"saturation":250}]},{"featureType":"road","elementType":"geometry","stylers":[{"lightness":50},{"visibility":"simplified"}]},{"featureType":"road","elementType":"labels","stylers":[{"visibility":"off"}]}]';
        	break;

        case "hopper": //Hopper
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcy50OjZ8cy5lOmd8cC5oOiMxNjVjNjR8cC5zOjM0fHAubDotNjl8cC52Om9uLHMudDo1fHMuZTpnfHAuaDojYjdjYWFhfHAuczotMTR8cC5sOi0xOHxwLnY6b24scy50OjgxfHAuaDojY2JkYWMxfHAuczotNnxwLmw6LTl8cC52Om9uLHMudDozfHMuZTpnfHAuaDojOGQ5YjgzfHAuczotODl8cC5sOi0xMnxwLnY6b24scy50OjQ5fHMuZTpnfHAuaDojZDRkYWQwfHAuczotODh8cC5sOjU0fHAudjpzaW1wbGlmaWVkLHMudDo1MHxzLmU6Z3xwLmg6I2JkYzViNnxwLnM6LTg5fHAubDotM3xwLnY6c2ltcGxpZmllZCxzLnQ6NTF8cy5lOmd8cC5oOiNiZGM1YjZ8cC5zOi04OXxwLmw6LTI2fHAudjpvbixzLnQ6MnxzLmU6Z3xwLmg6I2MxNzExOHxwLnM6NjF8cC5sOi00NXxwLnY6b24scy50OjQwfHAuaDojOGJhOTc1fHAuczotNDZ8cC5sOi0yOHxwLnY6b24scy50OjR8cy5lOmd8cC5oOiNhNDMyMTh8cC5zOjc0fHAubDotNTF8cC52OnNpbXBsaWZpZWQscy50OjE4fHAuaDojZmZmZmZmfHAubDoxMDB8cC52OnNpbXBsaWZpZWQscy50OjIwfHAuaDojZmZmZmZmfHAubDoxMDB8cC52Om9mZixzLnQ6MTl8cy5lOmx8cC5oOiNmZmZmZmZ8cC5sOjEwMHxwLnY6b2ZmLHMudDoyMXxwLmg6I2ZmZmZmZnxwLmw6MTAwfHAudjpvZmYscy50OjF8cC5oOiMzYTM5MzV8cC5zOjV8cC5sOi01N3xwLnY6b2ZmLHMudDozNnxzLmU6Z3xwLmg6I2NiYTkyM3xwLnM6NTB8cC5sOi00NnxwLnY6b24';
        	$googlemap_style = '[{"featureType":"water","elementType":"geometry","stylers":[{"hue":"#165c64"},{"saturation":34},{"lightness":-69},{"visibility":"on"}]},{"featureType":"landscape","elementType":"geometry","stylers":[{"hue":"#b7caaa"},{"saturation":-14},{"lightness":-18},{"visibility":"on"}]},{"featureType":"landscape.man_made","elementType":"all","stylers":[{"hue":"#cbdac1"},{"saturation":-6},{"lightness":-9},{"visibility":"on"}]},{"featureType":"road","elementType":"geometry","stylers":[{"hue":"#8d9b83"},{"saturation":-89},{"lightness":-12},{"visibility":"on"}]},{"featureType":"road.highway","elementType":"geometry","stylers":[{"hue":"#d4dad0"},{"saturation":-88},{"lightness":54},{"visibility":"simplified"}]},{"featureType":"road.arterial","elementType":"geometry","stylers":[{"hue":"#bdc5b6"},{"saturation":-89},{"lightness":-3},{"visibility":"simplified"}]},{"featureType":"road.local","elementType":"geometry","stylers":[{"hue":"#bdc5b6"},{"saturation":-89},{"lightness":-26},{"visibility":"on"}]},{"featureType":"poi","elementType":"geometry","stylers":[{"hue":"#c17118"},{"saturation":61},{"lightness":-45},{"visibility":"on"}]},{"featureType":"poi.park","elementType":"all","stylers":[{"hue":"#8ba975"},{"saturation":-46},{"lightness":-28},{"visibility":"on"}]},{"featureType":"transit","elementType":"geometry","stylers":[{"hue":"#a43218"},{"saturation":74},{"lightness":-51},{"visibility":"simplified"}]},{"featureType":"administrative.province","elementType":"all","stylers":[{"hue":"#ffffff"},{"saturation":0},{"lightness":100},{"visibility":"simplified"}]},{"featureType":"administrative.neighborhood","elementType":"all","stylers":[{"hue":"#ffffff"},{"saturation":0},{"lightness":100},{"visibility":"off"}]},{"featureType":"administrative.locality","elementType":"labels","stylers":[{"hue":"#ffffff"},{"saturation":0},{"lightness":100},{"visibility":"off"}]},{"featureType":"administrative.land_parcel","elementType":"all","stylers":[{"hue":"#ffffff"},{"saturation":0},{"lightness":100},{"visibility":"off"}]},{"featureType":"administrative","elementType":"all","stylers":[{"hue":"#3a3935"},{"saturation":5},{"lightness":-57},{"visibility":"off"}]},{"featureType":"poi.medical","elementType":"geometry","stylers":[{"hue":"#cba923"},{"saturation":50},{"lightness":-46},{"visibility":"on"}]}]';
        	break;

        case "cobalt": //Cobalt
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcC5pbDp0cnVlfHAuczoxMHxwLmw6MzB8cC5nOjAuNXxwLmg6IzQzNTE1OA';
        	$googlemap_style = '[{"featureType":"all","elementType":"all","stylers":[{"invert_lightness":true},{"saturation":10},{"lightness":30},{"gamma":0.5},{"hue":"#435158"}]}]';
        	break;

        case "night-visions": //Simple night vision - Stranger Thing
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcC5pbDp0cnVlfHAuczotOXxwLmw6MHxwLnY6c2ltcGxpZmllZCxzLnQ6ODF8cC53OjEuMDAscy50OjQ5fHAudzowLjQ5LHMudDo0OXxzLmU6bHxwLnY6b258cC53OjAuMDF8cC5sOi03fHAuczotMzUscy50OjQ5fHMuZTpsLnR8cC52Om9uLHMudDo0OXxzLmU6bC50LnN8cC52Om9mZixzLnQ6NDl8cy5lOmwuaXxwLnY6b24';
        	$googlemap_style = '[{"featureType":"all","elementType":"all","stylers":[{"invert_lightness":true},{"saturation":"-9"},{"lightness":"0"},{"visibility":"simplified"}]},{"featureType":"landscape.man_made","elementType":"all","stylers":[{"weight":"1.00"}]},{"featureType":"road.highway","elementType":"all","stylers":[{"weight":"0.49"}]},{"featureType":"road.highway","elementType":"labels","stylers":[{"visibility":"on"},{"weight":"0.01"},{"lightness":"-7"},{"saturation":"-35"}]},{"featureType":"road.highway","elementType":"labels.text","stylers":[{"visibility":"on"}]},{"featureType":"road.highway","elementType":"labels.text.stroke","stylers":[{"visibility":"off"}]},{"featureType":"road.highway","elementType":"labels.icon","stylers":[{"visibility":"on"}]}]';
        	break;

        case "red-hues": //Red Hues
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcC5oOiNkZDBkMGQscy50OjN8cy5lOmx8cC52Om9mZixzLnQ6M3xzLmU6Z3xwLmw6MTAwfHAudjpzaW1wbGlmaWVk';
        	$googlemap_style = '[{"stylers":[{"hue":"#dd0d0d"}]},{"featureType":"road","elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"road","elementType":"geometry","stylers":[{"lightness":100},{"visibility":"simplified"}]}]';
        	break;

        case "roads-only": //Roads only
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcy50OjF8cC52Om9mZixzLnQ6NXxwLnY6b2ZmLHMudDoyfHAudjpvZmYscy50OjN8cC52Om9uLHMudDozfHMuZTpsfHAudjpvZmYscy50OjR8cC52Om9uLHMudDo0fHMuZTpsfHAudjpvZmYscy50OjZ8cC52Om9uLHMudDo2fHMuZTpnfHAuYzojZmYxMjYwOGQscy50OjZ8cy5lOmwudC5mfHAudjpvZmYscy50OjZ8cy5lOmwudC5zfHAudjpvZmY';
        	$googlemap_style = '[{"featureType":"administrative","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"landscape","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"poi","elementType":"all","stylers":[{"visibility":"off"}]},{"featureType":"road","elementType":"all","stylers":[{"visibility":"on"}]},{"featureType":"road","elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"transit","elementType":"all","stylers":[{"visibility":"on"}]},{"featureType":"transit","elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"water","elementType":"all","stylers":[{"visibility":"on"}]},{"featureType":"water","elementType":"geometry","stylers":[{"color":"#12608d"}]},{"featureType":"water","elementType":"labels.text.fill","stylers":[{"visibility":"off"}]},{"featureType":"water","elementType":"labels.text.stroke","stylers":[{"visibility":"off"}]}]';
        	break;

        case "flat-map-with-labels": //Flat Map with Labels
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcy50OjZ8cC5oOiM3ZmM4ZWR8cC5zOjU1fHAubDotNnxwLnY6b24scy50OjZ8cy5lOmx8cC5oOiM3ZmM4ZWR8cC5zOjU1fHAubDotNnxwLnY6b2ZmLHMudDo0MHxzLmU6Z3xwLmg6IzgzY2VhZHxwLnM6MXxwLmw6LTE1fHAudjpvbixzLnQ6NXxzLmU6Z3xwLmg6I2YzZjRmNHxwLnM6LTg0fHAubDo1OXxwLnY6b24scy50OjV8cy5lOmx8cC5oOiNmZmZmZmZ8cC5zOi0xMDB8cC5sOjEwMHxwLnY6b2ZmLHMudDozfHMuZTpnfHAuaDojZmZmZmZmfHAuczotMTAwfHAubDoxMDB8cC52Om9uLHMudDozfHMuZTpsfHAuaDojYmJiYmJifHAuczotMTAwfHAubDoyNnxwLnY6b24scy50OjUwfHMuZTpnfHAuaDojZmZjYzAwfHAuczoxMDB8cC5sOi0zNXxwLnY6c2ltcGxpZmllZCxzLnQ6NDl8cy5lOmd8cC5oOiNmZmNjMDB8cC5zOjEwMHxwLmw6LTIyfHAudjpvbixzLnQ6MzV8cC5oOiNkN2U0ZTR8cC5zOi02MHxwLmw6MjN8cC52Om9u';
        	$googlemap_style = '[{"featureType":"water","elementType":"all","stylers":[{"hue":"#7fc8ed"},{"saturation":55},{"lightness":-6},{"visibility":"on"}]},{"featureType":"water","elementType":"labels","stylers":[{"hue":"#7fc8ed"},{"saturation":55},{"lightness":-6},{"visibility":"off"}]},{"featureType":"poi.park","elementType":"geometry","stylers":[{"hue":"#83cead"},{"saturation":1},{"lightness":-15},{"visibility":"on"}]},{"featureType":"landscape","elementType":"geometry","stylers":[{"hue":"#f3f4f4"},{"saturation":-84},{"lightness":59},{"visibility":"on"}]},{"featureType":"landscape","elementType":"labels","stylers":[{"hue":"#ffffff"},{"saturation":-100},{"lightness":100},{"visibility":"off"}]},{"featureType":"road","elementType":"geometry","stylers":[{"hue":"#ffffff"},{"saturation":-100},{"lightness":100},{"visibility":"on"}]},{"featureType":"road","elementType":"labels","stylers":[{"hue":"#bbbbbb"},{"saturation":-100},{"lightness":26},{"visibility":"on"}]},{"featureType":"road.arterial","elementType":"geometry","stylers":[{"hue":"#ffcc00"},{"saturation":100},{"lightness":-35},{"visibility":"simplified"}]},{"featureType":"road.highway","elementType":"geometry","stylers":[{"hue":"#ffcc00"},{"saturation":100},{"lightness":-22},{"visibility":"on"}]},{"featureType":"poi.school","elementType":"all","stylers":[{"hue":"#d7e4e4"},{"saturation":-60},{"lightness":23},{"visibility":"on"}]}]';
        	break;

        case "mondrian": //Mondrian
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcy5lOmx8cC52Om9mZixzLnQ6M3xzLmU6Zy5mfHAuYzojZmYwRjA5MTkscy50OjZ8cy5lOmcuZnxwLmM6I2ZmRTRGN0Y3LHMuZTpnLnN8cC52Om9mZixzLnQ6NDB8cy5lOmcuZnxwLmM6I2ZmMDAyRkE3LHMudDozN3xzLmU6Zy5mfHAuYzojZmZFNjAwMDMscy50OjV8cy5lOmcuZnxwLmM6I2ZmRkJGQ0Y0LHMudDozM3xzLmU6Zy5mfHAuYzojZmZGRkVEMDAscy50OjM0fHMuZTpnLmZ8cC5jOiNmZkQ0MUMxRCxzLnQ6MzV8cy5lOmcuZnxwLmM6I2ZmQkYwMDAwLHMudDo2NXxzLmU6Zy5mfHAuczotMTAw';
        	$googlemap_style = '[{"elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"road","elementType":"geometry.fill","stylers":[{"color":"#0F0919"}]},{"featureType":"water","elementType":"geometry.fill","stylers":[{"color":"#E4F7F7"}]},{"elementType":"geometry.stroke","stylers":[{"visibility":"off"}]},{"featureType":"poi.park","elementType":"geometry.fill","stylers":[{"color":"#002FA7"}]},{"featureType":"poi.attraction","elementType":"geometry.fill","stylers":[{"color":"#E60003"}]},{"featureType":"landscape","elementType":"geometry.fill","stylers":[{"color":"#FBFCF4"}]},{"featureType":"poi.business","elementType":"geometry.fill","stylers":[{"color":"#FFED00"}]},{"featureType":"poi.government","elementType":"geometry.fill","stylers":[{"color":"#D41C1D"}]},{"featureType":"poi.school","elementType":"geometry.fill","stylers":[{"color":"#BF0000"}]},{"featureType":"transit.line","elementType":"geometry.fill","stylers":[{"saturation":-100}]}]';
        	break;

        case "bright-and-bubbly": //Bright & Bubbly
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcy50OjZ8cC5jOiNmZjE5YTBkOCxzLnQ6MXxzLmU6bC50LnN8cC5jOiNmZmZmZmZmZnxwLnc6NixzLnQ6MXxzLmU6bC50LmZ8cC5jOiNmZmU4NTExMyxzLnQ6NDl8cy5lOmcuc3xwLmM6I2ZmZWZlOWU0fHAubDotNDAscy50OjUwfHMuZTpnLnN8cC5jOiNmZmVmZTllNHxwLmw6LTIwLHMudDozfHMuZTpsLnQuc3xwLmw6MTAwLHMudDozfHMuZTpsLnQuZnxwLmw6LTEwMCxzLnQ6NDl8cy5lOmwuaSxzLnQ6NXxzLmU6bHxwLnY6b2ZmLHMudDo1fHAubDoyMHxwLmM6I2ZmZWZlOWU0LHMudDo4MXxwLnY6b2ZmLHMudDo2fHMuZTpsLnQuc3xwLmw6MTAwLHMudDo2fHMuZTpsLnQuZnxwLmw6LTEwMCxzLnQ6MnxzLmU6bC50LmZ8cC5oOiMxMWZmMDAscy50OjJ8cy5lOmwudC5zfHAubDoxMDAscy50OjJ8cy5lOmwuaXxwLmg6IzRjZmYwMHxwLnM6NTgscy50OjJ8cy5lOmd8cC52Om9ufHAuYzojZmZmMGU0ZDMscy50OjQ5fHMuZTpnLmZ8cC5jOiNmZmVmZTllNHxwLmw6LTI1LHMudDo1MHxzLmU6Zy5mfHAuYzojZmZlZmU5ZTR8cC5sOi0xMCxzLnQ6MnxzLmU6bHxwLnY6c2ltcGxpZmllZA';
        	$googlemap_style = '[{"featureType":"water","stylers":[{"color":"#19a0d8"}]},{"featureType":"administrative","elementType":"labels.text.stroke","stylers":[{"color":"#ffffff"},{"weight":6}]},{"featureType":"administrative","elementType":"labels.text.fill","stylers":[{"color":"#e85113"}]},{"featureType":"road.highway","elementType":"geometry.stroke","stylers":[{"color":"#efe9e4"},{"lightness":-40}]},{"featureType":"road.arterial","elementType":"geometry.stroke","stylers":[{"color":"#efe9e4"},{"lightness":-20}]},{"featureType":"road","elementType":"labels.text.stroke","stylers":[{"lightness":100}]},{"featureType":"road","elementType":"labels.text.fill","stylers":[{"lightness":-100}]},{"featureType":"road.highway","elementType":"labels.icon"},{"featureType":"landscape","elementType":"labels","stylers":[{"visibility":"off"}]},{"featureType":"landscape","stylers":[{"lightness":20},{"color":"#efe9e4"}]},{"featureType":"landscape.man_made","stylers":[{"visibility":"off"}]},{"featureType":"water","elementType":"labels.text.stroke","stylers":[{"lightness":100}]},{"featureType":"water","elementType":"labels.text.fill","stylers":[{"lightness":-100}]},{"featureType":"poi","elementType":"labels.text.fill","stylers":[{"hue":"#11ff00"}]},{"featureType":"poi","elementType":"labels.text.stroke","stylers":[{"lightness":100}]},{"featureType":"poi","elementType":"labels.icon","stylers":[{"hue":"#4cff00"},{"saturation":58}]},{"featureType":"poi","elementType":"geometry","stylers":[{"visibility":"on"},{"color":"#f0e4d3"}]},{"featureType":"road.highway","elementType":"geometry.fill","stylers":[{"color":"#efe9e4"},{"lightness":-25}]},{"featureType":"road.arterial","elementType":"geometry.fill","stylers":[{"color":"#efe9e4"},{"lightness":-10}]},{"featureType":"poi","elementType":"labels","stylers":[{"visibility":"simplified"}]}]';
        	break;

        case "shades-of-grey": //Shades of Grey
        	$map_style = '5e18!12m4!1e68!2m2!1sset!2sRoadmap!12m3!1e37!2m1!1ssmartmaps!12m4!1e26!2m2!1sstyles!2zcy5lOmwudC5mfHAuczozNnxwLmM6I2ZmMDAwMDAwfHAubDo0MCxzLmU6bC50LnN8cC52Om9ufHAuYzojZmYwMDAwMDB8cC5sOjE2LHMuZTpsLml8cC52Om9mZixzLnQ6MXxzLmU6Zy5mfHAuYzojZmYwMDAwMDB8cC5sOjIwLHMudDoxfHMuZTpnLnN8cC5jOiNmZjAwMDAwMHxwLmw6MTd8cC53OjEuMixzLnQ6NXxzLmU6Z3xwLmM6I2ZmMDAwMDAwfHAubDoyMCxzLnQ6MnxzLmU6Z3xwLmM6I2ZmMDAwMDAwfHAubDoyMSxzLnQ6NDl8cy5lOmcuZnxwLmM6I2ZmMDAwMDAwfHAubDoxNyxzLnQ6NDl8cy5lOmcuc3xwLmM6I2ZmMDAwMDAwfHAubDoyOXxwLnc6MC4yLHMudDo1MHxzLmU6Z3xwLmM6I2ZmMDAwMDAwfHAubDoxOCxzLnQ6NTF8cy5lOmd8cC5jOiNmZjAwMDAwMHxwLmw6MTYscy50OjR8cy5lOmd8cC5jOiNmZjAwMDAwMHxwLmw6MTkscy50OjZ8cy5lOmd8cC5jOiNmZjAwMDAwMHxwLmw6MTc';
        	$googlemap_style = '[{"featureType":"all","elementType":"labels.text.fill","stylers":[{"saturation":36},{"color":"#000000"},{"lightness":40}]},{"featureType":"all","elementType":"labels.text.stroke","stylers":[{"visibility":"on"},{"color":"#000000"},{"lightness":16}]},{"featureType":"all","elementType":"labels.icon","stylers":[{"visibility":"off"}]},{"featureType":"administrative","elementType":"geometry.fill","stylers":[{"color":"#000000"},{"lightness":20}]},{"featureType":"administrative","elementType":"geometry.stroke","stylers":[{"color":"#000000"},{"lightness":17},{"weight":1.2}]},{"featureType":"landscape","elementType":"geometry","stylers":[{"color":"#000000"},{"lightness":20}]},{"featureType":"poi","elementType":"geometry","stylers":[{"color":"#000000"},{"lightness":21}]},{"featureType":"road.highway","elementType":"geometry.fill","stylers":[{"color":"#000000"},{"lightness":17}]},{"featureType":"road.highway","elementType":"geometry.stroke","stylers":[{"color":"#000000"},{"lightness":29},{"weight":0.2}]},{"featureType":"road.arterial","elementType":"geometry","stylers":[{"color":"#000000"},{"lightness":18}]},{"featureType":"road.local","elementType":"geometry","stylers":[{"color":"#000000"},{"lightness":16}]},{"featureType":"transit","elementType":"geometry","stylers":[{"color":"#000000"},{"lightness":19}]},{"featureType":"water","elementType":"geometry","stylers":[{"color":"#000000"},{"lightness":17}]}]';
        	break;

        case "newmap":
          $map_style = '';
          $googlemap_style = '';
          break;

        default:
          $map_style = apply_filters( "mapify-shortcode-googlemapstyle-render-customstyle-raw", "", $map_defined_style, $atts_filter, $content);
          $googlemap_style = apply_filters( "mapify-shortcode-googlemapstyle-render-customstyle-json", "", $map_defined_style, $atts_filter, $content);
          break;
      }
      $atts_filter["map_style"] = $map_style;
      $atts_filter["googlemap_style"] = $googlemap_style;


      do_action( "mapify-shortcode-before-variables-declared", $atts_filter, $content);
      $found_valid_maptype = false; $jsonarray = array();
      $peproMapifyMaptypes = apply_filters( "pepro-mapify-maptypes",array(
          esc_html__("Google Maps", $this->td)   => 'googlemap'  ,
          // esc_html__("OpenStreet", $this->td)  => 'openstreet' ,
          // esc_html__("CedarMaps", $this->td)   => 'cedarmaps'  ,
        ),$atts_filter, $content);
      foreach ($peproMapifyMaptypes as $key => $value) {
        if ($value == $maptype){ $found_valid_maptype = true;}
      }
      $maptype = $found_valid_maptype ? $maptype : "googlemap" ;
      $branchtype = ((("cat" !== $branchtype) || ("ids" !== $branchtype)) ? "ids" : $branchtype);
      if (!empty($branchcat)){$branchtype = "cat";}
      $uniqid = uniqid("{$this->db_slug}-");
      $css_class = apply_filters(VC_SHORTCODE_CUSTOM_CSS_FILTER_TAG,vc_shortcode_custom_css_class($css,""),"pepro-mapify",$atts);
      $el_id = empty($el_id) ? $uniqid : $el_id;
      $el_class = implode(" ", apply_filters( "pepro-mapify-output-container-classes", array($uniqid,$el_class,$css_class),$atts_filter, $content, $this->td, $uniqid, $el_class, $css_class));
      $content_filtered = ""; $loop = false;
      if ("cat" == $branchtype && !empty($branchcat)){
        $loop = new WP_Query( apply_filters( "mapify-wpquery-by-category",array(
          "post_type"       =>  "mapify",
          "posts_per_page"  =>  -1,
          "tax_query"       =>  array(
                                  array(
                                    "taxonomy"    => "mapify_category",
                                    "field"       => "slug",
                                    "terms"       => array_map(function($e){return trim($e);}, explode(",",$branchcat)),
                                  ),
                                ),
                              ),$atts_filter, $content)
        );
      }
      if ("ids" == $branchtype && !empty($branchids)){
        $loop = new WP_Query( apply_filters( "mapify-wpquery-by-ids",array(
          "post_type"       =>  "mapify",
          "posts_per_page"  =>  -1,
          "post__in"        =>  array_map(function($e){return trim($e);}, explode(",",$branchids) ),
        ),$atts_filter, $content));
      }
      if (!$loop){
        return do_shortcode($this->shortcode_wapper(__("No data found!", $this->td),$el_class,$el_id));
      }
      do_action( "mapify-shortcode-after-variables-declared", $atts_filter, $content, $jsonarray);

      $api = get_option("{$this->db_slug}-googlemapAPI","");

      $apiw = ( empty($api) ? "" : "key=$api&" );

      do_action( "mapify-shortcode-before-post-loop", $atts_filter, $content, $jsonarray);

      while( $loop->have_posts() ){
        $loop->the_post();
        $post_id = get_the_id();
        do_action( "mapify-shortcode-before-post-loop-items", $post_id, $atts);
        $title = get_the_title();
        $url = get_the_permalink($post_id);
        $img = get_the_post_thumbnail_url($post_id,'thumbnail');
        $categories_list = array();
        $categories = get_the_terms( $post_id, "mapify_category" );
        if ($categories){
          foreach ($categories as $category) {
            $categories_list[$category->slug] = $category->name; //term_id
          }
        }
        $raw_data = array(
          "id"          => $post_id,
          "title"       => $title,
          "image"       => $img,
          "img"         => $img,
          "url"         => $url,
          "categories"  => $categories_list,
          "map_data"    => json_decode(get_post_meta( $post_id, "place_details_map_data",true),true),
          "latitude"    => json_decode(get_post_meta( $post_id, "place_details_map_data",true),true)["latitude"],
          "longitude"   => json_decode(get_post_meta( $post_id, "place_details_map_data",true),true)["longitude"],
          "zoom"        => json_decode(get_post_meta( $post_id, "place_details_map_data",true),true)["gzoom"],
          "pin_img"     => get_post_meta( $post_id, "place_details_pinimg",     true ),
          "address"     => get_post_meta( $post_id, "place_details_address",    true ),
          "phone"       => get_post_meta( $post_id, "place_details_phone",      true ),
          "site"        => get_post_meta( $post_id, "place_details_site",       true ),
          "email"       => get_post_meta( $post_id, "place_details_email",      true ),
          "twitter"     => get_post_meta( $post_id, "place_details_socailtw",   true ),
          "facebook"    => get_post_meta( $post_id, "place_details_socailfb",   true ),
          "instagram"   => get_post_meta( $post_id, "place_details_socailig",   true ),
          "telegram"    => get_post_meta( $post_id, "place_details_socailtg",   true ),
          "linkedin"    => get_post_meta( $post_id, "place_details_socailli",   true ),
          "additional"  => get_post_meta( $post_id, "place_details_additional", true ),
        );
        $post_data = apply_filters( "mapify-getposts-json-data", $raw_data, $post_id, $raw_data);
        array_push($jsonarray, $post_data);
        do_action( "mapify-shortcode-after-post-loop-items", $post_id, $atts, $jsonarray);
      }

      do_action( "mapify-shortcode-after-post-loop", $atts_filter, $content, $jsonarray);

      wp_reset_postdata();

      do_action( "mapify-shortcode-before-enqueueing", $atts_filter, $content, $jsonarray);
      $custom_css = apply_filters( "mapify-shortcode-custom-css", "
       :root {
            --font-family: ".$this->read_opt(__CLASS__."fontFamily","inherit").";
        }
        .mapify-container#$el_id{
          width: $el_map_width;
          height: $el_map_height;
        	overflow: hidden;
        	background: url($loading_image), $loading_color;
        	background-size: auto;
        	background-position: center;
        	background-repeat: no-repeat;
        }
        .mapify-container#$el_id>div{
          opacity: 0;
        }
        .mapify-container#$el_id.loaded>div{
        	transition: all 0.3s ease-in-out;
          opacity: 100;
        }
        .mapify-container#$el_id > div:not(:first-child) {
        	display: none;
        }
        .mapify-container#$el_id > div iframe+div a div img {
        	display: none;
        }
        iframe ~ div.gmnoprint:not(:last-child), iframe ~ div.gm-style-cc {
        	display: none;
        }
        .mapify-container#$el_id > div iframe+div a div{
          width: 2rem !important;
          height: 2rem !important;
        }
        .mapify-container#$el_id > div iframe+div {
        	background: url('$mapfooterimage');
        	background-size: contain;
        	background-position: center;
        	background-repeat: no-repeat;
        	width: 2rem !important;
        	height: 2rem !important;
        	margin-bottom: 5px;
        	opacity: .7;
        }
        $custom_css_code
        ",
      $atts_filter, $content, $jsonarray, $custom_css_code);
      $u = uniqid();
      wp_enqueue_script   ( "mapify_glapi", "//maps.googleapis.com/maps/api/js?{$apiw}libraries=places",  array( 'jquery' ), '1.0.0', true );
      wp_enqueue_script   ( "mapify_clstr", "{$this->assets_url}js/markerclusterer.js", array( "jquery" ), "1.3.0", true);
      wp_register_script  ( "mapify_front-$u", "{$this->assets_url}js/mapify-front-shortcode.js", array( "jquery" ), current_time( "timestamp" ), true);
      wp_enqueue_style    ( "mapify_front", "{$this->assets_url}css/mapify-front-shortcode.css");
      wp_add_inline_style ( "mapify_front", $custom_css);
      wp_localize_script  ( "mapify_front-$u", "MAPIFYJSOBJ$u", apply_filters( "mapify-shortcode-js-data", array(
            "raw_data$u"    => $jsonarray,
            "settings$u"    => $atts_filter,
            "container$u"   => $el_id,
            "el_id"         => $el_id,
            "uniqid"        => $u,
            "clusterrepo"   => "{$this->assets_url}img",
            "spotlight"     => "{$this->assets_url}img/spotlight.png",
            "searchtxt"     => _x("Search here ....","mapify-js", $this->td),
            "searcherr"     => _x("No result found.","mapify-js", $this->td),
            "uncatzed"      => _x("Uncategorized","mapify-js", $this->td),
          ),$atts_filter, $content, $jsonarray)
      );
      wp_enqueue_script   ( "mapify_front-$u");
      echo "<script>
              if (typeof window.mapifyObjects === 'undefined') {
                window.mapifyObjects = ['MAPIFYJSOBJ$u'];
              }else{
                window.mapifyObjects.push('MAPIFYJSOBJ$u');
              }
           </script>";
      do_action( "mapify-shortcode-after-enqueueing", $atts_filter, $content, $jsonarray);
      do_action( "mapify-shortcode-before-return", $atts_filter, $content, $jsonarray, $content, $content_filtered,$el_class,$el_id);
      return apply_filters( "mapify-shortcode-return-data", do_shortcode($this->shortcode_wapper($content_filtered,$el_class,$el_id)),$atts_filter, $content, $jsonarray, $content_filtered ,$el_class ,$el_id);
    }
    public function admin_menu()
    {
        add_menu_page(
          $this->title_w,
          __("Mapify Settings", $this->td),
          "manage_options",
          $this->db_slug,
          array($this,'help_container'),
          'dashicons-location-alt',
          81
      );
    }
    public function _vc_activated()
    {
        if (!is_plugin_active('js_composer/js_composer.php') || !defined('WPB_VC_VERSION')){
            return false;
        }else{
            return true;
        }
    }
    public function admin_init($hook)
    {
      $pepro_mega_menu_options = $this->get_setting_options();
      foreach ($pepro_mega_menu_options as $sections) {
            foreach ($sections["data"] as $id=>$def) {
                add_option($id,$def);
                register_setting($sections["name"],$id);
            }
      }
    }
    public function wpIsFarsi()
    {
      return get_locale() === "fa_IR" ? true : false;
    }
    public function admin_enqueue_scripts($hook)
    {
      $screen = get_current_screen();
      // if ( 'post.php' == $hook && 'mapify' == $screen->post_type ){

      // }
    }
    public function admin_print_footer_scripts()
    {
        if (wp_script_is('quicktags')) {
            echo '
            <script type="text/javascript">
              /*QTags.addButton( id, display, arg1, arg2, access_key, title, priority, instance );*/
              /*QTags.addButton( "mybtnid", "ButtonName", "[mytext id=\"\"]", "", "", "tooltip", 0 );*/
            </script>';
        }
    }
    public function read_opt($mc, $def="")
    {
        return get_option($mc) <> "" ? get_option($mc) : $def;
    }
    public function print_setting_input($SLUG="", $CAPTION="", $extraHtml="", $type="text",$extraClass="",$extra="")
    {
        $ON = sprintf(_x("Enter %s", "setting-page", $this->td), $CAPTION);
        echo "<tr>
    			<th scope='row'>
    				<label for='$SLUG'>$CAPTION $extra</label>
    			</th>
    			<td><input name='$SLUG' $extraHtml type='$type' id='$SLUG' placeholder='$CAPTION' title='$ON' value='" . $this->read_opt($SLUG) . "' class='regular-text $extraClass' /></td>
    		</tr>";
    }
    public function print_setting_select($SLUG, $CAPTION, $dataArray=array())
    {
        $ON = sprintf(_x("Choose %s", "setting-page", $this->td), $CAPTION);
        $OPTS = "";
        foreach ($dataArray as $key => $value) {
            if ($key == "EMPTY") {
                $key = "";
            }
            $OPTS .= "<option value='$key' ". selected($this->read_opt($SLUG), $key, false) .">$value</option>";
        }
        echo "<tr>
    			<th scope='row'>
    				<label for='$SLUG'>$CAPTION</label>
    			</th>
    			<td><select name='$SLUG' id='$SLUG' title='$ON' class='regular-text'>
          ".$OPTS."
          </select>
          </td>
    		</tr>";
    }
    public function print_setting_editor($SLUG, $CAPTION, $re="")
    {
        echo "<tr><th><label for='$SLUG'>$CAPTION</label></th><td>";
        wp_editor($this->read_opt($SLUG, ''), strtolower(str_replace(array('-', '_', ' ', '*'), '', $SLUG)), array(
        'textarea_name' => $SLUG
      ));
        echo "<p class='$SLUG'>$re</p></td></tr>";
    }
    public function _callback($a)
    {
        return $a;
    }
  }
  /**
   * load plugin and load textdomain then set a global varibale to access plugin class!
   *
   * @version 1.0.0
   * @since   1.0.0
   * @license https://pepro.dev/license Pepro.dev License
   */
  add_action(
      "plugins_loaded", function () {
          global $PeproMapify;
          load_plugin_textdomain("mapify", false, dirname(plugin_basename(__FILE__))."/languages/");
          $PeproMapify = new PeproBranchesMap_AKA_Mapify;
          register_activation_hook(__FILE__,    array("PeproBranchesMap_AKA_Mapify", "activation_hook"));
          register_deactivation_hook(__FILE__,  array("PeproBranchesMap_AKA_Mapify", "deactivation_hook"));
          register_uninstall_hook(__FILE__,     array("PeproBranchesMap_AKA_Mapify", "uninstall_hook"));
      }
  );
}
/*################################################################################
END OF PLUGIN || Programming is art // Artist : Amirhosseinhpv [https://hpv.im/]
################################################################################*/
