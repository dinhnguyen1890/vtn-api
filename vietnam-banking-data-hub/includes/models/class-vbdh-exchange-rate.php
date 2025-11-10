<?php
/**
 * Exchange Rate Model
 * Quản lý dữ liệu tỷ giá
 */

if (!defined('ABSPATH')) {
    exit;
}

class VBDH_Exchange_Rate {

    /**
     * Lấy tỷ giá theo bank
     */
    public static function get_by_bank($bank_id, $currency = null, $limit = 50) {
        global $wpdb;
        $table = $wpdb->prefix . 'vn_exchange_rates';

        $where = $wpdb->prepare('WHERE bank_id = %d', $bank_id);

        if ($currency) {
            $where .= $wpdb->prepare(' AND currency_code = %s', $currency);
        }

        $sql = "SELECT * FROM $table $where ORDER BY fetched_at DESC LIMIT %d";

        return $wpdb->get_results($wpdb->prepare($sql, $limit));
    }

    /**
     * Lấy tỷ giá mới nhất của tất cả banks
     */
    public static function get_latest($currency = null, $limit = 100) {
        global $wpdb;
        $table = $wpdb->prefix . 'vn_exchange_rates';
        $banks_table = $wpdb->prefix . 'vn_banks';

        $where = 'WHERE b.is_active = 1';

        if ($currency) {
            $where .= $wpdb->prepare(' AND r.currency_code = %s', $currency);
        }

        $sql = "SELECT r.*, b.bank_name, b.bank_code, b.bank_logo_url
                FROM $table r
                INNER JOIN (
                    SELECT bank_id, currency_code, MAX(fetched_at) as max_fetched
                    FROM $table
                    GROUP BY bank_id, currency_code
                ) latest ON r.bank_id = latest.bank_id
                    AND r.currency_code = latest.currency_code
                    AND r.fetched_at = latest.max_fetched
                LEFT JOIN $banks_table b ON r.bank_id = b.id
                $where
                ORDER BY r.currency_code, r.buy_rate DESC
                LIMIT %d";

        return $wpdb->get_results($wpdb->prepare($sql, $limit));
    }

    /**
     * Tạo exchange rate mới
     */
    public static function create($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'vn_exchange_rates';

        $defaults = array(
            'bank_id' => 0,
            'currency_code' => '',
            'buy_rate' => 0,
            'sell_rate' => 0,
            'transfer_rate' => null,
            'effective_date' => current_time('mysql', false),
            'effective_time' => current_time('H:i:s')
        );

        $data = wp_parse_args($data, $defaults);

        return $wpdb->insert($table, $data);
    }

    /**
     * Bulk insert
     */
    public static function bulk_create($rates) {
        global $wpdb;
        $table = $wpdb->prefix . 'vn_exchange_rates';

        if (empty($rates)) {
            return false;
        }

        $values = array();
        $placeholders = array();

        foreach ($rates as $rate) {
            $placeholders[] = '(%d, %s, %f, %f, %f, %s, %s, %s)';
            $values[] = $rate['bank_id'];
            $values[] = $rate['currency_code'];
            $values[] = $rate['buy_rate'];
            $values[] = $rate['sell_rate'];
            $values[] = $rate['transfer_rate'] ?? null;
            $values[] = $rate['effective_date'] ?? current_time('mysql');
            $values[] = $rate['effective_time'] ?? current_time('H:i:s');
            $values[] = current_time('mysql');
        }

        $sql = "INSERT INTO $table
                (bank_id, currency_code, buy_rate, sell_rate, transfer_rate,
                 effective_date, effective_time, fetched_at)
                VALUES " . implode(', ', $placeholders);

        return $wpdb->query($wpdb->prepare($sql, $values));
    }

    /**
     * Xóa rates cũ
     */
    public static function delete_old_by_bank($bank_id, $before_date = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'vn_exchange_rates';

        if (!$before_date) {
            // Xóa rates cũ hơn 7 ngày
            $before_date = date('Y-m-d H:i:s', strtotime('-7 days'));
        }

        return $wpdb->query($wpdb->prepare(
            "DELETE FROM $table WHERE bank_id = %d AND fetched_at < %s",
            $bank_id,
            $before_date
        ));
    }

    /**
     * So sánh tỷ giá giữa các banks
     */
    public static function compare_banks($currency_code) {
        global $wpdb;
        $table = $wpdb->prefix . 'vn_exchange_rates';
        $banks_table = $wpdb->prefix . 'vn_banks';

        $sql = $wpdb->prepare(
            "SELECT r.*, b.bank_name, b.bank_code, b.bank_logo_url
            FROM $table r
            INNER JOIN (
                SELECT bank_id, MAX(fetched_at) as max_fetched
                FROM $table
                WHERE currency_code = %s
                GROUP BY bank_id
            ) latest ON r.bank_id = latest.bank_id AND r.fetched_at = latest.max_fetched
            LEFT JOIN $banks_table b ON r.bank_id = b.id
            WHERE r.currency_code = %s AND b.is_active = 1
            ORDER BY r.buy_rate DESC",
            $currency_code,
            $currency_code
        );

        return $wpdb->get_results($sql);
    }

    /**
     * Lấy danh sách currencies có sẵn
     */
    public static function get_available_currencies() {
        global $wpdb;
        $table = $wpdb->prefix . 'vn_exchange_rates';

        $sql = "SELECT DISTINCT currency_code FROM $table ORDER BY currency_code";

        return $wpdb->get_col($sql);
    }
}
