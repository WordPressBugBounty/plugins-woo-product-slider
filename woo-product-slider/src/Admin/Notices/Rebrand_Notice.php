<?php
/**
 * Rebrand announcement notice.
 *
 * Announces the Product Slider for WooCommerce to Reno Product Slider rename on
 * the plugin's own screens. Dismissing it hides the notice for the whole site,
 * matching how the offer banner behaves, so the dismissed state is kept in a
 * site option.
 *
 * @since      2.9.0
 * @package    Reno_Product_Slider
 * @subpackage Reno_Product_Slider/Admin/Notices
 * @author     ShapedPlugin <support@shapedplugin.com>
 */

namespace ShapedPlugin\WooProductSlider\Admin\Notices;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * The class for the rebrand announcement notice.
 */
class Rebrand_Notice {

	/**
	 * The single instance of the class.
	 *
	 * @var self
	 * @since 2.9.0
	 */
	private static $instance = null;

	/**
	 * Site option key holding the dismissed state.
	 *
	 * @var string
	 */
	const DISMISSED_OPTION_KEY = 'sp_wps_rebrand_notice_dismissed';

	/**
	 * Class constructor.
	 */
	private function __construct() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_rebrand_notice_style' ) );
		add_action( 'admin_notices', array( $this, 'render_rebrand_notice' ) );
		add_action( 'wp_ajax_wps_dismiss_rebrand_notice', array( $this, 'dismiss_rebrand_notice' ) );
	}

	/**
	 * Retrieves the singleton instance of the class.
	 *
	 * @return self The singleton instance of the class.
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Capability required to see and dismiss the notice.
	 *
	 * Dismissing writes a site-wide option, so the same permission that
	 * guards the plugin UI guards the notice.
	 *
	 * @return string
	 */
	private function get_capability() {
		return apply_filters( 'sp_wps_shortcodes_ui_permission', 'manage_options' );
	}

	/**
	 * Whether the current screen belongs to this plugin.
	 *
	 * Every plugin screen — the shortcode list and editor, Settings and Get
	 * Help — hangs off the sp_wps_shortcodes post type menu, so the post type
	 * alone identifies them all without hardcoding submenu hook names.
	 *
	 * @return bool
	 */
	private function is_plugin_screen() {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		$current_screen = get_current_screen();

		if ( ! is_object( $current_screen ) ) {
			return false;
		}

		return 'sp_wps_shortcodes' === $current_screen->post_type;
	}

	/**
	 * Determines whether the notice should be shown to the current user.
	 *
	 * @return bool
	 */
	public function should_render() {
		if ( ! $this->is_plugin_screen() || ! current_user_can( $this->get_capability() ) ) {
			return false;
		}

		return ! get_option( self::DISMISSED_OPTION_KEY );
	}

	/**
	 * Loads the notice styles and the dismiss script.
	 *
	 * Enqueued here rather than while rendering the notice, because styles
	 * enqueued during admin_notices are printed in the footer and the bar
	 * would flash unstyled first.
	 *
	 * The dismiss handler goes through the script API on a src-less handle rather
	 * than being printed inline next to the notice: that declares the jquery
	 * dependency (an inline block in the notices region can run before jQuery is
	 * printed and then throws, silently breaking dismissal) and keeps the code
	 * visible to script filters.
	 *
	 * @return void
	 */
	public function enqueue_rebrand_notice_style() {
		if ( ! $this->should_render() ) {
			return;
		}

		wp_enqueue_style( 'sp-wps-notices', esc_url( SP_WPS_URL . 'Admin/assets/css/notices.min.css' ), array(), SP_WPS_VERSION );

		wp_register_script( 'sp-wps-notices', false, array( 'jquery' ), SP_WPS_VERSION, true );
		wp_enqueue_script( 'sp-wps-notices' );
		wp_add_inline_script( 'sp-wps-notices', $this->get_dismiss_script() );
	}

	/**
	 * The dismiss handler attached to the notice script handle.
	 *
	 * Delegated from document because the notice is printed before this script
	 * runs on some screens, and `ajaxurl` is defined by the admin header on every
	 * admin page.
	 *
	 * @return string JavaScript source, without the enclosing script tag.
	 */
	private function get_dismiss_script() {
		return "( function ( $ ) {
	$( document ).on( 'click', '#wps-rebrand-notice .wps-rebrand-notice-dismiss', function ( event ) {
		event.preventDefault();
		$.post( window.ajaxurl, {
			action: 'wps_dismiss_rebrand_notice',
			nonce: $( this ).data( 'nonce' )
		} );
		$( '#wps-rebrand-notice' ).fadeOut( 300 );
	} );
}( jQuery ) );";
	}

	/**
	 * Renders the rebrand notice on the page.
	 *
	 * @return void
	 */
	public function render_rebrand_notice() {
		if ( ! $this->should_render() ) {
			return;
		}

		$nonce = wp_create_nonce( 'wps_rebrand_notice_dismiss' );
		?>
		<div id="wps-rebrand-notice" class="wps-rebrand-notice">
			<div class="wps-rebrand-notice-content">
				<img class="wps-rebrand-notice-icon" src="<?php echo esc_url( SP_WPS_URL . 'Admin/assets/images/rebrand-notice-icon.gif' ); ?>" alt="" width="18" height="18">
				<p class="wps-rebrand-notice-text">
					<?php
					// The <strong> tags are substituted into the sentence, so escaping
					// happens at output over the assembled string with an explicit tag
					// allow-list — escaping the format alone would emit them raw.
					echo wp_kses(
						sprintf(
							/* translators: 1: old plugin name, 2: new plugin name. */
							__( '%1$s is now %2$s — Everything else remains the same.', 'woo-product-slider' ),
							'<strong>' . esc_html__( 'Woo Product Slider', 'woo-product-slider' ) . '</strong>',
							'<strong>' . esc_html__( 'Reno Product Slider', 'woo-product-slider' ) . '</strong>'
						),
						array( 'strong' => array() )
					);
					?>
				</p>
			</div>

			<button type="button" class="wps-rebrand-notice-dismiss" data-nonce="<?php echo esc_attr( $nonce ); ?>">
				<span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice', 'woo-product-slider' ); ?></span>
				<svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg">
					<path d="M10.5 3.5 3.5 10.5M3.5 3.5l7 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
				</svg>
			</button>
		</div>
		<?php
	}

	/**
	 * Handles the AJAX request to dismiss the rebrand notice.
	 *
	 * @return void
	 */
	public function dismiss_rebrand_notice() {
		check_ajax_referer( 'wps_rebrand_notice_dismiss', 'nonce' );

		if ( ! current_user_can( $this->get_capability() ) ) {
			wp_send_json_error( array( 'error' => esc_html__( 'Authorization failed!', 'woo-product-slider' ) ), 401 );
		}

		// Not autoloaded: only ever read on this plugin's admin screens.
		update_option( self::DISMISSED_OPTION_KEY, 1, false );

		wp_send_json_success();
	}
}
