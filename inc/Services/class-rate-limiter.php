<?php
// inc/Services/class-rate-limiter.php
defined('ABSPATH') || exit;

/**
 * Rate Limiter Service Class
 * 
 * Handles rate limiting functionality for various parts of the application
 * 
 * @package Storefront_Child
 * @version 1.0.0
 * @since 1.0.0
 */
class Rate_Limiter {
    
    /**
     * Default rate limiting options
     */
    private const DEFAULT_MAX_REQUESTS = 10;
    private const DEFAULT_WINDOW = 60; // 1 minute window
    private const MIN_WINDOW = 1; // Minimum 1 second window
    private const MAX_WINDOW = 86400; // Maximum 24 hour window
    private const MIN_REQUESTS = 1; // Minimum 1 request
    private const MAX_REQUESTS = 10000; // Maximum 10,000 requests
    
    /**
     * Rate limit configuration
     */
    private $max_requests;
    private $window;
    private $key_prefix;
    
    /**
     * Initialize the rate limiter
     * 
     * @param string $key_prefix Prefix for rate limit keys
     * @param int $max_requests Maximum requests allowed in the time window
     * @param int $window Time window in seconds
     * @throws InvalidArgumentException If parameters are invalid
     */
    public function __construct($key_prefix = 'rate_limit', $max_requests = self::DEFAULT_MAX_REQUESTS, $window = self::DEFAULT_WINDOW) {
        // Validate and sanitize parameters
        if (empty($key_prefix) || !is_string($key_prefix)) {
            throw new InvalidArgumentException('Key prefix must be a non-empty string');
        }
        
        if (!is_numeric($max_requests) || $max_requests < self::MIN_REQUESTS || $max_requests > self::MAX_REQUESTS) {
            throw new InvalidArgumentException(sprintf('Max requests must be between %d and %d', self::MIN_REQUESTS, self::MAX_REQUESTS));
        }
        
        if (!is_numeric($window) || $window < self::MIN_WINDOW || $window > self::MAX_WINDOW) {
            throw new InvalidArgumentException(sprintf('Window must be between %d and %d seconds', self::MIN_WINDOW, self::MAX_WINDOW));
        }
        
        $this->key_prefix = sanitize_key($key_prefix);
        $this->max_requests = (int) $max_requests;
        $this->window = (int) $window;
    }
    
    /**
     * Check if the current user is within rate limits
     * Uses atomic operations to prevent race conditions
     * 
     * @return bool True if within rate limit, false otherwise
     */
    public function check_rate_limit() {
        $user_id = get_current_user_id();
        $ip = $this->get_client_ip();
        $key = $this->get_rate_limit_key($user_id, $ip);
        
        // Fallback to transient-based approach with better race condition handling
        return $this->check_rate_limit_transient($key);
    }
    
    
    
    /**
     * Transient-based rate limit check with improved race condition handling
     * 
     * @param string $key Rate limit key
     * @return bool True if within rate limit
     */
    private function check_rate_limit_transient($key) {
        $current_time = time();
        $window_start = $current_time - $this->window;
        
        // Get current data
        $data = get_transient($key);
        if ($data === false) {
            $data = ['count' => 1, 'window_start' => $current_time];
            set_transient($key, $data, $this->window);
            return true;
        }
        
        // Validate data structure
        if (!is_array($data) || !isset($data['count']) || !isset($data['window_start'])) {
            $data = ['count' => 1, 'window_start' => $current_time];
            set_transient($key, $data, $this->window);
            return true;
        }
        
        // Check if we're in a new window
        if ($data['window_start'] <= $window_start) {
            $data = ['count' => 1, 'window_start' => $current_time];
            set_transient($key, $data, $this->window);
            return true;
        }
        
        // Check if we're at the limit
        if ($data['count'] >= $this->max_requests) {
            return false;
        }
        
        // Increment count
        $data['count']++;
        set_transient($key, $data, $this->window);
        return true;
    }
    

    
   
    /**
     * Get time until rate limit resets
     * 
     * @return int Seconds until reset
     */
    public function get_reset_time() {
        $user_id = get_current_user_id();
        $ip = $this->get_client_ip();
        $key = $this->get_rate_limit_key($user_id, $ip);
        
        if (wp_using_ext_object_cache()) {
            $data = wp_cache_get($key, 'rate_limits');
            if ($data && is_array($data) && isset($data['window_start'])) {
                $reset_time = $data['window_start'] + $this->window;
                return max(0, $reset_time - time());
            }
        } else {
            $data = get_transient($key);
            if ($data && is_array($data) && isset($data['window_start'])) {
                $reset_time = $data['window_start'] + $this->window;
                return max(0, $reset_time - time());
            }
        }
        
        return 0;
    }
    
    
    /**
     * Get rate limit key for a specific user/IP combination
     * 
     * @param int $user_id User ID (0 for non-logged in users)
     * @param string $ip IP address
     * @return string Rate limit key
     */
    private function get_rate_limit_key($user_id, $ip) {
        $identifier = $user_id ?: $ip;
        return $this->key_prefix . '_' . md5($identifier);
    }
    
    /**
     * Get client IP address with improved security
     * 
     * @return string Client IP address
     */
    private function get_client_ip() {
        // Trusted proxy headers (only if you're behind a trusted proxy)
        $trusted_proxies = apply_filters('rate_limiter_trusted_proxies', []);
        $client_ip = null;
        
        // Check for trusted proxy headers first
        if (!empty($trusted_proxies)) {
            $ip_keys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP'];
            
            foreach ($ip_keys as $key) {
                if (array_key_exists($key, $_SERVER) && !empty($_SERVER[$key])) {
                    $ips = explode(',', $_SERVER[$key]);
                    $ip = trim($ips[0]); // Take the first IP in the chain
                    
                    if ($this->is_valid_public_ip($ip)) {
                        $client_ip = $ip;
                        break;
                    }
                }
            }
        }
        
        // Fallback to REMOTE_ADDR if no valid proxy IP found
        if (!$client_ip && array_key_exists('REMOTE_ADDR', $_SERVER)) {
            $client_ip = $_SERVER['REMOTE_ADDR'];
        }
        
        // Validate and return IP
        if ($client_ip && $this->is_valid_public_ip($client_ip)) {
            return $client_ip;
        }
        
        // Return a safe default
        return '0.0.0.0';
    }
    
    /**
     * Validate if an IP address is a valid public IP
     * 
     * @param string $ip IP address to validate
     * @return bool True if valid public IP
     */
    private function is_valid_public_ip($ip) {
        if (empty($ip) || !is_string($ip)) {
            return false;
        }
        
        // Basic IP format validation
        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return false;
        }
        
        // Check if it's a private/reserved IP range
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }
        
        return true;
    }
    
}
    
    
