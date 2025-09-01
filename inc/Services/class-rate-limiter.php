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
     */
    public function __construct($key_prefix = 'rate_limit', $max_requests = self::DEFAULT_MAX_REQUESTS, $window = self::DEFAULT_WINDOW) {
        $this->key_prefix = sanitize_key($key_prefix);
        $this->max_requests = (int) $max_requests;
        $this->window = (int) $window;
    }
    
    /**
     * Check if the current user is within rate limits
     * 
     * @return bool True if within rate limit, false otherwise
     */
    public function check_rate_limit() {
        $user_id = get_current_user_id();
        $ip = $this->get_client_ip();
        $key = $this->get_rate_limit_key($user_id, $ip);
        
        $requests = get_transient($key);
        if ($requests === false) {
            $requests = 1;
            set_transient($key, $requests, $this->window);
            return true;
        }
        
        if ($requests >= $this->max_requests) {
            return false;
        }
        
        set_transient($key, $requests + 1, $this->window);
        return true;
    }
    
    /**
     * Get the current request count for a user/IP
     * 
     * @return int Current request count
     */
    public function get_current_count() {
        $user_id = get_current_user_id();
        $ip = $this->get_client_ip();
        $key = $this->get_rate_limit_key($user_id, $ip);
        
        $requests = get_transient($key);
        return $requests ?: 0;
    }
    
    /**
     * Get remaining requests allowed for the current user/IP
     * 
     * @return int Remaining requests
     */
    public function get_remaining_requests() {
        $current = $this->get_current_count();
        return max(0, $this->max_requests - $current);
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
        
        $transient_timeout = get_option('_transient_timeout_' . $key);
        if ($transient_timeout) {
            return max(0, $transient_timeout - time());
        }
        
        return 0;
    }
    
    /**
     * Reset rate limit for current user/IP
     * 
     * @return bool True if reset successful
     */
    public function reset_rate_limit() {
        $user_id = get_current_user_id();
        $ip = $this->get_client_ip();
        $key = $this->get_rate_limit_key($user_id, $ip);
        
        return delete_transient($key);
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
        return $this->key_prefix . '_' . $identifier;
    }
    
    /**
     * Get client IP address
     * 
     * @return string Client IP address
     */
    private function get_client_ip() {
        $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Get rate limit configuration
     * 
     * @return array Rate limit configuration
     */
    public function get_config() {
        return [
            'max_requests' => $this->max_requests,
            'window' => $this->window,
            'key_prefix' => $this->key_prefix
        ];
    }
}
