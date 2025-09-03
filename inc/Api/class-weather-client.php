<?php
/**
 * Weather API Client
 * 
 * Secure wrapper class for the OpenWeatherMap API
 * Handles weather data retrieval by coordinates with proper validation and error handling
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Weather_Client {

    /**
     * Default API base URL
     */
    private const DEFAULT_API_BASE = 'https://api.openweathermap.org';

    /**
     * Default units
     */
    private const DEFAULT_UNITS = 'metric';

    /**
     * Maximum requests per minute (OpenWeatherMap free tier limit)
     */
    private const MAX_REQUESTS_PER_MINUTE = 60;

    /**
     * Request counter for rate limiting
     */
    private static $request_count = 0;
    private static $last_request_time = 0;

    /**
     * Get weather data by coordinates
     *
     * @param float $lat Latitude (-90 to 90)
     * @param float $lon Longitude (-180 to 180)
     * @param string $units Units (metric, imperial, kelvin)
     * @return array|WP_Error Weather data or error
     */
    public function get_weather_by_coordinates( $lat, $lon, $units = self::DEFAULT_UNITS ) {
        // Validate coordinates
        if ( ! $this->validate_coordinates( $lat, $lon ) ) {
            return new WP_Error( 'invalid_coordinates', 'Invalid coordinates provided. Latitude must be between -90 and 90, longitude between -180 and 180.' );
        }

        // Validate units
        if ( ! in_array( $units, [ 'metric', 'imperial', 'kelvin' ], true ) ) {
            return new WP_Error( 'invalid_units', 'Invalid units provided. Must be metric, imperial, or kelvin.' );
        }

        // Check rate limiting
        if ( ! $this->check_rate_limit() ) {
            return new WP_Error( 'rate_limit_exceeded', 'API rate limit exceeded. Please try again later.' );
        }

        // Get API configuration
        $api_key = $this->get_api_key();
        $api_base = $this->get_api_base();

        if ( is_wp_error( $api_key ) ) {
            return $api_key;
        }

        if ( is_wp_error( $api_base ) ) {
            return $api_base;
        }

        // Build API URL with proper escaping
        $api_url = add_query_arg(
            array(
                'lat' => $lat,
                'lon' => $lon,
                'appid' => $api_key,
                'units' => $units,
            ),
            trailingslashit( $api_base ) . 'data/2.5/weather'
        );

        // Make API request with timeout and user agent
        $response = wp_remote_get(
            $api_url,
            array(
                'timeout' => 30,
                'user-agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . get_bloginfo( 'url' ),
            )
        );

        // Check for HTTP errors
        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code( $response );
        $response_body = wp_remote_retrieve_body( $response );

        // Handle specific error codes
        switch ( $response_code ) {
            case 200:
                break;
            case 401:
                return new WP_Error( 'api_unauthorized', 'Invalid API key provided' );
            case 429:
                return new WP_Error( 'api_rate_limited', 'API rate limit exceeded by OpenWeatherMap' );
            case 500:
                return new WP_Error( 'api_server_error', 'OpenWeatherMap server error' );
            default:
                return new WP_Error( 
                    'api_error', 
                    sprintf( 'Weather API returned error code: %d', $response_code ),
                    array( 'status' => $response_code, 'body' => $response_body )
                );
        }

        // Parse response body
        $data = json_decode( $response_body, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return new WP_Error( 'json_parse_error', 'Failed to parse API response', array( 'body' => $response_body ) );
        }

        // Validate response structure
        if ( ! isset( $data['main']['temp'] ) ) {
            return new WP_Error( 'invalid_response', 'Invalid response structure from weather API' );
        }

        // Update rate limiting counter
        $this->update_rate_limit();

        return $data;
    }

    /**
     * Validate coordinates
     *
     * @param float $lat Latitude
     * @param float $lon Longitude
     * @return bool
     */
    private function validate_coordinates( $lat, $lon ) {
        if ( ! is_numeric( $lat ) || ! is_numeric( $lon ) ) {
            return false;
        }

        $lat = (float) $lat;
        $lon = (float) $lon;

        return ( $lat >= -90 && $lat <= 90 ) && ( $lon >= -180 && $lon <= 180 );
    }

    /**
     * Check rate limiting
     *
     * @return bool
     */
    private function check_rate_limit() {
        $current_time = time();
        
        // Reset counter if a minute has passed
        if ( $current_time - self::$last_request_time >= 60 ) {
            self::$request_count = 0;
            self::$last_request_time = $current_time;
        }

        return self::$request_count < self::MAX_REQUESTS_PER_MINUTE;
    }

    /**
     * Update rate limiting counter
     */
    private function update_rate_limit() {
        self::$request_count++;
        self::$last_request_time = time();
    }

    /**
     * Get API key from configuration
     *
     * @return string|WP_Error
     */
    private function get_api_key() {
        // Check for constant first
        if ( defined( 'WEATHER_API_KEY' ) ) {
            $api_key = WEATHER_API_KEY;
        } else {
            // Fallback to WordPress option
            $api_key = get_option( 'weather_api_key' );
        }

        if ( empty( $api_key ) ) {
            return new WP_Error( 'api_not_configured', 'Weather API key not configured. Please set WEATHER_API_KEY constant or weather_api_key option.' );
        }

        // Basic validation that it looks like an API key
        if ( ! preg_match( '/^[a-zA-Z0-9]{32}$/', $api_key ) ) {
            return new WP_Error( 'invalid_api_key', 'Invalid API key format' );
        }

        return $api_key;
    }

    /**
     * Get API base URL from configuration
     *
     * @return string|WP_Error
     */
    private function get_api_base() {
        // Check for constant first
        if ( defined( 'WEATHER_API_BASE' ) ) {
            $api_base = WEATHER_API_BASE;
        } else {
            // Fallback to default
            $api_base = self::DEFAULT_API_BASE;
        }

        // Validate URL format
        if ( ! filter_var( $api_base, FILTER_VALIDATE_URL ) ) {
            return new WP_Error( 'invalid_api_base', 'Invalid API base URL format' );
        }

        return $api_base;
    }
}
