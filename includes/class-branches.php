<?php
/**
 * Branch post type, taxonomy, data access and the single-branch template.
 *
 * @package Mapify
 */

namespace Mapify;

defined( 'ABSPATH' ) || exit;

class Branches {

	const POST_TYPE = 'mapify';
	const TAXONOMY  = 'mapify_category';

	/** Branches allowed in this edition. */
	const MAX_BRANCHES = 15;

	/** Meta fields: key (stored as place_details_{key}) => [label, input type]. */
	public static function meta_fields() {
		return apply_filters(
			'mapify_branch_meta_fields',
			array(
				'address'    => array( __( 'Address', 'mapify' ), 'textarea' ),
				'phone'      => array( __( 'Phone', 'mapify' ), 'text' ),
				'site'       => array( __( 'Website', 'mapify' ), 'url' ),
				'email'      => array( __( 'Email', 'mapify' ), 'email' ),
				'socailig'   => array( __( 'Instagram', 'mapify' ), 'url' ),
				'socailtg'   => array( __( 'Telegram', 'mapify' ), 'url' ),
				'socailtw'   => array( __( 'X (Twitter)', 'mapify' ), 'url' ),
				'socailfb'   => array( __( 'Facebook', 'mapify' ), 'url' ),
				'socailli'   => array( __( 'LinkedIn', 'mapify' ), 'url' ),
				'additional' => array( __( 'Additional text', 'mapify' ), 'textarea' ),
			)
		);
	}

	public static function register() {
		$labels = array(
			'name'                  => _x( 'Branches', 'Post Type General Name', 'mapify' ),
			'singular_name'         => _x( 'Branch', 'Post Type Singular Name', 'mapify' ),
			'menu_name'             => __( 'Branches', 'mapify' ),
			'name_admin_bar'        => __( 'Branch', 'mapify' ),
			'archives'              => __( 'Branch Archives', 'mapify' ),
			'all_items'             => __( 'All Branches', 'mapify' ),
			'add_new_item'          => __( 'Add New Branch', 'mapify' ),
			'add_new'               => __( 'Add New', 'mapify' ),
			'new_item'              => __( 'New Branch', 'mapify' ),
			'edit_item'             => __( 'Edit Branch', 'mapify' ),
			'update_item'           => __( 'Update Branch', 'mapify' ),
			'view_item'             => __( 'View Branch', 'mapify' ),
			'view_items'            => __( 'View Branches', 'mapify' ),
			'search_items'          => __( 'Search Branches', 'mapify' ),
			'not_found'             => __( 'No branch found', 'mapify' ),
			'not_found_in_trash'    => __( 'No branch found in Trash', 'mapify' ),
			'featured_image'        => __( 'Branch Image', 'mapify' ),
			'set_featured_image'    => __( 'Set branch image', 'mapify' ),
			'remove_featured_image' => __( 'Remove branch image', 'mapify' ),
			'use_featured_image'    => __( 'Use as branch image', 'mapify' ),
			'items_list'            => __( 'Branches list', 'mapify' ),
			'items_list_navigation' => __( 'Branches list navigation', 'mapify' ),
			'filter_items_list'     => __( 'Filter Branches list', 'mapify' ),
		);
		$slug   = Options::get( 'branch_slug' );
		register_post_type(
			self::POST_TYPE,
			array(
				'label'           => __( 'Branches', 'mapify' ),
				'description'     => __( 'Add branches to show on map', 'mapify' ),
				'labels'          => $labels,
				'supports'        => array( 'title', 'editor', 'thumbnail', 'revisions', 'page-attributes', 'elementor', 'custom-fields' ),
				'hierarchical'    => false,
				'public'          => true,
				'show_ui'         => true,
				'show_in_menu'    => true,
				'show_in_rest'    => true,
				'rest_base'       => self::POST_TYPE,
				'can_export'      => true,
				'menu_position'   => 25,
				'menu_icon'       => 'dashicons-location',
				'has_archive'     => $slug,
				'rewrite'         => array(
					'slug'       => $slug,
					'with_front' => true,
				),
				'capability_type' => 'post',
			)
		);
		register_taxonomy(
			self::TAXONOMY,
			array( self::POST_TYPE ),
			array(
				'hierarchical'      => true,
				'labels'            => array(
					'name'          => _x( 'Categories', 'taxonomy general name', 'mapify' ),
					'singular_name' => _x( 'Category', 'taxonomy singular name', 'mapify' ),
					'search_items'  => __( 'Search Categories', 'mapify' ),
					'all_items'     => __( 'All Categories', 'mapify' ),
					'parent_item'   => __( 'Parent Category', 'mapify' ),
					'edit_item'     => __( 'Edit Category', 'mapify' ),
					'update_item'   => __( 'Update Category', 'mapify' ),
					'add_new_item'  => __( 'Add New Category', 'mapify' ),
					'new_item_name' => __( 'New Category Name', 'mapify' ),
					'menu_name'     => __( 'Categories', 'mapify' ),
				),
				'show_ui'           => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'query_var'         => true,
				'rewrite'           => array( 'slug' => 'branches' ),
			)
		);

		self::register_meta();
	}

