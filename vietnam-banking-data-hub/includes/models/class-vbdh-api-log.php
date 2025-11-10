<?php
/**
 * API Log Model
 * Quản lý logs của API calls
 */

if (!defined('ABSPATH')) {
    exit;
}

class VBDH_API_Log {

    /**
     * Tạo log mới
     */
    public static function create($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'vn_api_logs';

        $defaults = array(
            'bank_id' => null,
            'api_endpoint' => '',
            'request_type' => 'GET',
            'status' => 'error',
            'response_code' => null,
            'error_message' => '',
            'execution_time' => 0
        );

        $data = wp_parse_args($data, $defaults);

        $wpdb->insert($table, $data);

        return $wpdb->insert_id;
    }

    /**
     * Log success call
     */
    public static function log_success($bank_id, $endpoint, $response_code, $execution_time) {
        return self::create(array(
            'bank_id' => $bank_id,
            'api_endpoint' => $endpoint,
            'request_type' => 'GET',
            'status' => 'success',
            'response_code' => $response_code,
            'execution_time' => $execution_time
        ));
    }

    /**
     * Log error call
     */
    public static function log_error($bank_id, $endpoint, $error_message, $response_code = null) {
        return self::create(array(
            'bank_id' => $bank_id,
            'api_endpoint' => $endpoint,
            'request_type' => 'GET',
            'status' => 'error',
            'response_code' => $response_code,
            'error_message' => $error_message
        ));
    }

    /**
     * Log timeout
     */
    public static function log_timeout($bank_id, $endpoint) {
        return self::create(array(
            'bank_id' => $bank_id,
            'api_endpoint' => $endpoint,
            'request_type' => 'GET',
            'status' => 'timeout',
            'error_message' => 'Request timeout'
        ));
    }

    /**
     * Lấy logs với filters
     */
    public static function get_logs($args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'vn_api_logs';
        $banks_table = $wpdb->prefix . 'vn_banks';

        $defaults = array(
            'bank_id' => null,
            'status' => null,
            'limit' => 100,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC'
        );

        $args = wp_parse_args($args, $defaults);

        $where = array('1=1');

        if ($args['bank_id']) {
            $where[] = $wpdb->prepare('l.bank_id = %d', $args['bank_id']);
        }

        if ($args['status']) {
            $where[] = $wpdb->prepare('l.status = %s', $args['status']);
        }

        $where_clause = implode(' AND ', $where);

        $sql = $wpdb->prepare(
            "SELECT l.*, b.bank_name, b.bank_code
            FROM $table l
            LEFT JOIN $banks_table b ON l.bank_id = b.id
            WHERE $where_clause
            ORDER BY l.{$args['orderby']} {$args['order']}
            LIMIT %d OFFSET %d",
            $args['limit'],
            $args['offset']
        );

        return $wpdb->get_results($sql);
    }

    /**
     * Đếm tổng số logs
     */
    public static function count_logs($args = array()) {
        global $wpdb;
        $table = $wpdb->prefix . 'vn_api_logs';

        $defaults = array(
            'bank_id' => null,
            'status' => null
        );

        $args = wp_parse_args($args, $defaults);

        $where = array('1=1');

        if ($args['bank_id']) {
            $where[] = $wpdb->prepare('bank_id = %d', $args['bank_id']);
        }

        if ($args['status']) {
            $where[] = $wpdb->prepare('status = %s', $args['status']);
        }

        $where_clause = implode(' AND ', $where);

        return $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE $where_clause");
    }

    /**
     * Lấy statistics
     */
    public static function get_stats($bank_id = null, $days = 7) {
        global $wpdb;
        $table = $wpdb->prefix . 'vn_api_logs';

        $where = $wpdb->prepare('WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)', $days);

        if ($bank_id) {
            $where .= $wpdb->prepare(' AND bank_id = %d', $bank_id);
        }

        $sql = "SELECT
                    COUNT(*) as total_calls,
                    SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) as success_calls,
                    SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as error_calls,
                    SUM(CASE WHEN status = 'timeout' THEN 1 ELSE 0 END) as timeout_calls,
                    AVG(execution_time) as avg_execution_time
                FROM $table
                $where";

        return $wpdb->get_row($sql);
    }

    /**
     * Xóa logs cũ
     */
    public static function cleanup_old_logs($days = 30) {
        global $wpdb;
        $table = $wpdb->prefix . 'vn_api_logs';

        return $wpdb->query($wpdb->prepare(
            "DELETE FROM $table WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        ));
    }
}
