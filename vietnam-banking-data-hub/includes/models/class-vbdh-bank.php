<?php
/**
 * Bank Model
 * Quản lý dữ liệu ngân hàng
 */

if (!defined('ABSPATH')) {
    exit;
}

class VBDH_Bank {

    /**
     * Lấy tất cả banks
     */
    public static function get_all($active_only = false) {
        global $wpdb;
        $table = $wpdb->prefix . 'vn_banks';

        $where = $active_only ? 'WHERE is_active = 1' : '';
        $sql = "SELECT * FROM $table $where ORDER BY bank_name ASC";

        return $wpdb->get_results($sql);
    }

    /**
     * Lấy bank theo ID
     */
    public static function get_by_id($bank_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'vn_banks';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $bank_id
        ));
    }

    /**
     * Lấy bank theo code
     */
    public static function get_by_code($bank_code) {
        global $wpdb;
        $table = $wpdb->prefix . 'vn_banks';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE bank_code = %s",
            $bank_code
        ));
    }

    /**
     * Tạo bank mới
     */
    public static function create($data) {
        global $wpdb;
        $table = $wpdb->prefix . 'vn_banks';

        $defaults = array(
            'bank_code' => '',
            'bank_name' => '',
            'bank_name_en' => '',
            'bank_logo_url' => '',
            'api_endpoint' => '',
            'api_type' => 'custom',
            'api_credentials' => '',
            'is_active' => 1
        );

        $data = wp_parse_args($data, $defaults);

        // Mã hóa credentials nếu có
        if (!empty($data['api_credentials']) && is_array($data['api_credentials'])) {
            $data['api_credentials'] = self::encrypt_credentials($data['api_credentials']);
        }

        $result = $wpdb->insert(
            $table,
            $data,
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d')
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Cập nhật bank
     */
    public static function update($bank_id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . 'vn_banks';

        // Mã hóa credentials nếu có
        if (isset($data['api_credentials']) && is_array($data['api_credentials'])) {
            $data['api_credentials'] = self::encrypt_credentials($data['api_credentials']);
        }

        return $wpdb->update(
            $table,
            $data,
            array('id' => $bank_id),
            null,
            array('%d')
        );
    }

    /**
     * Xóa bank
     */
    public static function delete($bank_id) {
        global $wpdb;
        $table = $wpdb->prefix . 'vn_banks';

        return $wpdb->delete(
            $table,
            array('id' => $bank_id),
            array('%d')
        );
    }

    /**
     * Cập nhật last_sync time
     */
    public static function update_last_sync($bank_id, $datetime = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'vn_banks';

        if (null === $datetime) {
            $datetime = current_time('mysql');
        }

        return $wpdb->update(
            $table,
            array('last_sync' => $datetime),
            array('id' => $bank_id),
            array('%s'),
            array('%d')
        );
    }

    /**
     * Mã hóa API credentials (simple base64 - nên dùng encryption tốt hơn trong production)
     */
    private static function encrypt_credentials($credentials) {
        return base64_encode(wp_json_encode($credentials));
    }

    /**
     * Giải mã API credentials
     */
    public static function decrypt_credentials($encrypted) {
        if (empty($encrypted)) {
            return array();
        }

        $decoded = base64_decode($encrypted);
        return json_decode($decoded, true) ?: array();
    }

    /**
     * Lấy API credentials đã giải mã
     */
    public static function get_credentials($bank_id) {
        $bank = self::get_by_id($bank_id);
        if (!$bank) {
            return array();
        }

        return self::decrypt_credentials($bank->api_credentials);
    }
}
