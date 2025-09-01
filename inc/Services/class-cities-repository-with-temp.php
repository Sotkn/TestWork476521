<?php
// inc/Services/class-cities-repository-with-temp.php
defined('ABSPATH') || exit;

/**
 * Cities Repository With Temperature Service Class
 * 
 * Extends cities functionality by adding temperature data to each city
 * 
 * @package Storefront_Child
 * @version 1.0.0
 * @since 1.0.0
 */
class CitiesRepositoryWithTemp {
    
    /**
     * City Data service instance
     * 
     * @var CityData
     */
    private $city_data;
    
    /**
     * Constructor
     * 
     * @param CityData $city_data City Data service instance
     */
    public function __construct(CityData $city_data = null) {
        $this->city_data = $city_data ?: new CityData();
    }
    
    /**
     * Get cities with countries and temperature data
     * 
     * @return array Array of cities with country and temperature information
     */
    public function get_cities_with_countries_and_temp(): array {
        
        $cities = Cities_Repository::get_cities_with_countries();
        
        return $this->add_temperature_to_cities($cities);
    }
    
    /**
     * Search cities and countries by term with temperature data
     * 
     * @param string $search_term Search term
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
     * @param array $cities Array of cities from repository
     * @return array Array of cities with temperature data added
     */
    private function add_temperature_to_cities(array $cities): array {
        $cities_with_temp = [];
        
        foreach ($cities as $city) {
            $city_with_temp = (array) $city;
            $city_with_temp['temperature_celsius'] = $this->city_data->get_temperature_in_celcius($city->city_id);
            
            $cities_with_temp[] = (object) $city_with_temp;
        }
        
        return $cities_with_temp;
    }
    
    /**
     * Flush cache from the base repository
     * 
     * @return void
     */
    public function flush_cache(): void {
        Cities_Repository::flush_cache();
    }
}
