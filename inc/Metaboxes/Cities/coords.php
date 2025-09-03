<?php
/**
 * Cities Coordinates Metabox
 * 
 * Handles the creation, display, and saving of coordinate data for cities.
 * Provides validation for latitude (-90 to 90) and longitude (-180 to 180).
 * 
 * @package StorefrontChild
 * @subpackage Cities
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

/**
 * Registers the coordinates metabox for the 'cities' custom post type.
 * 
 * @since 1.0.0
 * @return void
 */
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

/**
 * Renders the coordinates metabox content.
 * 
 * @since 1.0.0
 * @param WP_Post $post The post object being edited.
 * @return void
 */
function cities_coords_metabox_callback( $post ) {
    // Add nonce field for security verification
    wp_nonce_field( 'cities_coords_nonce_action', 'cities_coords_nonce' );
    
    // Retrieve existing coordinate values from post meta
    $latitude = get_post_meta( $post->ID, 'latitude', true );
    $longitude = get_post_meta( $post->ID, 'longitude', true );
    
    // Render the coordinate input fields
    display_fields($latitude, $longitude);
}

/**
 * Saves and validates the coordinates metabox data.
 * 
 * Performs security checks, validates coordinate ranges, and stores valid data.
 * Invalid coordinates are collected as errors for user notification.
 * 
 * @since 1.0.0
 * @param int $post_id The ID of the post being saved.
 * @return void
 */
function save_cities_coords_metabox( $post_id ) {
    // Verify nonce to prevent unauthorized form submissions
    if ( ! isset( $_POST['cities_coords_nonce'] ) || ! wp_verify_nonce( $_POST['cities_coords_nonce'], 'cities_coords_nonce_action' ) ) {
        return;
    }
    
    // Ensure user has permission to edit this post
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    
    // Skip processing during autosave operations
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    
    // Only process coordinates for 'cities' post type
    if ( get_post_type( $post_id ) !== 'cities' ) {
        return;
    }
    
    // Initialize array to collect validation errors
    $errors = array();
    
    // Process and validate latitude coordinate
    if ( isset( $_POST['latitude'] ) ) {
        $latitude = sanitize_text_field( $_POST['latitude'] );
        if ( $latitude !== '' && is_valid_latitude( $latitude ) ) {
            update_post_meta( $post_id, 'latitude', $latitude );
        } elseif ( $latitude !== '' ) {
            $errors[] = __( 'Latitude must be between -90 and 90.', 'storefront-child' );
        }
    }
    
    // Process and validate longitude coordinate
    if ( isset( $_POST['longitude'] ) ) {
        $longitude = sanitize_text_field( $_POST['longitude'] );
        if ( $longitude !== '' && is_valid_longitude( $longitude ) ) {
            update_post_meta( $post_id, 'longitude', $longitude );
        } elseif ( $longitude !== '' ) {
            $errors[] = __( 'Longitude must be between -180 and 180.', 'storefront-child' );
        }
    }
    
    // Store validation errors in transient for display on next page load
    if ( ! empty( $errors ) ) {
        set_transient( 'cities_coords_errors_' . $post_id, $errors, 45 );
    }
}
add_action( 'save_post', 'save_cities_coords_metabox' );

/**
 * Displays admin notices for coordinate validation errors.
 * 
 * Retrieves errors from transients and displays them as admin notices.
 * Automatically cleans up transients after display.
 * 
 * @since 1.0.0
 * @return void
 */
function display_cities_coords_admin_notices() {
    global $post;
    
    // Only show notices on cities post edit screens
    if ( ! $post || get_post_type( $post ) !== 'cities' ) {
        return;
    }
    
    // Retrieve stored validation errors
    $errors = get_transient( 'cities_coords_errors_' . $post->ID );
    if ( $errors ) {
        foreach ( $errors as $error ) {
            echo '<div class="notice notice-error"><p>' . esc_html( $error ) . '</p></div>';
        }
        // Clean up transient after displaying errors
        delete_transient( 'cities_coords_errors_' . $post->ID );
    }
}
add_action( 'admin_notices', 'display_cities_coords_admin_notices' );

/**
 * Renders the coordinate input fields HTML.
 * 
 * Creates a formatted table with latitude and longitude input fields,
 * including validation attributes and helpful descriptions.
 * 
 * @since 1.0.0
 * @param string $latitude  The current latitude value.
 * @param string $longitude The current longitude value.
 * @return void
 */
function display_fields($latitude, $longitude) {
    ?>
    <table class="form-table">
        <tr>
            <th><label for="latitude"><?php echo esc_html( __( 'Latitude', 'storefront-child' ) ); ?></label></th>
            <td>
                <input type="number" 
                       id="latitude" 
                       name="latitude" 
                       value="<?php echo esc_attr( $latitude ); ?>" 
                       class="regular-text" 
                       min="-90" 
                       max="90" 
                       step="any" />
                <p class="description"><?php echo esc_html( __( 'Must be between -90 and 90', 'storefront-child' ) ); ?></p>
            </td>
        </tr>
        <tr>
            <th><label for="longitude"><?php echo esc_html( __( 'Longitude', 'storefront-child' ) ); ?></label></th>
            <td>
                <input type="number" 
                       id="longitude" 
                       name="longitude" 
                       value="<?php echo esc_attr( $longitude ); ?>" 
                       class="regular-text" 
                       min="-180" 
                       max="180" 
                       step="any" />
                <p class="description"><?php echo esc_html( __( 'Must be between -180 and 180', 'storefront-child' ) ); ?></p>
            </td>
        </tr>
    </table>
    <?php
}

/**
 * Validates latitude coordinate values.
 * 
 * Ensures latitude is numeric and within valid range (-90 to 90 degrees).
 * 
 * @since 1.0.0
 * @param mixed $latitude The latitude value to validate.
 * @return bool True if valid, false otherwise.
 */
function is_valid_latitude( $latitude ) {
    return is_numeric( $latitude ) && $latitude >= -90 && $latitude <= 90;
}

/**
 * Validates longitude coordinate values.
 * 
 * Ensures longitude is numeric and within valid range (-180 to 180 degrees).
 * 
 * @since 1.0.0
 * @param mixed $longitude The longitude value to validate.
 * @return bool True if valid, false otherwise.
 */
function is_valid_longitude( $longitude ) {
    return is_numeric( $longitude ) && $longitude >= -180 && $longitude <= 180;
}