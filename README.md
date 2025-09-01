# Cities Search Functionality

This WordPress child theme implements a searchable cities list with AJAX functionality.

## Features

- **Real-time search**: Search cities and countries as you type
- **AJAX-powered**: No page reloads during search
- **Responsive design**: Works on all device sizes
- **WordPress compliant**: Follows WordPress coding standards and best practices

## File Structure

```
inc/
├── class-cities-repository.php     # Database operations and search methods
├── cities-hooks.php                # Cache management hooks and rendering function
├── bootstrap.php                   # File inclusion and initialization
└── Ajax/
    └── CitiesSearch.php            # AJAX handlers and asset enqueuing

assets/
├── js/cities-search.js            # JavaScript for search functionality
└── css/cities-search.css          # Styles for search interface

template-parts/cities/
└── list.php                       # Template part for cities display

page-templates/
└── cities-list.php                # Page template using the cities list
```

## How It Works

### 1. Database Layer (`Cities_Repository`)
- `get_cities_with_countries()`: Retrieves all cities grouped by countries
- `search_cities_and_countries($search_term)`: Searches cities and countries by term

### 2. Rendering Layer (`cities-hooks.php`)
- `render_cities_table($results)`: Renders the cities table HTML
- Used by both the initial page load and AJAX responses

### 3. AJAX Layer (`CitiesSearch` class)
- `handle_cities_search()`: Processes AJAX search requests
- `enqueue_cities_search_assets()`: Loads scripts and styles
- Returns rendered HTML for seamless updates

### 4. Frontend Layer
- JavaScript handles user input and AJAX requests
- CSS provides responsive, modern styling
- Auto-search after 500ms of no typing
- Loading states and error handling

## Usage

### For Developers

The `render_cities_table()` function can be used anywhere in your theme:

```php
$results = Cities_Repository::get_cities_with_countries();
render_cities_table($results);
```

### For Users

1. Navigate to a page using the "Cities List" template
2. Type in the search box to find cities or countries
3. Results update in real-time
4. Use the reset button to show all cities again

## Security Features

- Nonce verification for AJAX requests
- Input sanitization
- SQL injection prevention with prepared statements
- WordPress security best practices

## Performance Features

- Database query caching with transients
- Debounced search input (500ms delay)
- Efficient SQL queries with proper indexing
- Minimal DOM manipulation

## Customization

### Styling
Modify `assets/css/cities-search.css` to customize the appearance.

### JavaScript Behavior
Edit `assets/js/cities-search.js` to change search behavior, timing, or add features.

### Search Logic
Update the `search_cities_and_countries()` method in `Cities_Repository` to modify search behavior.

### AJAX Handling
Modify the `CitiesSearch` class to customize AJAX behavior.

### Rendering
Update the `render_cities_table()` function in `cities-hooks.php` to change how results are displayed.

## Requirements

- WordPress 5.0+
- PHP 7.4+
- jQuery (included with WordPress)
- Storefront parent theme

## Installation

1. Ensure all files are in the correct directory structure
2. The functionality is automatically loaded via `bootstrap.php`
3. Create a page and assign the "Cities List" template
4. The search functionality will be available on that page

## Troubleshooting

- Check browser console for JavaScript errors
- Verify AJAX endpoints are accessible
- Ensure proper file permissions
- Check WordPress debug log for PHP errors
