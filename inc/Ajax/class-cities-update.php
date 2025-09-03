
<?php
// inc/Ajax/class-cities-update.php
defined('ABSPATH') || exit;

// Require the Rate_Limiter class
require_once get_stylesheet_directory() . '/inc/Services/class-rate-limiter.php';

/**
 * Cities Update AJAX Handler Class
 * 
 * Simplified version that checks city cache and logs it
 * 
 * @package Storefront_Child
 * @version 1.0.0
 * @since 1.0.0
 */
class CitiesUpdate {
    
    /**
     * Rate limiter instance
     */
    private $rate_limiter;
    
    /**
     * Initialize the class
     */
    public function __construct() {
        // Initialize rate limiter with larger limits for city updates
        // Allow 50 requests per 5 minutes (300 seconds)
        $this->rate_limiter = new Rate_Limiter('cities_update', 50, 300);
        
        $this->init_hooks();
    }
    
    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Register cities status update action
        add_action('wp_ajax_update_cities_status', [$this, 'handle_update_cities_status']);
        add_action('wp_ajax_nopriv_update_cities_status', [$this, 'handle_update_cities_status']);
        
        // Enqueue assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_cities_update_assets']);
    }

    /**
     * Handle AJAX request to update cities status
     */
    public function handle_update_cities_status() {
        // Check rate limit before processing
        if (!$this->rate_limiter->check_rate_limit()) {
            $remaining_time = $this->rate_limiter->get_reset_time();
            wp_send_json_error([
                'message' => 'Rate limit exceeded. Please try again later.',
                'reset_time' => $remaining_time,
                'remaining_requests' => 0
            ]);
            return;
        }
        
        // Log the received city IDs
        error_log('=== Cities Update AJAX Request ===');
        error_log('POST data: ' . print_r($_POST, true));
        
        // Get city IDs from POST data
        $city_ids = $_POST['city_ids'] ?? [];
        
        // Log the city IDs
        if (!empty($city_ids)) {
            error_log('Received city IDs: ' . implode(', ', $city_ids));
            
            // Generate status list for each city and log it
            $city_status_list = $this->generateCityStatusList($city_ids);
            
            // Log the generated status list
            error_log('Generated city status list: ' . print_r($city_status_list, true));
            
        } else {
            error_log('No city IDs received');
        }
        
        // Send the city status list as response
        wp_send_json_success([
            'message' => 'City status list generated and logged',
            'city_ids' => $city_ids,
            'city_status_list' => $city_status_list ?? []
        ]);
    }

    /**
     * Generate a list with statuses for each city from meta data
     * 
     * @param array $city_ids Array of city IDs
     * @return array Array with city_id => status data mapping
     */
    private function generateCityStatusList(array $city_ids): array {
        $city_status_list = [];
        
        error_log('=== Generating city status list ===');
        
        foreach ($city_ids as $city_id) {
            error_log("Processing city ID: {$city_id}");
            
            // Get the weather cache meta for this city
            $cache_meta = get_post_meta($city_id, '__weather_cache', true);
            
            if ($cache_meta) {
                error_log("City {$city_id} has cache meta, extracting status and temperature");
                
                $status_data = $this->extractStatusAndTemperatureFromCache($cache_meta);
                
                if ($status_data !== null) {
                    $city_status_list[$city_id] = $status_data;
                    error_log("City {$city_id} data: " . print_r($status_data, true));
                } else {
                    error_log("City {$city_id} status could not be extracted from cache");
                }
            } else {
                error_log("City {$city_id} has no cache meta - not adding to status list");
            }
        }
        
        error_log("Final city status list contains " . count($city_status_list) . " cities");
        return $city_status_list;
    }

    /**
     * Extract status and temperature from cache meta data
     * 
     * @param mixed $cache_meta Cache meta data
     * @return array|null Status data array or null if not found
     */
    private function extractStatusAndTemperatureFromCache($cache_meta): ?array {
        $status_data = ['status' => null, 'temperature' => null];
        
        // If it's already an array, look for status and temperature directly
        if (is_array($cache_meta)) {
            if (isset($cache_meta['status'])) {
                $status_data['status'] = $cache_meta['status'];
            }
            
            if (isset($cache_meta['temperature_celsius'])) {
                $status_data['temperature'] = $cache_meta['temperature_celsius'];
            }
            
            // Check for nested weather_cache structure
            if (isset($cache_meta['weather_cache']) && is_array($cache_meta['weather_cache'])) {
                if (isset($cache_meta['weather_cache']['status'])) {
                    $status_data['status'] = $cache_meta['weather_cache']['status'];
                }
                
                if (isset($cache_meta['weather_cache']['temperature_celsius'])) {
                    $status_data['temperature'] = $cache_meta['weather_cache']['temperature_celsius'];
                }
            }
        }
        
        // If it's a string, try to decode JSON
        if (is_string($cache_meta)) {
            $decoded_cache = json_decode($cache_meta, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                if (isset($decoded_cache['status'])) {
                    $status_data['status'] = $decoded_cache['status'];
                }
                
                if (isset($decoded_cache['temperature_celsius'])) {
                    $status_data['temperature'] = $decoded_cache['temperature_celsius'];
                }
                
                // Check for nested weather_cache structure
                if (isset($decoded_cache['weather_cache']) && is_array($decoded_cache['weather_cache'])) {
                    if (isset($decoded_cache['weather_cache']['status'])) {
                        $status_data['status'] = $decoded_cache['weather_cache']['status'];
                    }
                    
                    if (isset($decoded_cache['weather_cache']['temperature_celsius'])) {
                        $status_data['temperature'] = $decoded_cache['weather_cache']['temperature_celsius'];
                    }
                }
            } else {
                error_log('JSON decode error: ' . json_last_error_msg());
            }
        }
        
        // Only return data if we have a status
        if ($status_data['status'] !== null) {
            // If status is 'valid', ensure we have temperature
            if ($status_data['status'] === 'valid' && $status_data['temperature'] === null) {
                error_log('Status is valid but no temperature found, setting temperature to null');
            }
            
            return $status_data;
        }
        
        error_log('Could not extract status from cache meta: ' . print_r($cache_meta, true));
        return null;
    }

    /**
     * Enqueue cities update assets
     */
    public function enqueue_cities_update_assets() {
        if (is_page_template('page-templates/cities-list.php')) {
            wp_enqueue_script(
                'cities-status-update',
                get_stylesheet_directory_uri() . '/assets/js/cities-status-update.js',
                ['jquery'],
                '1.0.0',
                true
            );

            wp_localize_script('cities-status-update', 'citiesStatusUpdateAjax', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('cities_search_nonce')
            ]);
        }
    }
}

// Initialize the class
new CitiesUpdate();
