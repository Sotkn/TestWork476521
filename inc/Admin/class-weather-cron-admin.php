<?php
// inc/Admin/class-weather-cron-admin.php
defined('ABSPATH') || exit;

/**
 * Weather Cron Admin Class
 *
 * Provides an admin dashboard for monitoring and managing the weather cron job.
 * Shows cron status, next run time, and provides manual controls.
 */
class Weather_Cron_Admin {

	/** Admin page slug */
	private const ADMIN_PAGE_SLUG = 'weather-cron-status';

	/** Admin page capability */
	private const ADMIN_CAPABILITY = 'manage_options';

	public function __construct() {
		add_action('admin_menu', [$this, 'add_admin_menu']);
		add_action('admin_init', [$this, 'handle_admin_actions']);
	}

	/**
	 * Add admin menu page
	 */
	public function add_admin_menu(): void {
		add_submenu_page(
			'tools.php', // Parent page
			__('Weather Cron Status', 'storefront-child'),
			__('Weather Cron', 'storefront-child'),
			self::ADMIN_CAPABILITY,
			self::ADMIN_PAGE_SLUG,
			[$this, 'render_admin_page']
		);
	}

	/**
	 * Handle admin actions (manual trigger, cleanup, etc.)
	 */
	public function handle_admin_actions(): void {
		if (!current_user_can(self::ADMIN_CAPABILITY)) {
			return;
		}

		$action = $_GET['action'] ?? '';
		$nonce = $_GET['_wpnonce'] ?? '';

		if ($action === 'trigger_now' && wp_verify_nonce($nonce, 'trigger_weather_cron')) {
			$this->trigger_cron_now();
		} elseif ($action === 'cleanup' && wp_verify_nonce($nonce, 'cleanup_weather_cron')) {
			$this->cleanup_cron();
		} elseif ($action === 'reschedule' && wp_verify_nonce($nonce, 'reschedule_weather_cron')) {
			$this->reschedule_cron();
		}
	}

	/**
	 * Manually trigger the cron job now
	 */
	private function trigger_cron_now(): void {
		try {
			$cron_manager = new Weather_Cron_Manager();
			$cron_manager->cron_handler();
			
			wp_redirect(add_query_arg('message', 'triggered', admin_url('tools.php?page=' . self::ADMIN_PAGE_SLUG)));
			exit;
		} catch (Exception $e) {
			wp_redirect(add_query_arg('message', 'error', admin_url('tools.php?page=' . self::ADMIN_PAGE_SLUG)));
			exit;
		}
	}

	/**
	 * Clean up the cron job
	 */
	private function cleanup_cron(): void {
		try {
			$cron_manager = new Weather_Cron_Manager();
			$cron_manager->cleanup();
			
			wp_redirect(add_query_arg('message', 'cleaned', admin_url('tools.php?page=' . self::ADMIN_PAGE_SLUG)));
			exit;
		} catch (Exception $e) {
			wp_redirect(add_query_arg('message', 'error', admin_url('tools.php?page=' . self::ADMIN_PAGE_SLUG)));
			exit;
		}
	}

	/**
	 * Reschedule the cron job
	 */
	private function reschedule_cron(): void {
		try {
			$cron_manager = new Weather_Cron_Manager();
			$cron_manager->cleanup();
			$cron_manager->init();
			
			wp_redirect(add_query_arg('message', 'rescheduled', admin_url('tools.php?page=' . self::ADMIN_PAGE_SLUG)));
			exit;
		} catch (Exception $e) {
			wp_redirect(add_query_arg('message', 'error', admin_url('tools.php?page=' . self::ADMIN_PAGE_SLUG)));
			exit;
		}
	}

