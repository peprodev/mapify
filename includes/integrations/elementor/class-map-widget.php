<?php
/**
 * Elementor widget: Branches Map.
 *
 * Content controls are generated from the shared schema; the Style tab exposes design
 * controls bound to the map's CSS custom properties and elements.
 *
 * @package Mapify
 */

namespace Mapify\Integrations\Elementor;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Css_Filter;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;
use Elementor\Widget_Base;
use Mapify\Options;
use Mapify\Renderer;
use Mapify\Schema;

require_once __DIR__ . '/class-gallery-control.php';

defined( 'ABSPATH' ) || exit;

class Map_Widget extends Widget_Base {

	public function get_name() {
		return 'mapify-map';
	}

	public function get_title() {
		return esc_html__( 'Branches Map', 'mapify' );
	}

	public function get_icon() {
		return 'eicon-google-maps';
	}

	public function get_categories() {
		return array( 'peprodev', 'general' );
	}

	public function get_keywords() {
		return array( 'map', 'branches', 'google', 'openstreetmap', 'mapbox', 'iran', 'svg', 'pin', 'location', 'نقشه', 'شعبه' );
	}

	public function get_style_depends(): array {
		return array( 'mapify-front' );
	}

	public function get_script_depends(): array {
		return array( 'mapify-front' );
	}

	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	protected function is_dynamic_content(): bool {
		return true;
	}

	public function get_custom_help_url() {
		return admin_url( 'edit.php?post_type=mapify&page=mapify-shortcode' );
	}

	/* ------------------------------------------------------------------ schema → controls */

	/**
	 * Merge a field's condition with the conditions of the fields it depends on (Elementor does not chain them).
	 */
	protected function condition_for( $key, array $fields, $depth = 0 ) {
		$field = isset( $fields[ $key ] ) ? $fields[ $key ] : array();
		if ( empty( $field['condition'] ) || $depth > 5 ) {
			return array();
		}
		$out = array();
		foreach ( $field['condition'] as $parent => $values ) {
			$parent_field = isset( $fields[ $parent ] ) ? $fields[ $parent ] : array();
			if ( isset( $parent_field['type'] ) && 'toggle' === $parent_field['type'] ) {
				$out[ $parent ] = in_array( true, $values, true ) ? 'yes' : '';
			} else {
				$out[ $parent ] = array_values( $values );
			}
			$out = array_merge( $this->condition_for( $parent, $fields, $depth + 1 ), $out );
		}
		return $out;
	}

