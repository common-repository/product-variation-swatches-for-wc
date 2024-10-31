<?php
	defined( 'ABSPATH' ) or die( 'Keep Quit' );
	if ( ! function_exists( 'evdpl_woocommerce_layout_injector_script_override' ) ):
		function evdpl_woocommerce_layout_injector_script_override() {
			if ( function_exists( 'sb_et_woocommerce_li_enqueue' ) ) :
				$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
				wp_dequeue_script( 'sb_et_woocommerce_li_js' );
				wp_enqueue_script( 'sb_et_woocommerce_li_js_override', woocommerce_variation_swatches()->assets_uri( "/js/divi_woocommerce_layout_injector{$suffix}.js" ), array( 'jquery' ), woocommerce_variation_swatches()->version(), true );
			endif;
		}
		
		add_action( 'wp_enqueue_scripts', 'evdpl_woocommerce_layout_injector_script_override', 99999 );
	endif;


// ==========================================================
// WOODMART Theme
// ==========================================================
if( !function_exists( 'woodmart_has_swatches') ) {
	function woodmart_has_swatches( $id, $attr_name, $options, $available_variations, $swatches_use_variation_images = false ) {
		return array();
	}
}