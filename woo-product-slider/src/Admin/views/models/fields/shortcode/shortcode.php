<?php
/**
 * The framework shortcode fields file.
 *
 * @package Woo_Product_Slider.
 * @subpackage Woo_Product_Slider/models.
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
} // Cannot access directly.

if ( ! class_exists( 'SPF_WPSP_Fields_shortcode' ) ) {
	/**
	 * SP_PC_Field_shortcode
	 */
	class SPF_WPSP_Field_shortcode extends SPF_WPSP_Fields {
		/**
		 * Field constructor.
		 *
		 * @param array  $field The field type.
		 * @param string $value The values of the field.
		 * @param string $unique The unique ID for the field.
		 * @param string $where To where show the output CSS.
		 * @param string $parent The parent args.
		 */
		public function __construct( $field, $value = '', $unique = '', $where = '', $parent = '' ) {
			parent::__construct( $field, $value, $unique, $where, $parent );
		}
		/**
		 * Render method.
		 *
		 * @return void
		 */
		public function render() {
			// Get the Post ID.
			$post_id = get_the_ID();
			if ( ! empty( $this->field['shortcode'] ) && 'shortcode' === $this->field['shortcode'] ) {
				echo ( ! empty( $post_id ) ) ? '<div class="spwps-scode-wrap-side"><p>To display your product slider, add the following shortcode into your post, custom post types, page, widget or block editor. If adding the slider to your theme files, additionally include the surrounding PHP code, <a href="https://docs.shapedplugin.com/docs/woocommerce-product-slider-pro/faq/how-to-use-product-slider-shortcode-to-your-theme-files-or-php-templates/" target="_blank">see how</a>.‎</p><span class="spwps-shortcode-selectable">[woo_product_slider id="' . esc_attr( $post_id ) . '"]</span></div><div class="wpspro-after-copy-text"><i class="fa fa-check-circle"></i> Shortcode Copied to Clipboard! </div>' : '';
			} elseif ( ! empty( $this->field['shortcode'] ) && 'pro_notice' === $this->field['shortcode'] ) {
				if ( ! empty( $post_id ) ) {
					echo '<div class="sp_wpsp_shortcode-area sp_wpsp-notice-wrapper">';
					echo '<div class="sp_wpsp-notice-heading">' . sprintf(
						/* translators: 1: start span tag, 2: close tag. */
						esc_html__( 'Unlock Your Growth with %1$sPRO%2$s', 'woo-product-slider' ),
						'<span>',
						'</span>'
					) . '</div>';

					echo '<p class="sp_wpsp-notice-desc">' . sprintf(
						/* translators: 1: start bold tag, 2: close tag. */
						esc_html__( 'Showcase Your Products Like a Pro: Boost Sales with Stunning Displays!', 'woo-product-slider' ),
						'<b>',
						'</b>'
					) . '</p>';

					echo '<ul>';
					echo '<li><i class="sp-wps-icon-check-icon"></i> ' . esc_html__( '9+ Responsive Layouts', 'woo-product-slider' ) . '</li>';
					echo '<li><i class="sp-wps-icon-check-icon"></i> ' . esc_html__( '30+ Pre-made Templates', 'woo-product-slider' ) . '</li>';
					echo '<li><i class="sp-wps-icon-check-icon"></i> ' . esc_html__( 'Advanced Query Builder', 'woo-product-slider' ) . '</li>';
					echo '<li><i class="sp-wps-icon-check-icon"></i> ' . esc_html__( 'Highlight Specific Products', 'woo-product-slider' ) . '</li>';
					echo '<li><i class="sp-wps-icon-check-icon"></i> ' . esc_html__( 'Show Best Sellers Products', 'woo-product-slider' ) . '</li>';
					echo '<li><i class="sp-wps-icon-check-icon"></i> ' . esc_html__( 'Promote Related Products', 'woo-product-slider' ) . '</li>';
					echo '<li><i class="sp-wps-icon-check-icon"></i> ' . esc_html__( 'Display Upsells & Cross-sells', 'woo-product-slider' ) . '</li>';
					echo '<li><i class="sp-wps-icon-check-icon"></i> ' . esc_html__( 'Live Product Filters & Search', 'woo-product-slider' ) . '</li>';
					echo '<li><i class="sp-wps-icon-check-icon"></i> ' . esc_html__( 'Product Badges & Rating Star', 'woo-product-slider' ) . '</li>';
					echo '<li><i class="sp-wps-icon-check-icon"></i> ' . esc_html__( '160+ Customizations and More', 'woo-product-slider' ) . '</li>';
					echo '</ul>';

					echo '<div class="sp_wpsp-notice-button">';
					echo '<a class="sp_wpsp-open-live-demo" href="https://wooproductslider.io/pricing/?ref=1" target="_blank">';
					echo esc_html__( 'Upgrade to Pro Now', 'woo-product-slider' ) . ' <i class="sp-wps-icon-shuttle_2285485-1"></i>';
					echo '</a>';
					echo '</div>';
					echo '</div>';
				}
			} else {
				echo ( ! empty( $post_id ) ) ? '<div class="spwps-scode-wrap-side"><p>Woo Product Slider has seamless integration with Gutenberg, Classic Editor, <strong>Elementor, Divi,</strong> Bricks, Beaver, Oxygen, WPBakery Builder, etc.</p></div>' : '';
			}
		}
	}
}
