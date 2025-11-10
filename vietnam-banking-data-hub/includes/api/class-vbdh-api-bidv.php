<?php
/**
 * BIDV API Connector
 * Kết nối với API của ngân hàng BIDV
 */

if (!defined('ABSPATH')) {
    exit;
}

class VBDH_API_BIDV extends VBDH_API_Base {

    private $access_token = null;
    private $mock_mode = true; // Bật mock mode cho testing

    /**
     * Authenticate với BIDV API (OAuth 2.0)
     */
    protected function authenticate() {
        // Mock mode - return fake token
        if ($this->mock_mode) {
            $this->access_token = 'mock_access_token_' . time();
            return true;
        }

        // Real OAuth 2.0 implementation (để sau)
        $endpoint = 'oauth/token';
        $body = array(
            'grant_type' => 'client_credentials',
            'client_id' => $this->credentials['client_id'] ?? '',
            'client_secret' => $this->credentials['client_secret'] ?? ''
        );

        $response = $this->make_request($endpoint, 'POST', $body);

        if (is_wp_error($response)) {
            return false;
        }

        $data = $this->parse_response($response);

        if (isset($data['access_token'])) {
            $this->access_token = $data['access_token'];
            return true;
        }

        return false;
    }

    /**
     * Fetch lãi suất từ BIDV
     */
    public function fetch_interest_rates() {
        // Mock mode - return fake data
        if ($this->mock_mode) {
            return $this->get_mock_interest_rates();
        }

        // Authenticate trước
        if (!$this->access_token) {
            $this->authenticate();
        }

        $headers = array(
            'Authorization' => 'Bearer ' . $this->access_token,
            'Content-Type' => 'application/json'
        );

        $response = $this->make_request('api/v1/interest-rates', 'GET', null, $headers);

        if (is_wp_error($response)) {
            return array();
        }

        $data = $this->parse_response($response);

        return $this->normalize_interest_rates($data);
    }

    /**
     * Fetch tỷ giá từ BIDV
     */
    public function fetch_exchange_rates() {
        // Mock mode - return fake data
        if ($this->mock_mode) {
            return $this->get_mock_exchange_rates();
        }

        // Authenticate trước
        if (!$this->access_token) {
            $this->authenticate();
        }

        $headers = array(
            'Authorization' => 'Bearer ' . $this->access_token,
            'Content-Type' => 'application/json'
        );

        $response = $this->make_request('api/v1/exchange-rates', 'GET', null, $headers);

        if (is_wp_error($response)) {
            return array();
        }

        $data = $this->parse_response($response);

        return $this->normalize_exchange_rates($data);
    }

    /**
     * Mock data - Lãi suất giả
     */
    private function get_mock_interest_rates() {
        return array(
            // Lãi suất tiết kiệm
            array(
                'rate_type' => 'savings',
                'product_name' => 'Tiết kiệm không kỳ hạn',
                'term_months' => 0,
                'interest_rate' => 0.50,
                'min_amount' => 0,
                'special_conditions' => 'Rút tiền bất kỳ lúc nào'
            ),
            array(
                'rate_type' => 'savings',
                'product_name' => 'Tiết kiệm 1 tháng',
                'term_months' => 1,
                'interest_rate' => 2.50,
                'min_amount' => 1000000,
                'special_conditions' => ''
            ),
            array(
                'rate_type' => 'savings',
                'product_name' => 'Tiết kiệm 3 tháng',
                'term_months' => 3,
                'interest_rate' => 3.00,
                'min_amount' => 1000000,
                'special_conditions' => ''
            ),
            array(
                'rate_type' => 'savings',
                'product_name' => 'Tiết kiệm 6 tháng',
                'term_months' => 6,
                'interest_rate' => 4.50,
                'min_amount' => 1000000,
                'special_conditions' => 'Nhận lãi cuối kỳ'
            ),
            array(
                'rate_type' => 'savings',
                'product_name' => 'Tiết kiệm 12 tháng',
                'term_months' => 12,
                'interest_rate' => 5.25,
                'min_amount' => 1000000,
                'special_conditions' => 'Nhận lãi cuối kỳ hoặc hàng tháng'
            ),
            array(
                'rate_type' => 'savings',
                'product_name' => 'Tiết kiệm 24 tháng',
                'term_months' => 24,
                'interest_rate' => 5.50,
                'min_amount' => 10000000,
                'special_conditions' => 'Khách hàng VIP'
            ),
            // Lãi suất vay
            array(
                'rate_type' => 'loan',
                'product_name' => 'Vay tiêu dùng',
                'term_months' => 12,
                'interest_rate' => 8.50,
                'min_amount' => 10000000,
                'max_amount' => 500000000,
                'special_conditions' => 'Không cần tài sản đảm bảo'
            ),
            array(
                'rate_type' => 'loan',
                'product_name' => 'Vay mua nhà',
                'term_months' => 240,
                'interest_rate' => 7.00,
                'min_amount' => 100000000,
                'max_amount' => 5000000000,
                'special_conditions' => 'Có tài sản đảm bảo'
            ),
            // Lãi suất thẻ tín dụng
            array(
                'rate_type' => 'credit_card',
                'product_name' => 'Thẻ BIDV Visa Classic',
                'term_months' => null,
                'interest_rate' => 18.00,
                'special_conditions' => 'Miễn phí năm đầu'
            ),
            array(
                'rate_type' => 'credit_card',
                'product_name' => 'Thẻ BIDV Mastercard Gold',
                'term_months' => null,
                'interest_rate' => 17.50,
                'special_conditions' => 'Cashback 0.5%'
            )
        );
    }

