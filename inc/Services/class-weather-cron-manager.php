<?php
// inc/Services/class-weather-cron-manager.php
defined('ABSPATH') || exit;

/**
 * Weather Cron Manager Service Class
 *
 * Manages a recurring cron job that adds all cities to the weather update queue
 * every 5 minutes. This ensures all cities get their weather data refreshed
 * periodically without manual intervention.
 */
class Weather_Cron_Manager {

	/** Cron hook name for the recurring job */
	public const RECURRING_CRON_HOOK = 'tw_update_all_cities_weather';

	/** Interval for the recurring cron job (5 minutes) */
	private const CRON_INTERVAL = 'every_5_minutes';

	/** @var WeatherUpdater */
	private $weather_updater;

	/** @var Cities_Repository */
	private $cities_repository;

	public function __construct() {
		$this->weather_updater = new WeatherUpdater();
		$this->cities_repository = new Cities_Repository();
	}

	/**
	 * Initialize the cron manager
	 *
	 * Sets up the recurring cron job and registers the cron handler.
	 * This method should be called during WordPress initialization.
	 */
	public function init(): void {
		// Register the cron interval
		add_filter('cron_schedules', [$this, 'add_cron_interval']);
		
		// Register the cron handler
		add_action(self::RECURRING_CRON_HOOK, [$this, 'cron_handler']);
		
		// Schedule the recurring cron job if it's not already scheduled
		if (!wp_next_scheduled(self::RECURRING_CRON_HOOK)) {
			wp_schedule_event(time(), self::CRON_INTERVAL, self::RECURRING_CRON_HOOK);
		}
	}

	/**
	 * Add custom cron interval for every 5 minutes
	 *
	 * @param array $schedules WordPress cron schedules
	 * @return array Modified schedules array
	 */
	public function add_cron_interval(array $schedules): array {
		$schedules[self::CRON_INTERVAL] = [
			'interval' => 5 * MINUTE_IN_SECONDS, // 5 minutes
			'display'  => __('Every 5 Minutes', 'storefront-child')
		];
		
		return $schedules;
	}

	/**
	 * Cron handler that adds all cities to the weather update queue
	 *
	 * This method is called every 5 minutes by WordPress cron.
	 * It retrieves all published cities and adds them to the weather
	 * update queue for processing.
	 */
	public function cron_handler(): void {
		try {
			// Get all cities with countries (this includes city IDs)
			$cities = $this->cities_repository::get_cities_with_countries();
			
			if (empty($cities)) {
				error_log('Weather Cron Manager: No cities found to update');
				return;
			}

			$city_count = 0;
			
			// Add each city to the weather update queue
			foreach ($cities as $city) {
				$city_id = (int) $city->city_id;
				
				if ($city_id > 0) {
					$this->weather_updater->add_to_queue($city_id);
					$city_count++;
				}
			}

			// Execute the queue to schedule individual city updates
			$this->weather_updater->execute_queue();
			
			// Log the operation for monitoring
			error_log(sprintf(
				'Weather Cron Manager: Added %d cities to weather update queue at %s',
				$city_count,
				current_time('Y-m-d H:i:s')
			));

		} catch (Exception $e) {
			error_log('Weather Cron Manager Error: ' . $e->getMessage());
		}
	}

	/**
	 * Clean up the cron job
	 *
	 * Removes the scheduled cron job. Useful for deactivation or cleanup.
	 */
	public function cleanup(): void {
		$timestamp = wp_next_scheduled(self::RECURRING_CRON_HOOK);
		if ($timestamp) {
			wp_unschedule_event($timestamp, self::RECURRING_CRON_HOOK);
		}
		wp_clear_scheduled_hook(self::RECURRING_CRON_HOOK);
	}

	/**
	 * Get the next scheduled run time
	 *
	 * @return int|false Next scheduled timestamp or false if not scheduled
	 */
	public function get_next_run_time() {
		return wp_next_scheduled(self::RECURRING_CRON_HOOK);
	}

	/**
	 * Check if the cron job is currently scheduled
	 *
	 * @return bool True if scheduled, false otherwise
	 */
	public function is_scheduled(): bool {
		return wp_next_scheduled(self::RECURRING_CRON_HOOK) !== false;
	}
}
