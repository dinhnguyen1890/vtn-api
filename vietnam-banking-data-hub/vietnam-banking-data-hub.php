<?php
/**
 * Plugin Name: Vietnam Banking Data Hub
 * Plugin URI: https://github.com/dinhnguyen1890/vietnam-banking-data-hub
 * Description: Tổng hợp và tự động cập nhật dữ liệu lãi suất, tỷ giá từ các ngân hàng Việt Nam. Tích hợp AI để tạo nội dung tự động.
 * Version: 1.0.0
 * Requires at least: 6.4
 * Requires PHP: 8.0
 * Author: Vietnam Banking Data Hub Team
 * Author URI: https://github.com/dinhnguyen1890
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: vbdh
 * Domain Path: /languages
 */

// Ngăn chặn truy cập trực tiếp
if (!defined('ABSPATH')) {
    exit;
}

// Định nghĩa các constants
define('VBDH_VERSION', '1.0.0');
define('VBDH_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('VBDH_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VBDH_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Class chính của plugin
 */
class Vietnam_Banking_Data_Hub {

    /**
     * Instance của class (Singleton pattern)
     */
    private static $instance = null;

    /**
     * Lấy instance của class
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_cron_hooks();
    }

    /**
     * Load các file cần thiết
     */
    private function load_dependencies() {
        // Database
        require_once VBDH_PLUGIN_DIR . 'includes/database/class-vbdh-database.php';

        // Models
        require_once VBDH_PLUGIN_DIR . 'includes/models/class-vbdh-bank.php';
        require_once VBDH_PLUGIN_DIR . 'includes/models/class-vbdh-interest-rate.php';
        require_once VBDH_PLUGIN_DIR . 'includes/models/class-vbdh-exchange-rate.php';
        require_once VBDH_PLUGIN_DIR . 'includes/models/class-vbdh-api-log.php';

        // API
        require_once VBDH_PLUGIN_DIR . 'includes/api/class-vbdh-api-base.php';
        require_once VBDH_PLUGIN_DIR . 'includes/api/class-vbdh-api-bidv.php';

        // Core
        require_once VBDH_PLUGIN_DIR . 'includes/class-vbdh-data-fetcher.php';
        require_once VBDH_PLUGIN_DIR . 'includes/class-vbdh-cron.php';

        // Content Generation (Phase 2)
        require_once VBDH_PLUGIN_DIR . 'includes/content/class-vbdh-content-generator.php';

        // Admin
        if (is_admin()) {
            require_once VBDH_PLUGIN_DIR . 'admin/class-vbdh-admin.php';
        }
    }

    /**
     * Load ngôn ngữ
     */
    private function set_locale() {
        add_action('plugins_loaded', array($this, 'load_plugin_textdomain'));
    }

    /**
     * Load text domain
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'vbdh',
            false,
            dirname(VBDH_PLUGIN_BASENAME) . '/languages/'
        );
    }

    /**
     * Đăng ký admin hooks
     */
    private function define_admin_hooks() {
        if (is_admin()) {
            $admin = new VBDH_Admin();
            add_action('admin_menu', array($admin, 'add_admin_menu'));
            add_action('admin_enqueue_scripts', array($admin, 'enqueue_styles'));
            add_action('admin_enqueue_scripts', array($admin, 'enqueue_scripts'));
        }
    }

    /**
     * Đăng ký cron hooks
     */
    private function define_cron_hooks() {
        $cron = new VBDH_Cron();
        add_action('vbdh_fetch_exchange_rates', array($cron, 'fetch_exchange_rates'));
        add_action('vbdh_fetch_interest_rates', array($cron, 'fetch_interest_rates'));
    }
}

/**
 * Hook activation - Tạo database tables
 */
function vbdh_activate_plugin() {
    require_once VBDH_PLUGIN_DIR . 'includes/database/class-vbdh-database.php';
    VBDH_Database::create_tables();

    // Đăng ký cron jobs
    if (!wp_next_scheduled('vbdh_fetch_exchange_rates')) {
        wp_schedule_event(time(), 'hourly', 'vbdh_fetch_exchange_rates');
    }

    if (!wp_next_scheduled('vbdh_fetch_interest_rates')) {
        wp_schedule_event(time(), 'vbdh_every_six_hours', 'vbdh_fetch_interest_rates');
    }

    // Set plugin version
    update_option('vbdh_version', VBDH_VERSION);
    update_option('vbdh_activation_time', current_time('mysql'));
}
register_activation_hook(__FILE__, 'vbdh_activate_plugin');

/**
 * Hook deactivation - Xóa cron jobs
 */
function vbdh_deactivate_plugin() {
    // Xóa scheduled events
    $timestamp = wp_next_scheduled('vbdh_fetch_exchange_rates');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'vbdh_fetch_exchange_rates');
    }

    $timestamp = wp_next_scheduled('vbdh_fetch_interest_rates');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'vbdh_fetch_interest_rates');
    }
}
register_deactivation_hook(__FILE__, 'vbdh_deactivate_plugin');

/**
 * Thêm custom cron schedule
 */
function vbdh_custom_cron_schedules($schedules) {
    // Mỗi 6 giờ
    $schedules['vbdh_every_six_hours'] = array(
        'interval' => 6 * HOUR_IN_SECONDS,
        'display'  => __('Every 6 Hours', 'vbdh')
    );

    return $schedules;
}
add_filter('cron_schedules', 'vbdh_custom_cron_schedules');

/**
 * Khởi động plugin
 */
function vbdh_run() {
    return Vietnam_Banking_Data_Hub::get_instance();
}

// Start the plugin
vbdh_run();
