<?php
/**
 * Update version.
 *
 * @package Woo Product Slider
 * @subpackage Woo Product Slider/Admin
 * @since 2.8.6
 */

update_option( 'woo_product_slider_version', SP_WPS_VERSION );
update_option( 'woo_product_slider_db_version', SP_WPS_VERSION );

// Delete transient.
if ( get_transient( 'spwps_plugins' ) ) {
	delete_transient( 'spwps_plugins' );
}
