<?php
/**
 * Cron Jobs Handler
 * Quản lý scheduled tasks
 */

if (!defined('ABSPATH')) {
    exit;
}

class VBDH_Cron {

    /**
     * Fetch exchange rates (chạy mỗi 1 giờ)
     */
    public function fetch_exchange_rates() {
        $this->log_cron_start('exchange_rates');

        $fetcher = new VBDH_Data_Fetcher();
        $result = $fetcher->fetch_all_banks_rates('exchange_rates');

        $this->log_cron_end('exchange_rates', $result);

        return $result;
    }

    /**
     * Fetch interest rates (chạy mỗi 6 giờ)
     */
    public function fetch_interest_rates() {
        $this->log_cron_start('interest_rates');

        $fetcher = new VBDH_Data_Fetcher();
        $result = $fetcher->fetch_all_banks_rates('interest_rates');

        $this->log_cron_end('interest_rates', $result);

        // Cleanup old data
        $this->cleanup_old_data();

        return $result;
    }

    /**
     * Cleanup old data
     */
    private function cleanup_old_data() {
        $banks = VBDH_Bank::get_all();

        foreach ($banks as $bank) {
            // Xóa interest rates cũ hơn 30 ngày
            VBDH_Interest_Rate::delete_old_by_bank($bank->id);

            // Xóa exchange rates cũ hơn 7 ngày
            VBDH_Exchange_Rate::delete_old_by_bank($bank->id);
        }

        // Xóa API logs cũ hơn 30 ngày
        VBDH_API_Log::cleanup_old_logs(30);
    }

    /**
     * Log khi cron bắt đầu
     */
    private function log_cron_start($type) {
        update_option('vbdh_last_cron_' . $type . '_start', current_time('mysql'));
        update_option('vbdh_cron_' . $type . '_running', true);
    }

    /**
     * Log khi cron kết thúc
     */
    private function log_cron_end($type, $result) {
        update_option('vbdh_last_cron_' . $type . '_end', current_time('mysql'));
        update_option('vbdh_last_cron_' . $type . '_result', wp_json_encode($result));
        update_option('vbdh_cron_' . $type . '_running', false);
    }

    /**
     * Check xem cron có đang chạy không
     */
    public static function is_cron_running($type) {
        return (bool) get_option('vbdh_cron_' . $type . '_running', false);
    }

    /**
     * Get last cron run info
     */
    public static function get_last_run_info($type) {
        $start = get_option('vbdh_last_cron_' . $type . '_start');
        $end = get_option('vbdh_last_cron_' . $type . '_end');
        $result = get_option('vbdh_last_cron_' . $type . '_result');

        return array(
            'start' => $start,
            'end' => $end,
            'result' => $result ? json_decode($result, true) : null,
            'running' => self::is_cron_running($type)
        );
    }

    /**
     * Get next scheduled time
     */
    public static function get_next_scheduled($hook) {
        $timestamp = wp_next_scheduled($hook);

        if (!$timestamp) {
            return null;
        }

        return array(
            'timestamp' => $timestamp,
            'datetime' => date('Y-m-d H:i:s', $timestamp),
            'human' => human_time_diff($timestamp, current_time('timestamp'))
        );
    }

    /**
     * Reschedule cron job
     */
    public static function reschedule($hook, $schedule) {
        // Unschedule existing
        $timestamp = wp_next_scheduled($hook);
        if ($timestamp) {
            wp_unschedule_event($timestamp, $hook);
        }

        // Schedule new
        wp_schedule_event(time(), $schedule, $hook);
    }

    /**
     * Manual trigger (từ admin)
     */
    public static function manual_trigger($type) {
        $cron = new self();

        switch ($type) {
            case 'exchange_rates':
                return $cron->fetch_exchange_rates();

            case 'interest_rates':
                return $cron->fetch_interest_rates();

            case 'both':
                $fetcher = new VBDH_Data_Fetcher();
                return $fetcher->fetch_all_banks_rates('both');

            default:
                return array(
                    'success' => false,
                    'message' => 'Invalid type'
                );
        }
    }
}
