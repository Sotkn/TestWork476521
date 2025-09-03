
<?php
/**
 * Cities Update AJAX Handler Class
 * 
 * Handles AJAX requests for updating city status information by checking
 * weather cache data and returning formatted status responses.
 * 
 * @package Storefront_Child
 * @version 1.0.1
 * @since 1.0.0
 */

// Prevent direct access
defined('ABSPATH') || exit;

// Include required dependencies
require_once get_stylesheet_directory() . '/inc/Services/class-rate-limiter.php';

class CitiesUpdate {
    
    /**
     * Rate limiter instance for controlling request frequency
     * 
     * @var Rate_Limiter
     */
    private $rate_limiter;
    
    /**
     * Nonce action for AJAX security verification
     * 
     * @var string
     */
    private const NONCE_ACTION = 'cities_update_nonce';
    
    /**
     * Constructor - initializes the class and sets up rate limiting
     */
    public function __construct() {
        // Initialize rate limiter: 50 requests per 5 minutes
        $this->rate_limiter = new Rate_Limiter('cities_update', 50, 300);
        
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks and actions
     */
    private function init_hooks() {
        // Register AJAX handlers for both logged-in and non-logged-in users
        add_action('wp_ajax_update_cities_status', [$this, 'handle_update_cities_status']);
        add_action('wp_ajax_nopriv_update_cities_status', [$this, 'handle_update_cities_status']);
        
        // Enqueue required JavaScript and CSS assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_cities_update_assets']);
    }

    /**
     * Handle AJAX request to update cities status
     * 
     * Processes city IDs, validates them, and returns formatted status data
     * from weather cache information.
     */
    public function handle_update_cities_status() {
        // Verify nonce for security
        if (!wp_verify_nonce($_POST['nonce'] ?? '', self::NONCE_ACTION)) {
            wp_send_json_error([
                'message' => 'Security check failed. Please refresh the page and try again.',
                'code' => 'invalid_nonce'
            ]);
            return;
        }
        
        // Check rate limit before processing
        if (!$this->rate_limiter->check_rate_limit()) {
            $remaining_time = $this->rate_limiter->get_reset_time();
            wp_send_json_error([
                'message' => 'Rate limit exceeded. Please try again later.',
                'reset_time' => $remaining_time,
                'remaining_requests' => 0,
                'code' => 'rate_limit_exceeded'
            ]);
            return;
        }
        
        // Get and validate city IDs from POST data
        $city_ids = $this->validate_and_sanitize_city_ids($_POST['city_ids'] ?? []);
        
        if (empty($city_ids)) {
            wp_send_json_error([
                'message' => 'No valid city IDs provided.',
                'code' => 'no_city_ids'
            ]);
            return;
        }
        
        // Generate status list for each city
        $city_status_list = $this->generateCityStatusList($city_ids);
        
        // Send the city status list as response
        wp_send_json_success([
            'message' => 'City status list generated successfully',
            'city_ids' => $city_ids,
            'city_status_list' => $city_status_list
        ]);
    }

    /**
     * Validate and sanitize city IDs from POST data
     * 
     * Ensures all city IDs are valid integers, exist in the database,
     * and belong to the 'cities' post type.
     * 
     * @param mixed $city_ids_raw Raw city IDs from POST data
     * @return array Array of validated and sanitized city IDs
     */
    private function validate_and_sanitize_city_ids($city_ids_raw): array {
        $validated_ids = [];
        
        // Ensure we have an array
        if (!is_array($city_ids_raw)) {
            return [];
        }
        
        foreach ($city_ids_raw as $city_id) {
            // Convert to integer and validate
            $city_id_int = intval($city_id);
            
            // Check if it's a valid positive integer
            if ($city_id_int > 0) {
                // Verify the post exists and is of the correct type
                $post = get_post($city_id_int);
                if ($post && $post->post_type === 'cities') {
                    $validated_ids[] = $city_id_int;
                }
            }
        }
        
        return $validated_ids;
    }

    /**
     * Generate a list with statuses for each city from meta data
     * 
     * Retrieves weather cache information for each city and extracts
     * status and temperature data for display.
     * 
     * @param array $city_ids Array of validated city IDs
     * @return array Array with city_id => status data mapping
     */
    private function generateCityStatusList(array $city_ids): array {
        $city_status_list = [];
        
        foreach ($city_ids as $city_id) {
            // Get the weather cache meta for this city
            $cache_meta = get_post_meta($city_id, '__weather_cache', true);
            
            if ($cache_meta) {
                $status_data = $this->extractStatusAndTemperatureFromCache($cache_meta);
                
                if ($status_data !== null) {
                    $city_status_list[$city_id] = $status_data;
                }
            }
        }
        
        return $city_status_list;
    }

    /**
     * Extract status and temperature from cache meta data
     * 
     * Handles multiple cache data formats (array, JSON string) and extracts
     * weather status and temperature information for display.
     * 
     * @param mixed $cache_meta Cache meta data (array or JSON string)
     * @return array|null Status data array with 'status' and 'temperature' keys, or null if not found
     */
    private function extractStatusAndTemperatureFromCache($cache_meta): ?array {
        $status_data = ['status' => null, 'temperature' => null];
        
        // If it's already an array, look for status and temperature directly
        if (is_array($cache_meta)) {
            if (isset($cache_meta['status'])) {
                $status_data['status'] = sanitize_text_field($cache_meta['status']);
            }
            
            if (isset($cache_meta['temperature_celsius'])) {
                $status_data['temperature'] = floatval($cache_meta['temperature_celsius']);
            }
            
            // Check for nested weather_cache structure
            if (isset($cache_meta['weather_cache']) && is_array($cache_meta['weather_cache'])) {
                if (isset($cache_meta['weather_cache']['status'])) {
                    $status_data['status'] = sanitize_text_field($cache_meta['weather_cache']['status']);
                }
                
                if (isset($cache_meta['weather_cache']['temperature_celsius'])) {
                    $status_data['temperature'] = floatval($cache_meta['weather_cache']['temperature_celsius']);
                }
            }
        }
        
        // If it's a string, try to decode JSON
        if (is_string($cache_meta)) {
            $decoded_cache = json_decode($cache_meta, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded_cache)) {
                if (isset($decoded_cache['status'])) {
                    $status_data['status'] = sanitize_text_field($decoded_cache['status']);
                }
                
                if (isset($decoded_cache['temperature_celsius'])) {
                    $status_data['temperature'] = floatval($decoded_cache['temperature_celsius']);
                }
                
                // Check for nested weather_cache structure
                if (isset($decoded_cache['weather_cache']) && is_array($decoded_cache['weather_cache'])) {
                    if (isset($decoded_cache['weather_cache']['status'])) {
                        $status_data['status'] = sanitize_text_field($decoded_cache['weather_cache']['status']);
                    }
                    
                    if (isset($decoded_cache['weather_cache']['temperature_celsius'])) {
                        $status_data['temperature'] = floatval($decoded_cache['weather_cache']['temperature_celsius']);
                    }
                }
            }
        }
        
        // Only return data if we have a status
        if ($status_data['status'] !== null) {
            return $status_data;
        }
        
        return null;
    }

    /**
     * Enqueue cities update assets for the cities list page
     * 
     * Loads the required JavaScript file and localizes AJAX data
     * only on the cities list page template.
     */
    
    public function enqueue_cities_update_assets() {
        if (!is_admin()) {
            wp_enqueue_script(
                'cities-status-update',
                get_stylesheet_directory_uri() . '/assets/js/cities-status-update.js',
                ['jquery'],
                '1.0.1',
                true
            );

            wp_localize_script('cities-status-update', 'citiesStatusUpdateAjax', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce(self::NONCE_ACTION)
            ]);
        }
    }
}

// Initialize the class
new CitiesUpdate();
