<?php

define('THEME_PATH', get_stylesheet_directory());
define('THEME_URL',  get_stylesheet_directory_uri());

// Include custom post type for cities
require_once __DIR__ . '/PostTypes/Cities/register.php';

// Include metaboxes for cities
require_once __DIR__ . '/Metaboxes/Cities/coords.php';