	protected function control_args( $key, array $field, array $fields ) {
		$args = array(
			'label' => $field['label'],
		);
		if ( ! empty( $field['description'] ) ) {
			$args['description'] = $field['description'];
		}
		if ( ! empty( $field['link'] ) ) {
			$args['description'] = ( isset( $args['description'] ) ? $args['description'] . ' ' : '' ) . '<a href="' . esc_url( $field['link']['url'] ) . '" target="_blank" rel="noopener">' . esc_html( $field['link']['label'] ) . '</a>';
		}
		$condition = $this->condition_for( $key, $fields );
		if ( $condition ) {
			$args['condition'] = $condition;
		}
		switch ( $field['type'] ) {
			case 'select':
				$options = Schema::options( $field );
				if ( ! empty( $field['previews'] ) ) {
					// Thumbnail gallery (see Gallery_Control).
					$choices = array();
					foreach ( $options as $value => $label ) {
						$choices[ $value ] = array(
							'title' => $label,
							'image' => Schema::style_preview( $key, $value ),
						);
					}
					$args['type']        = Gallery_Control::TYPE;
					$args['options']     = $choices;
					$args['search']      = count( $choices ) > 12;
					$args['label_block'] = true;
				} else {
					$args['type']    = Controls_Manager::SELECT;
					$args['options'] = $options;
				}
				$args['default'] = (string) $field['default'];
				if ( 'maptype' === $key ) {
					// Elementor section conditions need a concrete engine, so start from the global default.
					unset( $args['options'][''] );
					$args['default'] = Options::get( 'default_engine' );
				}
				break;
			case 'multiselect':
				$args['type']        = Controls_Manager::SELECT2;
				$args['multiple']    = true;
				$args['label_block'] = true;
				$args['options']     = Schema::options( $field );
				$args['default']     = is_array( $field['default'] ) ? $field['default'] : array();
				break;
			case 'toggle':
				$args['type']         = Controls_Manager::SWITCHER;
				$args['return_value'] = 'yes';
				$args['default']      = $field['default'] ? 'yes' : '';
				break;
			case 'number':
				$args['type'] = Controls_Manager::NUMBER;
				foreach ( array( 'min', 'max', 'placeholder' ) as $k ) {
					if ( isset( $field[ $k ] ) ) {
						$args[ $k ] = $field[ $k ];
					}
				}
				$args['default'] = $field['default'];
				break;
			case 'media':
				$args['type']        = Controls_Manager::MEDIA;
				$args['media_types'] = 'svg_file' === $key ? array( 'svg', 'image' ) : array( 'image' );
				$args['dynamic']     = array( 'active' => true );
				break;
			case 'code':
				$args['type']        = Controls_Manager::CODE;
				$args['language']    = isset( $field['language'] ) ? $field['language'] : 'html';
				$args['rows']        = 12;
				$args['label_block'] = true;
				$args['default']     = 'popup_markup' === $key ? Schema::default_popup_template() : $field['default'];
				break;
			case 'textarea':
				$args['type']    = Controls_Manager::TEXTAREA;
				$args['default'] = $field['default'];
				break;
			case 'color':
				$args['type']    = Controls_Manager::COLOR;
				$args['default'] = $field['default'];
				break;
			default:
				$args['type']    = Controls_Manager::TEXT;
				$args['dynamic'] = array( 'active' => true );
				$args['default'] = (string) $field['default'];
				if ( ! empty( $field['placeholder'] ) ) {
					$args['placeholder'] = $field['placeholder'];
				}
				if ( in_array( $key, array( 'center_coordinate', 'tiles_url', 'geo_bounds', 'mapbox_custom' ), true ) ) {
					$args['label_block'] = true;
					$args['classes']     = 'elementor-control-direction-ltr';
				}
		}
		return $args;
	}

	protected function add_schema_group( $group, array $fields, array $skip = array() ) {
		foreach ( $fields as $key => $field ) {
			if ( $field['group'] !== $group || in_array( $key, $skip, true ) ) {
				continue;
			}
			if ( 'repeater' === $field['type'] ) {
				$this->add_pins_control( $key, $field );
				continue;
			}
			$this->add_control( $key, $this->control_args( $key, $field, $fields ) );
		}
	}

	protected function add_pins_control( $key, array $field ) {
		$repeater = new Repeater();
		foreach ( $field['fields'] as $sub_key => $sub ) {
			$args = array(
				'label'   => $sub['label'],
				'default' => $sub['default'],
			);
			switch ( $sub['type'] ) {
				case 'select':
					$args['type']    = Controls_Manager::SELECT;
					$args['options'] = $sub['options'];
					break;
				case 'textarea':
					$args['type'] = Controls_Manager::WYSIWYG;
					break;
				case 'media':
					$args['type']    = Controls_Manager::MEDIA;
					$args['default'] = array( 'url' => '' );
					break;
				case 'color':
					$args['type'] = Controls_Manager::COLOR;
					break;
				default:
					$args['type']    = Controls_Manager::TEXT;
					$args['dynamic'] = array( 'active' => true );
			}
			if ( in_array( $sub_key, array( 'x', 'y', 'lat', 'lng' ), true ) ) {
				$args['classes'] = 'elementor-control-direction-ltr';
			}
			if ( 'link' === $sub_key ) {
				$args['type']        = Controls_Manager::URL;
				$args['default']     = array( 'url' => '' );
				$args['label_block'] = true;
			}
			if ( 'x' === $sub_key ) {
				$repeater->add_control(
					'position_heading',
					array(
						'label'     => __( 'Position on image / SVG', 'mapify' ),
						'type'      => Controls_Manager::HEADING,
						'separator' => 'before',
					)
				);
			}
			if ( 'lat' === $sub_key ) {
				$repeater->add_control(
					'geo_heading',
					array(
						'label'       => __( 'Position on geographic maps', 'mapify' ),
						'type'        => Controls_Manager::HEADING,
						'separator'   => 'before',
					)
				);
			}
			$repeater->add_control( $sub_key, $args );
		}
		$this->add_control(
			$key,
			array(
				'label'       => $field['label'],
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $repeater->get_controls(),
				'title_field' => '{{{ title }}}',
				'default'     => array(),
				'prevent_empty' => false,
			)
		);
	}

