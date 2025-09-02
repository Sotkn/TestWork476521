<?php
// inc/Repositories/class-weather-cache-repository.php
defined('ABSPATH') || exit;

/**
 * Weather Cache Repository Class
 *
 * A static class that manages weather cache data for cities.
 * Retrieves weather cache information from the database and provides
 * methods to access cached weather data for multiple cities.
 *
 * @package Storefront_Child
 * @subpackage Repositories
 * @since 1.0.0
 */
class WeatherCacheRepository {

	/**
	 * Retrieves weather cache data for a list of cities.
	 *
	 * Fetches the _weather_cache meta field for each city and returns
	 * a consolidated dictionary of weather data. Handles cases where
	 * weather cache may not exist yet.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 *
	 * @param array $cities Array of city objects from Cities_Repository.
	 *                      Each object should have: city_id, city_name, country_name, country_slug.
	 * @return array Dictionary of weather cache data indexed by city_id.
	 *               Returns empty array if no cities provided or no cache exists.
	 */
	public static function get_weather_cache_for_cities(array $cities): array {
		// Return empty array if no cities provided
		if (empty($cities)) {
			return [];
		}

		// Extract city IDs for meta query
		$city_ids = array_map(function($city) {
			return $city->city_id;
		}, $cities);

		// Get weather cache meta for all cities at once
		$weather_cache_data = self::get_weather_cache_meta($city_ids);

		// Build result dictionary
		$result = [];
		foreach ($cities as $city) {
			$city_id = $city->city_id;
			
			// Get cached weather data for this city
			$cached_weather = isset($weather_cache_data[$city_id]) ? $weather_cache_data[$city_id] : null;
			
			// Store in result dictionary
			$result[$city_id] = [
				'city_id' => $city_id,
				'city_name' => $city->city_name,
				'country_name' => $city->country_name,
				'country_slug' => $city->country_slug,
				'weather_cache' => $cached_weather,
				'has_cache' => !empty($cached_weather)
			];
		}

		return $result;
	}

	/**
	 * Retrieves weather cache meta data for multiple cities efficiently.
	 *
	 * Uses WordPress meta query to fetch _weather_cache meta for multiple
	 * cities in a single database query for better performance.
	 *
	 * @since 1.0.0
	 * @access private
	 * @static
	 *
	 * @param array $city_ids Array of city post IDs.
	 * @return array Associative array of city_id => weather_cache_data.
	 */
	private static function get_weather_cache_meta(array $city_ids): array {
		if (empty($city_ids)) {
			return [];
		}

		global $wpdb;

		// Prepare placeholders for IN clause
		$placeholders = implode(',', array_fill(0, count($city_ids), '%d'));

		// Query to get weather cache meta for multiple cities
		$sql = "
			SELECT 
				post_id,
				meta_value
			FROM {$wpdb->postmeta}
			WHERE meta_key = %s
			  AND post_id IN ({$placeholders})
		";

		// Prepare query parameters
		$params = array_merge(['_weather_cache'], $city_ids);
		
		// Execute query
		$results = $wpdb->get_results(
			$wpdb->prepare($sql, ...$params)
		);

		// Build result array
		$weather_cache = [];
		foreach ($results as $result) {
			$post_id = $result->post_id;
			$meta_value = $result->meta_value;
			
			// Decode JSON if it's valid, otherwise store as null
			$decoded_value = null;
			if (!empty($meta_value)) {
				$decoded_value = json_decode($meta_value, true);
				// If JSON decode fails, store original value
				if (json_last_error() !== JSON_ERROR_NONE) {
					$decoded_value = $meta_value;
				}
			}
			
			$weather_cache[$post_id] = $decoded_value;
		}

		return $weather_cache;
	}

	/**
	 * Retrieves weather cache for a single city.
	 *
	 * Convenience method to get weather cache for a single city.
	 * Useful when you only need data for one specific city.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 *
	 * @param int $city_id The city post ID.
	 * @return array|null Weather cache data for the city, or null if not found.
	 */
	public static function get_weather_cache_for_city(int $city_id) {
		$cities = [(object) [
			'city_id' => $city_id,
			'city_name' => '',
			'country_name' => '',
			'country_slug' => ''
		]];

		$result = self::get_weather_cache_for_cities($cities);
		
		return isset($result[$city_id]) ? $result[$city_id]['weather_cache'] : null;
	}

	/**
	 * Checks if a city has valid weather cache data.
	 *
	 * Determines whether a city has cached weather data that can be used.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 *
	 * @param int $city_id The city post ID.
	 * @return bool True if city has valid weather cache, false otherwise.
	 */
	public static function has_weather_cache(int $city_id): bool {
		$cache_data = self::get_weather_cache_for_city($city_id);
		return !empty($cache_data);
	}

	/**
	 * Gets a summary of weather cache status for multiple cities.
	 *
	 * Returns a summary showing which cities have cache and which don't,
	 * useful for determining which cities need fresh API calls.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 *
	 * @param array $cities Array of city objects from Cities_Repository.
	 * @return array Summary of cache status for cities.
	 */
	public static function get_cache_status_summary(array $cities): array {
		$weather_data = self::get_weather_cache_for_cities($cities);
		
		$summary = [
			'total_cities' => count($cities),
			'cached_cities' => 0,
			'uncached_cities' => 0,
			'cached_city_ids' => [],
			'uncached_city_ids' => []
		];

		foreach ($weather_data as $city_id => $data) {
			if ($data['has_cache']) {
				$summary['cached_cities']++;
				$summary['cached_city_ids'][] = $city_id;
			} else {
				$summary['uncached_cities']++;
				$summary['uncached_city_ids'][] = $city_id;
			}
		}

		return $summary;
	}
}
