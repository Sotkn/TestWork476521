<?php
// inc/Widgets/City_Temperature_Widget.php
defined('ABSPATH') || exit;

class City_Temperature_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'city_temperature_widget',
            __( 'City Temperature Widget', 'text_domain' ),
            array(
                'description' => __( 'Display a city with its coordinates', 'text_domain' ),
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

        $latitude = get_post_meta( $city_id, 'latitude', true );
        $longitude = get_post_meta( $city_id, 'longitude', true );

        echo $args['before_widget'];

        if ( ! empty( $instance['title'] ) ) {
            echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
        }

        echo '<div class="city-temperature-widget">';
        echo '<h3>' . esc_html( $city->post_title ) . '</h3>';
        
        if ( $latitude && $longitude ) {
            echo '<p><strong>' . __( 'Latitude:', 'text_domain' ) . '</strong> ' . esc_html( $latitude ) . '</p>';
            echo '<p><strong>' . __( 'Longitude:', 'text_domain' ) . '</strong> ' . esc_html( $longitude ) . '</p>';
        }
        echo '</div>';

        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'City Information', 'text_domain' );
        $city_id = ! empty( $instance['city_id'] ) ? $instance['city_id'] : '';

        // Get all cities
        $cities = get_posts( array(
            'post_type' => 'cities',
            'numberposts' => -1,
            'post_status' => 'publish'
        ) );
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php _e( 'Title:', 'text_domain' ); ?></label>
            <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'city_id' ) ); ?>"><?php _e( 'Select City:', 'text_domain' ); ?></label>
            <select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'city_id' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'city_id' ) ); ?>">
                <option value=""><?php _e( 'Select a city...', 'text_domain' ); ?></option>
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
