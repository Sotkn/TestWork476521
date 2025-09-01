<?php
/**
 * Partial: Cities list grouped by country
 * Expects $results (array of rows with ->country_name, ->city_name, etc.)
 * Usage: get_template_part('page-templates/cities-map', null, ['results' => $results]);
 */

/** Support both WP 5.5+ args and direct var */
if (!isset($results) && isset($args) && is_array($args) && isset($args['results'])) {
	$results = $args['results'];
}

$results = $results ?? [];

if (empty($results)) : ?>
	<p><?php _e('No cities found.', 'text_domain'); ?></p>
	<?php return;
endif;

/* Group by country */
$cities_by_country = [];
foreach ($results as $row) {
	$country = isset($row->country_name) ? (string)$row->country_name : '';
	if ($country === '') {
		$country = __('(No country)', 'text_domain');
	}
	$cities_by_country[$country][] = $row;
}
?>

<div class="cities-list-wrapper">
	<?php foreach ($cities_by_country as $country_name => $cities): ?>
		<div class="country-section">
			<h2><?php echo esc_html($country_name); ?></h2>
			<ul class="cities-list">
				<?php foreach ($cities as $city): ?>
					<li><?php echo esc_html(isset($city->city_name) ? $city->city_name : ''); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endforeach; ?>
</div>