    /**
     * Mock data - Tỷ giá giả
     */
    private function get_mock_exchange_rates() {
        return array(
            array(
                'currency_code' => 'USD',
                'buy_rate' => 23800.00,
                'sell_rate' => 24100.00,
                'transfer_rate' => 24050.00
            ),
            array(
                'currency_code' => 'EUR',
                'buy_rate' => 25800.00,
                'sell_rate' => 26200.00,
                'transfer_rate' => 26000.00
            ),
            array(
                'currency_code' => 'GBP',
                'buy_rate' => 29500.00,
                'sell_rate' => 30000.00,
                'transfer_rate' => 29750.00
            ),
            array(
                'currency_code' => 'JPY',
                'buy_rate' => 160.00,
                'sell_rate' => 165.00,
                'transfer_rate' => 162.50
            ),
            array(
                'currency_code' => 'AUD',
                'buy_rate' => 15800.00,
                'sell_rate' => 16200.00,
                'transfer_rate' => 16000.00
            ),
            array(
                'currency_code' => 'CNY',
                'buy_rate' => 3300.00,
                'sell_rate' => 3400.00,
                'transfer_rate' => 3350.00
            ),
            array(
                'currency_code' => 'THB',
                'buy_rate' => 680.00,
                'sell_rate' => 720.00,
                'transfer_rate' => 700.00
            )
        );
    }

    /**
     * Chuẩn hóa dữ liệu lãi suất về format thống nhất
     */
    private function normalize_interest_rates($data) {
        $normalized = array();

        foreach ($data as $rate) {
            $normalized[] = array(
                'bank_id' => $this->bank_id,
                'rate_type' => $rate['rate_type'],
                'product_name' => $rate['product_name'],
                'term_months' => $rate['term_months'] ?? null,
                'interest_rate' => $rate['interest_rate'],
                'min_amount' => $rate['min_amount'] ?? null,
                'max_amount' => $rate['max_amount'] ?? null,
                'special_conditions' => $rate['special_conditions'] ?? '',
                'effective_date' => current_time('mysql')
            );
        }

        return $normalized;
    }

    /**
     * Chuẩn hóa dữ liệu tỷ giá về format thống nhất
     */
    private function normalize_exchange_rates($data) {
        $normalized = array();

        foreach ($data as $rate) {
            $normalized[] = array(
                'bank_id' => $this->bank_id,
                'currency_code' => $rate['currency_code'],
                'buy_rate' => $rate['buy_rate'],
                'sell_rate' => $rate['sell_rate'],
                'transfer_rate' => $rate['transfer_rate'] ?? null,
                'effective_date' => current_time('mysql'),
                'effective_time' => current_time('H:i:s')
            );
        }

        return $normalized;
    }

    /**
     * Enable/Disable mock mode
     */
    public function set_mock_mode($enabled = true) {
        $this->mock_mode = $enabled;
    }
}
