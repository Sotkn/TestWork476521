<?php
// inc/Services/class-cities-repository-with-temp.php
defined('ABSPATH') || exit;

// Ensure WeatherCacheRepository is available
if (!class_exists('WeatherCacheRepository')) {
    require_once get_template_directory() . '/inc/Repositories/class-weather-cache-repository.php';
}

// Ensure WeatherUpdateManager is available
if (!class_exists('WeatherUpdateManager')) {
    require_once get_template_directory() . '/inc/Services/class-weather-update-manager.php';
}

/**
 * Cities Repository With Temperature Service Class
 * 
 * Extends cities functionality by adding temperature data to each city.
 * This service acts as a facade that combines city data with weather information.
 * Weather updates are handled by the WeatherUpdateManager service.
 * 
 * @package Storefront_Child
 * @version 1.0.0
 * @since 1.0.0
 */
class CitiesRepositoryWithTemp {
    
    /**
     * @var WeatherUpdateManager
     */
    private $weather_manager;
    
    /**
     * Constructor
     * 
     * Initializes the service with a weather update manager.
     */
    public function __construct() {
        $this->weather_manager = new WeatherUpdateManager();
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
     * This method processes cities and adds weather information.
     * Weather updates are delegated to the WeatherUpdateManager.
     * 
     * @param array $cities Array of cities from repository
     * @return array Array of cities with temperature data added
     */
    private function add_temperature_to_cities(array $cities): array {
        if (empty($cities)) {
            return [];
        }
        
        $cities_with_temp = [];
        
        try {
            // Get weather data for all cities (including any necessary updates)
            $cities_weather_data = $this->weather_manager->process_cities_weather($cities);
            
            foreach ($cities as $city) {
                if (!is_object($city) && !is_array($city)) {
                    error_log('Invalid city data type: ' . gettype($city));
                    continue;
                }
                
                $city_with_temp = $this->prepare_city_data($city);
                
                // Get weather data for this city
                $city_id = $this->get_city_id($city);
                $city_weather = $cities_weather_data[$city_id] ?? [
                    'temperature_celsius' => null,
                    'status' => 'unknown'
                ];
                
                // Add temperature and cache status to city data
                $city_with_temp['temperature_celsius'] = $city_weather['temperature_celsius'];
                $city_with_temp['cache_status'] = $city_weather['status'];
                
                $cities_with_temp[] = (object) $city_with_temp;
            }
            
        } catch (Exception $e) {
            error_log('Error in add_temperature_to_cities: ' . $e->getMessage());
            return $this->add_temperature_to_cities_fallback($cities);
        }
        
        return $cities_with_temp;
    }
    
    /**
     * Fallback method when weather processing fails
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
