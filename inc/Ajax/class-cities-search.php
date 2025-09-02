<?php
// inc/Ajax/CitiesSearch.php
defined('ABSPATH') || exit;

/**
 * Cities Search AJAX Handler Class
 * 
 * Handles all AJAX requests related to cities search functionality
 * 
 * @package Storefront_Child
 * @version 1.0.0
 * @since 1.0.0
 */
class CitiesSearch {
    
    /**
     * Rate limiter instance
     */
    private $rate_limiter;
    
    /**
     * Initialize the class
     */
    public function __construct() {
        $this->rate_limiter = new Rate_Limiter('cities_search_rate_limit', 10, 60);
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
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'cities_search_nonce')) {
            wp_send_json_error(['message' => __('Security check failed', 'storefront-child')], 403);
        }

        // Check rate limiting
        if (!$this->rate_limiter->check_rate_limit()) {
            wp_send_json_error(['message' => __('Too many requests. Please try again later.', 'storefront-child')], 429);
        }

        // Validate and sanitize search term
        $search_term = $this->validate_search_term($_POST['search_term'] ?? '');
        if (is_wp_error($search_term)) {
            wp_send_json_error(['message' => $search_term->get_error_message()], 400);
        }

        // Get search results with temperature data
        $cities_repo_with_temp = new CitiesRepositoryWithTemp();
        $results = $cities_repo_with_temp->search_cities_and_countries_with_temp($search_term);
        
        // Get the rendered table HTML
        $html = render_cities_table($results);
        
        wp_send_json_success([
            'html' => $html,
            'count' => count($results)
        ]);
    }

    /**
     * Validate search term input
     * 
     * @param string $search_term Raw search term
     * @return string|WP_Error Validated search term or error
     */
    private function validate_search_term($search_term) {
        $search_term = sanitize_text_field($search_term);
        
        // Check length limits
        if (strlen($search_term) > 100) {
            return new WP_Error('search_too_long', __('Search term is too long. Maximum 100 characters allowed.', 'storefront-child'));
        }
        
        // Check for potentially malicious content
        if (preg_match('/[<>"\']/', $search_term)) {
            return new WP_Error('invalid_characters', __('Search term contains invalid characters.', 'storefront-child'));
        }
        
        return $search_term;
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
                'searching' => __('Searching...', 'storefront-child'),
                'noResults' => __('No cities found.', 'storefront-child')
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
