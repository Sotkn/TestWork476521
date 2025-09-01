<?php
// inc/PostTypes/Cities/register.php
defined('ABSPATH') || exit;

// Register Custom Post Type for Cities
function register_cities_cpt() {
    $args = array(
        'label'                 => __( 'Cities', 'storefront-child' ),
        'description'           => __( 'Custom post type for cities', 'storefront-child' ),
        'labels'                => array(
            'name'                  => _x( 'Cities', 'Post Type General Name', 'storefront-child' ),
            'singular_name'         => _x( 'City', 'Post Type Singular Name', 'storefront-child' ),
            'menu_name'             => __( 'Cities', 'storefront-child' ),
            'name_admin_bar'        => __( 'City', 'storefront-child' ),
            'archives'              => __( 'City Archives', 'storefront-child' ),
            'attributes'            => __( 'City Attributes', 'storefront-child' ),
            'parent_item_colon'     => __( 'Parent City:', 'storefront-child' ),
            'all_items'             => __( 'All Cities', 'storefront-child' ),
            'add_new_item'          => __( 'Add New City', 'storefront-child' ),
            'add_new'               => __( 'Add New', 'storefront-child' ),
            'new_item'              => __( 'New City', 'storefront-child' ),
            'edit_item'             => __( 'Edit City', 'storefront-child' ),
            'update_item'           => __( 'Update City', 'storefront-child' ),
            'view_item'             => __( 'View City', 'storefront-child' ),
            'view_items'            => __( 'View Cities', 'storefront-child' ),
            'search_items'          => __( 'Search City', 'storefront-child' ),
            'not_found'             => __( 'Not found', 'storefront-child' ),
            'not_found_in_trash'    => __( 'Not found in Trash', 'storefront-child' ),
            'featured_image'        => __( 'Featured Image', 'storefront-child' ),
            'set_featured_image'    => __( 'Set featured image', 'storefront-child' ),
            'remove_featured_image' => __( 'Remove featured image', 'storefront-child' ),
            'use_featured_image'    => __( 'Use as featured image', 'storefront-child' ),
            'insert_into_item'      => __( 'Insert into city', 'storefront-child' ),
            'uploaded_to_this_item' => __( 'Uploaded to this city', 'storefront-child' ),
            'items_list'            => __( 'Cities list', 'storefront-child' ),
            'items_list_navigation' => __( 'Cities list navigation', 'storefront-child' ),
            'filter_items_list'     => __( 'Filter cities list', 'storefront-child' ),
        ),
        'supports'              => array( 'title' ),
        'hierarchical'          => false,
        'public'                => true,
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_position'         => 5,
        'show_in_admin_bar'     => true,
        'show_in_nav_menus'     => true,
        'can_export'            => true,
        'has_archive'           => false,
        'exclude_from_search'   => false,
        'publicly_queryable'    => true,
        'capability_type'       => 'post',
    );
    register_post_type( 'cities', $args );
}
add_action( 'init', 'register_cities_cpt', 0 );
