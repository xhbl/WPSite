<?php
/**
 * child theme functions and definitions.
 */


// Load child theme style
add_action( 'wp_enqueue_scripts', function() {
    wp_enqueue_style( 'xhbl-style', get_stylesheet_uri(), [], wp_get_theme()->get( 'Version' ) );
}, 20 );


// Load required extra functions
require_once __DIR__ . '/inc/xfunctions.php';
