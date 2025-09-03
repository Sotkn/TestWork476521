<?php
/**
 * Cities Search AJAX Handler Class
 * 
 * Handles all AJAX requests related to cities search functionality including
 * rate limiting, input validation, and search result processing.
 * 
 * @package Storefront_Child
 * @version 1.0.0
 * @since 1.0.0
 * @author Storefront Child Theme
 */
defined('ABSPATH') || exit;

class CitiesSearch {
    
    /**
     * Rate limiter instance for controlling search request frequency
     * 
     * @var Rate_Limiter
     */
    private $rate_limiter;
    
    /**
     * Initialize the class and set up WordPress hooks
     * 
     * Sets up rate limiting (10 requests per 60 seconds) and initializes
     * all necessary WordPress hooks for AJAX functionality.
     */
    public function __construct() {
        $this->rate_limiter = new Rate_Limiter('cities_search_rate_limit', 10, 60);
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks for AJAX and asset management
     * 
     * Registers AJAX actions for both authenticated and non-authenticated users,
     * and sets up asset enqueuing for the cities search functionality.
     */
    private function init_hooks() {
        // Register AJAX actions for both logged-in and non-logged-in users
        add_action('wp_ajax_cities_search', [$this, 'handle_cities_search']);
        add_action('wp_ajax_nopriv_cities_search', [$this, 'handle_cities_search']);
        
        // Enqueue CSS and JavaScript assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_cities_search_assets']);
    }
    
    /**
     * Handle AJAX search request for cities
     * 
     * Processes search requests with security validation, rate limiting,
     * input sanitization, and returns formatted search results.
     * 
     * @return void Sends JSON response via wp_send_json_*
     */
    public function handle_cities_search() {
        // Check if request method is POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            wp_send_json_error(['message' => __('Invalid request method', 'storefront-child')], 405);
        }

        // Verify nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'cities_search_nonce')) {
            wp_send_json_error(['message' => __('Security check failed', 'storefront-child')], 403);
        }

        // Check rate limiting to prevent abuse
        if (!$this->rate_limiter->check_rate_limit()) {
            wp_send_json_error(['message' => __('Too many requests. Please try again later.', 'storefront-child')], 429);
        }

        // Validate and sanitize search term input
        $search_term = $this->validate_search_term($_POST['search_term'] ?? '');
        if (is_wp_error($search_term)) {
            wp_send_json_error(['message' => $search_term->get_error_message()], 400);
        }

        // Check if search term is empty after validation
        if (empty($search_term)) {
            wp_send_json_error(['message' => __('Search term cannot be empty', 'storefront-child')], 400);
        }

        try {
            // Get search results with temperature data from repository
            $cities_repo_with_temp = new CitiesRepositoryWithTemp();
            $results = $cities_repo_with_temp->search_cities_and_countries_with_temp($search_term);
            
            // Ensure results is an array
            if (!is_array($results)) {
                $results = [];
            }
            
            // Generate HTML table from search results
            $html = render_cities_table($results);
            
            // Return success response with HTML and result count
            wp_send_json_success([
                'html' => $html,
                'count' => count($results)
            ]);
        } catch (Exception $e) {
            // Log error for debugging (in production, you might want to log this)
            error_log('Cities search error: ' . $e->getMessage());
            wp_send_json_error(['message' => __('An error occurred while searching. Please try again.', 'storefront-child')], 500);
        }
    }

    /**
     * Validate and sanitize search term input
     * 
     * Performs comprehensive validation including length checks and
     * malicious character detection to ensure input security.
     * 
     * @param string $search_term Raw search term from user input
     * @return string|WP_Error Validated and sanitized search term or WP_Error on validation failure
     */
    private function validate_search_term($search_term) {
        // Check if search term is a string
        if (!is_string($search_term)) {
            return new WP_Error('invalid_type', __('Search term must be a string', 'storefront-child'));
        }
        
        $search_term = sanitize_text_field( wp_unslash( $search_term ) );
	    $search_term = trim( preg_replace('/\s+/u', ' ', $search_term) );
        
        // Check minimum length
        if (strlen($search_term) < 1) {
            return new WP_Error('search_too_short', __('Search term cannot be empty', 'storefront-child'));
        }
        
        // Check maximum length limit
        if ( function_exists('mb_strlen') ) {
            if ( mb_strlen($search_term, 'UTF-8') > 100 ) {
                return new WP_Error('search_too_long', __('Search term is too long. Maximum 100 characters allowed.', 'storefront-child'));
            }
        } else {
            if ( strlen($search_term) > 100 ) {
                return new WP_Error('search_too_long', __('Search term is too long. Maximum 100 characters allowed.', 'storefront-child'));
            }
        }
        
        
        // Check for excessive whitespace
        if (preg_match('/^\s+$/', $search_term)) {
            return new WP_Error('search_whitespace_only', __('Search term cannot consist only of whitespace', 'storefront-child'));
        }
        
        return trim($search_term);
    }

    /**
     * Enqueue cities search CSS and JavaScript assets
     * 
     * Loads necessary frontend assets only on the cities list page template
     * to optimize performance and avoid loading unnecessary resources.
     */
    public function enqueue_cities_search_assets() {
        if (is_page_template('page-templates/cities-list.php')) {
            // Enqueue JavaScript with jQuery dependency
            wp_enqueue_script(
                'cities-search',
                get_stylesheet_directory_uri() . '/assets/js/cities-search.js',
                ['jquery'],
                '1.0.0',
                true
            );

            // Localize script with AJAX configuration and localized strings
            wp_localize_script('cities-search', 'citiesSearchAjax', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('cities_search_nonce'),
                'searching' => __('Searching...', 'storefront-child'),
                'noResults' => __('No cities found.', 'storefront-child')
            ]);

            // Enqueue CSS styles for search functionality
            wp_enqueue_style(
                'cities-search',
                get_stylesheet_directory_uri() . '/assets/css/cities-search.css',
                [],
                '1.0.0'
            );
        }
    }
}

// Initialize the CitiesSearch class
new CitiesSearch();
