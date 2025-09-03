<?php
// inc/Services/class-weather-update-manager.php
defined('ABSPATH') || exit;

// Ensure WeatherCacheRepository is available
if (!class_exists('WeatherCacheRepository')) {
    require_once get_template_directory() . '/inc/Repositories/class-weather-cache-repository.php';
}

// Ensure WeatherUpdater is available
if (!class_exists('WeatherUpdater')) {
    require_once get_template_directory() . '/inc/Services/class-weather-updater.php';
}

/**
 * Weather Update Manager Service Class
 * 
 * Handles all weather cache management, expiration checks, and update operations.
 * This service is responsible for determining when weather data needs updating
 * and managing the update queue.
 * 
 * @package Storefront_Child
 * @version 1.0.0
 * @since 1.0.0
 */
class WeatherUpdateManager {
    
    /**
     * Constructor
     */
    public function __construct() {
        // No dependencies needed for now
    }
    
    /**
     * Process weather data for cities and manage updates
     * 
     * @param array $cities Array of cities to process
     * @return array Array of weather data for each city
     */
    public function process_cities_weather(array $cities): array {
        if (empty($cities)) {
            return [];
        }
        
        if (!class_exists('WeatherUpdater')) {
            error_log('WeatherUpdater class not found');
            return $this->get_fallback_weather_data($cities);
        }
        
        try {
            // Get weather cache data for all cities at once to minimize database queries
            $weather_cities_cache_data = WeatherCacheRepository::get_weather_cache_for_cities($cities);
            $weather_updater = new WeatherUpdater();
            
            $cities_weather_data = [];
            
            foreach ($cities as $city) {
                if (!is_object($city) && !is_array($city)) {
                    error_log('Invalid city data type: ' . gettype($city));
                    continue;
                }
                
                // Handle cache management and weather data retrieval for this city
                $city_weather = $this->handle_cache_for_city($city, $weather_cities_cache_data);
                
                // Only add to queue if we have a valid city ID
                $city_id = $this->get_city_id($city);
                if ($city_weather['status'] !== 'valid' || $city_weather['temperature_celsius'] === null) {
                    if ($city_id > 0) {
                        $city_weather['status'] = ($weather_updater->add_to_queue($city_id) ? 'expected' : 'expired');
                    } else {
                        $city_weather['status'] = 'invalid';
                    }
                }
                
                $cities_weather_data[$city_id] = $city_weather;
            }
            
            $weather_updater->execute_queue();
            
            return $cities_weather_data;
            
        } catch (Exception $e) {
            error_log('Error in process_cities_weather: ' . $e->getMessage());
            return $this->get_fallback_weather_data($cities);
        }
    }
    
    /**
     * Get fallback weather data when WeatherUpdater is not available
     * 
     * @param array $cities Array of cities
     * @return array Array of fallback weather data
     */
    private function get_fallback_weather_data(array $cities): array {
        $fallback_data = [];
        
        foreach ($cities as $city) {
            if (!is_object($city) && !is_array($city)) {
                continue;
            }
            
            $city_id = $this->get_city_id($city);
            $fallback_data[$city_id] = [
                'temperature_celsius' => null,
                'status' => 'unavailable'
            ];
        }
        
        return $fallback_data;
    }
    
    /**
     * Get city ID safely
     * 
     * @param mixed $city City data
     * @return int City ID or 0 if invalid
     */
    private function get_city_id($city): int {
        if (is_object($city)) {
            return (int) ($city->city_id ?? 0);
        }
        
        if (is_array($city)) {
            return (int) ($city['city_id'] ?? 0);
        }
        
        return 0;
    }
    
    /**
     * Handle cache management for a specific city
     * 
     * @param mixed $city City object or array containing city information
     * @param array $weather_cache_data Array of weather cache data for all cities
     * @return array Array containing temperature and status information
     */
    private function handle_cache_for_city($city, array $weather_cache_data): array {
        // Check if cache exists for this city
        $city_cache = $this->cache_for_city_if_exist($city, $weather_cache_data);
        
        if ($city_cache) {
            // Cache exists, check if it's still valid
            return $this->check_cache_expiration($city, $city_cache);
        } else {
            // No cache exists, request fresh weather data
            return ['temperature_celsius' => null, 'status' => 'no_cache'];
        }
    }
    
    /**
     * Check if weather cache exists for a specific city
     * 
     * @param mixed $city City object or array
     * @param array $weather_cache_data Array of weather cache data
     * @return array|null City cache data if exists, null otherwise
     */
    private function cache_for_city_if_exist($city, array $weather_cache_data): ?array {
        $id = $this->get_city_id($city);
    
        if (
            $id > 0 &&
            isset($weather_cache_data[$id]['weather_cache']) &&
            is_array($weather_cache_data[$id]['weather_cache']) &&
            !empty($weather_cache_data[$id]['weather_cache'])
        ) {
            return $weather_cache_data[$id]['weather_cache'];
        }
        
        return null;
    }
    
    /**
     * Check if the cache for a city has expired
     * 
     * @param mixed $city City object or array
     * @param array $city_cache Cache data for the specific city
     * @return array Array containing temperature and status
     */
    private function check_cache_expiration($city, array $city_cache): array {
        $timestamp = (int) ($city_cache['timestamp'] ?? 0);
        $ttl = (int) ($city_cache['ttl'] ?? 0);
        $expired = ($timestamp + $ttl) < time();
    
        $temp = $city_cache['temperature_celsius'] ?? null;
        $original_status = $city_cache['status'] ?? 'unknown';
    
        if ($expired) {
            return ['temperature_celsius' => $temp, 'status' => 'expired'];
        }
    
        if ($original_status === 'valid') {
            return ['temperature_celsius' => $temp, 'status' => 'valid'];
        }
    
        return ['temperature_celsius' => $temp, 'status' => $original_status];
    }
}
