<?php
/**
 * Uninstall.php for cleaning plugin database.
 *
 * Trigger the file when plugin is deleted.
 *
 * @see delete_option(), delete_post_meta_key()
 * @since 2.3.1
 * @package Woo Product Slider
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Delete plugin data function.
 *
 * @return void
 */
function sp_wpsf_delete_plugin_data() {

	// Delete plugin option settings.
	$option_name = 'sp_woo_product_slider_options';
	delete_option( $option_name );
	delete_site_option( $option_name ); // For site options in Multisite.

	// Delete carousel post type.
	$carousel_posts = get_posts(
		array(
			'numberposts' => -1,
			'post_type'   => 'sp_wps_shortcodes',
			'post_status' => 'any',
		)
	);
	foreach ( $carousel_posts as $post ) {
		wp_delete_post( $post->ID, true );
	}

	// Delete Carousel post meta.
	delete_post_meta_by_key( 'sp_wps_shortcode_options' );

	// Delete offer banner related option keys.
	delete_option( 'shapedplugin_offer_banner_dismissed_black_friday_2025' );
	delete_option( 'shapedplugin_offer_banner_dismissed_new_year_2026' );
}

// Load WPSF file.
require plugin_dir_path( __FILE__ ) . '/main.php';
$wps_options     = get_option( 'sp_woo_product_slider_options' );
$wps_plugin_data = isset( $wps_options['wpsp_delete_all_data'] ) ? $wps_options['wpsp_delete_all_data'] : false;

if ( $wps_plugin_data ) {
	sp_wpsf_delete_plugin_data();
}
