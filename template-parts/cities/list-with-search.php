<?php
// template-parts/cities/list-with-search.php
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
	<p><?php _e('No cities found.', 'storefront-child'); ?></p>
	<?php return;
endif;
?>

<div class="cities-search-wrapper">
	<div class="search-form">
		<input type="text" id="cities-search" placeholder="<?php _e('Search cities or countries...', 'storefront-child'); ?>" />
		<button type="button" id="cities-search-btn"><?php _e('Search', 'storefront-child'); ?></button>
		<button type="button" id="cities-reset-btn" class="reset-btn"><?php _e('Reset', 'storefront-child'); ?></button>
	</div>
	<div class="search-info">
		<p class="search-hint"><?php _e('Type at least 2 characters to search, or leave empty to show all cities', 'storefront-child'); ?></p>
	</div>
</div>

<div id="cities-table-container">
	<?php echo render_cities_table($results); ?>
</div>

<?php
