# Cities Search & Weather Functionality

[![WordPress](https://img.shields.io/badge/WordPress-5.0+-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4+-green.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2%2B-orange.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

A comprehensive WordPress child theme that implements an advanced cities management system with real-time search functionality, weather data integration, and dynamic status updates.

## Table of Contents

- [Features](#features)
- [File Structure](#file-structure)
- [How It Works](#how-it-works)
- [Core Classes](#core-classes)
- [Usage](#usage)
- [Security Features](#security-features)
- [Performance Features](#performance-features)
- [Customization](#customization)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Troubleshooting](#troubleshooting)
- [API Endpoints](#api-endpoints)
- [Contributing](#contributing)

## Features

- **Real-time Search**: Search cities and countries as you type with AJAX-powered functionality
- **Weather Integration**: Automatic weather data fetching and intelligent caching system
- **Status Updates**: Real-time city status updates via AJAX with batch processing
- **Rate Limiting**: Built-in protection against API abuse and spam
- **Responsive Design**: Mobile-first design that works seamlessly on all device sizes
- **WordPress Compliant**: Follows WordPress coding standards and best practices
- **WP-Cron Integration**: Asynchronous background processing for weather updates
- **Smart Caching**: Efficient data caching with configurable TTL

## File Structure

```
inc/
├── Ajax/
│   ├── class-cities-search.php      # AJAX search handlers with rate limiting
│   └── class-cities-update.php      # AJAX status update handlers
├── Api/
│   └── class-weather-client.php     # Weather API integration
├── Services/
│   ├── class-cities-repository-with-temp.php  # Cities with temperature data
│   ├── class-rate-limiter.php       # Rate limiting service
│   ├── class-weather-cron-manager.php # WP-Cron management for weather updates
│   ├── class-weather-update-manager.php # Weather update orchestration
│   └── class-weather-updater.php    # Weather update queuing and processing
├── Repositories/
│   ├── class-cities-repository.php  # Basic cities database operations
│   └── class-weather-cache-repository.php  # Weather data caching
├── PostTypes/Cities/
│   └── register.php                  # Cities custom post type
├── Taxonomies/Countries/
│   └── register.php                  # Countries taxonomy
├── Metaboxes/Cities/
│   └── coords.php                    # Coordinates metabox for cities
├── Widgets/
│   └── City_Temperature_Widget.php  # Temperature display widget
├── Admin/
│   └── class-weather-cron-admin.php # Admin interface for cron management
├── CLI/
│   └── class-weather-cron-cli.php   # WP-CLI commands for weather management
├── cities-hooks.php                  # Cache management and rendering functions
├── bootstrap.php                     # File inclusion and initialization
└── config.php                        # Configuration settings

assets/
├── js/
│   ├── cities-search.js             # Search functionality JavaScript
│   └── cities-status-update.js      # Status update JavaScript
└── css/
    └── cities-search.css            # Search interface styles

template-parts/cities/
├── list.php                         # Basic cities list template
└── list-with-search.php             # Search-enabled cities list

page-templates/
└── cities-list.php                  # Page template for cities display
```

## How It Works

### 1. Cities Management
- **Custom Post Type**: Cities are stored as custom post types with comprehensive metadata
- **Countries Taxonomy**: Cities are organized by countries for easy categorization
- **Coordinates**: Each city has precise latitude/longitude stored in custom fields
- **Rich Metadata**: Extended city information including population, timezone, etc.

### 2. Search Functionality
- **Real-time Search**: AJAX-powered search with 500ms intelligent debouncing
- **Rate Limiting**: 10 requests per 60 seconds per user with IP tracking
- **Security**: Nonce verification and comprehensive input sanitization
- **Performance**: Efficient database queries with proper indexing and caching

### 3. Weather Integration
- **API Client**: Robust integration with multiple weather services
- **Smart Caching**: Weather data cached for 1 hour with configurable TTL
- **Queue System**: WP-Cron based weather update queuing with error handling
- **Rate Limiting**: Global API rate limiting (45 requests per 60 seconds)

### 4. Status Updates
- **Real-time Updates**: AJAX-powered city status updates with live feedback
- **Batch Processing**: Handle multiple cities simultaneously for efficiency
- **Rate Limiting**: 50 requests per 5 minutes per user with progressive delays
- **Error Handling**: Comprehensive error handling with user-friendly feedback

## Core Classes

### `CitiesSearch`
- **Purpose**: Handles AJAX search requests with security and performance
- **Features**: Rate limiting, nonce verification, input sanitization
- **Output**: Formatted HTML results with proper escaping

### `CitiesUpdate`
- **Purpose**: Manages city status update requests and processing
- **Features**: Batch processing, weather cache integration, error handling
- **Output**: Formatted status responses with user feedback

### `WeatherUpdater`
- **Purpose**: Queues and processes weather updates via WP-Cron
- **Features**: API rate limiting, intelligent caching, error recovery
- **Integration**: Seamless WP-Cron integration with admin controls

### `Rate_Limiter`
- **Purpose**: Generic rate limiting service for all API endpoints
- **Features**: Configurable limits, time windows, IP tracking
- **Protection**: Prevents API abuse and ensures fair usage

## Usage

### For Developers

#### Search Cities
```php
$cities_repo = new CitiesRepositoryWithTemp();
$results = $cities_repo->search_cities_and_countries_with_temp('search_term');
```

#### Render Cities Table
```php
$results = Cities_Repository::get_cities_with_countries();
render_cities_table($results);
```

#### Update Weather
```php
$weather_updater = new WeatherUpdater();
$weather_updater->add_to_queue($city_id);
$weather_updater->execute_queue();
```

#### Rate Limiting
```php
$rate_limiter = new Rate_Limiter();
if ($rate_limiter->is_allowed('search', $user_id)) {
    // Process request
} else {
    // Handle rate limit exceeded
}
```

### For Users

1. **Search Cities**: Navigate to a page using the "Cities List" template
2. **Real-time Search**: Type in the search box to find cities or countries instantly
3. **Status Updates**: Use the status update functionality to get current city information
4. **Weather Data**: View temperature and weather information for cities with automatic updates

## Security Features

- **Nonce Verification**: All AJAX requests require valid nonces with expiration
- **Input Sanitization**: Comprehensive input validation and sanitization
- **Rate Limiting**: Multiple layers of rate limiting to prevent abuse
- **SQL Injection Prevention**: Prepared statements and proper escaping
- **WordPress Security**: Follows WordPress security best practices and guidelines
- **IP Tracking**: User identification and rate limiting by IP address
- **Session Management**: Secure session handling with proper timeouts

## Performance Features

- **Database Caching**: Transients for frequently accessed data with smart invalidation
- **Weather Caching**: 1-hour TTL for weather data with background refresh
- **Debounced Search**: 500ms delay to reduce unnecessary API requests
- **Efficient Queries**: Optimized database queries with proper indexing
- **WP-Cron Integration**: Asynchronous weather updates without blocking
- **Progressive Enhancement**: Graceful degradation for better user experience
- **Lazy Loading**: Load data only when needed for optimal performance

## Customization

### Styling
- **CSS Customization**: Modify `assets/css/cities-search.css` for visual customization
- **Template Parts**: Update template parts in `template-parts/cities/` for layout changes
- **Responsive Design**: Mobile-first approach with customizable breakpoints

### JavaScript Behavior
- **Search Functionality**: Edit `assets/js/cities-search.js` for search behavior
- **Status Updates**: Modify `assets/js/cities-status-update.js` for update behavior
- **Event Handling**: Customize event listeners and user interactions

### Search Logic
- **Repository Methods**: Update search methods in repository classes
- **Rate Limiting**: Modify rate limiting parameters in service classes
- **Caching Strategy**: Adjust caching behavior and TTL values

### Weather Integration
- **API Configuration**: Configure API endpoints in `class-weather-client.php`
- **Caching TTL**: Adjust caching TTL in `class-weather-updater.php`
- **Rate Limits**: Modify rate limiting parameters for API calls
- **Error Handling**: Customize error handling and retry logic

## Requirements

- **WordPress**: 5.0+ (recommended: 6.0+)
- **PHP**: 7.4+ (recommended: 8.0+)
- **jQuery**: Included with WordPress (1.12.4+)
- **Parent Theme**: Storefront theme (latest version)
- **WP-Cron**: For weather update queuing (can be disabled)
- **Database**: MySQL 5.6+ or MariaDB 10.0+

## Installation

1. **File Placement**: Ensure all files are in the correct directory structure
2. **Auto-loading**: Functionality is automatically loaded via `bootstrap.php`
3. **Page Creation**: Create a page and assign the "Cities List" template
4. **API Configuration**: Configure weather API credentials if needed
5. **Verification**: Search and status update functionality will be available immediately

### Quick Start
```bash
# Clone or download the theme files
# Place in wp-content/themes/storefront-child/
# Activate the child theme in WordPress admin
# Create a page with "Cities List" template
# Configure weather API credentials
```

## Configuration

### Weather API
- **Credentials**: Set API credentials in `class-weather-client.php`
- **Rate Limiting**: Configure rate limiting parameters in `class-weather-updater.php`
- **Caching**: Adjust caching TTL based on your needs and API limits
- **Endpoints**: Configure multiple weather service endpoints for redundancy

### Rate Limiting
- **User Limits**: Modify rate limits in AJAX handler classes
- **Time Windows**: Adjust time windows for different user types
- **Global Limits**: Configure global API rate limits and burst handling
- **IP Tracking**: Enable/disable IP-based rate limiting

### WP-Cron Settings
- **Frequency**: Adjust weather update frequency in admin settings
- **Batch Size**: Configure batch processing size for weather updates
- **Error Handling**: Set retry limits and error recovery strategies

## Troubleshooting

### Common Issues

| Issue | Solution |
|-------|----------|
| **Search not working** | Check browser console for JavaScript errors |
| **AJAX failures** | Verify nonce generation and verification |
| **Weather updates** | Check WP-Cron functionality and API credentials |
| **Rate limiting** | Monitor rate limit headers in responses |
| **Performance issues** | Check caching configuration and database queries |

### Debug Steps
1. **Browser Console**: Check for JavaScript errors and network issues
2. **AJAX Endpoints**: Verify AJAX endpoints are accessible and responding
3. **File Permissions**: Ensure proper file permissions for theme files
4. **WordPress Debug**: Check WordPress debug log for PHP errors
5. **WP-Cron**: Verify WP-Cron is functioning properly
6. **API Credentials**: Check weather API credentials and rate limits
7. **Cache Status**: Verify caching is working correctly

### Debug Mode
Enable WordPress debug mode to get detailed error information:
```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

## API Endpoints

### Search Cities
- **Action**: `cities_search`
- **Method**: POST
- **Parameters**: 
  - `search_term` (string): Search query
  - `nonce` (string): Security nonce
- **Rate Limit**: 10 requests per 60 seconds
- **Response**: HTML formatted results

### Update Cities Status
- **Action**: `update_cities_status`
- **Method**: POST
- **Parameters**: 
  - `city_ids[]` (array): Array of city IDs
  - `nonce` (string): Security nonce
- **Rate Limit**: 50 requests per 5 minutes
- **Response**: JSON status updates

### Rate Limit Headers
All API responses include rate limit information:
```
X-RateLimit-Limit: 10
X-RateLimit-Remaining: 7
X-RateLimit-Reset: 1640995200
```

## Contributing

We welcome contributions to improve this project! Please follow these guidelines:

### Development Standards
1. **Code Style**: Follow WordPress coding standards (PSR-12 compatible)
2. **Security**: Maintain proper security practices and input validation
3. **Testing**: Test rate limiting functionality thoroughly
4. **AJAX Security**: Verify AJAX security measures work correctly
5. **Documentation**: Update documentation for new features

### Pull Request Process
1. **Issue Creation**: Create an issue describing the problem or feature
2. **Fork & Branch**: Fork the repository and create a feature branch
3. **Development**: Implement changes with proper testing
4. **Documentation**: Update README and inline documentation
5. **Testing**: Test all functionality including edge cases
6. **Pull Request**: Submit PR with detailed description

### Code Review Checklist
- [ ] Follows WordPress coding standards
- [ ] Includes proper security measures
- [ ] Has comprehensive error handling
- [ ] Includes rate limiting considerations
- [ ] Updates documentation as needed
- [ ] Passes all existing tests

## License

This project is licensed under the GPL v2 or later - see the [WordPress License](https://wordpress.org/about/license/) for details.

## Acknowledgments

- Built for WordPress community
- Inspired by modern web development practices
- Utilizes best practices for security and performance
- Designed with developer experience in mind

---

**Need Help?** Check the troubleshooting section or create an issue in the project repository.

**Last Updated**: December 2024
**Version**: 1.0.0
**WordPress Compatibility**: 5.0+
