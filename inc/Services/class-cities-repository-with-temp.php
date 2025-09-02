<?php
// inc/Services/class-cities-repository-with-temp.php
defined('ABSPATH') || exit;

// Ensure WeatherCacheRepository is available
if (!class_exists('WeatherCacheRepository')) {
    require_once get_template_directory() . '/inc/Repositories/class-weather-cache-repository.php';
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
     * Initializes the service. CityData dependency removed as it's not used.
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
        
        $cities = Cities_Repository::get_cities_with_countries();
        
        return $this->add_temperature_to_cities($cities);
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
        
        $cities = Cities_Repository::search_cities_and_countries($search_term);
        
        return $this->add_temperature_to_cities($cities);
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
        
        $cities_with_temp = [];
        
        // Get weather cache data for all cities at once to minimize database queries
        $weather_cities_cache_data = WeatherCacheRepository::get_weather_cache_for_cities($cities);
        $weather_updater = new WeatherUpdater();
        
        foreach ($cities as $city) {
            $city_with_temp = (array) $city;
            
            // Initialize temperature variable
            $temperature_celsius = null;

            // Handle cache management and weather data retrieval for this city
            $city_cache = $this->handle_cache_for_city($city, $weather_cities_cache_data);
            
            if ($city_cache['status'] != 'valid' || $city_cache['temperature_celsius'] === null) {
                $city_cache['status'] = ($weather_updater->add_to_queue($city->city_id) ? 'expected' : 'expired');
            }
            // Extract temperature from the weather info
            $temperature_celsius = $city_cache['temperature_celsius'] ?? null;
            
            // Add temperature and cache status to city data
            $city_with_temp['temperature_celsius'] = $temperature_celsius;
            $city_with_temp['cache_status'] = $city_cache['status'];
            
            $cities_with_temp[] = (object) $city_with_temp;
        }
        
        $weather_updater->execute_queue();
        
        
        return $cities_with_temp;
    }
    
    /**
     * Handle cache management for a specific city
     * 
     * This method orchestrates the cache checking, expiration validation,
     * and weather data update process for a single city.
     * 
     * @param object $city City object containing city information
     * @param array $weather_cache_data Array of weather cache data for all cities
     * @return array Array containing temperature and status information
     */
    private function handle_cache_for_city($city, array $weather_cache_data): array {
        // Check if cache exists for this city
        $city_cache = $this->cache_if_exist($city, $weather_cache_data);
        
        if ($city_cache) {
            // Cache exists, check if it's still valid
            $temp_and_status = $this->check_cache_expiration($city, $city_cache);
            return $temp_and_status;
        } else {
            // No cache exists, request fresh weather data
            return ['temperature_celsius' => null, 'status' => 'unavailable'];
        }
    }
    
    /**
     * Check if weather cache exists for a specific city
     * 
     * @param object $city City object
     * @param array $weather_cache_data Array of weather cache data
     * @return array|null City cache data if exists, null otherwise
     */
    private function cache_if_exist($city, array $weather_cache_data): ?array {
        $id = $city->city_id ?? 0;
    
        if (
            $id > 0 &&
            isset($weather_cache_data[$id]['weather_cache']) &&
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
     * @param object $city City object
     * @param array $city_cache Cache data for the specific city
     * @return array Array containing temperature and status
     */
    private function check_cache_expiration($city, $city_cache): array {
        $timestamp = $city_cache['timestamp'] ?? 0;
        $ttl = $city_cache['ttl'] ?? 0;
        $expired = ($timestamp + $ttl) < time();
    
        $temp = $city_cache['temperature_celsius'] ?? null;
        $original_status = $city_cache['status'] ?? 'unknown';
    
        if ($expired) {
            
            return ['temperature_celsius' => $temp, 'status' => 'expired'];
        }
    
        
        if ($original_status === 'success') {
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
            Cities_Repository::flush_cache();
        } else {
            error_log('Cities_Repository class not found when trying to flush cache');
        }
    }
}
