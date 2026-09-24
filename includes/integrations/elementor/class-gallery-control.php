<?php
/**
 * Elementor control: thumbnail gallery picker (map styles and tile layers).
 *
 * options: [ value => [ title, image ] ]. An option without an image shows a blank tile with its name.
 *
 * @package Mapify
 */

namespace Mapify\Integrations\Elementor;

use Elementor\Base_Data_Control;

defined( 'ABSPATH' ) || exit;

class Gallery_Control extends Base_Data_Control {

	const TYPE = 'mapify_gallery';

	public function get_type() {
		return self::TYPE;
	}

	public function enqueue() {
		wp_enqueue_style( 'mapify-elementor-gallery', MAPIFY_ASSETS . 'css/elementor-gallery.css', array(), MAPIFY_VERSION );
		wp_enqueue_script( 'mapify-elementor-gallery', MAPIFY_ASSETS . 'js/elementor-gallery.js', array( 'jquery' ), MAPIFY_VERSION, true );
	}

	protected function get_default_settings() {
		return array(
			'label_block' => true,
			'options'     => array(),
			'search'      => false,
			'note'        => '',
		);
	}

	public function content_template() {
		?>
		<div class="elementor-control-field mapify-gallery-control">
			<# if ( data.label ) { #>
			<label class="elementor-control-title">{{{ data.label }}}</label>
			<# } #>
			<# if ( data.search ) { #>
			<input type="search" class="mapify-gallery__search" placeholder="<?php echo esc_attr__( 'Search styles…', 'mapify' ); ?>" aria-label="<?php echo esc_attr__( 'Search styles…', 'mapify' ); ?>" />
			<# } #>
			<div class="mapify-gallery" role="listbox">
				<# _.each( data.options, function( option, value ) { #>
				<button type="button" class="mapify-gallery__item" role="option" data-value="{{ value }}" data-search="{{ ( option.title + ' ' + value ).toLowerCase() }}" title="{{ option.title }}">
					<# if ( option.image ) { #>
					<img class="mapify-gallery__thumb" src="{{ option.image }}" alt="" loading="lazy" />
					<# } else { #>
					<span class="mapify-gallery__thumb mapify-gallery__thumb--blank"><i class="eicon-map-pin" aria-hidden="true"></i></span>
					<# } #>
					<span class="mapify-gallery__label">{{{ option.title }}}</span>
				</button>
				<# } ); #>
			</div>
			<p class="mapify-gallery__empty" hidden><?php esc_html_e( 'No style found.', 'mapify' ); ?></p>
			<# if ( data.note ) { #>
			<p class="mapify-gallery__note">{{ data.note }}</p>
			<# } #>
			<input type="hidden" data-setting="{{ data.name }}" />
		</div>
		<# if ( data.description ) { #>
		<div class="elementor-control-field-description">{{{ data.description }}}</div>
		<# } #>
		<?php
	}
}
