<?php
// inc/Services/class-cities-repository-with-temp.php
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
 * Cities Repository With Temperature Service Class
 * 
 * Extends cities functionality by adding temperature data to each city.
 * This service acts as a facade that combines city data with weather information,
 * handling cache management and weather data updates.
 * 
 * @package Storefront_Child
 * @version 1.0.0
 * @since 1.0.0
 */
class CitiesRepositoryWithTemp {
    
    /**
     * Constructor
     * 
     * Initializes the service. 
     */
    public function __construct() {
        // No dependencies needed for now
    }
    
    /**
     * Get cities with countries and temperature data
     * 
     * Retrieves all cities with their country information and enriches them
     * with current temperature data from the weather cache.
     * 
     * @return array Array of cities with country and temperature information
     */
    public function get_cities_with_countries_and_temp(): array {
        if (!class_exists('Cities_Repository')) {
            error_log('Cities_Repository class not found');
            return [];
        }
        
        try {
            $cities = Cities_Repository::get_cities_with_countries();
            
            if (!is_array($cities)) {
                error_log('Cities_Repository::get_cities_with_countries() did not return an array');
                return [];
            }
            
            return $this->add_temperature_to_cities($cities);
        } catch (Exception $e) {
            error_log('Error in get_cities_with_countries_and_temp: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Search cities and countries by term with temperature data
     * 
     * Performs a search for cities and countries based on the provided search term
     * and enriches the results with temperature data.
     * 
     * @param string $search_term Search term to filter cities and countries
     * @return array Array of cities with country and temperature information
     */
    public function search_cities_and_countries_with_temp(string $search_term): array {
        if (!class_exists('Cities_Repository')) {
            error_log('Cities_Repository class not found');
            return [];
        }
        
        // Validate search term
        $search_term = trim($search_term);
        if (empty($search_term)) {
            return [];
        }
        
        try {
            $cities = Cities_Repository::search_cities_and_countries($search_term);
            
            if (!is_array($cities)) {
                error_log('Cities_Repository::search_cities_and_countries() did not return an array');
                return [];
            }
            
            return $this->add_temperature_to_cities($cities);
        } catch (Exception $e) {
            error_log('Error in search_cities_and_countries_with_temp: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Add temperature data to cities array
     * 
     * This is the core method that processes cities and adds weather information.
     * It handles cache management, expiration checks, and weather data updates.
     * 
     * @param array $cities Array of cities from repository
     * @return array Array of cities with temperature data added
     */
    private function add_temperature_to_cities(array $cities): array {
        if (empty($cities)) {
            return [];
        }
        
        if (!class_exists('WeatherUpdater')) {
            error_log('WeatherUpdater class not found');
            return $this->add_temperature_to_cities_fallback($cities);
        }
        
        $cities_with_temp = [];
        
        try {
            // Get weather cache data for all cities at once to minimize database queries
            $weather_cities_cache_data = WeatherCacheRepository::get_weather_cache_for_cities($cities);
            $weather_updater = new WeatherUpdater();
            
            foreach ($cities as $city) {
                if (!is_object($city) && !is_array($city)) {
                    error_log('Invalid city data type: ' . gettype($city));
                    continue;
                }
                
                $city_with_temp = $this->prepare_city_data($city);
                
                // Handle cache management and weather data retrieval for this city
                $city_cache = $this->handle_cache_for_city($city, $weather_cities_cache_data);
                
                // Only add to queue if we have a valid city ID
                $city_id = $this->get_city_id($city);
                if ($city_cache['status'] !== 'valid' || $city_cache['temperature_celsius'] === null) {
                    if ($city_id > 0) {
                        $city_cache['status'] = ($weather_updater->add_to_queue($city_id) ? 'expected' : 'expired');
                    } else {
                        $city_cache['status'] = 'invalid';
                    }
                }
                
                // Extract temperature from the weather info
                $temperature_celsius = $city_cache['temperature_celsius'] ?? null;
                
                // Add temperature and cache status to city data
                $city_with_temp['temperature_celsius'] = $temperature_celsius;
                $city_with_temp['cache_status'] = $city_cache['status'];
                
                $cities_with_temp[] = (object) $city_with_temp;
            }
            
            $weather_updater->execute_queue();
            
        } catch (Exception $e) {
            error_log('Error in add_temperature_to_cities: ' . $e->getMessage());
            return $this->add_temperature_to_cities_fallback($cities);
        }
        
        return $cities_with_temp;
    }
    
    /**
     * Fallback method when WeatherUpdater is not available
     * 
     * @param array $cities Array of cities
     * @return array Array of cities with default temperature data
     */
    private function add_temperature_to_cities_fallback(array $cities): array {
        $cities_with_temp = [];
        
        foreach ($cities as $city) {
            if (!is_object($city) && !is_array($city)) {
                continue;
            }
            
            $city_with_temp = $this->prepare_city_data($city);
            $city_with_temp['temperature_celsius'] = null;
            $city_with_temp['cache_status'] = 'unavailable';
            
            $cities_with_temp[] = (object) $city_with_temp;
        }
        
        return $cities_with_temp;
    }
    
    /**
     * Prepare city data for processing
     * 
     * @param mixed $city City data (object or array)
     * @return array Prepared city data
     */
    private function prepare_city_data($city): array {
        if (is_object($city)) {
            return (array) $city;
        }
        
        if (is_array($city)) {
            return $city;
        }
        
        return [];
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
     * This method orchestrates the cache checking, expiration validation,
     * and weather data update process for a single city.
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
            $temp_and_status = $this->check_cache_expiration($city, $city_cache);
            return $temp_and_status;
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
     * Compares the cache timestamp with TTL to determine if the cache
     * is still valid. If expired, triggers a weather data update.
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

    /**
     * Flush cache from the base repository
     * 
     * Clears all cached data from the Cities_Repository to ensure
     * fresh data is retrieved on next request.
     * 
     * @return void
     */
    public function flush_cache(): void {
        if (class_exists('Cities_Repository')) {
            try {
                Cities_Repository::flush_cache();
            } catch (Exception $e) {
                error_log('Error flushing cache: ' . $e->getMessage());
            }
        } else {
            error_log('Cities_Repository class not found when trying to flush cache');
        }
    }

    /**
     * Get a single city with temperature data by ID
     * 
     * Retrieves a specific city by ID and enriches it with temperature data
     * from the weather cache.
     * 
     * @param int $city_id The city ID to retrieve
     * @return object|null City object with temperature data or null if not found
     */
    public function get_city_with_temp_by_id(int $city_id): ?object {
        // Validate input
        if ($city_id <= 0) {
            error_log('Invalid city ID provided: ' . $city_id);
            return null;
        }
        
        if (!class_exists('Cities_Repository')) {
            error_log('Cities_Repository class not found');
            return null;
        }
        
        try {
            $city = Cities_Repository::get_city_by_id($city_id);
            if (!$city) {
                return null;
            }
            
            // Convert to array to match the expected format
            $city_array = [
                'city_id' => $city->city_id ?? $city_id,
                'city_name' => $city->city_name ?? '',
                'country_name' => $city->country_name ?? '',
                'country_slug' => $city->country_slug ?? ''
            ];
            
            $cities_with_temp = $this->add_temperature_to_cities([(object)$city_array]);
            
            return !empty($cities_with_temp) ? $cities_with_temp[0] : null;
            
        } catch (Exception $e) {
            error_log('Error in get_city_with_temp_by_id: ' . $e->getMessage());
            return null;
        }
    }
}
