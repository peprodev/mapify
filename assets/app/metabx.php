<?php
# @Author: Amirhosseinhpv
# @Date:   2020/08/13 20:47:02
# @Email:  its@hpv.im
# @Last modified by:   Amirhosseinhpv
# @Last modified time: 2021/03/27 17:10:25
# @License: GPLv2
# @Copyright: Copyright © 2020 Amirhosseinhpv, All rights reserved.


class PeproMapifyBranchesCPT_metabox {
  private $screens = array('mapify',);
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
    // __("Address", "mapify")
    // __("Phone", "mapify")
    // __("Site", "mapify")
    // __("Email", "mapify")
    // __("PinLong", "mapify")
    // __("PinLat", "mapify")
    // __("Province", "mapify")
    // __("Country", "mapify")
    // __("Twitter", "mapify")
    // __("Facebook", "mapify")
    // __("Instagram", "mapify")
    // __("Telegram", "mapify")
    // __("LinkedIn", "mapify")
    // __("Additional Text", "mapify")
  public function __construct() {
    global $wpdb;
    $this->td = "mapify";
    $this->plugin_dir = plugin_dir_path(__FILE__);
    $this->assets_url = plugins_url("/assets/", dirname(__FILE__,2));
    $this->db_slug = $this->td;
    $this->db_table = $wpdb->prefix . $this->db_slug;

    add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
    add_action( 'save_post', array( $this, 'save_post' ) );
  }
  public function get_fileds(){
    $fields = apply_filters( "mapify-cpt-metakeys", array(
      array('id' => 'pinimg',     'label' => __("Custom Pin Image", "mapify") ,'type' => 'text',),
      array('id' => 'content_template', 'label' => __("Post Content Template", "mapify") ,'type'  => 'select', 'value' => array( "default" =>  __("Inherit from Setting",$this->td), "post"    =>  __("Use Post Template",$this->td), "content" =>  __("Use Default Branches Template",$this->td), )),
      array('id' => 'address',    'label' => __("Address", "mapify")  ,'type'   => 'textarea',),
      array('id' => 'phone',      'label' => __("Phone", "mapify")    ,'type'   => 'text',),
      array('id' => 'site',       'label' => __("Site", "mapify")     ,'type'   => 'url',),
      array('id' => 'email',      'label' => __("Email", "mapify")    ,'type'   => 'email',),
      array('id' => 'socailtw',   'label' => __("Twitter", "mapify") ,'type'    => 'text',),
      array('id' => 'socailfb',   'label' => __("Facebook", "mapify") ,'type'   => 'text',),
      array('id' => 'socailig',   'label' => __("Instagram", "mapify") ,'type'  => 'text',),
      array('id' => 'socailtg',   'label' => __("Telegram", "mapify") ,'type'   => 'text',),
      array('id' => 'socailli',   'label' => __("LinkedIn", "mapify") ,'type'   => 'text',),
      array('id' => 'additional', 'label' => __("Additional Text", "mapify") ,'type'  => 'textarea',),
     ));
    array_push($fields,array('id' => 'map_data',   'label' => "RAW DATA" ,'type' => 'textarea',));
    return $fields ;
  }
  public function add_meta_boxes() {
    foreach ( $this->screens as $screen ) {
      add_meta_box(
        'place-details',
        __( 'Branch Details', $this->td),
        array( $this, 'add_meta_box_callback' ),
        $screen,
        'normal',
        'high'
      );
    }
  }
  public function add_meta_box_callback( $post ) {

    wp_nonce_field( 'place_details_data', 'place_details_nonce' );
    $api = get_option("{$this->db_slug}-googlemapAPI",""); $apiw = ( empty($api) ? "" : "key=$api&" );
    wp_enqueue_script(   "mapify_core",        "//cdn.amcharts.com/lib/4/core.js",                         array( "jquery" ), "4.0.0", true);
    wp_enqueue_script(   "mapify_maps",        "//cdn.amcharts.com/lib/4/maps.js",                         array( "jquery" ), "4.0.0", true);
    wp_enqueue_script(   "mapify_world",       "//cdn.amcharts.com/lib/4/geodata/worldLow.js",             array( "jquery" ), "4.0.0", true);
    wp_enqueue_script(   "mapify_countries2",  "//cdn.amcharts.com/lib/4/geodata/data/countries2.js",      array( "jquery" ), "4.0.0", true);
    wp_enqueue_script(   "mapify_googleapi",   "//maps.googleapis.com/maps/api/js?{$apiw}libraries=places",array( 'jquery' ), '1.0.0', true );
    wp_register_script(  "mapify_mapify",      "{$this->assets_url}js/backend-mapify.js",                  array( "jquery" ), current_time("timestamp"), true);
    wp_enqueue_style(    "mapify_mapify",      "{$this->assets_url}css/backend-mapify.css");
    wp_localize_script(  "mapify_mapify",      "MAPIFY", array(
      "api"        =>  $api?1:0,
      "spotlight"  =>  "{$this->assets_url}img/spotlight.png",
      "markersdir"  =>  "{$this->assets_url}img/markers",
      "drag"       =>  _x("To change, drag this marker","js-translate", $this->td),
      "dragging"   =>  _x("Currently dragging marker...","js-translate", $this->td),
      "vc_pinmarkermaker_clipboard" => __("Click To Set as your Pin image", $this->td),
      "vc_pinmarkermaker_numbers"   => __("Numbers", $this->td),
      "vc_pinmarkermaker_character" => __("Character", $this->td),
      "vc_pinmarkermaker_symbols"   => __("Symbols", $this->td),
      "drop"       =>  _x("Drop on your location","js-translate", $this->td),));
    wp_enqueue_script(   "mapify_mapify");

    ob_start();
    do_action( "mapify-cpt-metabox-before-print", $post);
    include "{$this->plugin_dir}mapify.php";
    $this->generate_fields( $post );
    do_action( "mapify-cpt-metabox-after-print", $post);
    $tcona = ob_get_contents();
    ob_end_clean();
    echo $tcona;
  }
  public function generate_fields( $post ) {
    $output = '';
    foreach ( $this->get_fileds() as $field ) {
      $label = '<label for="' . $field['id'] . '">' . $field['label'] . '</label>';
      $db_value = get_post_meta( $post->ID, 'place_details_' . $field['id'], true );
      switch ( $field['type'] ) {
        case 'textarea':
          $input = sprintf(
            '<textarea class="large-text" id="%s" placeholder="%s" name="%s" rows="5">%s</textarea>',
            $field['id'],
            $field['label'],
            $field['id'],
            $db_value
          );
          break;
        case 'select':
          $input = sprintf( '<select class="large-text" id="%s" placeholder="%s" name="%s">', $field['id'], $field['label'], $field['id'] );
          foreach ($field['value'] as $key => $value) {
            $checked = selected( $db_value, $key,false);
            $input .= "<option value='$key' $checked>$value</option>";
          }
          $input .= "</select>";
          break;
        default:
          $input = sprintf(
            '<input %s id="%s" title="%s" placeholder="%s" name="%s" type="%s" value="%s">',
            $field['type'] !== "color" ? "class='regular-text mapifyfields {$field['id']}'" : "",
            $field['id'],
            $field['label'],
            $field['label'],
            $field['id'],
            $field['type'],
            $db_value
          );
      }
      $output .= $this->row_format( $label, $input,$field['id'] );
    }
    echo '<table class="form-table"><tbody>' . $output . '</tbody></table>';
  }
  public function row_format( $label, $input, $id ) {
    return sprintf(
      // '%s',
      "<tr id='mapify-{$id}'><th scope='row'>%s</th><td>%s</td></tr>",
      $label,
      $input
    );
  }
  public function save_post( $post_id ) {
    if ( ! isset( $_POST['place_details_nonce'] ) )
      return $post_id;
    $nonce = $_POST['place_details_nonce'];
    if ( !wp_verify_nonce( $nonce, 'place_details_data' ) )
      return $post_id;

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
      return $post_id;

    foreach ( $this->get_fileds() as $field ) {
      if ( isset( $_POST[ $field['id'] ] ) ) {
        switch ( $field['type'] ) {
			case 'email':
				$_field_data = sanitize_email( $_POST[ $field['id'] ] );
				break;
			case 'textarea':
				$_field_data = sanitize_textarea_field( $_POST[ $field['id'] ] );
				break;
			default:
				$_field_data = sanitize_text_field( $_POST[ $field['id'] ] );
				break;
        }
        update_post_meta( $post_id, 'place_details_' . $field['id'], $_field_data );
      } else if ( $field['type'] === 'checkbox' ) {
        update_post_meta( $post_id, 'place_details_' . $field['id'], "" );
      }
    }
  }
}

new PeproMapifyBranchesCPT_metabox;
