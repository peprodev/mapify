<?php
/**
 * REST API (mapify/v1) and the JSON import / export of settings, categories and branches.
 *
 * @package Mapify
 */

namespace Mapify;

defined( 'ABSPATH' ) || exit;

class Transfer {

	const NS     = 'mapify/v1';
	const FORMAT = 'mapify-export';

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
		register_rest_route(
			self::NS,
			'/export',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'rest_export' ),
				'permission_callback' => array( __CLASS__, 'can_manage' ),
				'args'                => array(
					'settings'   => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'categories' => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'branches'   => array(
						'type'    => 'boolean',
						'default' => true,
					),
				),
			)
		);
		register_rest_route(
			self::NS,
			'/import',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'rest_import' ),
				'permission_callback' => array( __CLASS__, 'can_manage' ),
				'args'                => array(
					'data'       => array(
						'type'     => 'object',
						'required' => true,
					),
					'settings'   => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'categories' => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'branches'   => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'existing'   => array(
						'type'    => 'string',
						'enum'    => array( 'skip', 'update', 'duplicate' ),
						'default' => 'update',
					),
					'images'     => array(
						'type'    => 'boolean',
						'default' => false,
					),
				),
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
				$pin   = Branches::category_pin( $term->term_id );
				$out[] = array(
					'id'          => $term->term_id,
					'name'        => $term->name,
					'slug'        => $term->slug,
					'description' => $term->description,
					'parent'      => $term->parent,
					'count'       => $term->count,
					'pin_color'   => $pin['color'],
					'pin_image'   => $pin['image'],
				);
			}
		}
		return rest_ensure_response( $out );
	}

	/* ------------------------------------------------------------------ export */

	public static function rest_export( \WP_REST_Request $request ) {
		return rest_ensure_response( self::export( (bool) $request['settings'], (bool) $request['categories'], (bool) $request['branches'] ) );
	}

	public static function export( $settings = true, $categories = true, $branches = true ) {
		$out = array(
			'format'   => self::FORMAT,
			'version'  => MAPIFY_VERSION,
			'site'     => home_url( '/' ),
			'exported' => gmdate( 'c' ),
		);
		if ( $settings ) {
			$out['settings'] = Options::all();
		}
		if ( $categories ) {
			$out['categories'] = array();
			$terms             = get_terms(
				array(
					'taxonomy'   => Branches::TAXONOMY,
					'hide_empty' => false,
					'orderby'    => 'parent',
				)
			);
			if ( ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					$parent = $term->parent ? get_term( $term->parent, Branches::TAXONOMY ) : null;
					$meta   = array();
					foreach ( array_keys( Branches::term_meta_fields() ) as $key ) {
						$meta[ $key ] = (string) get_term_meta( $term->term_id, $key, true );
					}
					$out['categories'][] = array(
						'slug'        => $term->slug,
						'name'        => $term->name,
						'description' => $term->description,
						'parent'      => $parent && ! is_wp_error( $parent ) ? $parent->slug : '',
						'meta'        => $meta,
					);
				}
			}
		}
		if ( $branches ) {
			$out['branches'] = array();
			$posts           = get_posts(
				array(
					'post_type'      => Branches::POST_TYPE,
					'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
					'posts_per_page' => -1,
					'orderby'        => 'menu_order',
					'order'          => 'ASC',
				)
			);
			foreach ( $posts as $post ) {
				$meta = array();
				foreach ( self::branch_meta_keys() as $key ) {
					$meta[ $key ] = (string) get_post_meta( $post->ID, $key, true );
				}
				$terms             = wp_get_object_terms( $post->ID, Branches::TAXONOMY, array( 'fields' => 'slugs' ) );
				$thumb             = get_the_post_thumbnail_url( $post->ID, 'full' );
				$out['branches'][] = array(
					'slug'       => $post->post_name,
					'title'      => $post->post_title,
					'status'     => $post->post_status,
					'content'    => $post->post_content,
					'excerpt'    => $post->post_excerpt,
					'menu_order' => (int) $post->menu_order,
					'date'       => $post->post_date,
					'categories' => is_wp_error( $terms ) ? array() : $terms,
					'thumbnail'  => $thumb ? $thumb : '',
					'meta'       => $meta,
				);
			}
		}
		return apply_filters( 'mapify_export_data', $out );
	}

	/** Every stored branch meta key (place_details_*). */
	public static function branch_meta_keys() {
		$keys = array();
		foreach ( array_keys( Branches::meta_fields() ) as $key ) {
			$keys[] = 'place_details_' . $key;
		}
		foreach ( array( 'map_data', 'pinimg', 'pincolor', 'content_template' ) as $key ) {
			$keys[] = 'place_details_' . $key;
		}
		return $keys;
	}

	/* ------------------------------------------------------------------ import */

	public static function rest_import( \WP_REST_Request $request ) {
		$data = $request['data'];
		if ( ! is_array( $data ) || ( isset( $data['format'] ) && self::FORMAT !== $data['format'] ) ) {
			return new \WP_Error( 'mapify_invalid_file', __( 'This is not a Mapify export file.', 'mapify' ), array( 'status' => 400 ) );
		}
		$report = self::import(
			$data,
			array(
				'settings'   => (bool) $request['settings'],
				'categories' => (bool) $request['categories'],
				'branches'   => (bool) $request['branches'],
				'existing'   => $request['existing'],
				'images'     => (bool) $request['images'],
			)
		);
		return rest_ensure_response( $report );
	}

	/**
	 * @param array $data Decoded export file.
	 * @param array $args settings, categories, branches (bool), existing (skip|update|duplicate), images (bool).
	 * @return array Counts of what happened.
	 */
	public static function import( array $data, array $args ) {
		$report = array(
			'settings'   => false,
			'categories' => array(
				'created' => 0,
				'updated' => 0,
				'skipped' => 0,
			),
			'branches'   => array(
				'created' => 0,
				'updated' => 0,
				'skipped' => 0,
			),
			'errors'     => array(),
		);

		if ( $args['settings'] && ! empty( $data['settings'] ) && is_array( $data['settings'] ) ) {
			update_option( Options::KEY, Options::sanitize( wp_parse_args( $data['settings'], Options::all() ) ) );
			$report['settings'] = true;
		}

		if ( $args['categories'] && ! empty( $data['categories'] ) && is_array( $data['categories'] ) ) {
			$pending = array();
			foreach ( $data['categories'] as $cat ) {
				if ( ! is_array( $cat ) || empty( $cat['name'] ) ) {
					continue;
				}
				$slug      = sanitize_title( isset( $cat['slug'] ) && '' !== $cat['slug'] ? $cat['slug'] : $cat['name'] );
				$existing  = get_term_by( 'slug', $slug, Branches::TAXONOMY );
				$term_args = array(
					'slug'        => $slug,
					'description' => isset( $cat['description'] ) ? sanitize_textarea_field( $cat['description'] ) : '',
				);
				if ( $existing && 'skip' === $args['existing'] ) {
					++$report['categories']['skipped'];
					continue;
				}
				if ( $existing ) {
					// Categories are matched by slug and never duplicated, so branches keep pointing at one term.
					$term_args['name'] = sanitize_text_field( $cat['name'] );
					$result            = wp_update_term( $existing->term_id, Branches::TAXONOMY, $term_args );
					$key               = 'updated';
				} else {
					$result = wp_insert_term( sanitize_text_field( $cat['name'] ), Branches::TAXONOMY, $term_args );
					$key    = 'created';
				}
				if ( is_wp_error( $result ) ) {
					$report['errors'][] = $cat['name'] . ': ' . $result->get_error_message();
					continue;
				}
				++$report['categories'][ $key ];
				$term_id = (int) $result['term_id'];
				if ( ! empty( $cat['meta'] ) && is_array( $cat['meta'] ) ) {
					foreach ( Branches::term_meta_fields() as $meta_key => $field ) {
						if ( isset( $cat['meta'][ $meta_key ] ) ) {
							$value = call_user_func( $field[1], $cat['meta'][ $meta_key ] );
							'' === $value ? delete_term_meta( $term_id, $meta_key ) : update_term_meta( $term_id, $meta_key, $value );
						}
					}
				}
				if ( ! empty( $cat['parent'] ) ) {
					$pending[ $term_id ] = sanitize_title( $cat['parent'] );
				}
			}
			// Parents are linked once every category exists.
			foreach ( $pending as $term_id => $parent_slug ) {
				$parent = get_term_by( 'slug', $parent_slug, Branches::TAXONOMY );
				if ( $parent && (int) $parent->term_id !== $term_id ) {
					wp_update_term( $term_id, Branches::TAXONOMY, array( 'parent' => (int) $parent->term_id ) );
				}
			}
		}

		if ( $args['branches'] && ! empty( $data['branches'] ) && is_array( $data['branches'] ) ) {
			$keys     = self::branch_meta_keys();
			$registry = get_registered_meta_keys( 'post', Branches::POST_TYPE );
			foreach ( $data['branches'] as $branch ) {
				if ( ! is_array( $branch ) || ( empty( $branch['title'] ) && empty( $branch['slug'] ) ) ) {
					continue;
				}
				$slug     = sanitize_title( ! empty( $branch['slug'] ) ? $branch['slug'] : $branch['title'] );
				$existing = get_page_by_path( $slug, OBJECT, Branches::POST_TYPE );
				if ( $existing && 'skip' === $args['existing'] ) {
					++$report['branches']['skipped'];
					continue;
				}
				$status  = isset( $branch['status'] ) && in_array( $branch['status'], array( 'publish', 'draft', 'pending', 'private' ), true ) ? $branch['status'] : 'publish';
				$content = isset( $branch['content'] ) ? (string) $branch['content'] : '';
				$postarr = array(
					'post_type'    => Branches::POST_TYPE,
					'post_title'   => isset( $branch['title'] ) ? sanitize_text_field( $branch['title'] ) : '',
					'post_status'  => $status,
					'post_content' => current_user_can( 'unfiltered_html' ) ? $content : wp_kses_post( $content ),
					'post_excerpt' => isset( $branch['excerpt'] ) ? sanitize_textarea_field( $branch['excerpt'] ) : '',
					'menu_order'   => isset( $branch['menu_order'] ) ? (int) $branch['menu_order'] : 0,
				);
				if ( $existing && 'update' === $args['existing'] ) {
					$postarr['ID'] = $existing->ID;
					$post_id       = wp_update_post( wp_slash( $postarr ), true );
					$key           = 'updated';
				} else {
					$postarr['post_name'] = $slug;
					$post_id              = wp_insert_post( wp_slash( $postarr ), true );
					$key                  = 'created';
				}
				if ( is_wp_error( $post_id ) ) {
					$report['errors'][] = ( isset( $branch['title'] ) ? $branch['title'] : $slug ) . ': ' . $post_id->get_error_message();
					continue;
				}
				++$report['branches'][ $key ];
				if ( ! empty( $branch['meta'] ) && is_array( $branch['meta'] ) ) {
					foreach ( $keys as $meta_key ) {
						if ( ! isset( $branch['meta'][ $meta_key ] ) ) {
							continue;
						}
						$sanitize = isset( $registry[ $meta_key ]['sanitize_callback'] ) ? $registry[ $meta_key ]['sanitize_callback'] : 'sanitize_text_field';
						$value    = call_user_func( $sanitize, (string) $branch['meta'][ $meta_key ] );
						'' === $value ? delete_post_meta( $post_id, $meta_key ) : update_post_meta( $post_id, $meta_key, wp_slash( $value ) );
					}
				}
				if ( isset( $branch['categories'] ) && is_array( $branch['categories'] ) ) {
					$ids = array();
					foreach ( $branch['categories'] as $cat_slug ) {
						$term = get_term_by( 'slug', sanitize_title( $cat_slug ), Branches::TAXONOMY );
						if ( $term ) {
							$ids[] = (int) $term->term_id;
						}
					}
					wp_set_object_terms( $post_id, $ids, Branches::TAXONOMY );
				}
				if ( $args['images'] && ! empty( $branch['thumbnail'] ) && ! has_post_thumbnail( $post_id ) ) {
					$attachment = self::sideload( $branch['thumbnail'], $post_id );
					if ( is_wp_error( $attachment ) ) {
						$report['errors'][] = $postarr['post_title'] . ': ' . $attachment->get_error_message();
					} else {
						set_post_thumbnail( $post_id, $attachment );
					}
				}
			}
		}
		return apply_filters( 'mapify_import_report', $report, $data, $args );
	}

	protected static function sideload( $url, $post_id ) {
		$url = esc_url_raw( $url );
		if ( ! $url || ! wp_http_validate_url( $url ) ) {
			return new \WP_Error( 'mapify_bad_image', __( 'Image URL is not valid.', 'mapify' ) );
		}
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		return media_sideload_image( $url, $post_id, null, 'id' );
	}
}