	/* ------------------------------------------------------------------ controls */

	protected function register_controls() {
		$fields = Schema::fields();

		$this->start_controls_section( 'section_branches', array( 'label' => __( 'Branches', 'mapify' ) ) );
		$this->add_schema_group( 'source', $fields );
		$this->end_controls_section();

		$this->start_controls_section( 'section_map', array( 'label' => __( 'Map', 'mapify' ) ) );
		$this->add_schema_group( 'map', $fields );
		$this->add_control(
			'map_keys_notice',
			array(
				'type'            => Controls_Manager::ALERT,
				'alert_type'      => 'info',
				'content'         => sprintf(
					/* translators: %s: settings URL */
					__( 'API keys for Google Maps, Mapbox, Map.ir, Neshan and Parsimap are set in <a href="%s" target="_blank">Branches → Map Settings</a>.', 'mapify' ),
					esc_url( admin_url( 'edit.php?post_type=mapify&page=mapify' ) )
				),
				'condition'       => array( 'maptype' => array( 'google', 'mapbox', 'mapir', 'neshan', 'parsimap' ) ),
			)
		);
		$this->end_controls_section();

		$this->start_controls_section(
			'section_google',
			array(
				'label'     => __( 'Google Maps', 'mapify' ),
				'condition' => array( 'maptype' => 'google' ),
			)
		);
		$this->add_schema_group( 'google', $fields );
		$this->end_controls_section();

		$this->start_controls_section(
			'section_snazzy',
			array(
				'label'     => __( 'Snazzy Maps style', 'mapify' ),
				'condition' => array( 'maptype' => 'google' ),
			)
		);
		$this->add_control(
			'snazzy_help',
			array(
				'type'       => Controls_Manager::ALERT,
				'alert_type' => 'info',
				'heading'    => __( 'Style your map with Snazzy Maps', 'mapify' ),
				'content'    => sprintf(
					/* translators: %s: Snazzy Maps URL */
					__( 'Pick a free style on <a href="%s" target="_blank" rel="noopener">Snazzy Maps</a>, copy its “JavaScript Style Array”, choose “Custom Snazzy Maps style” in Google Maps → Map style and paste the array below.', 'mapify' ),
					esc_url( Schema::snazzy_url( 'explore' ) )
				),
			)
		);
		$this->add_schema_group( 'snazzy', $fields );
		$this->end_controls_section();

		$this->start_controls_section(
			'section_tiles',
			array(
				'label'     => __( 'Tile layer', 'mapify' ),
				'condition' => array( 'maptype' => array( 'osm', 'mapbox', 'neshan', 'parsimap', 'custom' ) ),
			)
		);
		$this->add_schema_group( 'tiles', $fields );
		$this->end_controls_section();

		$this->start_controls_section(
			'section_plane',
			array(
				'label'     => __( 'Image / SVG map', 'mapify' ),
				'condition' => array( 'maptype' => array( 'iran', 'svg', 'image' ) ),
			)
		);
		$this->add_schema_group( 'plane', $fields );
		$this->end_controls_section();

		$this->start_controls_section( 'section_pins', array( 'label' => __( 'Custom pins', 'mapify' ) ) );
		$this->add_control(
			'pins_help',
			array(
				'type'       => Controls_Manager::ALERT,
				'alert_type' => 'info',
				'content'    => $fields['pins']['description'],
			)
		);
		$this->add_schema_group( 'pins', $fields );
		$this->end_controls_section();

		$this->start_controls_section( 'section_markers', array( 'label' => __( 'Markers & clusters', 'mapify' ) ) );
		$this->add_schema_group( 'markers', $fields );
		$this->end_controls_section();

		$this->start_controls_section(
			'section_popup',
			array(
				'label'     => __( 'Popup', 'mapify' ),
				'condition' => array( 'pinaction' => 'popup' ),
			)
		);
		$this->add_schema_group( 'popup', $fields );
		$this->end_controls_section();

		$this->start_controls_section( 'section_list', array( 'label' => __( 'Branches list', 'mapify' ) ) );
		$this->add_schema_group( 'list', $fields );
		$this->end_controls_section();

		$this->start_controls_section( 'section_filter', array( 'label' => __( 'Category filter', 'mapify' ) ) );
		$this->add_control(
			'filter_help',
			array(
				'type'       => Controls_Manager::ALERT,
				'alert_type' => 'info',
				'content'    => sprintf(
					/* translators: %s: categories screen URL */
					__( 'Pin color and pin image of each category are set in <a href="%s" target="_blank">Branches → Categories</a>.', 'mapify' ),
					esc_url( admin_url( 'edit-tags.php?taxonomy=mapify_category&post_type=mapify' ) )
				),
			)
		);
		$this->add_schema_group( 'filter', $fields );
		$this->end_controls_section();

		$this->register_style_controls();
	}

