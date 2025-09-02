<?php
// inc/Services/class-weather-updater.php
defined('ABSPATH') || exit;



/**
 * Weather Updater Service Class
 * 
 * Handles updating weather data for cities by retrieving coordinates,
 * calling the weather API, and storing results in the weather cache.
 * 
 * @package Storefront_Child
 * @version 1.0.0
 * @since 1.0.0
 */
class WeatherUpdater {
    
    /**
     * Meta key for weather cache
     */
    private const META_KEY = '__weather_cache';
    
    /**
     * TTL for weather cache in seconds (1 hour)
     */
    private const CACHE_TTL = 3600;
    
    /**
     * Weather client instance
     */
    private $weather_client;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->weather_client = new Weather_Client();
    }
    
    /**
     * Update weather data for multiple cities
     * 
     * @param array $cities Array of city objects
     * @return void
     */
    public function update_weather_data(array $cities): void {
        if (empty($cities)) {
            return;
        }
        
        foreach ($cities as $city) {
            $this->update_weather_for_city($city);
        }
    }
    
    /**
     * Update weather data for a single city
     * 
     * @param object $city City object
     * @return void
     */
    private function update_weather_for_city($city): void {
        $city_id = $city->city_id ?? 0;
        
        if ($city_id <= 0) {
            return;
        }
        
        // Get coordinates for the city
        $latitude = get_post_meta($city_id, 'latitude', true);
        $longitude = get_post_meta($city_id, 'longitude', true);
        
        // Skip if coordinates are not available
        if (empty($latitude) || empty($longitude)) {
            $this->store_weather_cache($city_id, null, 'no_coordinates');
            return;
        }
        
        // Get weather data from API
        $weather_data = $this->weather_client->get_weather_by_coordinates($latitude, $longitude);
        
        if (is_wp_error($weather_data)) {
            // Store error status
            $this->store_weather_cache($city_id, null, 'api_error');
            return;
        }
        
        // Extract temperature from weather data
        $temperature_celsius = $weather_data['main']['temp'] ?? null;
        
        if ($temperature_celsius !== null) {
            // Store successful weather data
            $this->store_weather_cache($city_id, $temperature_celsius, 'success');
        } else {
            // Store error status if temperature not found
            $this->store_weather_cache($city_id, null, 'no_temperature');
        }
    }
    
    /**
     * Store weather cache data for a city
     * 
     * @param int $city_id City post ID
     * @param float|null $temperature_celsius Temperature in Celsius
     * @param string $status Request status
     * @return void
     */
    private function store_weather_cache(int $city_id, ?float $temperature_celsius, string $status): void {
        $cache_data = [
            'temperature_celsius' => $temperature_celsius,
            'timestamp' => time(),
            'ttl' => self::CACHE_TTL,
            'status' => $status
        ];
        
        update_post_meta($city_id, self::META_KEY, json_encode($cache_data));
    }
}
