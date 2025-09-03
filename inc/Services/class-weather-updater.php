<?php
// inc/Services/class-weather-updater.php
defined('ABSPATH') || exit;

/**
 * Weather Updater Service Class
 *
 * Queues weather updates to WP-Cron and handles per-city processing.
 */
class WeatherUpdater {

	/** Post meta key for weather cache */
	private const META_KEY = '__weather_cache';

	/** TTL (seconds) */
	private const CACHE_TTL = 3600;

	/** Cron hook name */
	public const CRON_HOOK = 'tw_update_weather_city';

    private const GLOBAL_WINDOW = 60;       // seconds

    private const GLOBAL_BUDGET = 45;       // maximum requests per window

	/** @var Weather_Client */
	private $weather_client;

	/** @var array Queue of city IDs to process */
	private $queue = [];

	public function __construct() {
		
		$this->weather_client = new Weather_Client();
	}

	/**
	 * Add a city to the processing queue
	 *
	 * @param int $city_id The city ID to add to the queue
	 * @return bool True if successfully added, false otherwise
	 */
	public function add_to_queue(int $city_id): bool {
		if ($city_id <= 0) {
			return false;
		}

		// Prevent duplicates in the queue
		if (!in_array($city_id, $this->queue, true)) {
			$this->queue[] = $city_id;
			return true;
		}

		return false;
	}

	/**
	 * Execute the queued weather updates
	 *
	 * Creates cron tasks for each city in the queue and spawns cron.
	 * This method schedules weather updates via WP-Cron for proper execution.
	 */
	public function execute_queue(): void {
		if (empty($this->queue)) {
			return;
		}

		$now = time();
		$offset = 0;

		// Create cron tasks for each city in the queue
		foreach ($this->queue as $city_id) {
			// Prevent duplicates
			if (wp_next_scheduled(self::CRON_HOOK, [$city_id])) {
				continue;
			}

			// Small delay to avoid overwhelming the API
			wp_schedule_single_event($now + $offset, self::CRON_HOOK, [$city_id]);
			$offset += 1; // 1 seconds step
		}

		// Push cron immediately (if DISABLE_WP_CRON is not disabled)
		if (function_exists('spawn_cron')) {
			spawn_cron();
		}

		// Clear the queue after scheduling
		$this->queue = [];
	}

    /**
	 * Update weather by city ID.
	 */
	public function update_weather_for_city_id(int $city_id): void {
		if ($city_id <= 0) return;

        if ($this->is_cache_fresh($city_id)) return;

        if (!$this->check_and_inc_global_budget()) return;

		$latitude  = get_post_meta($city_id, 'latitude', true);
		$longitude = get_post_meta($city_id, 'longitude', true);

		if (empty($latitude) || empty($longitude)) {
			$this->store_weather_cache($city_id, null, 'no_coordinates');
			return;
		}

		$weather_data = $this->weather_client->get_weather_by_coordinates($latitude, $longitude);

		if (is_wp_error($weather_data)) {
			$this->store_weather_cache($city_id, null, 'api_error');
			return;
		}

		$temperature_celsius = $weather_data['main']['temp'] ?? null;

		if ($temperature_celsius !== null) {
			$this->store_weather_cache($city_id, (float)$temperature_celsius, 'valid');
		} else {
			$this->store_weather_cache($city_id, null, 'no_temperature');
		}
	}

	

	/**
	 * Cron handler (called by one city).
	 * Must be public and callable.
	 */
	public static function cron_handler(int $city_id): void {
		$instance = new self();
		$instance->update_weather_for_city_id($city_id);
	}

	
	/**
	 * Save weather cache.
	 */
	private function store_weather_cache(int $city_id, ?float $temperature_celsius, string $status): void {
		$cache_data = [
			'temperature_celsius' => $temperature_celsius,
			'timestamp'           => time(),
			'ttl'                 => self::CACHE_TTL,
			'status'              => $status,
		];

		update_post_meta($city_id, self::META_KEY, wp_json_encode($cache_data));
	}

    private function is_cache_fresh(int $city_id): bool {
        $raw = get_post_meta($city_id, self::META_KEY, true);
        if (!$raw) return false;
        $data = json_decode((string)$raw, true);
        if (!is_array($data)) return false;
        $ts  = (int)($data['timestamp'] ?? 0);
        $ttl = (int)($data['ttl'] ?? self::CACHE_TTL);
        $status = (string)($data['status'] ?? '');
        // Only for valid data respect TTL
        if ($status !== 'valid') return false;
        return (time() - $ts) < $ttl;
    }

    private function check_and_inc_global_budget(): bool {
        $bucket = get_transient('tw_weather_budget');
        if (!is_array($bucket)) $bucket = ['start' => time(), 'count' => 0];
    
        if ((time() - $bucket['start']) >= self::GLOBAL_WINDOW) {
            $bucket = ['start' => time(), 'count' => 0]; // new window
        }
    
        if ($bucket['count'] >= self::GLOBAL_BUDGET) return false;
    
        $bucket['count']++;
        set_transient('tw_weather_budget', $bucket, self::GLOBAL_WINDOW + 5);
        return true;
    }
}


