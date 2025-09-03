# Cities Search & Weather Functionality

This WordPress child theme implements a comprehensive cities management system with search functionality, weather data integration, and real-time status updates.

## Features

- **Real-time search**: Search cities and countries as you type with AJAX
- **Weather integration**: Automatic weather data fetching and caching
- **Status updates**: Real-time city status updates via AJAX
- **Rate limiting**: Built-in protection against API abuse
- **Responsive design**: Works on all device sizes
- **WordPress compliant**: Follows WordPress coding standards and best practices

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
- **Custom Post Type**: Cities are stored as custom post types with coordinates
- **Countries Taxonomy**: Cities are organized by countries
- **Coordinates**: Each city has latitude/longitude stored in custom fields

### 2. Search Functionality
- **Real-time Search**: AJAX-powered search with 500ms debouncing
- **Rate Limiting**: 10 requests per 60 seconds per user
- **Security**: Nonce verification and input sanitization
- **Performance**: Efficient database queries with proper indexing

### 3. Weather Integration
- **API Client**: Integrates with weather services
- **Caching**: Weather data cached for 1 hour (configurable)
- **Queue System**: WP-Cron based weather update queuing
- **Rate Limiting**: Global API rate limiting (45 requests per 60 seconds)

### 4. Status Updates
- **Real-time Updates**: AJAX-powered city status updates
- **Batch Processing**: Handle multiple cities simultaneously
- **Rate Limiting**: 50 requests per 5 minutes per user
- **Error Handling**: Comprehensive error handling and user feedback

## Core Classes

### CitiesSearch
- Handles AJAX search requests
- Implements rate limiting and security
- Returns formatted HTML results

### CitiesUpdate
- Manages city status update requests
- Processes weather cache data
- Provides formatted status responses

### WeatherUpdater
- Queues weather updates via WP-Cron
- Manages API rate limiting
- Handles weather data caching

### Rate_Limiter
- Generic rate limiting service
- Configurable limits and time windows
- Prevents API abuse

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

### For Users

1. **Search Cities**: Navigate to a page using the "Cities List" template
2. **Real-time Search**: Type in the search box to find cities or countries
3. **Status Updates**: Use the status update functionality to get current city information
4. **Weather Data**: View temperature and weather information for cities

## Security Features

- **Nonce Verification**: All AJAX requests require valid nonces
- **Input Sanitization**: Comprehensive input validation and sanitization
- **Rate Limiting**: Multiple layers of rate limiting to prevent abuse
- **SQL Injection Prevention**: Prepared statements and proper escaping
- **WordPress Security**: Follows WordPress security best practices

## Performance Features

- **Database Caching**: Transients for frequently accessed data
- **Weather Caching**: 1-hour TTL for weather data
- **Debounced Search**: 500ms delay to reduce unnecessary requests
- **Efficient Queries**: Optimized database queries with proper indexing
- **WP-Cron Integration**: Asynchronous weather updates

## Customization

### Styling
- Modify `assets/css/cities-search.css` for visual customization
- Update template parts in `template-parts/cities/` for layout changes

### JavaScript Behavior
- Edit `assets/js/cities-search.js` for search functionality
- Modify `assets/js/cities-status-update.js` for status update behavior

### Search Logic
- Update search methods in repository classes
- Modify rate limiting parameters in service classes

### Weather Integration
- Configure API endpoints in `class-weather-client.php`
- Adjust caching TTL in `class-weather-updater.php`
- Modify rate limiting parameters for API calls

## Requirements

- **WordPress**: 5.0+
- **PHP**: 7.4+
- **jQuery**: Included with WordPress
- **Parent Theme**: Storefront theme
- **WP-Cron**: For weather update queuing

## Installation

1. Ensure all files are in the correct directory structure
2. The functionality is automatically loaded via `bootstrap.php`
3. Create a page and assign the "Cities List" template
4. Configure weather API credentials if needed
5. The search and status update functionality will be available

## Configuration

### Weather API
- Set API credentials in `class-weather-client.php`
- Configure rate limiting parameters in `class-weather-updater.php`
- Adjust caching TTL as needed

### Rate Limiting
- Modify rate limits in AJAX handler classes
- Adjust time windows for different user types
- Configure global API rate limits

## Troubleshooting

### Common Issues
- **Search not working**: Check browser console for JavaScript errors
- **AJAX failures**: Verify nonce generation and verification
- **Weather updates**: Check WP-Cron functionality and API credentials
- **Rate limiting**: Monitor rate limit headers in responses

### Debug Steps
1. Check browser console for JavaScript errors
2. Verify AJAX endpoints are accessible
3. Ensure proper file permissions
4. Check WordPress debug log for PHP errors
5. Verify WP-Cron is functioning properly
6. Check weather API credentials and rate limits

## API Endpoints

### Search Cities
- **Action**: `cities_search`
- **Method**: POST
- **Parameters**: `search_term`, `nonce`
- **Rate Limit**: 10 requests per 60 seconds

### Update Cities Status
- **Action**: `update_cities_status`
- **Method**: POST
- **Parameters**: `city_ids[]`, `nonce`
- **Rate Limit**: 50 requests per 5 minutes

## Contributing

When contributing to this project:
1. Follow WordPress coding standards
2. Maintain proper security practices
3. Test rate limiting functionality
4. Verify AJAX security measures
5. Update documentation for new features
