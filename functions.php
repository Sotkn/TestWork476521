<?php
// Enqueue parent theme styles
function child_theme_enqueue_styles() {
    
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
    
    // Enqueue child theme stylesheet
    wp_enqueue_style( 'child-style',
		get_stylesheet_directory_uri() . '/style.css',
		[ 'parent-style' ]
	);
}
add_action('wp_enqueue_scripts', 'child_theme_enqueue_styles');


// Include bootstrap
require_once __DIR__ . '/inc/bootstrap.php';
