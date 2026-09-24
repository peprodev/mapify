<?php
/**
 * Public REST API (mapify/v1) and branch categories in Tools → Export.
 *
 * @package Mapify
 */

namespace Mapify;

defined( 'ABSPATH' ) || exit;

class Transfer {

	const NS = 'mapify/v1';

	/** Arguments of a running Tools → Export request. */
	protected static $wxr = null;

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
		add_action(
			'export_wp',
			function ( $args ) {
				self::$wxr = $args;
			}
		);
		add_action( 'rss2_head', array( __CLASS__, 'wxr_terms' ) );
	}

	/**
	 * Tools → Export only lists categories (with their pin meta) when "All content" is chosen.
	 * When only Branches are exported, add the branch categories so the WordPress importer recreates them.
	 */
	public static function wxr_terms() {
		if ( ! self::$wxr || Branches::POST_TYPE !== self::$wxr['content'] || ! function_exists( 'wxr_term_meta' ) ) {
			return;
		}
		$terms = get_terms(
			array(
				'taxonomy'   => Branches::TAXONOMY,
				'hide_empty' => false,
				'orderby'    => 'parent',
			)
		);
		if ( is_wp_error( $terms ) ) {
			return;
		}
		foreach ( $terms as $t ) {
			$parent = $t->parent ? get_term( $t->parent, Branches::TAXONOMY ) : null;
			echo "\t<wp:term>\n";
			echo "\t\t<wp:term_id>" . (int) $t->term_id . "</wp:term_id>\n";
			echo "\t\t<wp:term_taxonomy>" . wxr_cdata( $t->taxonomy ) . "</wp:term_taxonomy>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo "\t\t<wp:term_slug>" . wxr_cdata( $t->slug ) . "</wp:term_slug>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo "\t\t<wp:term_parent>" . wxr_cdata( $parent && ! is_wp_error( $parent ) ? $parent->slug : '' ) . "</wp:term_parent>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			wxr_term_name( $t );
			wxr_term_description( $t );
			wxr_term_meta( $t );
			echo "\t</wp:term>\n";
		}
	}

	public static function routes() {
		register_rest_route(
			self::NS,
			'/branches',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'rest_branches' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'category' => array(
						'description' => __( 'Category slugs, comma separated.', 'mapify' ),
						'type'        => 'string',
					),
					'include'  => array(
						'description' => __( 'Branch IDs, comma separated.', 'mapify' ),
						'type'        => 'string',
					),
					'search'   => array(
						'description' => __( 'Search text.', 'mapify' ),
						'type'        => 'string',
					),
				),
			)
		);
		register_rest_route(
			self::NS,
			'/categories',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'rest_categories' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public static function can_manage() {
		return current_user_can( 'manage_options' );
	}

	/* ------------------------------------------------------------------ public read API */

	public static function rest_branches( \WP_REST_Request $request ) {
		$settings = array(
			'branchtype' => 'all',
			'branchcat'  => array(),
			'branchids'  => array(),
			'orderby'    => 'menu_order',
			'order'      => 'ASC',
		);
		if ( $request['category'] ) {
			$settings['branchtype'] = 'cat';
			$settings['branchcat']  = array_filter( array_map( 'sanitize_title', explode( ',', $request['category'] ) ) );
		} elseif ( $request['include'] ) {
			$settings['branchtype'] = 'id';
			$settings['branchids']  = array_filter( array_map( 'absint', explode( ',', $request['include'] ) ) );
		}
		$items = Branches::query( $settings );
		$term  = trim( (string) $request['search'] );
		if ( '' !== $term ) {
			$items = array_values(
				array_filter(
					$items,
					function ( $item ) use ( $term ) {
						$text = $item['title'] . ' ' . $item['address'] . ' ' . $item['phone'] . ' ' . implode( ' ', $item['categories'] );
						return false !== stripos( $text, $term );
					}
				)
			);
		}
		return rest_ensure_response( $items );
	}

	public static function rest_categories() {
		$terms = get_terms(
			array(
				'taxonomy'   => Branches::TAXONOMY,
				'hide_empty' => false,
			)
		);
		$out = array();
		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$out[] = apply_filters(
					'mapify_rest_category',
					array(
						'id'          => $term->term_id,
						'name'        => $term->name,
						'slug'        => $term->slug,
						'description' => $term->description,
						'parent'      => $term->parent,
						'count'       => $term->count,
					),
					$term
				);
			}
		}
		return rest_ensure_response( $out );
	}
}
