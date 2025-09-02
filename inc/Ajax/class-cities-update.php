
<?php
// inc/Ajax/class-cities-update.php
defined('ABSPATH') || exit;

// Ensure WeatherCacheRepository is available
if (!class_exists('WeatherCacheRepository')) {
    require_once get_template_directory() . '/inc/Repositories/class-weather-cache-repository.php';
}

/**
 * Cities Update AJAX Handler Class
 * 
 * Handles all AJAX requests related to cities temperature update functionality
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
        $this->rate_limiter = new Rate_Limiter('cities_update_rate_limit', 10, 60);
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
        // Add debugging
        error_log('=== Starting handle_update_cities_status ===');
        error_log('POST data: ' . print_r($_POST, true));
        
        // Verify nonce for security
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'cities_search_nonce')) {
            error_log('Nonce verification failed');
            wp_send_json_error(['message' => __('Security check failed', 'storefront-child')], 403);
        }
        
        error_log('Nonce verification passed');

        // Check rate limiting
        error_log('Checking rate limiting...');
        if (!$this->rate_limiter->check_rate_limit()) {
            error_log('Rate limiting failed');
            wp_send_json_error(['message' => __('Too many requests. Please try again later.', 'storefront-child')], 429);
        }
        
        error_log('Rate limiting passed');

        // Validate city IDs
        error_log('Validating city IDs...');
        $city_ids = $this->validate_city_ids($_POST['city_ids'] ?? []);
        if (is_wp_error($city_ids)) {
            error_log('City IDs validation failed: ' . $city_ids->get_error_message());
            wp_send_json_error(['message' => $city_ids->get_error_message()], 400);
        }

        error_log('City IDs validation passed. City IDs: ' . implode(', ', $city_ids));

        if (empty($city_ids)) {
            error_log('No city IDs to process');
            wp_send_json_success(['message' => __('No cities to update.', 'storefront-child')]);
        }

        // Check request limit for this specific action
        error_log('Checking request limit...');
        $request_limit_key = 'cities_status_update_limit_' . $this->get_client_identifier();
        $current_requests = get_transient($request_limit_key) ?: 0;
        $max_requests_per_hour = 50; // Limit to 50 requests per hour
        
        error_log('Current requests: ' . $current_requests . ', Max: ' . $max_requests_per_hour);
        
        if ($current_requests >= $max_requests_per_hour) {
            error_log('Request limit exceeded');
            wp_send_json_error([
                'message' => __('Request limit exceeded. Please try again later.', 'storefront-child'),
                'status' => 'limit_exceeded'
            ], 429);
        }

        // Increment request counter
        set_transient($request_limit_key, $current_requests + 1, HOUR_IN_SECONDS);
        error_log('Request limit check passed, counter incremented');

        // Get weather cache data for the requested cities
        error_log('Getting weather cache data for cities...');
        $weather_cache_data = $this->get_weather_cache_for_cities($city_ids);
        error_log('Weather cache data retrieved for ' . count($weather_cache_data) . ' cities');
        error_log('Weather cache data details: ' . print_r($weather_cache_data, true));
        
        // Process each city and determine status
        $city_updates = [];
        $abort_cities = [];
        
        error_log('Starting to process ' . count($city_ids) . ' cities');
        
        foreach ($city_ids as $city_id) {
            error_log('Processing city ID: ' . $city_id);
            
            $city_data = $this->get_city_data($city_id);
            if (!$city_data) {
                error_log('Skipping city ID ' . $city_id . ' - no city data');
                continue;
            }

            $weather_cache = $weather_cache_data[$city_id] ?? null;
            $update_data = $this->process_city_status_update($city_id, $city_data, $weather_cache);
            
            if ($update_data['status'] === 'abort') {
                $abort_cities[] = $city_id;
                error_log('City ID ' . $city_id . ' added to abort list');
            } else {
                $city_updates[$city_id] = $update_data;
                error_log('City ID ' . $city_id . ' added to updates list with status: ' . $update_data['status']);
            }
        }
        
        error_log('Finished processing cities. Updates: ' . count($city_updates) . ', Aborted: ' . count($abort_cities));

        // Return the updates
        error_log('Sending JSON success response');
        wp_send_json_success([
            'city_updates' => $city_updates,
            'abort_cities' => $abort_cities,
            'message' => sprintf(
                __('Processed %d cities. %d updates available, %d cities aborted.', 'storefront-child'),
                count($city_ids),
                count($city_updates),
                count($abort_cities)
            )
        ]);
    }

    /**
     * Get weather cache data for specific cities
     * 
     * @param array $city_ids Array of city IDs
     * @return array Weather cache data keyed by city ID
     */
    private function get_weather_cache_for_cities(array $city_ids): array {
        error_log('get_weather_cache_for_cities called with city IDs: ' . implode(', ', $city_ids));
        
        if (empty($city_ids)) {
            error_log('No city IDs provided, returning empty array');
            return [];
        }

        // Use the WeatherCacheRepository instead of direct SQL query for consistency
        if (class_exists('WeatherCacheRepository')) {
            error_log('Using WeatherCacheRepository for consistency');
            
            // Convert city IDs to the format expected by WeatherCacheRepository
            $cities = [];
            foreach ($city_ids as $city_id) {
                $cities[] = ['city_id' => $city_id];
            }
            
            $weather_cache_data = WeatherCacheRepository::get_weather_cache_for_cities($cities);
            
            // Convert the result format to match what this method expects
            $cache_data = [];
            foreach ($weather_cache_data as $city_id => $city_data) {
                if (isset($city_data['weather_cache']) && is_array($city_data['weather_cache'])) {
                    $cache_data[$city_id] = $city_data['weather_cache'];
                }
            }
            
            error_log('Cache data prepared for ' . count($cache_data) . ' cities using WeatherCacheRepository');
            return $cache_data;
        }

        // Fallback to direct SQL query if WeatherCacheRepository is not available
        error_log('WeatherCacheRepository not available, falling back to direct SQL query');
        
        global $wpdb;
        $meta_key = '__weather_cache';
        
        $placeholders = implode(',', array_fill(0, count($city_ids), '%d'));
        $sql = "SELECT post_id, meta_value FROM {$wpdb->postmeta} 
                WHERE meta_key = %s AND post_id IN ($placeholders)";
        
        // Prepare the query with the meta key and city IDs
        $query_args = array_merge([$meta_key], $city_ids);
        $sql = $wpdb->prepare($sql, ...$query_args);
        
        error_log('Executing SQL query: ' . $sql);
        
        $results = $wpdb->get_results($sql);
        
        if ($wpdb->last_error) {
            error_log('Database error: ' . $wpdb->last_error);
        }
        
        error_log('Query results count: ' . count($results));
        
        $cache_data = [];
        foreach ($results as $row) {
            $city_id = (int) $row->post_id;
            $meta_value = $row->meta_value;
            
            if (!empty($meta_value)) {
                $decoded = json_decode($meta_value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $cache_data[$city_id] = $decoded;
                }
            }
        }
        
        error_log('Cache data prepared for ' . count($cache_data) . ' cities');
        return $cache_data;
    }

    /**
     * Get basic city data by ID
     * 
     * @param int $city_id City ID
     * @return object|null City data or null if not found
     */
    private function get_city_data(int $city_id) {
        error_log('get_city_data called for city ID: ' . $city_id);
        
        if (!class_exists('Cities_Repository')) {
            error_log('ERROR: Cities_Repository class not found in get_city_data for city ID: ' . $city_id);
            return null;
        }
        
        error_log('Cities_Repository class found, calling get_city_by_id');
        
        try {
            $city_data = Cities_Repository::get_city_by_id($city_id);
            if (!$city_data) {
                error_log('No city data returned for city ID: ' . $city_id);
            } else {
                error_log('City data retrieved successfully for city ID: ' . $city_id);
            }
            return $city_data;
        } catch (Exception $e) {
            error_log('Exception in get_city_data for city ID ' . $city_id . ': ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Process city status update and determine what to return
     * 
     * @param int $city_id City ID
     * @param object $city_data City data object
     * @param array|null $weather_cache Weather cache data
     * @return array Update data with status and temperature
     */
    private function process_city_status_update(int $city_id, $city_data, $weather_cache): array {
        error_log('process_city_status_update called for city ID: ' . $city_id);
        error_log('City data: ' . print_r($city_data, true));
        error_log('Weather cache: ' . print_r($weather_cache, true));
        
        // Check if we have valid weather cache
        if ($weather_cache && 
            isset($weather_cache['temperature_celsius']) && 
            $weather_cache['temperature_celsius'] !== null &&
            isset($weather_cache['status']) && 
            $weather_cache['status'] === 'valid') {
            
            // Check if cache is still fresh (within TTL)
            $timestamp = $weather_cache['timestamp'] ?? 0;
            $ttl = $weather_cache['ttl'] ?? 3600; // Default 1 hour
            $is_fresh = (time() - $timestamp) < $ttl;
            
            if ($is_fresh) {
                error_log('Returning valid status for city ID: ' . $city_id);
                return [
                    'status' => 'valid',
                    'temperature' => $weather_cache['temperature_celsius']
                ];
            }
        }

        // Check if we should abort this city (too many failed attempts)
        $abort_key = "city_abort_{$city_id}";
        $failed_attempts = get_transient($abort_key) ?: 0;
        
        if ($failed_attempts >= 3) { // Abort after 3 failed attempts
            return ['status' => 'abort'];
        }

        // Increment failed attempts
        set_transient($abort_key, $failed_attempts + 1, DAY_IN_SECONDS);

        // Return current status (will be 'expected' or 'expired')
        $current_status = $weather_cache['status'] ?? 'unavailable';
        $temperature = $weather_cache['temperature_celsius'] ?? null;
        
        error_log('Returning status for city ID ' . $city_id . ': ' . $current_status . ', temperature: ' . $temperature);
        
        return [
            'status' => $current_status,
            'temperature' => $temperature
        ];
    }

    /**
     * Get client identifier for rate limiting
     * 
     * @return string Client identifier
     */
    private function get_client_identifier(): string {
        $user_id = get_current_user_id();
        $ip = $this->get_client_ip();
        return $user_id ?: $ip;
    }

    /**
     * Get client IP address
     * 
     * @return string Client IP address
     */
    private function get_client_ip(): string {
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
     * Validate city IDs input
     * 
     * @param array $city_ids Raw city IDs array
     * @return array|WP_Error Validated city IDs array or error
     */
    private function validate_city_ids($city_ids) {
        if (!is_array($city_ids)) {
            return new WP_Error('invalid_city_ids', __('City IDs must be an array.', 'storefront-child'));
        }

        $validated_ids = [];
        foreach ($city_ids as $id) {
            $city_id = intval($id);
            if ($city_id > 0) {
                $validated_ids[] = $city_id;
            }
        }

        // Limit the number of cities that can be checked at once
        if (count($validated_ids) > 50) {
            return new WP_Error('too_many_cities', __('Too many cities requested. Maximum 50 allowed.', 'storefront-child'));
        }

        return $validated_ids;
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
