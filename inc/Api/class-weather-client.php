<?php
/**
 * Weather API Client
 * 
 * Simple wrapper class for the OpenWeatherMap API
 * Only handles the basic weather data retrieval by coordinates
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class Weather_Client {

    /**
     * Get weather data by coordinates
     *
     * @param float $lat Latitude
     * @param float $lon Longitude
     * @return array|WP_Error Weather data or error
     */
    public function get_weather_by_coordinates( $lat, $lon ) {
        // Validate coordinates
        if ( ! is_numeric( $lat ) || ! is_numeric( $lon ) ) {
            return new WP_Error( 'invalid_coordinates', 'Invalid coordinates provided' );
        }

        // Check if API constants are defined
        if ( ! defined( 'WEATHER_API_KEY' ) || ! defined( 'WEATHER_API_BASE' ) ) {
            return new WP_Error( 'api_not_configured', 'Weather API not configured' );
        }

        // Build API URL
        $api_url = sprintf(
            '%s/data/2.5/weather?lat=%s&lon=%s&appid=%s&units=%s',
            WEATHER_API_BASE,
            urlencode( $lat ),
            urlencode( $lon ),
            WEATHER_API_KEY,
            'metric'
        );

        // Make API request
        $response = wp_remote_get( $api_url );

        // Check for HTTP errors
        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code( $response );
        if ( $response_code !== 200 ) {
            return new WP_Error( 
                'api_error', 
                sprintf( 'Weather API returned error code: %d', $response_code ),
                array( 'status' => $response_code )
            );
        }

        // Parse response body
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return new WP_Error( 'json_parse_error', 'Failed to parse API response' );
        }

        return $data;
    }
}
