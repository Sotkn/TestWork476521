<?php
// inc/bootstrap.php
defined('ABSPATH') || exit;

// Ensure WordPress is fully loaded
if (!function_exists('add_action')) {
    return;
}




define('THEME_PATH', get_stylesheet_directory());
define('THEME_URL',  get_stylesheet_directory_uri());

// Include custom post type for cities
require_once __DIR__ . '/PostTypes/Cities/register.php';

// Include metaboxes for cities
require_once __DIR__ . '/Metaboxes/Cities/coords.php';

// Include taxonomies for cities
require_once __DIR__ . '/Taxonomies/Countries/register.php';

// Include widgets for cities
require_once __DIR__ . '/Widgets/City_Temperature_Widget.php';

// Include template tags
require_once __DIR__ . '/template-tags-cities.php';
    
// Include weather updater
require_once __DIR__ . '/Services/class-weather-updater.php';
// Include cities hooks
require_once __DIR__ . '/cities-hooks.php';

// Include cities repository
require_once __DIR__ . '/Repositories/class-cities-repository.php';
require_once __DIR__ . '/Repositories/class-weather-cache-repository.php';



// Include rate limiter service
require_once __DIR__ . '/Services/class-rate-limiter.php';
require_once __DIR__ . '/Services/class-weather-updater.php';

// Include AJAX handlers
require_once __DIR__ . '/Ajax/class-cities-search.php';
require_once __DIR__ . '/Ajax/class-cities-update.php';

// Include city data service
require_once __DIR__ . '/Services/FAKE-class-city-data.php';

// Include cities repository with temperature
require_once __DIR__ . '/Services/class-cities-repository-with-temp.php';

// Include weather client
require_once __DIR__ . '/Api/class-weather-client.php';

// Api
require_once __DIR__ . '/Api/class-weather-client.php';








