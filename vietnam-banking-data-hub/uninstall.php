<?php
/**
 * Uninstall Script
 * Xóa tất cả dữ liệu khi plugin bị gỡ cài đặt
 */

// Ngăn chặn truy cập trực tiếp
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Load database class
require_once plugin_dir_path(__FILE__) . 'includes/database/class-vbdh-database.php';

// Xóa tất cả database tables
VBDH_Database::drop_tables();

// Xóa options
delete_option('vbdh_version');
delete_option('vbdh_activation_time');

// Xóa cron-related options
delete_option('vbdh_last_cron_exchange_rates_start');
delete_option('vbdh_last_cron_exchange_rates_end');
delete_option('vbdh_last_cron_exchange_rates_result');
delete_option('vbdh_cron_exchange_rates_running');

delete_option('vbdh_last_cron_interest_rates_start');
delete_option('vbdh_last_cron_interest_rates_end');
delete_option('vbdh_last_cron_interest_rates_result');
delete_option('vbdh_cron_interest_rates_running');

// Xóa tất cả transients
global $wpdb;
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_vbdh_%' OR option_name LIKE '_transient_timeout_vbdh_%'");

// Log uninstall
if (function_exists('error_log')) {
    error_log('Vietnam Banking Data Hub plugin uninstalled successfully');
}
