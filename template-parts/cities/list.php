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
	
	if (!empty($country_name) && !empty($city_name)) {
		if (!isset($cities_by_country[$country_name])) {
			$cities_by_country[$country_name] = [];
		}
		$cities_by_country[$country_name][] = [
			'name' => $city_name,
			'temperature' => $temperature
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
					<li class="city-item">
						<span class="city-name"><?php echo esc_html($city['name']); ?></span>
						<?php if ($city['temperature'] !== null) : ?>
							<span class="city-temperature"><?php echo esc_html($city['temperature']); ?>°C</span>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endforeach; ?>
</div>
