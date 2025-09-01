<?php
// inc/Ajax/CitiesSearch.php
defined('ABSPATH') || exit;

/**
 * Cities Search AJAX Handler Class
 * 
 * Handles all AJAX requests related to cities search functionality
 */
class CitiesSearch {
    
    /**
     * Initialize the class
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Register AJAX actions for both logged-in and non-logged-in users
        add_action('wp_ajax_cities_search', [$this, 'handle_cities_search']);
        add_action('wp_ajax_nopriv_cities_search', [$this, 'handle_cities_search']);
        
        // Enqueue assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_cities_search_assets']);
    }
    
    /**
     * Handle AJAX search request for cities
     */
    public function handle_cities_search() {
        // Verify nonce for security
        if (!wp_verify_nonce($_POST['nonce'], 'cities_search_nonce')) {
            wp_die('Security check failed');
        }

        $search_term = sanitize_text_field($_POST['search_term'] ?? '');
        $results = Cities_Repository::search_cities_and_countries($search_term);
        
        // Start output buffering to capture the rendered table
        ob_start();
        render_cities_table($results);
        $html = ob_get_clean();
        
        wp_send_json_success([
            'html' => $html,
            'count' => count($results)
        ]);
    }
    
    /**
     * Enqueue cities search assets
     */
    public function enqueue_cities_search_assets() {
        if (is_page_template('page-templates/cities-list.php')) {
            wp_enqueue_script(
                'cities-search',
                get_stylesheet_directory_uri() . '/assets/js/cities-search.js',
                ['jquery'],
                '1.0.0',
                true
            );

            wp_localize_script('cities-search', 'citiesSearchAjax', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('cities_search_nonce'),
                'searching' => __('Searching...', 'text_domain'),
                'noResults' => __('No cities found.', 'text_domain')
            ]);

            wp_enqueue_style(
                'cities-search',
                get_stylesheet_directory_uri() . '/assets/css/cities-search.css',
                [],
                '1.0.0'
            );
        }
    }
}

// Initialize the class
new CitiesSearch();
