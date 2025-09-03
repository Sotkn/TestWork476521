<?php
/**
 * Configuration file for the Storefront Child Theme
 * 
 * Copy this file to your theme directory and configure the values below
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Weather API Configuration
// Get your free API key from: https://openweathermap.org/api
if ( ! defined( 'WEATHER_API_KEY' ) ) {
    // Option 1: Define constant in wp-config.php (recommended for production)
    // define( 'WEATHER_API_KEY', 'your_api_key_here' );
    
    // Option 2: Use WordPress option (set via admin or programmatically)
    // update_option( 'weather_api_key', 'your_api_key_here' );
    
    // Option 3: Set default value here (not recommended for production)
    // define( 'WEATHER_API_KEY', 'your_api_key_here' );
}

// Weather API Base URL (optional - uses default if not defined)
if ( ! defined( 'WEATHER_API_BASE' ) ) {
    // Default: https://api.openweathermap.org
    // define( 'WEATHER_API_BASE', 'https://api.openweathermap.org' );
}

// Security Note: Never commit API keys to version control
// For production, use environment variables or wp-config.php
