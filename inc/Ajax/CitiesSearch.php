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
     * Rate limiting options
     */
    private const RATE_LIMIT_KEY = 'cities_search_rate_limit';
    private const RATE_LIMIT_MAX = 10; // Max requests per minute
    private const RATE_LIMIT_WINDOW = 60; // 1 minute window
    
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
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'cities_search_nonce')) {
            wp_send_json_error(['message' => __('Security check failed', 'storefront-child')], 403);
        }

        // Check rate limiting
        if (!$this->check_rate_limit()) {
            wp_send_json_error(['message' => __('Too many requests. Please try again later.', 'storefront-child')], 429);
        }

        // Validate and sanitize search term
        $search_term = $this->validate_search_term($_POST['search_term'] ?? '');
        if (is_wp_error($search_term)) {
            wp_send_json_error(['message' => $search_term->get_error_message()], 400);
        }

        // Get search results
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
     * Check rate limiting for the current user
     * 
     * @return bool True if within rate limit, false otherwise
     */
    private function check_rate_limit() {
        $user_id = get_current_user_id();
        $ip = $this->get_client_ip();
        $key = self::RATE_LIMIT_KEY . '_' . ($user_id ?: $ip);
        
        $requests = get_transient($key);
        if ($requests === false) {
            $requests = 1;
            set_transient($key, $requests, self::RATE_LIMIT_WINDOW);
            return true;
        }
        
        if ($requests >= self::RATE_LIMIT_MAX) {
            return false;
        }
        
        set_transient($key, $requests + 1, self::RATE_LIMIT_WINDOW);
        return true;
    }
    
    /**
     * Get client IP address
     * 
     * @return string Client IP address
     */
    private function get_client_ip() {
        $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
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
