<?php
/**
 * Branch edit screen: location picker and details meta box.
 *
 * @package Mapify
 */

namespace Mapify;

defined( 'ABSPATH' ) || exit;

class Branch_Editor {

	public static function init() {
		add_action( 'add_meta_boxes_' . Branches::POST_TYPE, array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post_' . Branches::POST_TYPE, array( __CLASS__, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_filter( 'manage_' . Branches::POST_TYPE . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . Branches::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'column' ), 10, 2 );

		// Category pin settings.
		add_action( Branches::TAXONOMY . '_add_form_fields', array( __CLASS__, 'term_add_fields' ) );
		add_action( Branches::TAXONOMY . '_edit_form_fields', array( __CLASS__, 'term_edit_fields' ) );
		add_action( 'created_' . Branches::TAXONOMY, array( __CLASS__, 'term_save' ) );
		add_action( 'edited_' . Branches::TAXONOMY, array( __CLASS__, 'term_save' ) );
		add_filter( 'manage_edit-' . Branches::TAXONOMY . '_columns', array( __CLASS__, 'term_columns' ) );
		add_filter( 'manage_' . Branches::TAXONOMY . '_custom_column', array( __CLASS__, 'term_column' ), 10, 3 );
	}

	public static function assets( $hook ) {
		$screen = get_current_screen();
		if ( $screen && Branches::TAXONOMY === $screen->taxonomy && in_array( $hook, array( 'edit-tags.php', 'term.php' ), true ) ) {
			wp_enqueue_media();
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_style( 'mapify-branch-editor', MAPIFY_ASSETS . 'css/branch-editor.css', array(), MAPIFY_VERSION );
			wp_enqueue_script( 'mapify-branch-editor', MAPIFY_ASSETS . 'js/branch-editor.js', array( 'wp-color-picker', 'jquery' ), MAPIFY_VERSION, true );
			wp_localize_script( 'mapify-branch-editor', 'MapifyBranchEditor', array( 'i18n' => self::i18n() ) );
			return;
		}
		if ( ! $screen || Branches::POST_TYPE !== $screen->post_type || ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style( 'mapify-leaflet', MAPIFY_ASSETS . 'vendor/leaflet/leaflet.css', array(), '1.9.4' );
		wp_enqueue_script( 'mapify-leaflet', MAPIFY_ASSETS . 'vendor/leaflet/leaflet.js', array(), '1.9.4', true );
		wp_enqueue_style( 'mapify-branch-editor', MAPIFY_ASSETS . 'css/branch-editor.css', array(), MAPIFY_VERSION );
		wp_enqueue_script( 'mapify-branch-editor', MAPIFY_ASSETS . 'js/branch-editor.js', array( 'mapify-leaflet', 'wp-color-picker', 'jquery' ), MAPIFY_VERSION, true );
		$center = array_map( 'floatval', array_pad( explode( ',', Options::get( 'default_center' ) ), 2, 0 ) );
		wp_localize_script(
			'mapify-branch-editor',
			'MapifyBranchEditor',
			array(
				'center'      => $center,
				'zoom'        => (int) Options::get( 'default_zoom' ),
				'geocoder'    => Options::get( 'geocoder' ),
				'locale'      => determine_locale(),
				'i18n'        => self::i18n(),
			)
		);
	}

	protected static function i18n() {
		return array(
			'search'      => __( 'Search an address or place…', 'mapify' ),
			'noResult'    => __( 'Nothing found.', 'mapify' ),
			'chooseImage' => __( 'Choose pin image', 'mapify' ),
			'useImage'    => __( 'Use this image', 'mapify' ),
		);
	}

	/* ------------------------------------------------------------------ categories */

	protected static function term_inputs( $color, $image ) {
		ob_start();
		?>
		<input type="text" id="mapify-term-color" name="mapify_pin_color" class="mapify-color" value="<?php echo esc_attr( $color ); ?>" />
		<?php
		$color_html = ob_get_clean();
		ob_start();
		?>
		<div class="mapify-pinbox__image">
			<img src="<?php echo esc_url( $image ); ?>" alt="" <?php echo $image ? '' : 'hidden'; ?> />
			<input type="url" dir="ltr" id="mapify-term-image" name="mapify_pin_image" class="regular-text" value="<?php echo esc_attr( $image ); ?>" placeholder="https://" />
			<button type="button" class="button mapify-pinbox__choose"><?php esc_html_e( 'Choose image', 'mapify' ); ?></button>
		</div>
		<?php
		return array( $color_html, ob_get_clean() );
	}

	public static function term_add_fields() {
		wp_nonce_field( 'mapify_term_pin', 'mapify_term_nonce' );
		list( $color, $image ) = self::term_inputs( '', '' );
		?>
		<div class="form-field mapify-term-field">
			<label for="mapify-term-color"><?php esc_html_e( 'Pin color', 'mapify' ); ?></label>
			<?php echo $color; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>
		</div>
		<div class="form-field mapify-term-field">
			<label for="mapify-term-image"><?php esc_html_e( 'Pin image', 'mapify' ); ?></label>
			<?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>
			<p><?php esc_html_e( 'Branches in this category use this pin on the map, unless the branch has its own pin color or image.', 'mapify' ); ?></p>
		</div>
		<?php
	}

	public static function term_edit_fields( $term ) {
		$pin = Branches::category_pin( $term->term_id );
		wp_nonce_field( 'mapify_term_pin', 'mapify_term_nonce' );
		list( $color, $image ) = self::term_inputs( $pin['color'], $pin['image'] );
		?>
		<tr class="form-field mapify-term-field">
			<th scope="row"><label for="mapify-term-color"><?php esc_html_e( 'Pin color', 'mapify' ); ?></label></th>
			<td><?php echo $color; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?></td>
		</tr>
		<tr class="form-field mapify-term-field">
			<th scope="row"><label for="mapify-term-image"><?php esc_html_e( 'Pin image', 'mapify' ); ?></label></th>
			<td>
				<?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>
				<p class="description"><?php esc_html_e( 'Branches in this category use this pin on the map, unless the branch has its own pin color or image.', 'mapify' ); ?></p>
			</td>
		</tr>
		<?php
	}

	public static function term_save( $term_id ) {
		if ( ! isset( $_POST['mapify_term_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['mapify_term_nonce'] ) ), 'mapify_term_pin' ) || ! current_user_can( 'manage_categories' ) ) {
			return;
		}
		foreach ( Branches::term_meta_fields() as $key => $field ) {
			if ( ! isset( $_POST[ $key ] ) ) {
				continue;
			}
			$value = call_user_func( $field[1], wp_unslash( $_POST[ $key ] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized by the field callback.
			if ( '' === $value ) {
				delete_term_meta( $term_id, $key );
			} else {
				update_term_meta( $term_id, $key, $value );
			}
		}
	}

	public static function term_columns( $columns ) {
		$out = array();
		foreach ( $columns as $key => $label ) {
			if ( 'name' === $key ) {
				$out['mapify_pin'] = '<span class="screen-reader-text">' . esc_html__( 'Pin', 'mapify' ) . '</span>';
			}
			$out[ $key ] = $label;
		}
		return $out;
	}

	public static function term_column( $content, $column, $term_id ) {
		if ( 'mapify_pin' !== $column ) {
			return $content;
		}
		$pin = Branches::category_pin( $term_id );
		if ( $pin['image'] ) {
			return '<img class="mapify-term-pin" src="' . esc_url( $pin['image'] ) . '" alt="" />';
		}
		if ( $pin['color'] ) {
			return '<span class="mapify-term-pin mapify-term-pin--color" style="--c:' . esc_attr( $pin['color'] ) . '" title="' . esc_attr( $pin['color'] ) . '"></span>';
		}
		return '<span aria-hidden="true">—</span>';
	}

	public static function add_meta_boxes() {
		// Meta is edited in the boxes below; 'custom-fields' support is only there for the REST API.
		remove_meta_box( 'postcustom', null, 'normal' );
		add_meta_box( 'mapify-location', __( 'Location', 'mapify' ), array( __CLASS__, 'location_box' ), null, 'normal', 'high' );
		add_meta_box( 'place-details', __( 'Branch details', 'mapify' ), array( __CLASS__, 'details_box' ), null, 'normal', 'high' );
		add_meta_box( 'mapify-pin', __( 'Map pin', 'mapify' ), array( __CLASS__, 'pin_box' ), null, 'side', 'default' );
	}

	public static function location_box( $post ) {
		wp_nonce_field( 'place_details_data', 'place_details_nonce' );
		$location = Branches::location( $post->ID );
		$raw      = (string) get_post_meta( $post->ID, 'place_details_map_data', true );
		?>
		<div class="mapify-loc">
			<?php if ( 'none' !== Options::get( 'geocoder' ) ) : ?>
			<div class="mapify-loc__search">
				<input type="search" class="mapify-loc__query" placeholder="<?php esc_attr_e( 'Search an address or place…', 'mapify' ); ?>" />
				<button type="button" class="button mapify-loc__find"><?php esc_html_e( 'Find', 'mapify' ); ?></button>
				<ul class="mapify-loc__results" hidden></ul>
			</div>
			<?php endif; ?>
			<div class="mapify-loc__map" id="mapify-loc-map"></div>
			<div class="mapify-loc__fields">
				<label><span><?php esc_html_e( 'Latitude', 'mapify' ); ?></span>
					<input type="text" inputmode="decimal" dir="ltr" id="mapify-lat" value="<?php echo esc_attr( $location ? $location['latitude'] : '' ); ?>" /></label>
				<label><span><?php esc_html_e( 'Longitude', 'mapify' ); ?></span>
					<input type="text" inputmode="decimal" dir="ltr" id="mapify-lng" value="<?php echo esc_attr( $location ? $location['longitude'] : '' ); ?>" /></label>
				<button type="button" class="button mapify-loc__locate"><?php esc_html_e( 'Use my location', 'mapify' ); ?></button>
				<button type="button" class="button-link mapify-loc__clear"><?php esc_html_e( 'Clear', 'mapify' ); ?></button>
			</div>
			<p class="description"><?php esc_html_e( 'Click the map or drag the pin to set the branch location.', 'mapify' ); ?></p>
			<input type="hidden" name="map_data" id="map_data" value="<?php echo esc_attr( $raw ); ?>" />
		</div>
		<?php
	}

	public static function details_box( $post ) {
		echo '<div class="mapify-fields">';
		foreach ( Branches::meta_fields() as $key => $field ) {
			$value = (string) get_post_meta( $post->ID, 'place_details_' . $key, true );
			$id    = 'mapify-field-' . $key;
			$wide  = 'textarea' === $field[1] ? ' mapify-fields__row--wide' : '';
			echo '<p class="mapify-fields__row' . esc_attr( $wide ) . '"><label for="' . esc_attr( $id ) . '">' . esc_html( $field[0] ) . '</label>';
			if ( 'textarea' === $field[1] ) {
				echo '<textarea class="large-text" rows="3" id="' . esc_attr( $id ) . '" name="' . esc_attr( $key ) . '">' . esc_textarea( $value ) . '</textarea>';
			} else {
				$dir = in_array( $field[1], array( 'url', 'email' ), true ) || 'phone' === $key ? ' dir="ltr"' : '';
				echo '<input class="widefat" type="' . esc_attr( 'phone' === $key ? 'tel' : $field[1] ) . '"' . $dir . ' id="' . esc_attr( $id ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" />'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			echo '</p>';
		}
		echo '</div>';
	}

	public static function pin_box( $post ) {
		$img      = (string) get_post_meta( $post->ID, 'place_details_pinimg', true );
		$color    = (string) get_post_meta( $post->ID, 'place_details_pincolor', true );
		$template = (string) get_post_meta( $post->ID, 'place_details_content_template', true );
		?>
		<div class="mapify-pinbox">
			<p><label for="mapify-pincolor"><?php esc_html_e( 'Pin color', 'mapify' ); ?></label><br />
				<input type="text" id="mapify-pincolor" name="pincolor" class="mapify-color" value="<?php echo esc_attr( $color ); ?>" /></p>
			<p><label for="mapify-pinimg"><?php esc_html_e( 'Custom pin image', 'mapify' ); ?></label></p>
			<div class="mapify-pinbox__image">
				<img src="<?php echo esc_url( $img ); ?>" alt="" <?php echo $img ? '' : 'hidden'; ?> />
				<input type="url" dir="ltr" id="mapify-pinimg" name="pinimg" class="widefat" value="<?php echo esc_attr( $img ); ?>" placeholder="https://" />
				<button type="button" class="button mapify-pinbox__choose"><?php esc_html_e( 'Choose image', 'mapify' ); ?></button>
			</div>
			<p><label for="mapify-template"><?php esc_html_e( 'Branch page layout', 'mapify' ); ?></label>
				<select id="mapify-template" name="content_template" class="widefat">
					<option value="default" <?php selected( in_array( $template, array( '', 'default' ), true ) ); ?>><?php esc_html_e( 'Inherit from settings', 'mapify' ); ?></option>
					<option value="content" <?php selected( $template, 'content' ); ?>><?php esc_html_e( 'Branch card + content', 'mapify' ); ?></option>
					<option value="post" <?php selected( $template, 'post' ); ?>><?php esc_html_e( 'Content only', 'mapify' ); ?></option>
				</select></p>
		</div>
		<?php
	}

	public static function save( $post_id ) {
		if ( ! isset( $_POST['place_details_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['place_details_nonce'] ) ), 'place_details_data' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		foreach ( Branches::meta_fields() as $key => $field ) {
			if ( ! isset( $_POST[ $key ] ) ) {
				continue;
			}
			$raw = wp_unslash( $_POST[ $key ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			switch ( $field[1] ) {
				case 'email':
					$value = sanitize_email( $raw );
					break;
				case 'url':
					$value = esc_url_raw( $raw );
					break;
				case 'textarea':
					$value = sanitize_textarea_field( $raw );
					break;
				default:
					$value = sanitize_text_field( $raw );
			}
			update_post_meta( $post_id, 'place_details_' . $key, $value );
		}
		if ( isset( $_POST['pinimg'] ) ) {
			update_post_meta( $post_id, 'place_details_pinimg', esc_url_raw( wp_unslash( $_POST['pinimg'] ) ) );
		}
		if ( isset( $_POST['pincolor'] ) ) {
			update_post_meta( $post_id, 'place_details_pincolor', (string) sanitize_hex_color( wp_unslash( $_POST['pincolor'] ) ) );
		}
		if ( isset( $_POST['content_template'] ) ) {
			$t = sanitize_key( $_POST['content_template'] );
			update_post_meta( $post_id, 'place_details_content_template', in_array( $t, array( 'default', 'post', 'content' ), true ) ? $t : 'default' );
		}
		if ( isset( $_POST['map_data'] ) ) {
			$data = json_decode( wp_unslash( $_POST['map_data'] ), true ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( is_array( $data ) && isset( $data['latitude'], $data['longitude'] ) && is_numeric( $data['latitude'] ) && is_numeric( $data['longitude'] ) ) {
				update_post_meta(
					$post_id,
					'place_details_map_data',
					wp_json_encode(
						array(
							'latitude'  => (float) $data['latitude'],
							'longitude' => (float) $data['longitude'],
							'gzoom'     => isset( $data['gzoom'] ) ? (int) $data['gzoom'] : 14,
						)
					)
				);
			} else {
				delete_post_meta( $post_id, 'place_details_map_data' );
			}
		}
	}

	public static function columns( $columns ) {
		$out = array();
		foreach ( $columns as $key => $label ) {
			$out[ $key ] = $label;
			if ( 'title' === $key ) {
				$out['mapify_location'] = __( 'Location', 'mapify' );
				$out['mapify_phone']    = __( 'Phone', 'mapify' );
			}
		}
		return $out;
	}

	public static function column( $column, $post_id ) {
		if ( 'mapify_location' === $column ) {
			$loc = Branches::location( $post_id );
			echo $loc ? '<code dir="ltr">' . esc_html( round( $loc['latitude'], 5 ) . ', ' . round( $loc['longitude'], 5 ) ) . '</code>' : '<span aria-hidden="true">—</span>';
		} elseif ( 'mapify_phone' === $column ) {
			echo esc_html( (string) get_post_meta( $post_id, 'place_details_phone', true ) );
		}
	}
}
