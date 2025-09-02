<?php
// template-parts/cities/list.php
defined('ABSPATH') || exit;

/**
 * Partial: Cities list grouped by country
 * Expects $results (array of rows with ->country_name, ->city_name, etc.)
 * Usage: get_template_part('template-parts/cities/list', null, ['results' => $results]);
 */

/** Support both WP 5.5+ args and direct var */
if (!isset($results) && isset($args) && is_array($args) && isset($args['results'])) {
	$results = $args['results'];
}

$results = $results ?? [];

if (empty($results)) : ?>
	<p><?php _e('No cities found.', 'storefront-child'); ?></p>
	<?php return;
endif;

// Group cities by country with temperature data
$cities_by_country = [];
foreach ($results as $row) {
	$country_name = $row->country_name ?? '';
	$city_name = $row->city_name ?? '';
	$temperature = $row->temperature_celsius ?? null;
	$cache_status = $row->cache_status ?? 'unknown';
	$city_id = $row->city_id ?? 0;
	
	if (!empty($country_name) && !empty($city_name)) {
		if (!isset($cities_by_country[$country_name])) {
			$cities_by_country[$country_name] = [];
		}
		$cities_by_country[$country_name][] = [
			'name' => $city_name,
			'temperature' => $temperature,
			'cache_status' => $cache_status,
			'city_id' => $city_id
		];
	}
}

// Sort countries alphabetically
ksort($cities_by_country);
?>

<div class="cities-list-wrapper">
	<?php foreach ($cities_by_country as $country_name => $cities) : ?>
		<div class="country-section">
			<h2><?php echo esc_html($country_name); ?></h2>
			<ul class="cities-list">
				<?php foreach ($cities as $city) : ?>
					<li class="city-item" data-city-id="<?php echo esc_attr($city['city_id']); ?>">
						<span class="city-name"><?php echo esc_html($city['name']); ?></span>
						<div class="city-info">
							<?php if ($city['temperature'] !== null) : ?>
								<span class="city-temperature"><?php echo esc_html($city['temperature']); ?>°C</span>
							<?php endif; ?>
							<span class="cache-status-indicator cache-status-<?php echo esc_attr($city['cache_status']); ?>" 
								  title="<?php echo esc_attr(ucfirst($city['cache_status'])); ?> cache status">
								<?php echo get_cache_status_icon($city['cache_status']); ?>
							</span>
						</div>
						<!-- Hidden field for cache status -->
						<input type="hidden" 
							   class="cache-status-field" 
							   value="<?php echo esc_attr($city['cache_status']); ?>" 
							   data-city-id="<?php echo esc_attr($city['city_id']); ?>" />
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endforeach; ?>
</div>

<?php
/**
 * Get cache status icon based on status
 * 
 * @param string $status Cache status
 * @return string HTML for status icon
 */
function get_cache_status_icon($status) {
	switch ($status) {
		case 'valid':
			return '<span class="dashicons dashicons-yes-alt"></span>';
		case 'expired':
			return '<span class="dashicons dashicons-clock"></span>';
		case 'expected':
			return '<span class="dashicons dashicons-update"></span>';
		case 'unavailable':
			return '<span class="dashicons dashicons-no-alt"></span>';
		default:
			return '<span class="dashicons dashicons-help"></span>';
	}
}
?>
