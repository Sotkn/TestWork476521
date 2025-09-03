<?php
// inc/Repositories/class-weather-cache-repository.php
defined('ABSPATH') || exit;

/**
 * Weather Cache Repository (simplified)
 *
 * Public method: get_weather_cache_for_cities($cities)
 * Returns an array keyed by city_id:
 * [
 *   <city_id> => [
 *     'city_name'     => (string),
 *     'weather_cache' => (array|null|string) // decoded JSON, null, or raw string if JSON is invalid
 *   ],
 *   ...
 * ]
 *
 * Expects $cities as an array of objects/arrays with:
 * - city_id   (int, required)
 * - city_name (string, optional)
 */
class WeatherCacheRepository {

	/** Meta key for weather cache */
	private const META_KEY = '__weather_cache';

	/**
	 * Retrieve weather cache for multiple cities.
	 *
	 * @param array $cities Array of city objects/arrays.
	 * @return array
	 */
	public static function get_weather_cache_for_cities(array $cities): array {
		if (empty($cities)) {
			return [];
		}

		// Normalize input and collect IDs
		$result = [];
		$ids    = [];

		foreach ($cities as $c) {
			$city_id = (int) (is_object($c) ? ($c->city_id ?? 0) : ($c['city_id'] ?? 0));
			if ($city_id <= 0) {
				continue;
			}
			$city_name = (string) (is_object($c) ? ($c->city_name ?? '') : ($c['city_name'] ?? ''));

			$ids[] = $city_id;
			$result[$city_id] = [
				'city_name'     => $city_name,
				'weather_cache' => null,
			];
		}

		if (empty($ids)) {
			return $result;
		}

		// Fetch meta for all IDs in one query
		$raw_meta = self::fetch_meta_for_ids($ids);

		foreach ($raw_meta as $post_id => $meta_value) {
			$decoded = null;
			if ($meta_value !== null && $meta_value !== '') {
				$decoded = json_decode($meta_value, true);
				if (json_last_error() !== JSON_ERROR_NONE) {
					$decoded = $meta_value; // fallback to raw string
				}
			}
			if (isset($result[$post_id])) {
				$result[$post_id]['weather_cache'] = $decoded;
			}
		}
		
		return $result;
	}

	/**
	 * Flush weather cache for all cities (only for 'cities' post type).
	 *
	 * @return int Number of cache entries deleted.
	 */
	public static function flush_weather_cache_for_all_cities(): int {
		global $wpdb;

		// Delete weather cache only for posts of type 'cities'
		$deleted = $wpdb->query($wpdb->prepare("
			DELETE pm FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
			WHERE pm.meta_key = %s 
			AND p.post_type = %s
		", self::META_KEY, 'cities'));

		if ($deleted === false) {
			error_log('Failed to flush weather cache: ' . $wpdb->last_error);
			return 0;
		}

		error_log("Weather cache flushed for cities post type: {$deleted} entries deleted");
		return (int) $deleted;
	}

	/**
	 * Fetch raw meta values for multiple post IDs in a single query.
	 *
	 * @param int[] $ids
	 * @return array<int,string|null> post_id => meta_value
	 */
	private static function fetch_meta_for_ids(array $ids): array {
		global $wpdb;

		$ids = array_values(array_unique(array_map('intval', $ids)));
		if (empty($ids)) {
			return [];
		}

		$placeholders = implode(',', array_fill(0, count($ids), '%d'));

		$sql = "
			SELECT post_id, meta_value
			FROM {$wpdb->postmeta}
			WHERE meta_key = %s
			  AND post_id IN ($placeholders)
		";

		$params  = array_merge([self::META_KEY], $ids);
		$sql_prepared = $wpdb->prepare($sql, ...$params);
		
		
		
		$rows    = $wpdb->get_results($sql_prepared);
		$results = [];

		if (!empty($rows)) {
			foreach ($rows as $row) {
				$results[(int) $row->post_id] = $row->meta_value;
			}
		}
		
		

		return $results;
	}
}
