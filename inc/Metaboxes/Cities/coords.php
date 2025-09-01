<?php
// inc/Metaboxes/Cities/coords.php
defined('ABSPATH') || exit;

// Add metabox for Cities coordinates
function add_cities_coords_metabox() {
    add_meta_box(
        'cities_coords',
        __( 'Coordinates', 'storefront-child' ),
        'cities_coords_metabox_callback',
        'cities',
        'normal',
        'default'
    );
}
add_action( 'add_meta_boxes', 'add_cities_coords_metabox' );

// Metabox callback function
function cities_coords_metabox_callback( $post ) {
    // Add nonce field for security
    wp_nonce_field( 'cities_coords_nonce_action', 'cities_coords_nonce' );
    
    // Get current values
    $latitude = get_post_meta( $post->ID, 'latitude', true );
    $longitude = get_post_meta( $post->ID, 'longitude', true );
    
    // Display fields
    display_fields($latitude, $longitude);
    
}

// Save metabox data
function save_cities_coords_metabox( $post_id ) {
    // Check if nonce is valid
    if ( ! isset( $_POST['cities_coords_nonce'] ) || ! wp_verify_nonce( $_POST['cities_coords_nonce'], 'cities_coords_nonce_action' ) ) {
        return;
    }
    
    // Check if user has permission to edit post
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    
    // Check if this is an autosave
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    
    // Save latitude with validation
    if ( isset( $_POST['latitude'] ) ) {
        $latitude = sanitize_text_field( $_POST['latitude'] );
        if ( is_valid_latitude( $latitude ) ) {
            update_post_meta( $post_id, 'latitude', $latitude );
        } else {
            // Add admin notice for invalid latitude
            add_action( 'admin_notices', function() {
                echo '<div class="notice notice-error"><p>' . __( 'Latitude must be between -90 and 90.', 'storefront-child' ) . '</p></div>';
            });
        }
    }
    // Save longitude with validation
    if ( isset( $_POST['longitude'] ) ) {
        $longitude = sanitize_text_field( $_POST['longitude'] );
        if ( is_valid_longitude( $longitude ) ) {
            update_post_meta( $post_id, 'longitude', $longitude );
        } else {
            // Add admin notice for invalid longitude
            add_action( 'admin_notices', function() {
                echo '<div class="notice notice-error"><p>' . __( 'Longitude must be between -180 and 180.', 'storefront-child' ) . '</p></div>';
            });
        }
    }
}
add_action( 'save_post', 'save_cities_coords_metabox' );

// Display fields function
function display_fields($latitude, $longitude) {
    echo '<table class="form-table">';
    echo '<tr>';
            echo '<th><label for="latitude">' . __( 'Latitude', 'storefront-child' ) . '</label></th>';
    echo '<td>';
    echo '<input type="number" id="latitude" name="latitude" value="' . esc_attr( $latitude ) . '" class="regular-text" min="-90" max="90" step="any" />';
            echo '<p class="description">' . __( 'Must be between -90 and 90', 'storefront-child' ) . '</p>';
    echo '</td>';
    echo '</tr>';
    echo '<tr>';
            echo '<th><label for="longitude">' . __( 'Longitude', 'storefront-child' ) . '</label></th>';
    echo '<td>';
    echo '<input type="number" id="longitude" name="longitude" value="' . esc_attr( $longitude ) . '" class="regular-text" min="-180" max="180" step="any" />';
            echo '<p class="description">' . __( 'Must be between -180 and 180', 'storefront-child' ) . '</p>';
    echo '</td>';
    echo '</tr>';
    echo '</table>';
}

function is_valid_latitude( $latitude ) {
    return $latitude >= -90 && $latitude <= 90;
}

function is_valid_longitude( $longitude ) {
    return $longitude >= -180 && $longitude <= 180;
}