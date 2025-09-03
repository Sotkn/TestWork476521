<?php
// inc/bootstrap.php
defined('ABSPATH') || exit;

// Ensure WordPress is fully loaded
if (!function_exists('add_action')) {
    return;
}

// Include configuration file
if (!file_exists(__DIR__ . '/config.php')) {
    error_log('Storefront Child Theme: Configuration file not found');
    return;
}
require_once __DIR__ . '/config.php';

// Define theme constants
define('THEME_PATH', get_stylesheet_directory());
define('THEME_URL', get_stylesheet_directory_uri());

// Include core repositories first (dependencies for services)
require_once __DIR__ . '/Repositories/class-cities-repository.php';
require_once __DIR__ . '/Repositories/class-weather-cache-repository.php';

// Include API client (dependency for weather services)
require_once __DIR__ . '/Api/class-weather-client.php';

// Include services (depend on repositories and API client)
require_once __DIR__ . '/Services/class-rate-limiter.php';
require_once __DIR__ . '/Services/class-weather-updater.php';
require_once __DIR__ . '/Services/class-weather-update-manager.php';
require_once __DIR__ . '/Services/class-cities-repository-with-temp.php';
require_once __DIR__ . '/Services/class-weather-cron-manager.php';


// Include admin classes
require_once __DIR__ . '/Admin/class-weather-cron-admin.php';

// Include CLI classes (only when WP-CLI is available)
if (defined('WP_CLI') && WP_CLI) {
	require_once __DIR__ . '/CLI/class-weather-cron-cli.php';
}


// Include post types, taxonomies, and metaboxes
require_once __DIR__ . '/PostTypes/Cities/register.php';
require_once __DIR__ . '/Taxonomies/Countries/register.php';
require_once __DIR__ . '/Metaboxes/Cities/coords.php';

// Include widgets
require_once __DIR__ . '/Widgets/City_Temperature_Widget.php';

// Include template tags
require_once __DIR__ . '/template-tags-cities.php';

// Include hooks (depend on all other components)
require_once __DIR__ . '/cities-hooks.php';

// Include AJAX handlers (depend on repositories and services)
require_once __DIR__ . '/Ajax/class-cities-search.php';
require_once __DIR__ . '/Ajax/class-cities-update.php';








