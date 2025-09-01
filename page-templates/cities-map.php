<?php
/**
 * Template Name: Cities Map
 * Template Post Type: page
 */

get_header();
?>

<div class="cities-map-container">
    <div class="container">
        <h1><?php _e( 'Cities by Country', 'text_domain' ); ?></h1>
        
        <?php
        // Custom action hook before the cities table
        do_action( 'cities_map_before_table' );
        
        global $wpdb;
        
        // Get all countries with their cities using $wpdb
        $results = $wpdb->get_results("
            SELECT 
                t.name as country_name,
                t.slug as country_slug,
                p.ID as city_id,
                p.post_title as city_name
            FROM {$wpdb->terms} t
            INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
            INNER JOIN {$wpdb->term_relationships} tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
            INNER JOIN {$wpdb->posts} p ON tr.object_id = p.ID
            WHERE tt.taxonomy = 'countries'
            AND p.post_type = 'cities'
            AND p.post_status = 'publish'
            ORDER BY t.name ASC, p.post_title ASC
        ");
        
        if ( $results ) {
            // Group cities by country
            $cities_by_country = array();
            foreach ( $results as $row ) {
                $cities_by_country[$row->country_name][] = $row;
            }
            ?>
            
            <div class="cities-list-wrapper">
                <?php foreach ( $cities_by_country as $country_name => $cities ) : ?>
                    <div class="country-section">
                        <h2><?php echo esc_html( $country_name ); ?></h2>
                        <ul class="cities-list">
                            <?php foreach ( $cities as $city ) : ?>
                                <li><?php echo esc_html( $city->city_name ); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php } else { ?>
            <p><?php _e( 'No cities found.', 'text_domain' ); ?></p>
        <?php } ?>
        
        <?php
        // Custom action hook after the cities table
        do_action( 'cities_map_after_table' );
        ?>
        
    </div>
</div>

<?php get_footer(); ?>


