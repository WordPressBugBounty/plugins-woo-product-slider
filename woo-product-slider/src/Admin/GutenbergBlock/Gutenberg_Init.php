<?php
/**
 * The plugin gutenberg block Initializer.
 *
 * @link       https://shapedplugin.com/
 * @since      2.5.4
 *
 * @package    woo-product-slider-free
 * @subpackage woo-product-slider-free/Admin
 * @author     ShapedPlugin <support@shapedplugin.com>
 */

namespace ShapedPlugin\WooProductSlider\Admin\GutenbergBlock;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gutenberg_Init class.
 */
class Gutenberg_Init {
	/**
	 * Custom Gutenberg Block Initializer.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'woo_product_slider_free_gutenberg_shortcode_block' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'woo_product_slider_free_block_editor_assets' ) );
		add_action( 'enqueue_block_assets', array( $this, 'woo_product_slider_free_block_canvas_assets' ) );
	}

	/**
	 * Register the block script for the outer block editor document.
	 */
	public function woo_product_slider_free_block_editor_assets() {
		$asset_file = plugin_dir_path( __FILE__ ) . 'build/index.asset.php';
		$asset      = file_exists( $asset_file ) ? require $asset_file : array(
			'dependencies' => array( 'wp-block-editor', 'wp-blocks', 'wp-components', 'wp-element', 'wp-escape-html', 'wp-i18n', 'wp-server-side-render' ),
			'version'      => SP_WPS_VERSION,
		);

		wp_enqueue_script(
			'woo-product-slider-free-shortcode-block',
			plugins_url( '/GutenbergBlock/build/index.js', __DIR__ ),
			array_merge( $asset['dependencies'], array( 'jquery' ) ),
			$asset['version'],
			true
		);

		wp_localize_script(
			'woo-product-slider-free-shortcode-block',
			'sp_wps_load_script',
			array(
				'path'          => SP_WPS_URL,
				'loadScript'    => SP_WPS_URL . 'Frontend/assets/js/scripts.min.js',
				'url'           => admin_url( 'post-new.php?post_type=sp_wps_shortcodes' ),
				'shortCodeList' => $this->woo_product_slider_free_post_list(),
			)
		);
	}

	/**
	 * Enqueue the slider runtime assets inside the block editor canvas.
	 *
	 * Since WordPress 7.1 the post editor canvas is always an iframe and that
	 * iframe document is built only from the assets collected on
	 * `enqueue_block_assets`. Styles and scripts hooked to
	 * `enqueue_block_editor_assets` only reach the outer admin document, so the
	 * server side rendered slider preview stayed unstyled and uninitialized.
	 */
	public function woo_product_slider_free_block_canvas_assets() {
		if ( ! is_admin() ) {
			return;
		}

		wp_enqueue_style( 'sp-wps-swiper' );
		wp_enqueue_style( 'sp-wps-font-awesome' );
		wp_enqueue_style( 'sp_wps-fontello-icon' );
		wp_enqueue_style( 'sp-wps-style' );

		wp_enqueue_script( 'sp-wps-swiper-js' );
		wp_enqueue_script( 'sp-wps-scripts' );

		// The editor chrome styles live in the admin stylesheet, which only loads
		// on the outer document, so the canvas needs its own copy.
		wp_add_inline_style(
			'sp-wps-style',
			'.sp-wpsp-gutenberg-shortcode{padding-bottom:0}div.sp-wpsp-gutenberg-shortcode:after{display:none}.sp-wpsp-gutenberg-shortcode select{padding: 8px 12px;line-height: 1.5;}a.sp_woo_product_slider_block_edit_button{font-size:16px;margin:10px 0;display:inline-block}.sp-wpsp-block-preview .wps-pagination{pointer-events:none}.sp-wpsp-block-preview .spswp_variations_form.variations_form{display:none}'
		);
	}

	/**
	 * Shortcode list.
	 *
	 * @return array
	 */
	public function woo_product_slider_free_post_list() {
		$shortcodes = get_posts(
			array(
				'post_type'      => 'sp_wps_shortcodes',
				'post_status'    => 'publish',
				'posts_per_page' => 9999,
			)
		);

		if ( count( $shortcodes ) < 1 ) {
			return array();
		}

		return array_map(
			function ( $shortcode ) {
					return (object) array(
						'id'    => absint( $shortcode->ID ),
						'title' => esc_html( $shortcode->post_title ),
					);
			},
			$shortcodes
		);
	}

	/**
	 * Register Gutenberg shortcode block.
	 */
	public function woo_product_slider_free_gutenberg_shortcode_block() {
		/**
		 * Register Gutenberg block on server-side.
		 */
		register_block_type(
			'woo-product-slider-pro/shortcode',
			array(
				'api_version'     => 3,
				'attributes'      => array(
					'shortcode'          => array(
						'type'    => 'string',
						'default' => '',
					),
					'showInputShortcode' => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'preview'            => array(
						'type'    => 'boolean',
						'default' => false,
					),
					'is_admin'           => array(
						'type'    => 'boolean',
						'default' => is_admin(),
					),
				),
				'example'         => array(
					'attributes' => array(
						'preview' => true,
					),
				),
				'render_callback' => array( $this, 'woo_product_slider_free_render_shortcode' ),
			)
		);
	}

	/**
	 * Render callback.
	 *
	 * @param string $attributes ShortCode.
	 * @return string
	 */
	public function woo_product_slider_free_render_shortcode( $attributes ) {
		$class_name = '';
		if ( ! empty( $attributes['className'] ) ) {
			$class_name = $attributes['className'];
		}
		if ( empty( $attributes['shortcode'] ) || ! get_post_status( $attributes['shortcode'] ) ) {
			return ' ';
		}
		if ( ! $attributes['is_admin'] ) {
			return '<div class="' . esc_attr( $class_name ) . '">' . do_shortcode( '[reno_product_slider id="' . sanitize_text_field( $attributes['shortcode'] ) . '"]' ) . '</div>';
		}

		$edit_page_link = get_edit_post_link( sanitize_text_field( $attributes['shortcode'] ) );

		return ' <div id="' . uniqid() . '"> <a href="' . $edit_page_link . '" target="_blank" class="sp_woo_product_slider_block_edit_button">Edit View</a>' . do_shortcode( '[reno_product_slider id="' . sanitize_text_field( $attributes['shortcode'] ) . '"]' ) . '</div>';
	}
}
