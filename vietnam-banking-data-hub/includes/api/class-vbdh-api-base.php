<?php
/**
 * API Base Class
 * Class cha cho tất cả API connectors
 */

if (!defined('ABSPATH')) {
    exit;
}

abstract class VBDH_API_Base {

    protected $bank_id;
    protected $bank_code;
    protected $api_endpoint;
    protected $credentials;
    protected $timeout = 30;
    protected $max_retries = 3;

    /**
     * Constructor
     */
    public function __construct($bank_id) {
        $this->bank_id = $bank_id;
        $this->load_bank_config();
    }

    /**
     * Load config của bank
     */
    protected function load_bank_config() {
        $bank = VBDH_Bank::get_by_id($this->bank_id);

        if (!$bank) {
            throw new Exception('Bank not found');
        }

        $this->bank_code = $bank->bank_code;
        $this->api_endpoint = $bank->api_endpoint;
        $this->credentials = VBDH_Bank::decrypt_credentials($bank->api_credentials);
    }

    /**
     * Abstract methods - phải implement trong child classes
     */
    abstract public function fetch_interest_rates();
    abstract public function fetch_exchange_rates();
    abstract protected function authenticate();

    /**
     * Make HTTP request với retry mechanism
     */
    protected function make_request($endpoint, $method = 'GET', $body = null, $headers = array()) {
        $start_time = microtime(true);
        $url = trailingslashit($this->api_endpoint) . ltrim($endpoint, '/');

        $args = array(
            'method' => $method,
            'timeout' => $this->timeout,
            'headers' => $headers,
            'body' => $body
        );

        $response = null;
        $last_error = '';

        // Retry mechanism
        for ($attempt = 1; $attempt <= $this->max_retries; $attempt++) {
            $response = wp_remote_request($url, $args);

            if (!is_wp_error($response)) {
                $execution_time = microtime(true) - $start_time;
                $response_code = wp_remote_retrieve_response_code($response);

                // Log success
                VBDH_API_Log::log_success(
                    $this->bank_id,
                    $url,
                    $response_code,
                    $execution_time
                );

                return $response;
            }

            $last_error = $response->get_error_message();

            // Nếu chưa đến retry cuối, đợi một chút
            if ($attempt < $this->max_retries) {
                sleep(pow(2, $attempt)); // Exponential backoff
            }
        }

        // Tất cả retries đều fail
        VBDH_API_Log::log_error(
            $this->bank_id,
            $url,
            'Failed after ' . $this->max_retries . ' retries. Last error: ' . $last_error
        );

        return new WP_Error('api_request_failed', $last_error);
    }

    /**
     * Parse JSON response
     */
    protected function parse_response($response) {
        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_parse_error', 'Failed to parse JSON response');
        }

        return $data;
    }

    /**
     * Validate response data
     */
    protected function validate_response($data) {
        if (is_wp_error($data)) {
            return false;
        }

        if (!is_array($data)) {
            return false;
        }

        return true;
    }

    /**
     * Get bank info
     */
    public function get_bank_info() {
        return array(
            'id' => $this->bank_id,
            'code' => $this->bank_code,
            'endpoint' => $this->api_endpoint
        );
    }
}