	/** Branch meta that is not listed in meta_fields(): key => sanitize callback. */
	protected static function extra_meta() {
		return array(
			'map_data'         => array( __CLASS__, 'sanitize_map_data' ),
			'pinimg'           => 'esc_url_raw',
			'pincolor'         => array( __CLASS__, 'sanitize_color' ),
			'content_template' => array( __CLASS__, 'sanitize_template' ),
		);
	}

	/**
	 * Every branch field is exposed to the REST API (wp/v2/mapify).
	 */
	protected static function register_meta() {
		$auth = function () {
			return current_user_can( 'edit_posts' );
		};
		foreach ( self::meta_fields() as $key => $field ) {
			register_post_meta(
				self::POST_TYPE,
				'place_details_' . $key,
				array(
					'type'              => 'string',
					'single'            => true,
					'default'           => '',
					'show_in_rest'      => true,
					'sanitize_callback' => self::field_sanitizer( $field[1] ),
					'auth_callback'     => $auth,
				)
			);
		}
		foreach ( self::extra_meta() as $key => $sanitize ) {
			register_post_meta(
				self::POST_TYPE,
				'place_details_' . $key,
				array(
					'type'              => 'string',
					'single'            => true,
					'default'           => '',
					'show_in_rest'      => true,
					'sanitize_callback' => $sanitize,
					'auth_callback'     => $auth,
				)
			);
		}
		register_rest_field(
			self::POST_TYPE,
			'mapify_location',
			array(
				'get_callback'    => function ( $post ) {
					return self::location( $post['id'] );
				},
				'update_callback' => function ( $value, $post ) {
					if ( null === $value || '' === $value ) {
						delete_post_meta( $post->ID, 'place_details_map_data' );
						return true;
					}
					$clean = self::sanitize_map_data( $value );
					if ( '' === $clean ) {
						return new \WP_Error( 'mapify_invalid_location', __( 'Location needs numeric latitude and longitude.', 'mapify' ), array( 'status' => 400 ) );
					}
					update_post_meta( $post->ID, 'place_details_map_data', $clean );
					return true;
				},
				'schema'          => array(
					'description' => __( 'Branch location on the map.', 'mapify' ),
					'type'        => array( 'object', 'null' ),
					'context'     => array( 'view', 'edit' ),
					'properties'  => array(
						'latitude'  => array( 'type' => 'number' ),
						'longitude' => array( 'type' => 'number' ),
						'zoom'      => array( 'type' => 'integer' ),
					),
				),
			)
		);
	}

	public static function field_sanitizer( $type ) {
		switch ( $type ) {
			case 'email':
				return 'sanitize_email';
			case 'url':
				return 'esc_url_raw';
			case 'textarea':
				return 'sanitize_textarea_field';
		}
		return 'sanitize_text_field';
	}

	public static function sanitize_color( $value ) {
		return (string) sanitize_hex_color( (string) $value );
	}

	public static function sanitize_template( $value ) {
		$value = sanitize_key( (string) $value );
		return in_array( $value, array( 'default', 'post', 'content' ), true ) ? $value : 'default';
	}