	protected function register_style_controls() {
		$root = '{{WRAPPER}} .mapify';

		/* Map box */
		$this->start_controls_section(
			'style_map',
			array(
				'label' => __( 'Map', 'mapify' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_responsive_control(
			'map_height',
			array(
				'label'       => __( 'Height', 'mapify' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => array( 'px', 'vh', 'em', 'rem' ),
				'range'       => array(
					'px' => array( 'min' => 150, 'max' => 1200 ),
					'vh' => array( 'min' => 10, 'max' => 100 ),
				),
				'default'     => array(
					'unit' => 'px',
					'size' => (int) Options::get( 'default_height' ) ? (int) Options::get( 'default_height' ) : 500,
				),
				'description' => __( 'Image and SVG maps size themselves by their aspect ratio.', 'mapify' ),
				'selectors'   => array( $root => '--mapify-height: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->add_control(
			'accent',
			array(
				'label'     => __( 'Accent color', 'mapify' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( $root => '--mapify-accent: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'loading_bg',
			array(
				'label'     => __( 'Background while loading', 'mapify' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( $root => '--mapify-loading-bg: {{VALUE}};' ),
			)
		);
		$this->add_responsive_control(
			'map_radius',
			array(
				'label'      => __( 'Border radius', 'mapify' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),
				'selectors'  => array( $root => '--mapify-radius: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'map_border',
				'selector' => '{{WRAPPER}} .mapify__stage',
			)
		);
		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'map_shadow',
				'selector' => '{{WRAPPER}} .mapify__stage',
			)
		);
		$this->add_group_control(
			Group_Control_Css_Filter::get_type(),
			array(
				'name'     => 'map_filters',
				'label'    => __( 'Map tiles filter', 'mapify' ),
				'selector' => '{{WRAPPER}} .leaflet-tile-pane, {{WRAPPER}} .mapify-plane__image',
			)
		);
		$this->end_controls_section();

		/* Pins */
		$this->start_controls_section(
			'style_pins',
			array(
				'label' => __( 'Pins', 'mapify' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_control(
			'pin_color',
			array(
				'label'     => __( 'Color', 'mapify' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( $root => '--mapify-pin-color: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'pin_hover_color',
			array(
				'label'     => __( 'Hover / active color', 'mapify' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .mapify-pin:hover, {{WRAPPER}} .mapify-pin.is-active' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'pin_inner_color',
			array(
				'label'     => __( 'Inner dot / border color', 'mapify' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .mapify-pin__hole' => 'fill: {{VALUE}};',
					'{{WRAPPER}} .mapify-pin__dot'  => 'border-color: {{VALUE}};',
				),
			)
		);
		$this->add_responsive_control(
			'pin_size',
			array(
				'label'     => __( 'Size', 'mapify' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 12, 'max' => 96 ) ),
				'selectors' => array( $root => '--mapify-pin-size: {{SIZE}}px;' ),
			)
		);
		$this->add_control(
			'tooltip_heading',
			array(
				'label'     => __( 'Tooltip', 'mapify' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);
		$this->add_control(
			'tooltip_bg',
			array(
				'label'     => __( 'Background', 'mapify' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .mapify-pin[data-tip]::after, {{WRAPPER}} .mapify-plane__tip' => 'background: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'tooltip_color',
			array(
				'label'     => __( 'Text color', 'mapify' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .mapify-pin[data-tip]::after, {{WRAPPER}} .mapify-plane__tip' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'tooltip_typography',
				'selector' => '{{WRAPPER}} .mapify-pin[data-tip]::after, {{WRAPPER}} .mapify-plane__tip',
			)
		);
		$this->add_control(
			'cluster_heading',
			array(
				'label'     => __( 'Clusters', 'mapify' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);
		$this->add_control(
			'cluster_bg',
			array(
				'label'     => __( 'Background', 'mapify' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( $root => '--mapify-cluster-bg: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'cluster_color',
			array(
				'label'     => __( 'Text color', 'mapify' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( $root => '--mapify-cluster-color: {{VALUE}};' ),
			)
		);
		$this->end_controls_section();

		/* Popup */
		$popup_box = '{{WRAPPER}} .mapify-plane__popup, {{WRAPPER}} .leaflet-popup-content-wrapper, {{WRAPPER}} .gm-style .gm-style-iw-c';
		$this->start_controls_section(
			'style_popup',
			array(
				'label'     => __( 'Popup', 'mapify' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'pinaction' => 'popup' ),
			)
		);
		$this->add_responsive_control(
			'popup_width',
			array(
				'label'      => __( 'Width', 'mapify' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 160, 'max' => 600 ) ),
				'selectors'  => array( $root => '--mapify-popup-width: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->add_control(
			'popup_bg',
			array(
				'label'     => __( 'Background', 'mapify' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( $root => '--mapify-popup-bg: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'popup_color',
			array(
				'label'     => __( 'Text color', 'mapify' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( $root => '--mapify-popup-color: {{VALUE}};' ),
			)
		);
		$this->add_responsive_control(
			'popup_radius',
			array(
				'label'     => __( 'Border radius', 'mapify' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'selectors' => array( $root => '--mapify-popup-radius: {{SIZE}}px;' ),
			)
		);
		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'popup_shadow',
				'selector' => $popup_box,
			)
		);
		$this->add_responsive_control(
			'popup_padding',
			array(
				'label'      => __( 'Content padding', 'mapify' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array( '{{WRAPPER}} .mapify-card__body' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);
		$this->add_responsive_control(
			'popup_image_height',
			array(
				'label'      => __( 'Image height', 'mapify' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array( 'px' => array( 'min' => 0, 'max' => 400 ) ),
				'selectors'  => array( '{{WRAPPER}} .mapify-card__image' => 'aspect-ratio: auto; height: {{SIZE}}{{UNIT}};' ),
			)
		);
		$this->add_control(
			'popup_title_heading',
			array(
				'label'     => __( 'Title', 'mapify' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);
		$this->add_control(
			'popup_title_color',
			array(
				'label'     => __( 'Color', 'mapify' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .mapify-card__title' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'popup_title_typography',
				'selector' => '{{WRAPPER}} .mapify-card__title',
			)
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'      => 'popup_text_typography',
				'label'     => __( 'Text typography', 'mapify' ),
				'selector'  => '{{WRAPPER}} .mapify-popup',
				'separator' => 'before',
			)
		);
		$this->add_control(
			'popup_button_heading',
			array(
				'label'     => __( 'Button', 'mapify' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);
		$this->add_control(
			'popup_button_bg',
			array(
				'label'     => __( 'Background', 'mapify' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .mapify-card__link' => 'background: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'popup_button_color',
			array(
				'label'     => __( 'Text color', 'mapify' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .mapify-card__link' => 'color: {{VALUE}} !important;' ),
			)
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'popup_button_typography',
				'selector' => '{{WRAPPER}} .mapify-card__link',
			)
		);
		$this->end_controls_section();

		/* Branches list */
		$item = '{{WRAPPER}} .mapify .mapify__item';
		$this->start_controls_section(
			'style_list',
			array(
				'label'     => __( 'Branches list', 'mapify' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'branchlistshow' => 'yes' ),
			)
		);
		$this->add_responsive_control(
			'list_width',
			array(
				'label'      => __( 'Sidebar width', 'mapify' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array( 'px' => array( 'min' => 160, 'max' => 600 ) ),
				'selectors'  => array( $root => '--mapify-list-width: {{SIZE}}{{UNIT}};' ),
				'condition'  => array( 'branchplacement' => array( 'start', 'end' ) ),
			)
		);
		$this->add_responsive_control(
			'list_gap',
			array(
				'label'     => __( 'Space between list and map', 'mapify' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 80 ) ),
				'selectors' => array( $root => '--mapify-gap: {{SIZE}}px;' ),
			)
		);
		$this->add_responsive_control(
			'list_items_gap',
			array(
				'label'     => __( 'Space between items', 'mapify' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'selectors' => array( '{{WRAPPER}} .mapify__items' => 'gap: {{SIZE}}px;' ),
			)
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'list_typography',
				'selector' => $item,
			)
		);
		$this->add_responsive_control(
			'list_padding',
			array(
				'label'      => __( 'Padding', 'mapify' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array( $item => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);
		$this->add_responsive_control(
			'list_radius',
			array(
				'label'      => __( 'Border radius', 'mapify' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array( $item => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);
		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'list_border',
				'selector' => $item,
			)
		);
		$this->start_controls_tabs( 'list_tabs' );
		foreach (
			array(
				'normal' => array( __( 'Normal', 'mapify' ), $item ),
				'hover'  => array( __( 'Hover', 'mapify' ), $item . ':hover' ),
				'active' => array( __( 'Active', 'mapify' ), $item . '.is-active' ),
			) as $state => $meta
		) {
			$this->start_controls_tab( 'list_tab_' . $state, array( 'label' => $meta[0] ) );
			$this->add_control(
				'list_bg_' . $state,
				array(
					'label'     => __( 'Background', 'mapify' ),
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( $meta[1] => 'background: {{VALUE}};' ),
				)
			);
			$this->add_control(
				'list_color_' . $state,
				array(
					'label'     => __( 'Text color', 'mapify' ),
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( $meta[1] => 'color: {{VALUE}};' ),
				)
			);
			$this->add_group_control(
				Group_Control_Box_Shadow::get_type(),
				array(
					'name'     => 'list_shadow_' . $state,
					'selector' => $meta[1],
				)
			);
			$this->end_controls_tab();
		}
		$this->end_controls_tabs();
		$this->add_control(
			'group_title_heading',
			array(
				'label'     => __( 'Category titles', 'mapify' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
				'condition' => array( 'brancheslistcat' => 'category' ),
			)
		);
		$this->add_control(
			'group_title_color',
			array(
				'label'     => __( 'Color', 'mapify' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .mapify__group-title' => 'color: {{VALUE}}; opacity: 1;' ),
				'condition' => array( 'brancheslistcat' => 'category' ),
			)
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'      => 'group_title_typography',
				'selector'  => '{{WRAPPER}} .mapify__group-title',
				'condition' => array( 'brancheslistcat' => 'category' ),
			)
		);
		$this->end_controls_section();

		/* Search */
		$search = '{{WRAPPER}} .mapify .mapify__search-input';
		$this->start_controls_section(
			'style_search',
			array(
				'label'     => __( 'Search box', 'mapify' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'branchlistshow' => 'yes',
					'branchessearch' => 'yes',
				),
			)
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'search_typography',
				'selector' => $search,
			)
		);
		$this->add_control(
			'search_bg',
			array(
				'label'     => __( 'Background', 'mapify' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( $search => 'background: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'search_color',
			array(
				'label'     => __( 'Text color', 'mapify' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( $search => 'color: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'search_border_color',
			array(
				'label'     => __( 'Border color', 'mapify' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( $search => 'border-color: {{VALUE}};' ),
			)
		);
		$this->add_responsive_control(
			'search_radius',
			array(
				'label'     => __( 'Border radius', 'mapify' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'selectors' => array( $search => 'border-radius: {{SIZE}}px;' ),
			)
		);
		$this->end_controls_section();

		/* Category filter */
		$chip = '{{WRAPPER}} .mapify .mapify__cat';
		$this->start_controls_section(
			'style_filter',
			array(
				'label' => __( 'Category filter', 'mapify' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'chip_typography',
				'selector' => $chip,
			)
		);
		$this->start_controls_tabs( 'chip_tabs' );
		foreach (
			array(
				'normal' => array( __( 'Normal', 'mapify' ), $chip ),
				'active' => array( __( 'Active', 'mapify' ), $chip . '.is-active' ),
			) as $state => $meta
		) {
			$this->start_controls_tab( 'chip_tab_' . $state, array( 'label' => $meta[0] ) );
			$this->add_control(
				'chip_bg_' . $state,
				array(
					'label'     => __( 'Background', 'mapify' ),
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( $meta[1] => 'background: {{VALUE}};' ),
				)
			);
			$this->add_control(
				'chip_color_' . $state,
				array(
					'label'     => __( 'Text color', 'mapify' ),
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( $meta[1] => 'color: {{VALUE}};' ),
				)
			);
			$this->add_control(
				'chip_border_' . $state,
				array(
					'label'     => __( 'Border color', 'mapify' ),
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( $meta[1] => 'border-color: {{VALUE}};' ),
				)
			);
			$this->end_controls_tab();
		}
		$this->end_controls_tabs();
		$this->add_responsive_control(
			'chip_radius',
			array(
				'label'     => __( 'Border radius', 'mapify' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 40 ) ),
				'selectors' => array( $chip => 'border-radius: {{SIZE}}px;' ),
				'separator' => 'before',
			)
		);
		$this->end_controls_section();

		/* Directions button */
		$dir = '{{WRAPPER}} .mapify-card__directions';
		$this->start_controls_section(
			'style_directions',
			array(
				'label'     => __( 'Get directions button', 'mapify' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'pinaction'        => 'popup',
					'popup_directions' => 'yes',
				),
			)
		);
		$this->add_control(
			'directions_bg',
			array(
				'label'     => __( 'Background', 'mapify' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( $dir => 'background: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'directions_color',
			array(
				'label'     => __( 'Text color', 'mapify' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( $dir => 'color: {{VALUE}} !important;' ),
			)
		);
		$this->add_control(
			'directions_border',
			array(
				'label'     => __( 'Border color', 'mapify' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( $dir => 'border-color: {{VALUE}};' ),
			)
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'directions_typography',
				'selector' => $dir,
			)
		);
		$this->end_controls_section();

		/* Regions */
		$this->start_controls_section(
			'style_regions',
			array(
				'label'     => __( 'Regions (SVG maps)', 'mapify' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'maptype' => array( 'iran', 'svg' ) ),
			)
		);
		foreach (
			array(
				'region_fill'   => array( __( 'Fill', 'mapify' ), '--mapify-region-fill' ),
				'region_active' => array( __( 'Regions with branches', 'mapify' ), '--mapify-region-active' ),
				'region_hover'  => array( __( 'Hover / selected', 'mapify' ), '--mapify-region-hover' ),
				'region_stroke' => array( __( 'Border color', 'mapify' ), '--mapify-region-stroke' ),
				'plane_bg'      => array( __( 'Map background', 'mapify' ), '--mapify-plane-bg' ),
			) as $key => $meta
		) {
			$this->add_control(
				$key,
				array(
					'label'     => $meta[0],
					'type'      => Controls_Manager::COLOR,
					'selectors' => array( $root => $meta[1] . ': {{VALUE}};' ),
				)
			);
		}
		$this->add_control(
			'region_stroke_width',
			array(
				'label'     => __( 'Border width', 'mapify' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array( 'px' => array( 'min' => 0, 'max' => 6, 'step' => 0.5 ) ),
				'selectors' => array( $root => '--mapify-region-stroke-width: {{SIZE}};' ),
			)
		);
		$this->end_controls_section();
	}

	/* ------------------------------------------------------------------ render */

	protected function render() {
		$settings = $this->get_settings_for_display();
		$raw      = array();
		foreach ( Schema::fields() as $key => $field ) {
			if ( 'appearance' === $field['group'] || ! array_key_exists( $key, $settings ) ) {
				continue;
			}
			$raw[ $key ] = $settings[ $key ];
		}
		$raw['el_id'] = '';
		echo Renderer::render( Schema::normalize( $raw ), array( 'style_vars' => false ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in Renderer.
	}
}
