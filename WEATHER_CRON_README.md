# Weather Cron Job System

This system automatically updates weather data for all cities every 5 minutes using WordPress cron jobs.

## Overview

The weather cron job system consists of several components:

1. **Weather_Cron_Manager** - Manages the recurring cron job
2. **Weather_Cron_Admin** - Provides admin interface for monitoring and control
3. **Weather_Cron_CLI** - CLI commands for testing and management

## How It Works

1. **Every 5 minutes**, a cron job runs that:
   - Retrieves all published cities from the database
   - Adds each city to the weather update queue
   - Schedules individual city weather updates with small delays
   - Respects rate limiting to stay within API limits

2. **Individual city updates** are processed with delays to avoid overwhelming the weather API

3. **Weather data is cached** for each city with a 1-hour TTL

## Features

- **Automatic scheduling**: Runs every 5 minutes without manual intervention
- **Rate limiting**: Respects API limits to prevent overwhelming the weather service
- **Error handling**: Gracefully handles API errors and logs issues
- **Admin interface**: Monitor status and manually control the cron job
- **CLI support**: Manage the cron job from the command line
- **Logging**: Comprehensive logging for monitoring and debugging

## Admin Interface

Access the admin interface at **Tools > Weather Cron** in your WordPress admin panel.

The interface shows:
- Current cron job status (Active/Inactive)
- Next scheduled run time
- Number of cities in the system
- Manual controls (Trigger Now, Reschedule, Stop)

## CLI Commands

If you have WP-CLI installed, you can use these commands:

```bash
# Check cron status
wp weather-cron status

# Test the cron job manually
wp weather-cron test

# Start the cron job
wp weather-cron start

# Stop the cron job
wp weather-cron stop

# Restart the cron job
wp weather-cron restart
```

## Configuration

The cron job interval is set to 5 minutes by default. To change this:

1. Edit `inc/Services/class-weather-cron-manager.php`
2. Modify the `CRON_INTERVAL` constant
3. Update the interval value in `add_cron_interval()`

## Monitoring

### Logs

The system logs important events to the WordPress error log:
- When cities are added to the queue
- API errors and failures
- Cron job execution status

### Status Checks

You can monitor the cron job status through:
- Admin interface (Tools > Weather Cron)
- CLI commands (`wp weather-cron status`)
- WordPress cron system (`wp cron event list`)

## Troubleshooting

### Cron Job Not Running

1. Check if WordPress cron is enabled
2. Verify the cron job is scheduled (`wp weather-cron status`)
3. Check for errors in the WordPress error log
4. Try manually triggering the cron job

### API Rate Limiting

The system includes built-in rate limiting:
- Maximum 45 requests per 60-second window
- Individual city updates are spaced 1 second apart
- Failed requests are logged and retried on next run

### Performance Issues

- The cron job processes cities in batches
- Weather data is cached for 1 hour
- Only cities with fresh coordinates are updated
- Failed updates are retried on subsequent runs

## File Structure

```
inc/
├── Services/
│   ├── class-weather-cron-manager.php    # Main cron manager
│   └── class-weather-updater.php         # Individual city updates
├── Admin/
│   └── class-weather-cron-admin.php      # Admin interface
├── CLI/
│   └── class-weather-cron-cli.php        # CLI commands
└── cities-hooks.php                       # Hook initialization
```

## Dependencies

- WordPress 5.0+
- Cities post type with coordinates
- Weather API client
- Rate limiting service

## Security

- Admin interface requires `manage_options` capability
- CLI commands are only available when WP-CLI is running
- All user inputs are properly sanitized
- Nonces are used for admin actions

## Support

For issues or questions:
1. Check the WordPress error log
2. Use the admin interface to monitor status
3. Test with CLI commands if available
4. Review the cron job logs in WordPress admin
