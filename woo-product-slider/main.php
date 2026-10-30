<?php
/**
 * Plugin Name:     Reno Product Slider (formerly Product Slider for WooCommerce)
 * Plugin URI:      https://renoproductslider.com/?ref=1
 * Description:     Slide your WooCommerce Products in a tidy and professional slider or carousel with an easy-to-use and intuitive Shortcode Generator.
 * Version:         2.9.1
 * Author:          ShapedPlugin LLC
 * Author URI:      https://shapedplugin.com/
 * License:         GPLv3
 * License URI:     https://www.gnu.org/licenses/gpl-3.0.html
 * Requires at least: 5.0
 * Requires PHP: 7.0
 * Requires Plugins: woocommerce
 * WC requires at least: 4.5
 * WC tested up to: 11.2.1
 * Text Domain:     woo-product-slider
 * Domain Path:     /languages
 *
 * @package         Reno_Product_Slider
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
require_once __DIR__ . '/vendor/autoload.php';
if ( ! defined( 'SP_WPS_NAME' ) ) {
	define( 'SP_WPS_NAME', 'woo-product-slider' );
}
if ( ! defined( 'SP_WPS_VERSION' ) ) {
	define( 'SP_WPS_VERSION', '2.9.1' );
}
if ( ! defined( 'SP_WPS_PATH' ) ) {
	define( 'SP_WPS_PATH', plugin_dir_path( __FILE__ ) . 'src/' );
}
if ( ! defined( 'SP_WPS_URL' ) ) {
	define( 'SP_WPS_URL', plugin_dir_url( __FILE__ ) . 'src/' );
}
if ( ! defined( 'SP_WPS_BASENAME' ) ) {
	define( 'SP_WPS_BASENAME', plugin_basename( __FILE__ ) );
}

// Load the shared "Variation Gallery Onboarding" module. Self-registers and, via
// a version-guarded bootstrap, boots only once even if several ShapedPlugin
// plugins bundle their own copy.
//
// Gated here as well as inside load.php: the loader's boot callback is defined by
// whichever bundled copy is included first (function_exists guard), so a sibling
// plugin shipping an older, ungated copy would otherwise boot the module on
// frontend, REST and cron requests regardless of this copy's own gate. Every hook
// the module registers is admin-only. is_admin() is true for admin-ajax.php, which
// the install/activate handlers need.
if ( is_admin() ) {
	require_once SP_WPS_PATH . 'Admin/VariationGalleryOnboarding/load.php';
}

/**
 * Pro version check.
 *
 * Detection is independent of the Pro plugin's folder and main-file names:
 * a hardcoded "woo-product-slider-pro/woo-product-slider-pro.php" match breaks
 * whenever the Pro plugin is installed under a different directory (e.g. a
 * GitHub/branch ZIP that extracts as "woo-product-slider-pro-<branch>"). We
 * detect Pro by its runtime footprint first, then fall back to matching the
 * Pro main-file basename anywhere in the active-plugins list.
 *
 * @return boolean
 */
function is_woo_product_slider_pro_active() {
	// Fast, name-independent path: the Pro plugin defines this constant (and the
	// function below) the moment its main file loads. Pro is sorted before the
	// free plugin in the active-plugins list, so it is already loaded here.
	if ( defined( 'SP_WPSPRO_VERSION' ) || function_exists( 'sp_woo_product_slider_pro' ) ) {
		return true;
	}

	// Load-order- and folder-name-independent fallback: scan every active plugin
	// (per-site + network) and match the Pro main-file basename regardless of the
	// directory that wraps it. The options are read directly rather than through
	// is_plugin_active(), so wp-admin/includes/plugin.php is deliberately not
	// loaded here — this function runs on every frontend request.
	$active = (array) get_option( 'active_plugins', array() );
	if ( is_multisite() ) {
		$active = array_merge( $active, array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) ) );
	}

	foreach ( $active as $plugin_file ) {
		if ( 'woo-product-slider-pro.php' === basename( $plugin_file ) ) {
			return true;
		}
	}

	return false;
}

if ( ! function_exists( 'woo_product_slider' ) ) {
	/**
	 * Shortcode converter function
	 *
	 * @param  int $id shortcode id.
	 * @return void
	 */
	function woo_product_slider( $id ) {
		echo do_shortcode( '[reno_product_slider id="' . $id . '"]' );
	}
}

// Declare that the plugin is compatible with WooCommerce High-Performance order storage feature.
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);

/**
 * Returns the main instance.
 *
 * @since 2.0
 * @return WooProductSlider
 */
function sp_woo_product_slider() {
	if ( ! defined( 'SHAPEDPLIUGIN_OFFER_BANNER_LOADED' ) ) {
		define( 'SHAPEDPLIUGIN_OFFER_BANNER_LOADED', true );

		ShapedPlugin\WooProductSlider\Admin\Notices\ShapedPlugin_Offer_Banner::instance();
	}

	return ShapedPlugin\WooProductSlider\Includes\WooProductSlider::instance();
}

if ( ! is_woo_product_slider_pro_active() ) {
	sp_woo_product_slider();
}
