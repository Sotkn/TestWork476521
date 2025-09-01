<?php
// inc/class-cities-repository.php
defined('ABSPATH') || exit;

class Cities_Repository {
	/** Return array of cities with countries */
	public static function get_cities_with_countries(): array {
		$cache_key = 'cities_with_countries_v1';
		$cached = get_transient($cache_key);
		if ($cached !== false) {
			return $cached;
		}

		global $wpdb;

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

		$results = $wpdb->get_results(
			$wpdb->prepare($sql, 'countries', 'cities')
		);

		// Short cache
		set_transient($cache_key, $results, HOUR_IN_SECONDS);

		return $results ?: [];
	}

	/** Search cities and countries by term */
	public static function search_cities_and_countries(string $search_term): array {
		if (strlen(trim($search_term)) < 2) {
			return self::get_cities_with_countries();
		}

		global $wpdb;
		$search_term = '%' . $wpdb->esc_like(trim($search_term)) . '%';

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

		$results = $wpdb->get_results(
			$wpdb->prepare($sql, 'countries', 'cities', $search_term, $search_term)
		);

		return $results ?: [];
	}

	// Flush cache
	public static function flush_cache(): void {
		delete_transient('cities_with_countries_v1');
	}
}