	/**
	 * Render the admin page
	 */
	public function render_admin_page(): void {
		$cron_manager = new Weather_Cron_Manager();
		$next_run = $cron_manager->get_next_run_time();
		$is_scheduled = $cron_manager->is_scheduled();
		
		// Get cities count for display
		$cities = Cities_Repository::get_cities_with_countries();
		$cities_count = count($cities);
		
		?>
		<div class="wrap">
			<h1><?php _e('Weather Cron Status', 'storefront-child'); ?></h1>
			
			<?php $this->display_admin_messages(); ?>
			
			<div class="card">
				<h2><?php _e('Cron Job Status', 'storefront-child'); ?></h2>
				
				<table class="form-table">
					<tr>
						<th scope="row"><?php _e('Status', 'storefront-child'); ?></th>
						<td>
							<?php if ($is_scheduled): ?>
								<span style="color: green;">✓ <?php _e('Active', 'storefront-child'); ?></span>
							<?php else: ?>
								<span style="color: red;">✗ <?php _e('Inactive', 'storefront-child'); ?></span>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Next Run', 'storefront-child'); ?></th>
						<td>
							<?php if ($next_run): ?>
								<?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $next_run); ?>
								<br>
								<small><?php echo human_time_diff(time(), $next_run); ?> from now</small>
							<?php else: ?>
								<?php _e('Not scheduled', 'storefront-child'); ?>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Interval', 'storefront-child'); ?></th>
						<td><?php _e('Every 5 minutes', 'storefront-child'); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php _e('Cities Count', 'storefront-child'); ?></th>
						<td><?php echo number_format($cities_count); ?></td>
					</tr>
				</table>
			</div>
			
			<div class="card">
				<h2><?php _e('Actions', 'storefront-child'); ?></h2>
				
				<p>
					<a href="<?php echo wp_nonce_url(admin_url('tools.php?page=' . self::ADMIN_PAGE_SLUG . '&action=trigger_now'), 'trigger_weather_cron'); ?>" 
					   class="button button-primary">
						<?php _e('Trigger Now', 'storefront-child'); ?>
					</a>
					
					<a href="<?php echo wp_nonce_url(admin_url('tools.php?page=' . self::ADMIN_PAGE_SLUG . '&action=reschedule'), 'reschedule_weather_cron'); ?>" 
					   class="button button-secondary">
						<?php _e('Reschedule', 'storefront-child'); ?>
					</a>
					
					<a href="<?php echo wp_nonce_url(admin_url('tools.php?page=' . self::ADMIN_PAGE_SLUG . '&action=cleanup'), 'cleanup_weather_cron'); ?>" 
					   class="button button-link-delete" 
					   onclick="return confirm('<?php _e('Are you sure you want to stop the cron job?', 'storefront-child'); ?>')">
						<?php _e('Stop Cron Job', 'storefront-child'); ?>
					</a>
				</p>
				
				<p class="description">
					<?php _e('Use "Trigger Now" to manually run the weather update for all cities. Use "Reschedule" to restart the cron job if it\'s not working properly.', 'storefront-child'); ?>
				</p>
			</div>
			
			<div class="card">
				<h2><?php _e('How It Works', 'storefront-child'); ?></h2>
				
				<ol>
					<li><?php _e('Every 5 minutes, the cron job retrieves all published cities from the database.', 'storefront-child'); ?></li>
					<li><?php _e('Each city is added to the weather update queue.', 'storefront-child'); ?></li>
					<li><?php _e('Individual city weather updates are scheduled with small delays to avoid overwhelming the weather API.', 'storefront-child'); ?></li>
					<li><?php _e('Weather data is fetched and cached for each city.', 'storefront-child'); ?></li>
					<li><?php _e('The process respects rate limiting to stay within API limits.', 'storefront-child'); ?></li>
				</ol>
			</div>
		</div>
		<?php
	}

	/**
	 * Display admin messages
	 */
	private function display_admin_messages(): void {
		$message = $_GET['message'] ?? '';
		
		switch ($message) {
			case 'triggered':
				echo '<div class="notice notice-success is-dismissible"><p>' . 
					 __('Weather cron job triggered successfully!', 'storefront-child') . '</p></div>';
				break;
			case 'cleaned':
				echo '<div class="notice notice-warning is-dismissible"><p>' . 
					 __('Weather cron job stopped successfully.', 'storefront-child') . '</p></div>';
				break;
			case 'rescheduled':
				echo '<div class="notice notice-success is-dismissible"><p>' . 
					 __('Weather cron job rescheduled successfully!', 'storefront-child') . '</p></div>';
				break;
			case 'error':
				echo '<div class="notice notice-error is-dismissible"><p>' . 
					 __('An error occurred while processing the request.', 'storefront-child') . '</p></div>';
				break;
		}
	}
}
