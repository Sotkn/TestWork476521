# Weather API Security Improvements

## Overview
The `Weather_Client` class has been completely rewritten to address critical security vulnerabilities and improve code quality.

## Security Issues Fixed

### 1. **API Key Exposure**
- **Before**: API key was directly embedded in URLs and could be logged
- **After**: API key is properly validated and can be stored securely
- **Solution**: Use WordPress options or constants, never hardcode in source

### 2. **Input Validation**
- **Before**: Basic numeric validation only
- **After**: Comprehensive coordinate validation (-90 to 90 for lat, -180 to 180 for lon)
- **Solution**: Added `validate_coordinates()` method with proper range checking

### 3. **Rate Limiting**
- **Before**: No rate limiting, could overwhelm API
- **After**: Built-in rate limiting (60 requests per minute)
- **Solution**: Prevents API abuse and respects OpenWeatherMap limits

### 4. **Error Handling**
- **Before**: Generic error messages
- **After**: Specific error codes and messages for different failure scenarios
- **Solution**: Better debugging and user experience

### 5. **URL Construction**
- **Before**: Manual string concatenation with `sprintf()`
- **After**: WordPress `add_query_arg()` for proper URL escaping
- **Solution**: Prevents URL injection and ensures proper encoding

## Configuration Options

### Option 1: WordPress Constants (Recommended for Production)
Add to your `wp-config.php`:
```php
define( 'WEATHER_API_KEY', 'your_32_character_api_key_here' );
define( 'WEATHER_API_BASE', 'https://api.openweathermap.org' );
```

### Option 2: WordPress Options
Set programmatically or via admin:
```php
update_option( 'weather_api_key', 'your_api_key_here' );
```

### Option 3: Configuration File
Edit `inc/config.php` and uncomment the appropriate lines.

## Security Best Practices

1. **Never commit API keys to version control**
2. **Use environment variables in production**
3. **Store sensitive data in wp-config.php or database**
4. **Validate all input parameters**
5. **Implement rate limiting**
6. **Use HTTPS for API calls**
7. **Log and monitor API usage**

## API Key Format Validation
The class now validates that API keys match the expected OpenWeatherMap format (32 alphanumeric characters).

## Rate Limiting
- Maximum 60 requests per minute (OpenWeatherMap free tier limit)
- Automatic reset after 60 seconds
- Prevents accidental API abuse

## Error Codes
- `invalid_coordinates`: Invalid latitude/longitude values
- `invalid_units`: Invalid units parameter
- `rate_limit_exceeded`: Too many requests
- `api_unauthorized`: Invalid API key
- `api_rate_limited`: OpenWeatherMap rate limit hit
- `api_server_error`: OpenWeatherMap server error
- `invalid_response`: Unexpected response structure

## Usage Example
```php
$weather_client = new Weather_Client();
$weather_data = $weather_client->get_weather_by_coordinates( 40.7128, -74.0060, 'metric' );

if ( is_wp_error( $weather_data ) ) {
    // Handle error
    error_log( $weather_data->get_error_message() );
} else {
    // Use weather data
    $temperature = $weather_data['main']['temp'];
}
```

## Migration Notes
- The class now accepts an optional `$units` parameter
- Error handling has changed - check return values for `WP_Error`
- API configuration is more flexible with fallback options
- Rate limiting is automatic and transparent
