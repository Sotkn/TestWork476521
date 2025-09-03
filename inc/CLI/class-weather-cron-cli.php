<?php
// inc/CLI/class-weather-cron-cli.php
defined('ABSPATH') || exit;

/**
 * Weather Cron CLI Commands
 *
 * Provides WP-CLI commands for testing and managing the weather cron job.
 * Only available when WP-CLI is running.
 */
if (defined('WP_CLI') && WP_CLI) {
	
	/**
	 * Weather Cron CLI Commands
	 */
	class Weather_Cron_CLI {
		
		/**
		 * Test the weather cron job
		 *
		 * ## EXAMPLES
		 *
		 *     wp weather-cron test
		 *
		 * @when after_wp_load
		 */
		public function test() {
			WP_CLI::log('Testing weather cron job...');
			
			try {
				$cron_manager = new Weather_Cron_Manager();
				$cron_manager->cron_handler();
				
				WP_CLI::success('Weather cron job executed successfully!');
				
			} catch (Exception $e) {
				WP_CLI::error('Weather cron job failed: ' . $e->getMessage());
			}
		}
		
		/**
		 * Show the status of the weather cron job
		 *
		 * ## EXAMPLES
		 *
		 *     wp weather-cron status
		 *
		 * @when after_wp_load
		 */
		public function status() {
			$cron_manager = new Weather_Cron_Manager();
			$next_run = $cron_manager->get_next_run_time();
			$is_scheduled = $cron_manager->is_scheduled();
			
			WP_CLI::log('Weather Cron Status:');
			WP_CLI::log('  Status: ' . ($is_scheduled ? 'Active' : 'Inactive'));
			
			if ($next_run) {
				WP_CLI::log('  Next Run: ' . date('Y-m-d H:i:s', $next_run));
				WP_CLI::log('  Time Until Next Run: ' . human_time_diff(time(), $next_run));
			} else {
				WP_CLI::log('  Next Run: Not scheduled');
			}
			
			WP_CLI::log('  Interval: Every 5 minutes');
			
			// Get cities count
			$cities = Cities_Repository::get_cities_with_countries();
			$cities_count = count($cities);
			WP_CLI::log('  Cities Count: ' . number_format($cities_count));
		}
		
		/**
		 * Start the weather cron job
		 *
		 * ## EXAMPLES
		 *
		 *     wp weather-cron start
		 *
		 * @when after_wp_load
		 */
		public function start() {
			WP_CLI::log('Starting weather cron job...');
			
			try {
				$cron_manager = new Weather_Cron_Manager();
				$cron_manager->init();
				
				WP_CLI::success('Weather cron job started successfully!');
				
			} catch (Exception $e) {
				WP_CLI::error('Failed to start weather cron job: ' . $e->getMessage());
			}
		}
		
		/**
		 * Stop the weather cron job
		 *
		 * ## EXAMPLES
		 *
		 *     wp weather-cron stop
		 *
		 * @when after_wp_load
		 */
		public function stop() {
			WP_CLI::log('Stopping weather cron job...');
			
			try {
				$cron_manager = new Weather_Cron_Manager();
				$cron_manager->cleanup();
				
				WP_CLI::success('Weather cron job stopped successfully!');
				
			} catch (Exception $e) {
				WP_CLI::error('Failed to stop weather cron job: ' . $e->getMessage());
			}
		}
		
		/**
		 * Restart the weather cron job
		 *
		 * ## EXAMPLES
		 *
		 *     wp weather-cron restart
		 *
		 * @when after_wp_load
		 */
		public function restart() {
			WP_CLI::log('Restarting weather cron job...');
			
			try {
				$cron_manager = new Weather_Cron_Manager();
				$cron_manager->cleanup();
				$cron_manager->init();
				
				WP_CLI::success('Weather cron job restarted successfully!');
				
			} catch (Exception $e) {
				WP_CLI::error('Failed to restart weather cron job: ' . $e->getMessage());
			}
		}
	}
	
	// Register the CLI commands
	WP_CLI::add_command('weather-cron', 'Weather_Cron_CLI');
}
