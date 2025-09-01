<?php
// inc/cities-hooks.php
defined('ABSPATH') || exit;

add_action('save_post_cities', function() {
	Cities_Repository::flush_cache();
}, 10, 0);

add_action('edited_countries', function() {
	Cities_Repository::flush_cache();
}, 10, 0);

add_action('created_countries', function() {
	Cities_Repository::flush_cache();
}, 10, 0);

add_action('delete_term', function($term, $tt_id, $taxonomy) {
	if ($taxonomy === 'countries') {
		Cities_Repository::flush_cache();
	}
}, 10, 3);

/**
 * Render cities table - can be used by both template and AJAX
 * 
 * @param array $results Array of city results
 */
function render_cities_table($results) {
	if (empty($results)) {
		echo '<p>' . __('No cities found.', 'storefront-child') . '</p>';
		return;
	}

	/* Group by country */
	$cities_by_country = [];
	foreach ($results as $row) {
		$country = isset($row->country_name) ? (string)$row->country_name : '';
		if ($country === '') {
			$country = __('(No country)', 'storefront-child');
		}
		$cities_by_country[$country][] = $row;
	}
	
	// Initialize CityData service for temperature
	$city_data = new CityData();
	?>

	<div class="cities-list-wrapper">
		<?php foreach ($cities_by_country as $country_name => $cities): ?>
			<div class="country-section">
				<h2><?php echo esc_html($country_name); ?></h2>
				<ul class="cities-list">
					<?php foreach ($cities as $city): ?>
						<li>
							<?php echo esc_html(isset($city->city_name) ? $city->city_name : ''); ?>
							<?php if (isset($city->city_id)): ?>
								<?php $temperature = $city_data->get_temperature_in_celcius($city->city_id); ?>
								<?php if ($temperature): ?>
									<span class="city-temperature"> - <?php echo esc_html($temperature); ?>°C</span>
								<?php endif; ?>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endforeach; ?>
	</div>

	<?php
}


