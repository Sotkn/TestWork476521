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
