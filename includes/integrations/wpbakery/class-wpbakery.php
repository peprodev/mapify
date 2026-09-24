<?php
/**
 * WPBakery Page Builder element: Branches Map.
 *
 * Uses vc_lean_map() so the (large) param list is only built when the editor needs it.
 *
 * @package Mapify
 */

namespace Mapify\Integrations;

use Mapify\Schema;
use Mapify\Shortcode;

defined( 'ABSPATH' ) || exit;

class WPBakery {

	public static function init() {
		if ( ! function_exists( 'vc_lean_map' ) ) {
			return;
		}
		if ( function_exists( 'vc_add_shortcode_param' ) ) {
			vc_add_shortcode_param( 'mapify_image_select', array( __CLASS__, 'param_image_select' ), MAPIFY_ASSETS . 'js/wpbakery-params.js?ver=' . MAPIFY_VERSION );
		}
		vc_lean_map( Shortcode::TAG, array( __CLASS__, 'settings' ) );
	}

	/** Two-column layout for short inputs. */
	protected static function column_class( $key, array $field ) {
		if ( in_array( $field['type'], array( 'toggle', 'number', 'color' ), true ) ) {
			return 'vc_col-sm-6';
		}
		if ( 'select' === $field['type'] && empty( $field['previews'] ) && ! in_array( $key, array( 'maptype', 'branchtype' ), true ) ) {
			return 'vc_col-sm-6';
		}
		return 'vc_col-sm-12';
	}

	protected static function dependency( array $field, array $fields ) {
		if ( empty( $field['condition'] ) ) {
			return null;
		}
		// WPBakery supports one dependency per param and hides dependents of hidden params itself.
		$parent = array_keys( $field['condition'] )[0];
		$values = $field['condition'][ $parent ];
		if ( isset( $fields[ $parent ] ) && 'toggle' === $fields[ $parent ]['type'] ) {
			return array(
				'element'   => $parent,
				'not_empty' => true,
			);
		}
		return array(
			'element' => $parent,
			'value'   => array_values( array_map( 'strval', $values ) ),
		);
	}

	protected static function values( array $options ) {
		$out = array();
		foreach ( $options as $value => $label ) {
			$out[ $label ] = (string) $value;
		}
		return $out;
	}

