<?php

require_once __DIR__ . '/../vendor/autoload.php';


if (defined('WP_PHPUNIT__DIR')) {
	require_once WP_PHPUNIT__DIR . '/includes/functions.php';

	
	tests_add_filter('muplugins_loaded', function () {
		
		switch_theme('storefront-child');
		
		$theme_dir = dirname(__DIR__);
		if (file_exists($theme_dir . '/functions.php')) {
			require $theme_dir . '/functions.php';
		}
	});

	require_once WP_PHPUNIT__DIR . '/includes/bootstrap.php';
}
