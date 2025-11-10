<?php
/**
 * Interest Rate Model
 * Quản lý dữ liệu lãi suất
 */

if (!defined('ABSPATH')) {
    exit;
}

class VBDH_Interest_Rate {

    /**
     * Lấy lãi suất theo bank
     */
    public static function get_by_bank($bank_id, $rate_type = null, $limit = 100) {
        global $wpdb;
        $table = $wpdb->prefix . 'vn_interest_rates';

        $where = $wpdb->prepare('WHERE bank_id = %d', $bank_id);

        if ($rate_type) {
            $where .= $wpdb->prepare(' AND rate_type = %s', $rate_type);
        }

        $sql = "SELECT * FROM $table $where ORDER BY term_months ASC, interest_rate DESC LIMIT %d";

        return $wpdb->get_results($wpdb->prepare($sql, $limit));
    }

    /**
     * Lấy lãi suất mới nhất
     */
    public static function get_latest($limit = 50) {
        global $wpdb;
        $table = $wpdb->prefix . 'vn_interest_rates';
        $banks_table = $wpdb->prefix . 'vn_banks';

        $sql = "SELECT r.*, b.bank_name, b.bank_code
                FROM $table r
                LEFT JOIN $banks_table b ON r.bank_id = b.id
                WHERE b.is_active = 1
                ORDER BY r.fetched_at DESC
                LIMIT %d";

        return $wpdb->get_results($wpdb->prepare($sql, $limit));
    }

    /**
     * Tạo interest rate mới
     */
    public static function create($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'vn_interest_rates';

        $defaults = array(
            'bank_id' => 0,
            'rate_type' => 'savings',
            'product_name' => '',
            'term_months' => 0,
            'interest_rate' => 0,
            'min_amount' => null,
            'max_amount' => null,
            'special_conditions' => '',
            'effective_date' => current_time('mysql', false)
        );

        $data = wp_parse_args($data, $defaults);

        return $wpdb->insert($table, $data);
    }

    /**
     * Bulk insert - hiệu quả hơn khi insert nhiều records
     */
    public static function bulk_create($rates) {
        global $wpdb;
        $table = $wpdb->prefix . 'vn_interest_rates';

        if (empty($rates)) {
            return false;
        }

        $values = array();
        $placeholders = array();

        foreach ($rates as $rate) {
            $placeholders[] = '(%d, %s, %s, %d, %f, %f, %f, %s, %s, %s)';
            $values[] = $rate['bank_id'];
            $values[] = $rate['rate_type'];
            $values[] = $rate['product_name'];
            $values[] = $rate['term_months'] ?? 0;
            $values[] = $rate['interest_rate'];
            $values[] = $rate['min_amount'] ?? null;
            $values[] = $rate['max_amount'] ?? null;
            $values[] = $rate['special_conditions'] ?? '';
            $values[] = $rate['effective_date'] ?? current_time('mysql');
            $values[] = current_time('mysql');
        }

        $sql = "INSERT INTO $table
                (bank_id, rate_type, product_name, term_months, interest_rate,
                 min_amount, max_amount, special_conditions, effective_date, fetched_at)
                VALUES " . implode(', ', $placeholders);

        return $wpdb->query($wpdb->prepare($sql, $values));
    }

    /**
     * Xóa rates cũ của một bank (trước khi sync mới)
     */
    public static function delete_old_by_bank($bank_id, $before_date = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'vn_interest_rates';

        if (!$before_date) {
            // Xóa rates cũ hơn 30 ngày
            $before_date = date('Y-m-d H:i:s', strtotime('-30 days'));
        }

        return $wpdb->query($wpdb->prepare(
            "DELETE FROM $table WHERE bank_id = %d AND fetched_at < %s",
            $bank_id,
            $before_date
        ));
    }

    /**
     * So sánh lãi suất giữa các ngân hàng
     */
    public static function compare_banks($rate_type = 'savings', $term_months = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'vn_interest_rates';
        $banks_table = $wpdb->prefix . 'vn_banks';

        $where = $wpdb->prepare('WHERE r.rate_type = %s AND b.is_active = 1', $rate_type);

        if ($term_months !== null) {
            $where .= $wpdb->prepare(' AND r.term_months = %d', $term_months);
        }

        $sql = "SELECT r.*, b.bank_name, b.bank_code, b.bank_logo_url
                FROM $table r
                INNER JOIN (
                    SELECT bank_id, MAX(fetched_at) as max_fetched
                    FROM $table
                    GROUP BY bank_id
                ) latest ON r.bank_id = latest.bank_id AND r.fetched_at = latest.max_fetched
                LEFT JOIN $banks_table b ON r.bank_id = b.id
                $where
                ORDER BY r.interest_rate DESC";

        return $wpdb->get_results($sql);
    }
}
