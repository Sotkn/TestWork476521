<?php
/**
 * Cities Repository Class
 *
 * Manages database queries for cities and countries with caching support.
 * Provides search functionality and data retrieval methods for city-related operations.
 *
 * @package Storefront_Child
 * @subpackage Repositories
 * @since 1.0.0
 */

defined('ABSPATH') || exit;

class Cities_Repository {

	/**
	 * Retrieves all cities with their associated countries.
	 *
	 * Fetches published cities and their country information from the database.
	 * Results are cached for one hour to improve performance.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 *
	 * @return array Array of objects containing city and country data.
	 *               Each object has: country_name, country_slug, city_id, city_name.
	 */
	public static function get_cities_with_countries(): array {
		// Check if data is cached
		$cache_key = 'cities_with_countries_v1';
		$cached = get_transient($cache_key);
		if ($cached !== false) {
			return $cached;
		}

		global $wpdb;

		// SQL query to join cities with countries
		$sql = "
			SELECT 
				t.name   AS country_name,
				t.slug   AS country_slug,
				p.ID     AS city_id,
				p.post_title AS city_name
			FROM {$wpdb->terms} t
			INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
			INNER JOIN {$wpdb->term_relationships} tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
			INNER JOIN {$wpdb->posts} p ON tr.object_id = p.ID
			WHERE tt.taxonomy = %s
			  AND p.post_type = %s
			  AND p.post_status = 'publish'
			ORDER BY t.name ASC, p.post_title ASC
		";

		// Execute the query with proper sanitization
		$results = $wpdb->get_results(
			$wpdb->prepare($sql, 'countries', 'cities')
		);

		// Check for database errors
		if ($wpdb->last_error) {
			error_log('Cities Repository Database Error: ' . $wpdb->last_error);
			return [];
		}

		// Cache results for one hour to improve performance
		set_transient($cache_key, $results, HOUR_IN_SECONDS);

		return $results ?: [];
	}

	/**
	 * Searches cities and countries by a search term.
	 *
	 * Performs a database search for cities and countries matching the provided
	 * search term. If the search term is less than 2 characters, returns all
	 * cities and countries instead.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 *
	 * @param string $search_term The search term to look for in city and country names.
	 * @return array Array of objects containing matching city and country data.
	 *               Each object has: country_name, country_slug, city_id, city_name.
	 */
	public static function search_cities_and_countries(string $search_term): array {
		// Validate and sanitize input
		$search_term = sanitize_text_field(trim($search_term));
		
		// Return all results if search term is too short
		if (strlen($search_term) < 2) {
			return self::get_cities_with_countries();
		}

		global $wpdb;
		
		// Prepare search term for LIKE query with proper escaping
		$escaped_search_term = '%' . $wpdb->esc_like($search_term) . '%';

		// SQL query to search cities and countries by name
		$sql = "
			SELECT 
				t.name   AS country_name,
				t.slug   AS country_slug,
				p.ID     AS city_id,
				p.post_title AS city_name
			FROM {$wpdb->terms} t
			INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
			INNER JOIN {$wpdb->term_relationships} tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
			INNER JOIN {$wpdb->posts} p ON tr.object_id = p.ID
			WHERE tt.taxonomy = %s
			  AND p.post_type = %s
			  AND p.post_status = 'publish'
			  AND (t.name LIKE %s OR p.post_title LIKE %s)
			ORDER BY t.name ASC, p.post_title ASC
		";

		// Execute the search query with proper sanitization
		$results = $wpdb->get_results(
			$wpdb->prepare($sql, 'countries', 'cities', $escaped_search_term, $escaped_search_term)
		);

		// Check for database errors
		if ($wpdb->last_error) {
			error_log('Cities Repository Search Error: ' . $wpdb->last_error);
			return [];
		}

		return $results ?: [];
	}

	/**
	 * Retrieves a single city by its ID.
	 *
	 * Fetches a specific city by its post ID with basic city information.
	 * Validates input to ensure positive integer ID.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 *
	 * @param int $city_id The city post ID to retrieve.
	 * @return object|null City data object or null if not found.
	 */
	public static function get_city_by_id(int $city_id): ?object {
		// Validate input
		if ($city_id <= 0) {
			return null;
		}

		global $wpdb;

		// SQL query to get city by ID
		$sql = "
			SELECT 
				p.ID AS city_id,
				p.post_title AS city_name,
				p.post_status AS city_status
			FROM {$wpdb->posts} p
			WHERE p.ID = %d 
			  AND p.post_type = 'cities'
			  AND p.post_status = 'publish'
		";

		$result = $wpdb->get_row(
			$wpdb->prepare($sql, $city_id)
		);

		// Check for database errors
		if ($wpdb->last_error) {
			error_log('Cities Repository Get City Error: ' . $wpdb->last_error);
			return null;
		}

		return $result ?: null;
	}

	/**
	 * Flushes the cached cities and countries data.
	 *
	 * Removes the transient cache for cities with countries data.
	 * Useful when data needs to be refreshed immediately.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 *
	 * @return bool True on successful deletion, false otherwise.
	 */
	public static function flush_cache(): bool {
		return delete_transient('cities_with_countries_v1');
	}

	/**
	 * Invalidates cache when cities or countries are updated.
	 *
	 * This method should be called whenever cities or countries are
	 * created, updated, or deleted to ensure cache consistency.
	 * Alias for flush_cache() method.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 *
	 * @return bool True on successful cache invalidation, false otherwise.
	 */
	public static function invalidate_cache(): bool {
		return self::flush_cache();
	}
}
