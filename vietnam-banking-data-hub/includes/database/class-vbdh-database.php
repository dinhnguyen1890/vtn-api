<?php
/**
 * Database Management Class
 * Quản lý tạo và cập nhật database tables
 */

if (!defined('ABSPATH')) {
    exit;
}

class VBDH_Database {

    /**
     * Tạo tất cả database tables
     */
    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $charset_collate = str_replace('utf8mb4_unicode_520_ci', 'utf8mb4_unicode_ci', $charset_collate);

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Table 1: Banks
        self::create_banks_table($wpdb, $charset_collate);

        // Table 2: Interest Rates
        self::create_interest_rates_table($wpdb, $charset_collate);

        // Table 3: Exchange Rates
        self::create_exchange_rates_table($wpdb, $charset_collate);

        // Table 4: API Logs
        self::create_api_logs_table($wpdb, $charset_collate);

        // Table 5: Content Generation Queue
        self::create_content_queue_table($wpdb, $charset_collate);
    }

    /**
     * Table 1: Banks
     */
    private static function create_banks_table($wpdb, $charset_collate) {
        $table_name = $wpdb->prefix . 'vn_banks';

        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            bank_code varchar(20) NOT NULL,
            bank_name varchar(255) NOT NULL,
            bank_name_en varchar(255) DEFAULT NULL,
            bank_logo_url varchar(500) DEFAULT NULL,
            api_endpoint varchar(500) DEFAULT NULL,
            api_type enum('bidv','vpbank','techcombank','vietcombank','custom') NOT NULL DEFAULT 'custom',
            api_credentials text DEFAULT NULL,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            last_sync datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY bank_code (bank_code),
            KEY is_active (is_active),
            KEY api_type (api_type)
        ) $charset_collate;";

        dbDelta($sql);
    }

    /**
     * Table 2: Interest Rates
     */
    private static function create_interest_rates_table($wpdb, $charset_collate) {
        $table_name = $wpdb->prefix . 'vn_interest_rates';
        $banks_table = $wpdb->prefix . 'vn_banks';

        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            bank_id bigint(20) NOT NULL,
            rate_type enum('savings','loan','credit_card') NOT NULL,
            product_name varchar(255) NOT NULL,
            term_months int(11) DEFAULT NULL,
            interest_rate decimal(5,2) NOT NULL,
            min_amount decimal(15,2) DEFAULT NULL,
            max_amount decimal(15,2) DEFAULT NULL,
            special_conditions text DEFAULT NULL,
            effective_date date DEFAULT NULL,
            fetched_at datetime DEFAULT CURRENT_TIMESTAMP,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY bank_id (bank_id),
            KEY rate_type (rate_type),
            KEY term_months (term_months),
            KEY effective_date (effective_date),
            KEY fetched_at (fetched_at)
        ) $charset_collate;";

        dbDelta($sql);
    }

    /**
     * Table 3: Exchange Rates
     */
    private static function create_exchange_rates_table($wpdb, $charset_collate) {
        $table_name = $wpdb->prefix . 'vn_exchange_rates';

        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            bank_id bigint(20) NOT NULL,
            currency_code varchar(10) NOT NULL,
            buy_rate decimal(12,2) DEFAULT NULL,
            sell_rate decimal(12,2) DEFAULT NULL,
            transfer_rate decimal(12,2) DEFAULT NULL,
            effective_date date DEFAULT NULL,
            effective_time time DEFAULT NULL,
            fetched_at datetime DEFAULT CURRENT_TIMESTAMP,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY bank_id (bank_id),
            KEY currency_code (currency_code),
            KEY effective_date (effective_date),
            KEY fetched_at (fetched_at),
            KEY bank_currency (bank_id, currency_code, effective_date)
        ) $charset_collate;";

        dbDelta($sql);
    }

    /**
     * Table 4: API Logs
     */
    private static function create_api_logs_table($wpdb, $charset_collate) {
        $table_name = $wpdb->prefix . 'vn_api_logs';

        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            bank_id bigint(20) DEFAULT NULL,
            api_endpoint varchar(500) NOT NULL,
            request_type varchar(50) DEFAULT NULL,
            status enum('success','error','timeout') NOT NULL,
            response_code int(11) DEFAULT NULL,
            error_message text DEFAULT NULL,
            execution_time float DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY bank_id (bank_id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";

        dbDelta($sql);
    }

    /**
     * Table 5: Content Generation Queue
     */
    private static function create_content_queue_table($wpdb, $charset_collate) {
        $table_name = $wpdb->prefix . 'vn_content_generation_queue';

        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            content_type enum('daily_rates','comparison','ranking','analysis') NOT NULL,
            trigger_type enum('schedule','data_change','manual') NOT NULL,
            parameters text DEFAULT NULL,
            status enum('pending','processing','completed','failed') NOT NULL DEFAULT 'pending',
            post_id bigint(20) DEFAULT NULL,
            error_message text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            processed_at datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY content_type (content_type),
            KEY status (status),
            KEY created_at (created_at),
            KEY post_id (post_id)
        ) $charset_collate;";

        dbDelta($sql);
    }

    /**
     * Xóa tất cả tables (dùng khi uninstall)
     */
    public static function drop_tables() {
        global $wpdb;

        $tables = array(
            $wpdb->prefix . 'vn_content_generation_queue',
            $wpdb->prefix . 'vn_api_logs',
            $wpdb->prefix . 'vn_exchange_rates',
            $wpdb->prefix . 'vn_interest_rates',
            $wpdb->prefix . 'vn_banks'
        );

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
    }

    /**
     * Kiểm tra xem tables đã tồn tại chưa
     */
    public static function tables_exist() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'vn_banks';
        $query = $wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($table_name));

        return $wpdb->get_var($query) === $table_name;
    }
}
