<?php
/**
 * Promotion notice class
 *
 * For displaying limited time promotion in admin panel
 *
 * @since      2.2.0
 * @package    Woo_Product_Slider
 * @subpackage Woo_Product_Slider/Admin/Notices
 * @author     ShapedPlugin <support@shapedplugin.com>
 */

namespace ShapedPlugin\WooProductSlider\Admin\Notices;

if ( ! defined( 'ABSPATH' ) ) {
	die; // Cannot access directly.
}

/**
 * Promotion notice class
 * For displaying limited time promotion in admin panel
 */
class Dashboard_Notice {

	/**
	 * Option key for limited time promo
	 *
	 * @var string
	 */
	public $promo_option_key = '_woo_product_slider_limited_time_promo';

	/**
	 * Class constructor
	 */
	public function __construct() {
		add_action( 'admin_notices', array( $this, 'display_admin_notice' ) );
		add_action( 'wp_ajax_sp-wps-never-show-review-notice', array( $this, 'dismiss_review_notice' ) );
		add_filter( 'admin_footer_text', array( $this, 'admin_footer' ), 1, 2 );
		add_filter( 'update_footer', array( $this, 'admin_footer_version' ), 11 );
	}

	/**
	 * Gets current time and converts to EST timezone.
	 *
	 * @return string
	 */
	private function get_current_time_est() {
		$dt = new \DateTime( 'now', new \DateTimeZone( 'UTC' ) );
		$dt->setTimezone( new \DateTimeZone( 'EST' ) );

		return $dt->format( 'Y-m-d H:i:s T' );
	}

	/**
	 * Display admin notice.
	 *
	 * @return void
	 */
	public function display_admin_notice() {
		// Show only to Admins.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Variable default value.
		$review = get_option( 'sp_woo_product_slider_review_notice_dismiss' );
		$time   = time();
		$load   = false;

		if ( ! $review ) {
			$review = array(
				'time'      => $time,
				'dismissed' => false,
			);
			add_option( 'sp_woo_product_slider_review_notice_dismiss', $review );
		} elseif ( ( isset( $review['dismissed'] ) && ! $review['dismissed'] ) && ( isset( $review['time'] ) && ( ( $review['time'] + ( DAY_IN_SECONDS * 3 ) ) <= $time ) ) ) {
			$load = true;
		}

		// If we cannot load, return early.
		if ( ! $load ) {
			return;
		}
		?>
		<div id="sp-wps-review-notice" class="sp-wps-review-notice">
			<div class="sp-wps-plugin-icon">
				<img src="<?php echo esc_url( SP_WPS_URL . 'Admin/assets/images/product-review-notice.svg' ); ?>" alt="Product Slider for Woocommerce">
			</div>
			<div class="sp-wps-notice-text">
				<h3>Enjoying <strong>Product Slider for Woocommerce</strong>?</h3>
				<p>We hope you had a wonderful experience using <strong>Woo Product Slider</strong>. Please take a moment to leave a review on <a href="https://wordpress.org/support/plugin/woo-product-slider/reviews/" target="_blank"><strong>WordPress.org</strong></a>. Your positive review will help us improve. Thanks! 😊</p>

				<p class="sp-wps-review-actions">
					<a href="https://wordpress.org/support/plugin/woo-product-slider/reviews/" target="_blank" class="button button-primary notice-dismissed rate-woo-product-slider">Ok, you deserve ★★★★★</a>
					<a href="#" class="notice-dismissed remind-me-later"><span class="dashicons dashicons-clock"></span>Nope, maybe later
					</a>
					<a href="#" class="notice-dismissed never-show-again"><span class="dashicons dashicons-dismiss"></span>Never show again</a>
				</p>
			</div>
		</div>

		<script type='text/javascript'>

			jQuery(document).ready( function($) {
				$(document).on('click', '#sp-wps-review-notice.sp-wps-review-notice .notice-dismissed', function( event ) {
					if ( $(this).hasClass('rate-woo-product-slider') ) {
						var notice_dismissed_value = "1";
					}
					if ( $(this).hasClass('remind-me-later') ) {
						var notice_dismissed_value =  "2";
						event.preventDefault();
					}
					if ( $(this).hasClass('never-show-again') ) {
						var notice_dismissed_value =  "3";
						event.preventDefault();
					}

					$.post( ajaxurl, {
						action: 'sp-wps-never-show-review-notice',
						notice_dismissed_data : notice_dismissed_value,
						nonce: '<?php echo esc_attr( wp_create_nonce( 'sp_wps_review_notice' ) ); ?>'
					});

					$('#sp-wps-review-notice.sp-wps-review-notice').hide();
				});
			});

		</script>
		<?php
	}

	/**
	 * Dismiss review notice
	 *
	 * @since  2.1.14
	 *
	 * @return void
	 **/
	public function dismiss_review_notice() {
		$post_data = wp_unslash( $_POST );

		if ( ! isset( $post_data['nonce'] ) || ! wp_verify_nonce( sanitize_key( $post_data['nonce'] ), 'sp_wps_review_notice' ) ) {
			return;
		}
		$review = get_option( 'sp_woo_product_slider_review_notice_dismiss' );
		if ( ! $review ) {
			$review = array();
		}
		$dismiss_status = isset( $post_data['notice_dismissed_data'] ) ? $post_data['notice_dismissed_data'] : '';
		switch ( $dismiss_status ) {
			case '1':
				$review['time']      = time();
				$review['dismissed'] = true;
				break;
			case '2':
				$review['time']      = time();
				$review['dismissed'] = false;
				break;
			case '3':
				$review['time']      = time();
				$review['dismissed'] = true;
				break;
		}
		update_option( 'sp_woo_product_slider_review_notice_dismiss', $review );
		die;
	}
	/**
	 * Review Text
	 *
	 * @param string $text Footer text.
	 *
	 * @return string
	 */
	public function admin_footer( $text ) {
		$screen = get_current_screen();
		if ( 'sp_wps_shortcodes' === $screen->post_type || 'sp_wps_shortcodes_page_wps_settings' === $screen->id ) {

			$url  = 'https://wordpress.org/support/plugin/woo-product-slider/reviews/';
			$text = sprintf( wp_kses_post( 'Enjoying <strong>Product Slider for WooCommerce?</strong> Please rate us <span class="spwps-footer-text-star">★★★★★</span> <a href="%s" target="_blank">WordPress.org</a>. Your positive feedback will help us grow more. Thank you! 😊', 'woo-product-slider' ), $url );
		}

		return $text;
	}
	/**
	 * Footer version Text
	 *
	 * @param string $text Footer version text.
	 *
	 * @return string
	 */
	public function admin_footer_version( $text ) {
		$screen = get_current_screen();
		if ( 'sp_wps_shortcodes' === $screen->post_type ) {
			$text = 'Woo Product Slider ' . SP_WPS_VERSION;
		}

		return $text;
	}
}
