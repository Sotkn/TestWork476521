<?php
// inc/cities-hooks.php
defined('ABSPATH') || exit;

/**
 * Invalidate cities cache when a city post is saved
 */
add_action('save_post_cities', function($post_ID, $post) {
	try {
		Cities_Repository::invalidate_cache();
	} catch (Exception $e) {
		error_log('Failed to invalidate cities cache: ' . $e->getMessage());
	}
}, 10, 2);

/**
 * Invalidate cities cache when a country term is edited
 */
add_action('edited_countries', function() {
	try {
		Cities_Repository::invalidate_cache();
	} catch (Exception $e) {
		error_log('Failed to invalidate cities cache: ' . $e->getMessage());
	}
}, 10, 0);

/**
 * Invalidate cities cache when a country term is created
 */
add_action('created_countries', function() {
	try {
		Cities_Repository::invalidate_cache();
	} catch (Exception $e) {
		error_log('Failed to invalidate cities cache: ' . $e->getMessage());
	}
}, 10, 0);

/**
 * Invalidate cities cache when a country term is deleted
 */
add_action('delete_term', function($term, $tt_id, $taxonomy) {
	if ($taxonomy === 'countries') {
		try {
			Cities_Repository::invalidate_cache();
		} catch (Exception $e) {
			error_log('Failed to invalidate cities cache: ' . $e->getMessage());
		}
	}
}, 10, 3);

/**
 * Cron hook for weather updater
 */
add_action(
	WeatherUpdater::CRON_HOOK,
	[WeatherUpdater::class, 'cron_handler'],
	10,
	1
);

/**
 * Initialize the weather cron manager
 */
add_action('init', function() {
	try {
		$cron_manager = new Weather_Cron_Manager();
		$cron_manager->init();
	} catch (Exception $e) {
		error_log('Failed to initialize weather cron manager: ' . $e->getMessage());
	}
}, 20);

/**
 * Initialize the weather cron admin interface
 */
add_action('init', function() {
	try {
		new Weather_Cron_Admin();
	} catch (Exception $e) {
		error_log('Failed to initialize weather cron admin: ' . $e->getMessage());
	}
}, 30);