	/**
	 * Accepts the stored JSON string or an array with latitude/longitude (and optional zoom/gzoom).
	 */
	public static function sanitize_map_data( $value ) {
		$data = is_array( $value ) ? $value : json_decode( (string) $value, true );
		if ( ! is_array( $data ) || ! isset( $data['latitude'], $data['longitude'] ) || ! is_numeric( $data['latitude'] ) || ! is_numeric( $data['longitude'] ) ) {
			return '';
		}
		$zoom = isset( $data['gzoom'] ) ? $data['gzoom'] : ( isset( $data['zoom'] ) ? $data['zoom'] : 14 );
		return wp_json_encode(
			array(
				'latitude'  => (float) $data['latitude'],
				'longitude' => (float) $data['longitude'],
				'gzoom'     => (int) $zoom,
			)
		);
	}

	/* ------------------------------------------------------------------ free version limit */

	public static function init_limit() {
		add_filter( 'wp_insert_post_empty_content', array( __CLASS__, 'block_insert' ), 10, 2 );
		add_filter( 'rest_pre_insert_' . self::POST_TYPE, array( __CLASS__, 'block_rest_insert' ), 10, 2 );
	}

	/** Branches that count toward the limit (everything except auto-drafts and the trash). */
	public static function count_branches() {
		$query = new \WP_Query(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => array( 'publish', 'future', 'draft', 'pending', 'private' ),
				'fields'         => 'ids',
				'posts_per_page' => -1,
				'no_found_rows'  => true,
			)
		);
		return count( $query->posts );
	}

	public static function can_add() {
		return self::count_branches() < self::MAX_BRANCHES;
	}

	public static function limit_message() {
		return sprintf(
			/* translators: %d: maximum number of branches */
			__( 'The free version of Mapify supports up to %d branches. Upgrade to Mapify Pro for unlimited branches.', 'mapify' ),
			self::MAX_BRANCHES
		);
	}

	/**
	 * Stop new branches once the limit is reached: plain inserts (imports, duplicators) and
	 * auto-drafts that would become real branches.
	 */
	public static function block_insert( $maybe_empty, $postarr ) {
		if ( $maybe_empty || ! isset( $postarr['post_type'] ) || self::POST_TYPE !== $postarr['post_type'] ) {
			return $maybe_empty;
		}
		$status = isset( $postarr['post_status'] ) ? $postarr['post_status'] : 'draft';
		if ( in_array( $status, array( 'auto-draft', 'trash', 'inherit' ), true ) ) {
			return $maybe_empty;
		}
		$id = empty( $postarr['ID'] ) ? 0 : (int) $postarr['ID'];
		if ( $id && ! in_array( get_post_status( $id ), array( 'auto-draft', 'trash' ), true ) ) {
			return $maybe_empty;
		}
		return ! self::can_add();
	}

	public static function block_rest_insert( $prepared, $request ) {
		if ( empty( $request['id'] ) && ! self::can_add() ) {
			return new \WP_Error( 'mapify_branch_limit', self::limit_message(), array( 'status' => 403 ) );
		}
		return $prepared;
	}

	/**
	 * Location stored by the editor as JSON: {"latitude":..,"longitude":..,"gzoom":..}.
	 */
	public static function location( $post_id ) {
		$data = json_decode( (string) get_post_meta( $post_id, 'place_details_map_data', true ), true );
		if ( ! is_array( $data ) || ! isset( $data['latitude'], $data['longitude'] ) || ! is_numeric( $data['latitude'] ) || ! is_numeric( $data['longitude'] ) ) {
			return null;
		}
		return array(
			'latitude'  => (float) $data['latitude'],
			'longitude' => (float) $data['longitude'],
			'zoom'      => isset( $data['gzoom'] ) ? (int) $data['gzoom'] : 14,
		);
	}

	/**
	 * Query branches for normalized widget settings.
	 */
	public static function query( array $settings ) {
		if ( 'none' === $settings['branchtype'] ) {
			return array();
		}
		$args = array(
			'post_type'      => self::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => self::MAX_BRANCHES,
			'orderby'        => $settings['orderby'],
			'order'          => $settings['order'],
			'no_found_rows'  => true,
		);
		if ( 'cat' === $settings['branchtype'] ) {
			if ( empty( $settings['branchcat'] ) ) {
				return array();
			}
			$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => self::TAXONOMY,
					'field'    => 'slug',
					'terms'    => $settings['branchcat'],
				),
			);
		} elseif ( 'id' === $settings['branchtype'] ) {
			if ( empty( $settings['branchids'] ) ) {
				return array();
			}
			$args['post__in'] = array_map( 'absint', $settings['branchids'] );
		}
		if ( 'post__in' === $args['orderby'] && empty( $args['post__in'] ) ) {
			$args['orderby'] = 'title';
		}
		$posts = get_posts( apply_filters( 'mapify_branches_query_args', $args, $settings ) );
		$out   = array();
		foreach ( $posts as $post ) {
			$item = self::export( $post );
			if ( $item ) {
				$out[] = $item;
			}
		}
		return apply_filters( 'mapify_branches_data', $out, $settings );
	}

	/**
	 * Data sent to the front-end for one branch.
	 */
	public static function export( $post ) {
		$post = get_post( $post );
		if ( ! $post ) {
			return null;
		}
		$location   = self::location( $post->ID );
		$categories = array();
		$terms      = get_the_terms( $post->ID, self::TAXONOMY );
		if ( $terms && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$categories[ $term->term_id ] = $term->name;
			}
		}
		$image     = get_the_post_thumbnail_url( $post->ID, 'medium_large' );
		$own_img   = esc_url_raw( (string) get_post_meta( $post->ID, 'place_details_pinimg', true ) );
		$own_color = sanitize_hex_color( (string) get_post_meta( $post->ID, 'place_details_pincolor', true ) );
		$data      = array(
			'id'         => $post->ID,
			'title'      => get_the_title( $post ),
			'url'        => get_permalink( $post ),
			'image'      => $image ? $image : '',
			'img'        => $image ? $image : '',
			'categories' => $categories,
			'cats'       => array_map( 'strval', array_keys( $categories ) ),
			'latitude'   => $location ? $location['latitude'] : null,
			'longitude'  => $location ? $location['longitude'] : null,
			'zoom'       => $location ? $location['zoom'] : null,
			'pin_img'    => $own_img,
			'color'      => $own_color ? $own_color : '',
			'branch_pin' => $own_img,
		);
		$aliases = array(
			'socailtw' => 'twitter',
			'socailfb' => 'facebook',
			'socailig' => 'instagram',
			'socailtg' => 'telegram',
			'socailli' => 'linkedin',
		);
		foreach ( array_keys( self::meta_fields() ) as $key ) {
			$name          = isset( $aliases[ $key ] ) ? $aliases[ $key ] : $key;
			$data[ $name ] = (string) get_post_meta( $post->ID, 'place_details_' . $key, true );
		}
		return apply_filters( 'mapify_branch_export', $data, $post );
	}

	/**
	 * Replace the_content of a single branch with the branch card when enabled.
	 */
	public static function filter_content( $content ) {
		if ( ! is_singular( self::POST_TYPE ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		$choice = get_post_meta( get_the_ID(), 'place_details_content_template', true );
		if ( 'content' === $choice || ( ( '' === $choice || 'default' === $choice ) && 'content' === Options::get( 'branch_template' ) ) ) {
			return apply_filters( 'mapify-branches-single-post-template', self::single_template( get_the_ID() ) . $content, get_post(), get_the_ID() );
		}
		return $content;
	}

	public static function single_template( $post_id ) {
		$data = self::export( $post_id );
		if ( ! $data ) {
			return '';
		}
		wp_enqueue_style( 'dashicons' );
		wp_enqueue_style( 'mapify-front' );

		$row = function ( $icon, $label, $html ) {
			return sprintf(
				'<li class="mapify-branch__row"><span class="dashicons dashicons-%1$s" aria-hidden="true"></span><span class="mapify-branch__label">%2$s</span> <span class="mapify-branch__value">%3$s</span></li>',
				esc_attr( $icon ),
				esc_html( $label ),
				$html
			);
		};
		$rows = '';
		if ( $data['address'] ) {
			$rows .= $row( 'location', __( 'Address:', 'mapify' ), nl2br( esc_html( $data['address'] ) ) );
		}
		if ( $data['phone'] ) {
			$rows .= $row( 'phone', __( 'Phone:', 'mapify' ), '<a href="tel:' . esc_attr( preg_replace( '/[^0-9+]/', '', $data['phone'] ) ) . '">' . esc_html( $data['phone'] ) . '</a>' );
		}
		if ( $data['site'] ) {
			$rows .= $row( 'admin-site-alt3', __( 'Website:', 'mapify' ), '<a href="' . esc_url( $data['site'] ) . '">' . esc_html( $data['site'] ) . '</a>' );
		}
		if ( $data['email'] ) {
			$rows .= $row( 'email', __( 'Email:', 'mapify' ), '<a href="mailto:' . esc_attr( $data['email'] ) . '">' . esc_html( $data['email'] ) . '</a>' );
		}
		$social = '';
		foreach (
			array(
				'instagram' => array( 'instagram', __( 'Instagram', 'mapify' ) ),
				'telegram'  => array( 'format-chat', __( 'Telegram', 'mapify' ) ),
				'twitter'   => array( 'twitter', __( 'X (Twitter)', 'mapify' ) ),
				'facebook'  => array( 'facebook', __( 'Facebook', 'mapify' ) ),
				'linkedin'  => array( 'linkedin', __( 'LinkedIn', 'mapify' ) ),
			) as $key => $meta
		) {
			if ( $data[ $key ] ) {
				$social .= sprintf( '<a class="mapify-branch__social" href="%s" title="%s"><span class="dashicons dashicons-%s" aria-hidden="true"></span></a>', esc_url( $data[ $key ] ), esc_attr( $meta[1] ), esc_attr( $meta[0] ) );
			}
		}
		if ( $social ) {
			$rows .= $row( 'share', __( 'Social:', 'mapify' ), $social );
		}
		$directions = '';
		if ( null !== $data['latitude'] ) {
			$ll          = $data['latitude'] . ',' . $data['longitude'];
			$directions  = '<div class="mapify-branch__directions">';
			$directions .= '<a class="mapify-branch__btn" target="_blank" rel="noopener" href="' . esc_url( 'https://www.google.com/maps/dir/?api=1&destination=' . $ll ) . '">' . esc_html__( 'Google Maps', 'mapify' ) . '</a>';
			$directions .= '<a class="mapify-branch__btn" target="_blank" rel="noopener" href="' . esc_url( 'https://www.waze.com/ul?ll=' . $ll . '&navigate=yes' ) . '">' . esc_html__( 'Waze', 'mapify' ) . '</a>';
			$directions .= '<a class="mapify-branch__btn" target="_blank" rel="noopener" href="' . esc_url( 'https://neshan.org/maps/@' . $ll . ',16z' ) . '">' . esc_html__( 'Neshan', 'mapify' ) . '</a>';
			$directions .= '<a class="mapify-branch__btn" target="_blank" rel="noopener" href="' . esc_url( 'https://balad.ir/location?latitude=' . $data['latitude'] . '&longitude=' . $data['longitude'] ) . '">' . esc_html__( 'Balad', 'mapify' ) . '</a>';
			$directions .= '</div>';
		}
		$image = '';
		if ( has_post_thumbnail( $post_id ) ) {
			$image = '<a class="mapify-branch__image" href="' . esc_url( get_the_post_thumbnail_url( $post_id, 'full' ) ) . '">' . get_the_post_thumbnail( $post_id, 'medium_large' ) . '</a>';
		}
		$html = '<div class="mapify-branch">' . $image . '<div class="mapify-branch__details"><ul class="mapify-branch__rows">' . $rows . '</ul>' . $directions . '</div>'
			. ( $data['additional'] ? '<div class="mapify-branch__extra">' . wpautop( esc_html( $data['additional'] ) ) . '</div>' : '' )
			. '</div>';
		return apply_filters( 'pepro-mapify-branchestemplate_return', $html, $data );
	}
}
