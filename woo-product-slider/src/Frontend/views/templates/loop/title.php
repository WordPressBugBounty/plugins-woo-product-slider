<?php
/**
 * Product title.
 *
 * This template can be overridden by copying it to yourtheme/woo-product-slider/templates/loop/title.php
 *
 * @package    woo-product-slider
 * @subpackage woo-product-slider/Frontend
 */

if ( $product_name ) {
	do_action( 'sp_wpspro_before_product_title', $post_id ); // $post_id is shortcode id.
	?>
		<div class="wpsf-product-title"><a href="<?php echo esc_url( get_the_permalink() ); ?>"><?php the_title(); ?></a></div>
	<?php
	do_action( 'sp_wpspro_after_product_title', $post_id ); // $post_id is shortcode id.
}
