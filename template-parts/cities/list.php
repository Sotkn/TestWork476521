<?php
// template-parts/cities/list.php
defined('ABSPATH') || exit;

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
?>

<div class="cities-search-wrapper">
	<div class="search-form">
		<input type="text" id="cities-search" placeholder="<?php _e('Search cities or countries...', 'text_domain'); ?>" />
		<button type="button" id="cities-search-btn"><?php _e('Search', 'text_domain'); ?></button>
		<button type="button" id="cities-reset-btn" class="reset-btn"><?php _e('Reset', 'text_domain'); ?></button>
	</div>
	<div class="search-info">
		<p class="search-hint"><?php _e('Type at least 2 characters to search, or leave empty to show all cities', 'text_domain'); ?></p>
	</div>
</div>

<div id="cities-table-container">
	<?php render_cities_table($results); ?>
</div>

<?php 

/**
 * Render cities table - can be used by both template and AJAX
 */
function render_cities_table($results) {
	if (empty($results)) {
		echo '<p>' . __('No cities found.', 'text_domain') . '</p>';
		return;
	}

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

	<?php
}
