<?php
/**
 * Data Fetcher Class
 * Lấy dữ liệu từ các banks và lưu vào database
 */

if (!defined('ABSPATH')) {
    exit;
}

class VBDH_Data_Fetcher {

    private $cache_duration = HOUR_IN_SECONDS; // 1 hour cache

    /**
     * Fetch dữ liệu từ tất cả banks active
     */
    public function fetch_all_banks_rates($type = 'both') {
        $banks = VBDH_Bank::get_all(true); // Chỉ lấy active banks

        if (empty($banks)) {
            return array(
                'success' => false,
                'message' => 'Không có ngân hàng nào được kích hoạt'
            );
        }

        $results = array();

        foreach ($banks as $bank) {
            $result = $this->fetch_bank_rates($bank->id, $type);
            $results[$bank->bank_code] = $result;
        }

        return array(
            'success' => true,
            'results' => $results,
            'total_banks' => count($banks)
        );
    }

    /**
     * Fetch dữ liệu từ một bank cụ thể
     */
    public function fetch_bank_rates($bank_id, $type = 'both') {
        $bank = VBDH_Bank::get_by_id($bank_id);

        if (!$bank) {
            return array(
                'success' => false,
                'message' => 'Bank not found'
            );
        }

        // Check cache
        $cache_key = 'vbdh_bank_' . $bank_id . '_' . $type;
        $cached_data = get_transient($cache_key);

        if (false !== $cached_data) {
            return array(
                'success' => true,
                'cached' => true,
                'message' => 'Data from cache'
            );
        }

        try {
            $api = $this->get_api_instance($bank);

            if (!$api) {
                return array(
                    'success' => false,
                    'message' => 'API connector not found for ' . $bank->api_type
                );
            }

            $result = array(
                'success' => true,
                'bank_code' => $bank->bank_code,
                'bank_name' => $bank->bank_name
            );

            // Fetch interest rates
            if ($type === 'interest_rates' || $type === 'both') {
                $interest_rates = $api->fetch_interest_rates();

                if (!empty($interest_rates)) {
                    $saved = VBDH_Interest_Rate::bulk_create($interest_rates);
                    $result['interest_rates'] = array(
                        'fetched' => count($interest_rates),
                        'saved' => $saved ? true : false
                    );
                } else {
                    $result['interest_rates'] = array(
                        'fetched' => 0,
                        'message' => 'No interest rates data'
                    );
                }
            }

            // Fetch exchange rates
            if ($type === 'exchange_rates' || $type === 'both') {
                $exchange_rates = $api->fetch_exchange_rates();

                if (!empty($exchange_rates)) {
                    $saved = VBDH_Exchange_Rate::bulk_create($exchange_rates);
                    $result['exchange_rates'] = array(
                        'fetched' => count($exchange_rates),
                        'saved' => $saved ? true : false
                    );
                } else {
                    $result['exchange_rates'] = array(
                        'fetched' => 0,
                        'message' => 'No exchange rates data'
                    );
                }
            }

            // Update last sync time
            VBDH_Bank::update_last_sync($bank_id);

            // Set cache
            set_transient($cache_key, $result, $this->cache_duration);

            return $result;

        } catch (Exception $e) {
            VBDH_API_Log::log_error($bank_id, 'fetch_rates', $e->getMessage());

            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }

    /**
     * Lấy API instance dựa trên api_type của bank
     */
    private function get_api_instance($bank) {
        switch ($bank->api_type) {
            case 'bidv':
                return new VBDH_API_BIDV($bank->id);

            case 'vpbank':
                // TODO: Implement VPBank API
                return null;

            case 'techcombank':
                // TODO: Implement Techcombank API
                return null;

            case 'vietcombank':
                // TODO: Implement Vietcombank API
                return null;

            default:
                return null;
        }
    }

    /**
     * Detect data changes (so sánh với data cũ)
     */
    public function detect_changes($bank_id, $new_rates, $type = 'exchange') {
        $changes = array();

        if ($type === 'exchange') {
            // Get latest exchange rates
            $old_rates = VBDH_Exchange_Rate::get_by_bank($bank_id);

            // So sánh và detect changes
            foreach ($new_rates as $new_rate) {
                foreach ($old_rates as $old_rate) {
                    if ($old_rate->currency_code === $new_rate['currency_code']) {
                        if ($old_rate->buy_rate != $new_rate['buy_rate'] ||
                            $old_rate->sell_rate != $new_rate['sell_rate']) {
                            $changes[] = array(
                                'type' => 'exchange_rate',
                                'currency' => $new_rate['currency_code'],
                                'old_buy' => $old_rate->buy_rate,
                                'new_buy' => $new_rate['buy_rate'],
                                'old_sell' => $old_rate->sell_rate,
                                'new_sell' => $new_rate['sell_rate']
                            );
                        }
                        break;
                    }
                }
            }
        }

        return $changes;
    }

    /**
     * Clear cache cho một bank
     */
    public function clear_cache($bank_id = null) {
        if ($bank_id) {
            delete_transient('vbdh_bank_' . $bank_id . '_both');
            delete_transient('vbdh_bank_' . $bank_id . '_interest_rates');
            delete_transient('vbdh_bank_' . $bank_id . '_exchange_rates');
        } else {
            // Clear all bank caches
            $banks = VBDH_Bank::get_all();
            foreach ($banks as $bank) {
                $this->clear_cache($bank->id);
            }
        }
    }

    /**
     * Force fetch (bỏ qua cache)
     */
    public function force_fetch($bank_id, $type = 'both') {
        $this->clear_cache($bank_id);
        return $this->fetch_bank_rates($bank_id, $type);
    }
}
