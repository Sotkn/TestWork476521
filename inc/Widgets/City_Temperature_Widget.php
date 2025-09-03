<?php
// inc/Widgets/City_Temperature_Widget.php
defined('ABSPATH') || exit;

class City_Temperature_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'city_temperature_widget',
            __( 'City Temperature Widget', 'storefront-child' ),
            array(
                'description' => __( 'Display a city with its temperature', 'storefront-child' ),
            )
        );
    }

    public function widget( $args, $instance ) {
        $city_id = ! empty( $instance['city_id'] ) ? $instance['city_id'] : '';
        
        if ( empty( $city_id ) ) {
            return;
        }

        $city = get_post( $city_id );
        if ( ! $city || $city->post_type !== 'cities' ) {
            return;
        }

        // Use the same approach as the cities list page
        $cities_repo_with_temp = new CitiesRepositoryWithTemp();
        $city_with_temp = $cities_repo_with_temp->get_city_with_temp_by_id( $city_id );
        
        if ( ! $city_with_temp ) {
            return;
        }

        $temperature = $city_with_temp->temperature_celsius ?? null;
        $cache_status = $city_with_temp->cache_status ?? 'unknown';

        echo $args['before_widget'];

        if ( ! empty( $instance['title'] ) ) {
            echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
        }

        echo '<div class="city-temperature-widget">';
        echo '<h3>' . esc_html( $city->post_title ) . '</h3>';
        
        if ( $temperature !== null ) {
            echo '<p><strong>' . __( 'Temperature:', 'storefront-child' ) . '</strong> ' . esc_html( $temperature ) . '°C</p>';
        } else {
            echo '<p><em>' . __( 'Temperature data not available', 'storefront-child' ) . '</em></p>';
        }
        
        // Add cache status indicator like the cities list
        echo '<span class="cache-status-indicator cache-status-' . esc_attr( $cache_status ) . '" title="' . esc_attr( ucfirst( $cache_status ) ) . ' cache status">';
        echo $this->get_cache_status_icon( $cache_status );
        echo '</span>';
        
        echo '</div>';

        echo $args['after_widget'];
    }

    /**
     * Get cache status icon based on status
     * 
     * @param string $status Cache status
     * @return string HTML for status icon
     */
    private function get_cache_status_icon($status) {
        switch ($status) {
            case 'valid':
                return '<span class="dashicons dashicons-yes-alt"></span>';
            case 'expired':
                return '<span class="dashicons dashicons-clock"></span>';
            case 'expected':
                return '<span class="dashicons dashicons-update"></span>';
            case 'unavailable':
                return '<span class="dashicons dashicons-no-alt"></span>';
            case 'abort':
                return '<span class="dashicons dashicons-dismiss"></span>';
            default:
                return '<span class="dashicons dashicons-help"></span>';
        }
    }

    public function form( $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'City Information', 'storefront-child' );
        $city_id = ! empty( $instance['city_id'] ) ? $instance['city_id'] : '';

        // Get all cities
        $cities = get_posts( array(
            'post_type' => 'cities',
            'numberposts' => -1,
            'post_status' => 'publish'
        ) );
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php _e( 'Title:', 'storefront-child' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'city_id' ) ); ?>"><?php _e( 'Select City:', 'storefront-child' ); ?></label>
            <select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'city_id' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'city_id' ) ); ?>">
                <option value=""><?php _e( 'Select a city...', 'storefront-child' ); ?></option>
                <?php foreach ( $cities as $city ) : ?>
                    <option value="<?php echo esc_attr( $city->ID ); ?>" <?php selected( $city_id, $city->ID ); ?>>
                        <?php echo esc_html( $city->post_title ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? sanitize_text_field( $new_instance['title'] ) : '';
        $instance['city_id'] = ( ! empty( $new_instance['city_id'] ) ) ? absint( $new_instance['city_id'] ) : '';

        return $instance;
    }
}

// Register the widget
function register_city_temperature_widget() {
    register_widget( 'City_Temperature_Widget' );
}
add_action( 'widgets_init', 'register_city_temperature_widget' );
