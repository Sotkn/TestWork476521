<?php
// Register Custom Taxonomy for Countries
function register_countries_taxonomy() {
    $args = array(
        'label'                 => __( 'Countries', 'text_domain' ),
        'description'           => __( 'Countries taxonomy for cities', 'text_domain' ),
        'labels'                => array(
            'name'                       => _x( 'Countries', 'Taxonomy General Name', 'text_domain' ),
            'singular_name'              => _x( 'Country', 'Taxonomy Singular Name', 'text_domain' ),
            'menu_name'                  => __( 'Countries', 'text_domain' ),
            'all_items'                  => __( 'All Countries', 'text_domain' ),
            'parent_item'                => __( 'Parent Country', 'text_domain' ),
            'parent_item_colon'          => __( 'Parent Country:', 'text_domain' ),
            'new_item_name'              => __( 'New Country Name', 'text_domain' ),
            'add_new_item'               => __( 'Add New Country', 'text_domain' ),
            'edit_item'                  => __( 'Edit Country', 'text_domain' ),
            'update_item'                => __( 'Update Country', 'text_domain' ),
            'view_item'                  => __( 'View Country', 'text_domain' ),
            'separate_items_with_commas' => __( 'Separate countries with commas', 'text_domain' ),
            'add_or_remove_items'        => __( 'Add or remove countries', 'text_domain' ),
            'choose_from_most_used'      => __( 'Choose from the most used', 'text_domain' ),
            'popular_items'              => __( 'Popular Countries', 'text_domain' ),
            'search_items'               => __( 'Search Countries', 'text_domain' ),
            'not_found'                  => __( 'Not Found', 'text_domain' ),
            'no_terms'                   => __( 'No countries', 'text_domain' ),
            'items_list'                 => __( 'Countries list', 'text_domain' ),
            'items_list_navigation'      => __( 'Countries list navigation', 'text_domain' ),
        ),
        'hierarchical'          => false,
        'public'                => true,
        'show_ui'               => true,
        'show_admin_column'     => true,
        'show_in_nav_menus'     => true,
        'show_tagcloud'         => true,
        'show_in_rest'          => true,
    );
    register_taxonomy( 'countries', array( 'cities' ), $args );
}
add_action( 'init', 'register_countries_taxonomy', 0 );
