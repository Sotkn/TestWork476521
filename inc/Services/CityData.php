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
     * @return int Returns the city ID (fake implementation)
     */
    public function get_temperature_in_celcius($city_id) {
        return $city_id;
    }
}
