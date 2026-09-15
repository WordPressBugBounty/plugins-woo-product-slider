<?php
/**
 * Variation Gallery Onboarding — shared module loader.
 *
 * Drop-in, reusable onboarding module used across ShapedPlugin's WooCommerce
 * plugins. Multiple plugins may each bundle their own copy of this module; this
 * loader guarantees that only ONE copy (the highest version) is ever booted, so
 * there are never duplicate buttons, modals, hooks or AJAX handlers.
 *
 * How it works:
 *  - Every bundled copy calls {@see sp_variation_gallery_onboarding_register()}
 *    at include time, recording its version + file path + assets URL.
 *  - The very first copy to load also registers a single `plugins_loaded`
 *    bootstrap callback (guarded by function_exists()).
 *  - On `plugins_loaded` the registry is sorted by version and only the newest
 *    copy is required and instantiated once.
 *
 * This file intentionally contains ONLY procedural bootstrap code and no
 * namespaced symbols, so including it from any plugin (regardless of that
 * plugin's own namespace / autoloader) is always safe.
 *
 * @package ShapedPlugin\VariationGalleryOnboarding
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! function_exists( 'sp_variation_gallery_onboarding_register' ) ) {
	/**
	 * Register a bundled copy of the module.
	 *
	 * @param string $version    Semantic version of this copy.
	 * @param string $file        Absolute path to the module main class file.
	 * @param string $assets_url Base URL of this module directory (with trailing slash tolerated).
	 * @return void
	 */
	function sp_variation_gallery_onboarding_register( $version, $file, $assets_url ) {
		global $sp_variation_gallery_onboarding_registry;

		if ( ! is_array( $sp_variation_gallery_onboarding_registry ) ) {
			$sp_variation_gallery_onboarding_registry = array();
		}

		$sp_variation_gallery_onboarding_registry[ $version ] = array(
			'version'    => $version,
			'file'       => $file,
			'assets_url' => $assets_url,
		);
	}

	/**
	 * Boot the highest registered version exactly once.
	 *
	 * @return void
	 */
	function sp_variation_gallery_onboarding_boot() {
		global $sp_variation_gallery_onboarding_registry;

		// Every hook the module registers is admin-only (admin_enqueue_scripts,
		// admin_footer, wp_ajax_*, woocommerce_product_after_variable_attributes),
		// so the class file is never parsed on frontend, REST or cron requests.
		// is_admin() is true for admin-ajax.php, which the AJAX handlers need.
		if ( ! is_admin() ) {
			return;
		}

		if ( empty( $sp_variation_gallery_onboarding_registry ) || ! is_array( $sp_variation_gallery_onboarding_registry ) ) {
			return;
		}

		// Highest semantic version wins.
		uksort( $sp_variation_gallery_onboarding_registry, 'version_compare' );
		$selected = end( $sp_variation_gallery_onboarding_registry );

		if ( empty( $selected['file'] ) || ! is_readable( $selected['file'] ) ) {
			return;
		}

		require_once $selected['file'];

		$class = '\\ShapedPlugin\\VariationGalleryOnboarding\\Variation_Gallery_Onboarding';
		if ( class_exists( $class ) ) {
			$class::instance( $selected['assets_url'], $selected['version'] );
		}
	}

	// Register the single shared bootstrap. Runs after all plugins are loaded so
	// the full registry is available, but before admin_init where hooks attach.
	add_action( 'plugins_loaded', 'sp_variation_gallery_onboarding_boot', 20 );
}

// Register THIS copy. Guarded registrations from other plugins are deduped by
// version inside the registry; the boot step then loads only the newest.
sp_variation_gallery_onboarding_register(
	'1.0.0',
	__DIR__ . '/includes/class-variation-gallery-onboarding.php',
	plugin_dir_url( __FILE__ )
);