	protected static function param( $key, array $field, array $fields ) {
		$groups = Schema::groups();
		$param  = array(
			'param_name'       => $key,
			'heading'          => $field['label'],
			'group'            => $groups[ $field['group'] ],
			'edit_field_class' => 'vc_column ' . self::column_class( $key, $field ),
			'save_always'      => true,
		);
		if ( ! empty( $field['description'] ) ) {
			$param['description'] = $field['description'];
		}
		$dep = self::dependency( $field, $fields );
		if ( $dep ) {
			$param['dependency'] = $dep;
		}
		switch ( $field['type'] ) {
			case 'select':
				$options = Schema::options( $field );
				if ( ! empty( $field['previews'] ) ) {
					$images = array();
					foreach ( $options as $value => $label ) {
						$images[ $value ] = Schema::style_preview( $key, $value );
					}
					$param['type']   = 'mapify_image_select';
					$param['value']  = $options;
					$param['images'] = $images;
				} else {
					$param['type']  = 'dropdown';
					$param['value'] = self::values( $options );
				}
				$param['std'] = (string) $field['default'];
				if ( 'maptype' === $key ) {
					$param['admin_label'] = true;
				}
				break;
			case 'multiselect':
				$param['type']  = 'checkbox';
				$param['value'] = self::values( Schema::options( $field ) );
				unset( $param['save_always'] );
				if ( is_array( $field['default'] ) && $field['default'] ) {
					$param['std'] = implode( ',', $field['default'] );
				}
				break;
			case 'toggle':
				$param['type']  = 'checkbox';
				$param['value'] = array( __( 'Yes', 'mapify' ) => 'yes' );
				$param['std']   = $field['default'] ? 'yes' : '';
				break;
			case 'color':
				$param['type'] = 'colorpicker';
				break;
			case 'media':
				$param['type'] = 'attach_image';
				break;
			case 'code':
				$param['type']  = 'textarea_raw_html';
				if ( 'popup_markup' === $key ) {
					// WPBakery stores this param base64-encoded.
					$param['value'] = base64_encode( rawurlencode( Schema::default_popup_template() ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
				}
				break;
			case 'textarea':
				$param['type'] = 'textarea';
				break;
			case 'repeater':
				$param['type']   = 'param_group';
				$param['value']  = '';
				$param['params'] = array();
				foreach ( $field['fields'] as $sub_key => $sub ) {
					$p = array(
						'param_name'       => $sub_key,
						'heading'          => $sub['label'],
						'edit_field_class' => 'vc_column ' . ( in_array( $sub_key, array( 'x', 'y', 'lat', 'lng' ), true ) ? 'vc_col-sm-3' : 'vc_col-sm-12' ),
					);
					switch ( $sub['type'] ) {
						case 'select':
							$p['type']  = 'dropdown';
							$p['value'] = self::values( $sub['options'] );
							$p['std']   = $sub['default'];
							$p['edit_field_class'] = 'vc_column vc_col-sm-6';
							break;
						case 'textarea':
							$p['type'] = 'textarea';
							break;
						case 'media':
							$p['type'] = 'attach_image';
							$p['edit_field_class'] = 'vc_column vc_col-sm-6';
							break;
						case 'color':
							$p['type'] = 'colorpicker';
							$p['edit_field_class'] = 'vc_column vc_col-sm-6';
							break;
						default:
							$p['type'] = 'textfield';
							if ( 'title' === $sub_key ) {
								$p['admin_label'] = true;
							}
					}
					if ( 'unit' === $sub_key ) {
						$p['edit_field_class'] = 'vc_column vc_col-sm-6';
					}
					$param['params'][] = $p;
				}
				break;
			default:
				$param['type'] = 'textfield';
				if ( isset( $field['placeholder'] ) ) {
					$param['description'] = trim( ( isset( $param['description'] ) ? $param['description'] . ' ' : '' ) . sprintf( /* translators: %s: example */ __( 'e.g. %s', 'mapify' ), $field['placeholder'] ) );
				}
		}
		return $param;
	}

	public static function settings() {
		$fields = Schema::fields();
		$params = array();
		foreach ( $fields as $key => $field ) {
			if ( 'el_id' === $key ) {
				$params[] = array(
					'type'        => 'el_id',
					'param_name'  => 'el_id',
					'heading'     => $field['label'],
					'group'       => Schema::groups()['advanced'],
					'settings'    => array( 'auto_generate' => false ),
				);
				continue;
			}
			$params[] = self::param( $key, $field, $fields );
		}
		$params[] = array(
			'type'       => 'css_editor',
			'heading'    => __( 'CSS box', 'mapify' ),
			'param_name' => 'css',
			'group'      => __( 'Design Options', 'mapify' ),
		);

		return array(
			'name'                    => __( 'Branches Map', 'mapify' ),
			'base'                    => Shortcode::TAG,
			'category'                => __( 'PeproDev Elements', 'mapify' ),
			'description'             => __( 'Branches on Google, OSM, Mapbox, Map.ir, SVG or image maps', 'mapify' ),
			'icon'                    => MAPIFY_ASSETS . 'img/mapify-icon.svg',
			'show_settings_on_create' => true,
			'admin_enqueue_css'       => array( MAPIFY_ASSETS . 'css/wpbakery.css?ver=' . MAPIFY_VERSION ),
			'params'                  => $params,
		);
	}

	/**
	 * Image grid select (used for Google map styles).
	 */
	public static function param_image_select( $settings, $value ) {
		$name  = esc_attr( $settings['param_name'] );
		$value = '' === (string) $value && isset( $settings['std'] ) ? $settings['std'] : $value;
		$html  = count( $settings['value'] ) > 12 ? '<input type="search" class="mapify-vc-images__search" placeholder="' . esc_attr__( 'Search styles…', 'mapify' ) . '" />' : '';
		$html .= '<div class="mapify-vc-images" data-target="' . $name . '">';
		foreach ( $settings['value'] as $option => $label ) {
			$img   = isset( $settings['images'][ $option ] ) ? $settings['images'][ $option ] : '';
			$html .= '<button type="button" class="mapify-vc-images__item' . ( (string) $option === (string) $value ? ' is-selected' : '' ) . '" data-value="' . esc_attr( $option ) . '" data-search="' . esc_attr( strtolower( $label . ' ' . $option ) ) . '">'
				. ( $img ? '<img src="' . esc_url( $img ) . '" alt="" loading="lazy" />' : '<span class="mapify-vc-images__blank"></span>' )
				. '<span>' . esc_html( $label ) . '</span></button>';
		}
		$html .= '</div><input type="hidden" name="' . $name . '" class="wpb_vc_param_value ' . $name . ' ' . esc_attr( $settings['type'] ) . '_field" value="' . esc_attr( $value ) . '" />';
		return $html;
	}
}
