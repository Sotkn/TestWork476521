<?php
// inc/template-tags-cities.php
defined('ABSPATH') || exit;

/**
 * Render cities table HTML for AJAX usage
 * 
 * @param array $results Array of city results from Cities_Repository
 * @return string HTML output of the cities table
 */
function render_cities_table(array $results): string {
	if (empty($results)) {
		return '<p>' . esc_html__('No cities found.', 'storefront-child') . '</p>';
	}
	
	ob_start();
	get_template_part('template-parts/cities/list', null, ['results' => $results]);
	return ob_get_clean();
}