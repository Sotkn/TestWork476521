<?php
// inc/Services/CityData.php
defined('ABSPATH') || exit;

/**
 * City Data Service Class
 * 
 * Provides city-related data services including temperature information
 * 
 * @package Storefront_Child
 * @version 1.0.0
 * @since 1.0.0
 */
class CityData {
    
    /**
     * Get temperature for a city
     * 
     * @param int $city_id The city ID
     * @return int Returns a realistic temperature between -10 and 35 degrees Celsius
     */
    public function get_temperature_in_celcius($city_id) {
        // Generate a realistic temperature based on city ID
        // This is a mock implementation - in production you'd call a weather API
        $seed = $city_id * 12345; // Create a seed based on city ID
        $temperature = ($seed % 45) - 10; // Range from -10 to 35 degrees Celsius
        
        return $temperature;
    }
}


