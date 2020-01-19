<?php

/**
 * Include assets after buddyforms
 */
function buddyforms_pods_include_assets() {
	wp_enqueue_style( 'buddyforms_pods_css', BUDDYFORMS_PODS_ASSETS_URL . "css/pods-form.css" );
}

add_action( 'buddyforms_front_js_css_after_enqueue', 'buddyforms_pods_include_assets');