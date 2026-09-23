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
				'supports'        => array( 'title', 'editor', 'thumbnail', 'revisions', 'page-attributes', 'elementor' ),
				'hierarchical'    => false,
				'public'          => true,
				'show_ui'         => true,
				'show_in_menu'    => true,
				'show_in_rest'    => true,
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

		foreach ( array_keys( self::meta_fields() ) as $key ) {
			register_post_meta(
				self::POST_TYPE,
				'place_details_' . $key,
				array(
					'type'          => 'string',
					'single'        => true,
					'show_in_rest'  => true,
					'auth_callback' => function () {
						return current_user_can( 'edit_posts' );
					},
				)
			);
		}
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
			'posts_per_page' => -1,
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
		$image = get_the_post_thumbnail_url( $post->ID, 'medium_large' );
		$data  = array(
			'id'         => $post->ID,
			'title'      => get_the_title( $post ),
			'url'        => get_permalink( $post ),
			'image'      => $image ? $image : '',
			'img'        => $image ? $image : '',
			'categories' => $categories,
			'latitude'   => $location ? $location['latitude'] : null,
			'longitude'  => $location ? $location['longitude'] : null,
			'zoom'       => $location ? $location['zoom'] : null,
			'pin_img'    => esc_url_raw( (string) get_post_meta( $post->ID, 'place_details_pinimg', true ) ),
			'color'      => sanitize_hex_color( (string) get_post_meta( $post->ID, 'place_details_pincolor', true ) ),
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
