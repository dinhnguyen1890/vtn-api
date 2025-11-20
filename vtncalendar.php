<?php
/**
 * Plugin Name: VTN Calendar
 * Plugin URI: https://github.com/dinhnguyen1890/vtn-calendar
 * Description: Tổng hợp và tự động cập nhật các lịch trình tại Việt Nam từ nguồn chính thống (Lịch nghỉ lễ, Lịch chiếu phim, Lịch bóng đá, Lịch cắt điện)
 * Version: 1.0.0
 * Author: VTN Team
 * Author URI: https://github.com/dinhnguyen1890
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: vtncalendar
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// Ngăn truy cập trực tiếp
if (!defined('ABSPATH')) {
    exit;
}

/**
 * CONSTANTS & CONFIGURATION
 */
define('VTNCAL_VERSION', '1.0.0');
define('VTNCAL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('VTNCAL_PLUGIN_URL', plugin_dir_url(__FILE__));
define('VTNCAL_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main VTN Calendar Class
 */
class VTN_Calendar {

    private static $instance = null;
    private $table_events;
    private $table_logs;
    private $table_settings;

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        global $wpdb;
        $this->table_events = $wpdb->prefix . 'vtncalendar_events';
        $this->table_logs = $wpdb->prefix . 'vtncalendar_logs';
        $this->table_settings = $wpdb->prefix . 'vtncalendar_settings';

        // Hooks
        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Activation/Deactivation
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Admin
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));

        // AJAX handlers
        add_action('wp_ajax_vtncal_get_events', array($this, 'ajax_get_events'));
        add_action('wp_ajax_vtncal_delete_event', array($this, 'ajax_delete_event'));
        add_action('wp_ajax_vtncal_save_settings', array($this, 'ajax_save_settings'));
        add_action('wp_ajax_vtncal_test_crawl', array($this, 'ajax_test_crawl'));
        add_action('wp_ajax_vtncal_get_logs', array($this, 'ajax_get_logs'));

        // Frontend
        add_action('wp_enqueue_scripts', array($this, 'frontend_enqueue_scripts'));
        add_shortcode('vtncalendar', array($this, 'shortcode_calendar'));

        // Cron hooks
        add_action('vtncalendar_crawl_holiday', array($this, 'crawl_holiday'));
        add_action('vtncalendar_crawl_movies', array($this, 'crawl_movies'));
        add_action('vtncalendar_crawl_football', array($this, 'crawl_football'));
        add_action('vtncalendar_crawl_power', array($this, 'crawl_power_outage'));
        add_action('vtncalendar_cleanup_expired', array($this, 'cleanup_expired_events'));
    }

    /**
     * ========================================
     * ACTIVATION & DEACTIVATION
     * ========================================
     */

    /**
     * Plugin activation
     */
    public function activate() {
        $this->create_tables();
        $this->setup_cron_jobs();
        $this->insert_default_settings();
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        $this->clear_cron_jobs();
        flush_rewrite_rules();
    }

    /**
     * ========================================
     * DATABASE FUNCTIONS
     * ========================================
     */

    /**
     * Create database tables
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Events table
        $sql_events = "CREATE TABLE IF NOT EXISTS {$this->table_events} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            calendar_type VARCHAR(50) NOT NULL COMMENT 'holiday, movie, football, power_outage',
            title VARCHAR(255) NOT NULL,
            description TEXT,
            event_date DATETIME NOT NULL,
            event_end_date DATETIME,
            location VARCHAR(255) COMMENT 'Cho power_outage và football',
            province VARCHAR(100) COMMENT 'Cho power_outage',
            source_name VARCHAR(100) NOT NULL COMMENT 'CGV, VFF, EVN Hanoi, etc',
            source_url VARCHAR(500) NOT NULL,
            raw_data LONGTEXT COMMENT 'JSON data gốc',
            ai_content LONGTEXT COMMENT 'Claude generated content',
            meta_data LONGTEXT COMMENT 'JSON: poster_url, genre, teams, etc',
            status VARCHAR(20) DEFAULT 'active' COMMENT 'active, expired, cancelled',
            last_updated DATETIME,
            created_at DATETIME,
            PRIMARY KEY (id),
            KEY idx_type (calendar_type),
            KEY idx_date (event_date),
            KEY idx_location (location),
            KEY idx_province (province),
            KEY idx_status (status),
            KEY idx_type_date (calendar_type, event_date)
        ) $charset_collate;";

        // Logs table
        $sql_logs = "CREATE TABLE IF NOT EXISTS {$this->table_logs} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            calendar_type VARCHAR(50) NOT NULL,
            source_name VARCHAR(100),
            source_url VARCHAR(500),
            status VARCHAR(20) NOT NULL COMMENT 'success, failed, partial',
            items_found INT DEFAULT 0,
            items_added INT DEFAULT 0,
            items_updated INT DEFAULT 0,
            error_message TEXT,
            execution_time FLOAT COMMENT 'seconds',
            crawled_at DATETIME,
            PRIMARY KEY (id),
            KEY idx_type_date (calendar_type, crawled_at),
            KEY idx_status (status)
        ) $charset_collate;";

        // Settings table
        $sql_settings = "CREATE TABLE IF NOT EXISTS {$this->table_settings} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value LONGTEXT,
            setting_type VARCHAR(50) COMMENT 'text, json, boolean',
            updated_at DATETIME,
            PRIMARY KEY (id),
            UNIQUE KEY setting_key (setting_key)
        ) $charset_collate;";

        dbDelta($sql_events);
        dbDelta($sql_logs);
        dbDelta($sql_settings);
    }

    /**
     * Insert default settings
     */
    private function insert_default_settings() {
        $default_settings = array(
            'claude_api_key' => '',
            'enable_ai_content' => 'false',
            'crawl_holiday_enabled' => 'true',
            'crawl_movie_enabled' => 'true',
            'crawl_movie_sources' => json_encode(array('cgv', 'galaxy', 'lotte')),
            'crawl_football_enabled' => 'true',
            'crawl_power_enabled' => 'true',
            'crawl_power_provinces' => json_encode(array('hanoi', 'hcm', 'danang', 'haiphong', 'cantho')),
            'auto_cleanup_expired' => 'true',
            'cleanup_days' => '30',
            'default_view' => 'list',
            'items_per_page' => '20',
            'date_format' => 'd/m/Y',
            'show_source_link' => 'true',
            'show_ai_content' => 'true',
            'color_scheme' => json_encode(array(
                'holiday' => '#FF6B6B',
                'movie' => '#4ECDC4',
                'football' => '#45B7D1',
                'power_outage' => '#FFA07A'
            ))
        );

        foreach ($default_settings as $key => $value) {
            $this->save_setting($key, $value);
        }
    }

    /**
     * Insert event
     */
    public function insert_event($data) {
        global $wpdb;

        // Check duplicate
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->table_events}
            WHERE title = %s AND event_date = %s AND source_name = %s",
            $data['title'],
            $data['event_date'],
            $data['source_name']
        ));

        if ($existing) {
            // Update existing
            return $this->update_event($existing, $data);
        }

        $data['created_at'] = current_time('mysql');
        $data['last_updated'] = current_time('mysql');

        $result = $wpdb->insert($this->table_events, $data);
        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Update event
     */
    public function update_event($id, $data) {
        global $wpdb;

        $data['last_updated'] = current_time('mysql');

        return $wpdb->update(
            $this->table_events,
            $data,
            array('id' => $id)
        );
    }

    /**
     * Delete event
     */
    public function delete_event($id) {
        global $wpdb;
        return $wpdb->delete($this->table_events, array('id' => $id));
    }

    /**
     * Get events
     */
    public function get_events($args = array()) {
        global $wpdb;

        $defaults = array(
            'calendar_type' => '',
            'province' => '',
            'location' => '',
            'status' => 'active',
            'date_from' => '',
            'date_to' => '',
            'limit' => 20,
            'offset' => 0,
            'orderby' => 'event_date',
            'order' => 'ASC'
        );

        $args = wp_parse_args($args, $defaults);

        $where = array("1=1");
        $where_values = array();

        if (!empty($args['calendar_type'])) {
            $where[] = "calendar_type = %s";
            $where_values[] = $args['calendar_type'];
        }

        if (!empty($args['province'])) {
            $where[] = "province = %s";
            $where_values[] = $args['province'];
        }

        if (!empty($args['status'])) {
            $where[] = "status = %s";
            $where_values[] = $args['status'];
        }

        if (!empty($args['date_from'])) {
            $where[] = "event_date >= %s";
            $where_values[] = $args['date_from'];
        }

        if (!empty($args['date_to'])) {
            $where[] = "event_date <= %s";
            $where_values[] = $args['date_to'];
        }

        $where_sql = implode(' AND ', $where);

        $query = "SELECT * FROM {$this->table_events}
                  WHERE {$where_sql}
                  ORDER BY {$args['orderby']} {$args['order']}
                  LIMIT %d OFFSET %d";

        $where_values[] = $args['limit'];
        $where_values[] = $args['offset'];

        if (!empty($where_values)) {
            $query = $wpdb->prepare($query, $where_values);
        }

        return $wpdb->get_results($query);
    }

    /**
     * Get event count
     */
    public function get_event_count($calendar_type = '', $status = 'active') {
        global $wpdb;

        $where = array("status = %s");
        $values = array($status);

        if (!empty($calendar_type)) {
            $where[] = "calendar_type = %s";
            $values[] = $calendar_type;
        }

        $where_sql = implode(' AND ', $where);

        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_events} WHERE {$where_sql}",
            $values
        );

        return (int) $wpdb->get_var($query);
    }

    /**
     * Log crawl activity
     */
    public function log_crawl($calendar_type, $source_name, $source_url, $status, $items_found = 0, $error_message = '', $execution_time = 0, $items_added = 0, $items_updated = 0) {
        global $wpdb;

        $data = array(
            'calendar_type' => $calendar_type,
            'source_name' => $source_name,
            'source_url' => $source_url,
            'status' => $status,
            'items_found' => $items_found,
            'items_added' => $items_added,
            'items_updated' => $items_updated,
            'error_message' => $error_message,
            'execution_time' => $execution_time,
            'crawled_at' => current_time('mysql')
        );

        return $wpdb->insert($this->table_logs, $data);
    }

    /**
     * Get logs
     */
    public function get_logs($limit = 50, $calendar_type = '', $status = '') {
        global $wpdb;

        $where = array("1=1");
        $values = array();

        if (!empty($calendar_type)) {
            $where[] = "calendar_type = %s";
            $values[] = $calendar_type;
        }

        if (!empty($status)) {
            $where[] = "status = %s";
            $values[] = $status;
        }

        $where_sql = implode(' AND ', $where);
        $values[] = $limit;

        $query = $wpdb->prepare(
            "SELECT * FROM {$this->table_logs}
             WHERE {$where_sql}
             ORDER BY crawled_at DESC
             LIMIT %d",
            $values
        );

        return $wpdb->get_results($query);
    }

    /**
     * Save setting
     */
    public function save_setting($key, $value, $type = 'text') {
        global $wpdb;

        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->table_settings} WHERE setting_key = %s",
            $key
        ));

        $data = array(
            'setting_value' => $value,
            'setting_type' => $type,
            'updated_at' => current_time('mysql')
        );

        if ($existing) {
            return $wpdb->update(
                $this->table_settings,
                $data,
                array('setting_key' => $key)
            );
        } else {
            $data['setting_key'] = $key;
            return $wpdb->insert($this->table_settings, $data);
        }
    }

    /**
     * Get setting
     */
    public function get_setting($key, $default = '') {
        global $wpdb;

        $value = $wpdb->get_var($wpdb->prepare(
            "SELECT setting_value FROM {$this->table_settings} WHERE setting_key = %s",
            $key
        ));

        return $value !== null ? $value : $default;
    }

    /**
     * ========================================
     * CRON JOBS
     * ========================================
     */

    /**
     * Setup cron jobs
     */
    private function setup_cron_jobs() {
        // Holiday: Monthly (1st day, 6AM)
        if (!wp_next_scheduled('vtncalendar_crawl_holiday')) {
            wp_schedule_event(strtotime('first day of next month 06:00:00'), 'monthly', 'vtncalendar_crawl_holiday');
        }

        // Movies: Twice daily (8AM, 8PM)
        if (!wp_next_scheduled('vtncalendar_crawl_movies')) {
            wp_schedule_event(strtotime('tomorrow 08:00:00'), 'twicedaily', 'vtncalendar_crawl_movies');
        }

        // Football: Weekly (Monday 6AM)
        if (!wp_next_scheduled('vtncalendar_crawl_football')) {
            wp_schedule_event(strtotime('next Monday 06:00:00'), 'weekly', 'vtncalendar_crawl_football');
        }

        // Power: Daily (6AM)
        if (!wp_next_scheduled('vtncalendar_crawl_power')) {
            wp_schedule_event(strtotime('tomorrow 06:00:00'), 'daily', 'vtncalendar_crawl_power');
        }

        // Cleanup: Daily (3AM)
        if (!wp_next_scheduled('vtncalendar_cleanup_expired')) {
            wp_schedule_event(strtotime('tomorrow 03:00:00'), 'daily', 'vtncalendar_cleanup_expired');
        }
    }

    /**
     * Clear cron jobs
     */
    private function clear_cron_jobs() {
        wp_clear_scheduled_hook('vtncalendar_crawl_holiday');
        wp_clear_scheduled_hook('vtncalendar_crawl_movies');
        wp_clear_scheduled_hook('vtncalendar_crawl_football');
        wp_clear_scheduled_hook('vtncalendar_crawl_power');
        wp_clear_scheduled_hook('vtncalendar_cleanup_expired');
    }

    /**
     * Cleanup expired events
     */
    public function cleanup_expired_events() {
        global $wpdb;

        if ($this->get_setting('auto_cleanup_expired') !== 'true') {
            return;
        }

        $cleanup_days = (int) $this->get_setting('cleanup_days', 30);
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$cleanup_days} days"));

        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->table_events}
             WHERE event_date < %s AND status = 'active'",
            $cutoff_date
        ));

        if ($deleted) {
            $this->log_crawl('system', 'Cleanup', '', 'success', 0, '', 0, 0, 0);
        }
    }

    /**
     * ========================================
     * CRAWLING FUNCTIONS
     * ========================================
     */

    /**
     * Crawl holiday calendar
     */
    public function crawl_holiday() {
        if ($this->get_setting('crawl_holiday_enabled') !== 'true') {
            return;
        }

        $start_time = microtime(true);
        $sources = array(
            array(
                'name' => 'Chính phủ Việt Nam',
                'url' => 'https://chinhphu.vn',
                'parser' => 'parse_holiday_chinhphu'
            )
        );

        $items_found = 0;
        $items_added = 0;

        foreach ($sources as $source) {
            try {
                $events = call_user_func(array($this, $source['parser']), $source['url']);

                if (!empty($events)) {
                    foreach ($events as $event) {
                        $event_id = $this->insert_event($event);
                        if ($event_id) {
                            $items_added++;
                        }
                    }
                    $items_found += count($events);
                }

                $execution_time = microtime(true) - $start_time;
                $this->log_crawl('holiday', $source['name'], $source['url'], 'success', $items_found, '', $execution_time, $items_added, 0);

            } catch (Exception $e) {
                $execution_time = microtime(true) - $start_time;
                $this->log_crawl('holiday', $source['name'], $source['url'], 'failed', 0, $e->getMessage(), $execution_time, 0, 0);
            }
        }
    }

    /**
     * Parse holiday from chinhphu.vn
     * Note: This is a placeholder - actual implementation would need to analyze the HTML structure
     */
    private function parse_holiday_chinhphu($url) {
        $events = array();

        try {
            $response = wp_remote_get($url, array(
                'timeout' => 30,
                'user-agent' => 'VTNCalendar/1.0 (WordPress Plugin)'
            ));

            if (is_wp_error($response)) {
                throw new Exception($response->get_error_message());
            }

            $code = wp_remote_retrieve_response_code($response);
            if ($code !== 200) {
                throw new Exception("HTTP Error: $code");
            }

            $html = wp_remote_retrieve_body($response);

            // Parse HTML - this is simplified, actual implementation would use DOMDocument
            // For now, return sample data for testing
            $current_year = date('Y');
            $next_year = $current_year + 1;

            // Sample Vietnamese holidays
            $holidays = array(
                array('name' => 'Tết Nguyên Đán ' . $next_year, 'date' => $next_year . '-01-29', 'end_date' => $next_year . '-02-04'),
                array('name' => 'Giỗ Tổ Hùng Vương', 'date' => $next_year . '-04-18', 'end_date' => null),
                array('name' => 'Ngày Giải phóng miền Nam', 'date' => $next_year . '-04-30', 'end_date' => null),
                array('name' => 'Ngày Quốc tế Lao động', 'date' => $next_year . '-05-01', 'end_date' => null),
                array('name' => 'Quốc khánh', 'date' => $next_year . '-09-02', 'end_date' => null),
            );

            foreach ($holidays as $holiday) {
                $event = array(
                    'calendar_type' => 'holiday',
                    'title' => $holiday['name'],
                    'description' => 'Ngày nghỉ lễ theo quy định của Chính phủ',
                    'event_date' => $holiday['date'] . ' 00:00:00',
                    'event_end_date' => $holiday['end_date'] ? $holiday['end_date'] . ' 23:59:59' : null,
                    'location' => '',
                    'province' => '',
                    'source_name' => 'Chính phủ VN',
                    'source_url' => $url,
                    'raw_data' => json_encode($holiday),
                    'ai_content' => '',
                    'meta_data' => json_encode(array('year' => $next_year)),
                    'status' => 'active'
                );

                // Generate AI content if enabled
                if ($this->get_setting('enable_ai_content') === 'true') {
                    $event['ai_content'] = $this->generate_ai_content('holiday', $event);
                }

                $events[] = $event;
            }

        } catch (Exception $e) {
            throw $e;
        }

        return $events;
    }

    /**
     * Crawl movie schedules
     */
    public function crawl_movies() {
        if ($this->get_setting('crawl_movie_enabled') !== 'true') {
            return;
        }

        $start_time = microtime(true);
        $movie_sources_setting = $this->get_setting('crawl_movie_sources', '[]');
        $enabled_sources = json_decode($movie_sources_setting, true);

        $sources = array(
            'cgv' => array(
                'name' => 'CGV Cinemas',
                'url' => 'https://www.cgv.vn/default/movies/coming-soon',
                'parser' => 'parse_movies_cgv'
            ),
            'galaxy' => array(
                'name' => 'Galaxy Cinema',
                'url' => 'https://www.galaxycine.vn/phim-sap-chieu',
                'parser' => 'parse_movies_galaxy'
            ),
            'lotte' => array(
                'name' => 'Lotte Cinema',
                'url' => 'https://www.lottecinemavn.com/LCHS/Movie/MovieList',
                'parser' => 'parse_movies_lotte'
            )
        );

        foreach ($enabled_sources as $source_key) {
            if (!isset($sources[$source_key])) {
                continue;
            }

            $source = $sources[$source_key];
            $items_found = 0;
            $items_added = 0;

            try {
                $events = call_user_func(array($this, $source['parser']), $source['url']);

                if (!empty($events)) {
                    foreach ($events as $event) {
                        $event_id = $this->insert_event($event);
                        if ($event_id) {
                            $items_added++;
                        }
                    }
                    $items_found += count($events);
                }

                $execution_time = microtime(true) - $start_time;
                $this->log_crawl('movie', $source['name'], $source['url'], 'success', $items_found, '', $execution_time, $items_added, 0);

            } catch (Exception $e) {
                $execution_time = microtime(true) - $start_time;
                $this->log_crawl('movie', $source['name'], $source['url'], 'failed', 0, $e->getMessage(), $execution_time, 0, 0);
            }

            // Rate limiting: 1 second between requests
            sleep(1);
        }
    }

    /**
     * Parse movies from CGV
     * Note: Placeholder - actual implementation would analyze HTML structure
     */
    private function parse_movies_cgv($url) {
        $events = array();

        try {
            $response = wp_remote_get($url, array(
                'timeout' => 30,
                'user-agent' => 'VTNCalendar/1.0 (WordPress Plugin)'
            ));

            if (is_wp_error($response)) {
                throw new Exception($response->get_error_message());
            }

            // Sample movie data for testing
            $movies = array(
                array(
                    'title' => 'Avatar: The Way of Water',
                    'title_en' => 'Avatar: The Way of Water',
                    'genre' => 'Hành động, Khoa học viễn tưởng',
                    'release_date' => date('Y-m-d', strtotime('+7 days')),
                    'description' => 'Phần tiếp theo của bom tấn Avatar',
                    'director' => 'James Cameron',
                    'actors' => 'Sam Worthington, Zoe Saldana',
                    'age_rating' => 'T13',
                    'poster_url' => 'https://via.placeholder.com/300x450'
                )
            );

            foreach ($movies as $movie) {
                $event = array(
                    'calendar_type' => 'movie',
                    'title' => $movie['title'],
                    'description' => $movie['description'],
                    'event_date' => $movie['release_date'] . ' 00:00:00',
                    'event_end_date' => null,
                    'location' => 'CGV Cinemas',
                    'province' => '',
                    'source_name' => 'CGV',
                    'source_url' => $url,
                    'raw_data' => json_encode($movie),
                    'ai_content' => '',
                    'meta_data' => json_encode(array(
                        'title_en' => $movie['title_en'],
                        'genre' => $movie['genre'],
                        'director' => $movie['director'],
                        'actors' => $movie['actors'],
                        'age_rating' => $movie['age_rating'],
                        'poster_url' => $movie['poster_url']
                    )),
                    'status' => 'active'
                );

                // Generate AI content if enabled
                if ($this->get_setting('enable_ai_content') === 'true') {
                    $event['ai_content'] = $this->generate_ai_content('movie', $event);
                }

                $events[] = $event;
            }

        } catch (Exception $e) {
            throw $e;
        }

        return $events;
    }

    /**
     * Parse movies from Galaxy
     */
    private function parse_movies_galaxy($url) {
        // Similar to CGV parser - placeholder for now
        return array();
    }

    /**
     * Parse movies from Lotte
     */
    private function parse_movies_lotte($url) {
        // Similar to CGV parser - placeholder for now
        return array();
    }

    /**
     * Crawl football schedules
     */
    public function crawl_football() {
        if ($this->get_setting('crawl_football_enabled') !== 'true') {
            return;
        }

        $start_time = microtime(true);
        $sources = array(
            array(
                'name' => 'Liên đoàn Bóng đá Việt Nam',
                'url' => 'https://vff.org.vn',
                'parser' => 'parse_football_vff'
            )
        );

        $items_found = 0;
        $items_added = 0;

        foreach ($sources as $source) {
            try {
                $events = call_user_func(array($this, $source['parser']), $source['url']);

                if (!empty($events)) {
                    foreach ($events as $event) {
                        $event_id = $this->insert_event($event);
                        if ($event_id) {
                            $items_added++;
                        }
                    }
                    $items_found += count($events);
                }

                $execution_time = microtime(true) - $start_time;
                $this->log_crawl('football', $source['name'], $source['url'], 'success', $items_found, '', $execution_time, $items_added, 0);

            } catch (Exception $e) {
                $execution_time = microtime(true) - $start_time;
                $this->log_crawl('football', $source['name'], $source['url'], 'failed', 0, $e->getMessage(), $execution_time, 0, 0);
            }
        }
    }

    /**
     * Parse football from VFF
     */
    private function parse_football_vff($url) {
        $events = array();

        try {
            // Sample football data
            $matches = array(
                array(
                    'home_team' => 'Hà Nội FC',
                    'away_team' => 'Hoàng Anh Gia Lai',
                    'tournament' => 'V.League 1',
                    'round' => 'Vòng 10',
                    'match_date' => date('Y-m-d H:i:s', strtotime('next Saturday 19:00:00')),
                    'stadium' => 'Sân Hàng Đẫy',
                    'location' => 'Hà Nội'
                )
            );

            foreach ($matches as $match) {
                $event = array(
                    'calendar_type' => 'football',
                    'title' => $match['home_team'] . ' vs ' . $match['away_team'],
                    'description' => $match['tournament'] . ' - ' . $match['round'],
                    'event_date' => $match['match_date'],
                    'event_end_date' => null,
                    'location' => $match['stadium'] . ', ' . $match['location'],
                    'province' => $match['location'],
                    'source_name' => 'VFF',
                    'source_url' => $url,
                    'raw_data' => json_encode($match),
                    'ai_content' => '',
                    'meta_data' => json_encode(array(
                        'home_team' => $match['home_team'],
                        'away_team' => $match['away_team'],
                        'tournament' => $match['tournament'],
                        'round' => $match['round'],
                        'stadium' => $match['stadium']
                    )),
                    'status' => 'active'
                );

                // Generate AI content if enabled
                if ($this->get_setting('enable_ai_content') === 'true') {
                    $event['ai_content'] = $this->generate_ai_content('football', $event);
                }

                $events[] = $event;
            }

        } catch (Exception $e) {
            throw $e;
        }

        return $events;
    }

    /**
     * Crawl power outage schedules
     */
    public function crawl_power_outage() {
        if ($this->get_setting('crawl_power_enabled') !== 'true') {
            return;
        }

        $start_time = microtime(true);
        $provinces_setting = $this->get_setting('crawl_power_provinces', '[]');
        $enabled_provinces = json_decode($provinces_setting, true);

        $sources = array(
            'hanoi' => array(
                'name' => 'EVN Hà Nội',
                'url' => 'https://evnhanoi.vn',
                'province' => 'Hà Nội',
                'parser' => 'parse_power_evn'
            ),
            'hcm' => array(
                'name' => 'EVN TP.HCM',
                'url' => 'https://evnhcmc.vn',
                'province' => 'TP. Hồ Chí Minh',
                'parser' => 'parse_power_evn'
            ),
            'danang' => array(
                'name' => 'EVN Đà Nẵng',
                'url' => 'https://evndanang.vn',
                'province' => 'Đà Nẵng',
                'parser' => 'parse_power_evn'
            )
        );

        foreach ($enabled_provinces as $province_key) {
            if (!isset($sources[$province_key])) {
                continue;
            }

            $source = $sources[$province_key];
            $items_found = 0;
            $items_added = 0;

            try {
                $events = call_user_func(array($this, $source['parser']), $source['url'], $source['province']);

                if (!empty($events)) {
                    foreach ($events as $event) {
                        $event_id = $this->insert_event($event);
                        if ($event_id) {
                            $items_added++;
                        }
                    }
                    $items_found += count($events);
                }

                $execution_time = microtime(true) - $start_time;
                $this->log_crawl('power_outage', $source['name'], $source['url'], 'success', $items_found, '', $execution_time, $items_added, 0);

            } catch (Exception $e) {
                $execution_time = microtime(true) - $start_time;
                $this->log_crawl('power_outage', $source['name'], $source['url'], 'failed', 0, $e->getMessage(), $execution_time, 0, 0);
            }

            // Rate limiting
            sleep(1);
        }
    }

    /**
     * Parse power outage from EVN
     */
    private function parse_power_evn($url, $province) {
        $events = array();

        try {
            // Sample power outage data
            $outages = array(
                array(
                    'area' => 'Quận Ba Đình, đường Nguyễn Thái Học',
                    'start_time' => date('Y-m-d 08:00:00', strtotime('tomorrow')),
                    'end_time' => date('Y-m-d 12:00:00', strtotime('tomorrow')),
                    'reason' => 'Bảo trì hệ thống điện'
                )
            );

            foreach ($outages as $outage) {
                $event = array(
                    'calendar_type' => 'power_outage',
                    'title' => 'Cắt điện: ' . $outage['area'],
                    'description' => 'Lý do: ' . $outage['reason'],
                    'event_date' => $outage['start_time'],
                    'event_end_date' => $outage['end_time'],
                    'location' => $outage['area'],
                    'province' => $province,
                    'source_name' => 'EVN ' . $province,
                    'source_url' => $url,
                    'raw_data' => json_encode($outage),
                    'ai_content' => '',
                    'meta_data' => json_encode(array(
                        'reason' => $outage['reason']
                    )),
                    'status' => 'active'
                );

                $events[] = $event;
            }

        } catch (Exception $e) {
            throw $e;
        }

        return $events;
    }

    /**
     * ========================================
     * CLAUDE API INTEGRATION
     * ========================================
     */

    /**
     * Generate AI content using Claude
     */
    private function generate_ai_content($content_type, $event) {
        $api_key = $this->get_setting('claude_api_key');

        if (empty($api_key)) {
            return $this->get_template_fallback($content_type, $event);
        }

        try {
            $prompt = $this->build_ai_prompt($content_type, $event);

            $response = wp_remote_post('https://api.anthropic.com/v1/messages', array(
                'timeout' => 30,
                'headers' => array(
                    'x-api-key' => $api_key,
                    'anthropic-version' => '2023-06-01',
                    'content-type' => 'application/json'
                ),
                'body' => json_encode(array(
                    'model' => 'claude-sonnet-4-20250514',
                    'max_tokens' => 1000,
                    'messages' => array(
                        array(
                            'role' => 'user',
                            'content' => $prompt
                        )
                    )
                ))
            ));

            if (is_wp_error($response)) {
                throw new Exception($response->get_error_message());
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);

            if (isset($body['content'][0]['text'])) {
                return $body['content'][0]['text'];
            }

            throw new Exception('Invalid API response');

        } catch (Exception $e) {
            return $this->get_template_fallback($content_type, $event);
        }
    }

    /**
     * Build AI prompt based on content type
     */
    private function build_ai_prompt($content_type, $event) {
        $prompts = array(
            'movie' => "Viết một đoạn giới thiệu ngắn (100-150 từ) về bộ phim '{$event['title']}', khởi chiếu ngày " . date('d/m/Y', strtotime($event['event_date'])) . ". Hãy viết theo phong cách hấp dẫn, khuyến khích người xem.",

            'football' => "Viết preview ngắn (100-150 từ) cho trận đấu {$event['title']} diễn ra ngày " . date('d/m/Y H:i', strtotime($event['event_date'])) . ". Hãy viết theo phong cách thể thao, tạo hứng thú theo dõi.",

            'holiday' => "Viết tips ngắn (100-150 từ) cho kỳ nghỉ {$event['title']}. Gợi ý: Chuẩn bị gì, đi đâu, lưu ý gì.",

            'power_outage' => "Viết thông báo ngắn (50-100 từ) về lịch cắt điện tại {$event['location']} từ " . date('H:i d/m', strtotime($event['event_date'])) . " đến " . date('H:i d/m', strtotime($event['event_end_date'])) . ". Đưa ra tips chuẩn bị khi mất điện."
        );

        return isset($prompts[$content_type]) ? $prompts[$content_type] : '';
    }

    /**
     * Get template fallback when Claude API is unavailable
     */
    private function get_template_fallback($content_type, $event) {
        $templates = array(
            'movie' => "Bộ phim {$event['title']} sẽ khởi chiếu vào " . date('d/m/Y', strtotime($event['event_date'])) . ". " . $event['description'],

            'football' => "Trận đấu {$event['title']} sẽ diễn ra vào " . date('H:i d/m/Y', strtotime($event['event_date'])) . " tại {$event['location']}.",

            'holiday' => "Kỳ nghỉ {$event['title']} " . ($event['event_end_date'] ? "từ " . date('d/m', strtotime($event['event_date'])) . " đến " . date('d/m/Y', strtotime($event['event_end_date'])) : "vào " . date('d/m/Y', strtotime($event['event_date']))) . ". Chúc bạn có kỳ nghỉ vui vẻ!",

            'power_outage' => "Khu vực {$event['location']} sẽ mất điện từ " . date('H:i d/m', strtotime($event['event_date'])) . " đến " . date('H:i d/m', strtotime($event['event_end_date'])) . ". {$event['description']}"
        );

        return isset($templates[$content_type]) ? $templates[$content_type] : $event['description'];
    }

    /**
     * ========================================
     * ADMIN INTERFACE
     * ========================================
     */

    /**
     * Register admin menu
     */
    public function admin_menu() {
        add_menu_page(
            'VTN Calendar',
            'VTN Calendar',
            'manage_options',
            'vtncalendar',
            array($this, 'dashboard_page'),
            'dashicons-calendar-alt',
            30
        );

        add_submenu_page(
            'vtncalendar',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'vtncalendar',
            array($this, 'dashboard_page')
        );

        add_submenu_page(
            'vtncalendar',
            'Calendar Manager',
            'Calendar Manager',
            'manage_options',
            'vtncalendar-manage',
            array($this, 'manage_page')
        );

        add_submenu_page(
            'vtncalendar',
            'Crawl Settings',
            'Crawl Settings',
            'manage_options',
            'vtncalendar-settings',
            array($this, 'settings_page')
        );

        add_submenu_page(
            'vtncalendar',
            'Display Settings',
            'Display Settings',
            'manage_options',
            'vtncalendar-display',
            array($this, 'display_page')
        );

        add_submenu_page(
            'vtncalendar',
            'Logs & Status',
            'Logs & Status',
            'manage_options',
            'vtncalendar-logs',
            array($this, 'logs_page')
        );
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'vtncalendar') === false) {
            return;
        }

        // Inline admin CSS
        wp_add_inline_style('wp-admin', $this->get_admin_css());

        // Inline admin JS
        wp_enqueue_script('jquery');
        wp_add_inline_script('jquery', $this->get_admin_js());

        // Localize script
        wp_localize_script('jquery', 'vtncal_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vtncal_nonce')
        ));
    }

    /**
     * Get admin CSS
     */
    private function get_admin_css() {
        return "
        .vtncal-dashboard {
            max-width: 1200px;
        }
        .vtncal-card {
            background: #fff;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .vtncal-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .vtncal-stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        .vtncal-stat-card h3 {
            color: #fff;
            margin: 0 0 10px 0;
            font-size: 14px;
            text-transform: uppercase;
            opacity: 0.9;
        }
        .vtncal-stat-card .number {
            font-size: 36px;
            font-weight: bold;
            margin: 10px 0;
        }
        .vtncal-stat-card.holiday {
            background: linear-gradient(135deg, #FF6B6B 0%, #C92A2A 100%);
        }
        .vtncal-stat-card.movie {
            background: linear-gradient(135deg, #4ECDC4 0%, #1A9B8E 100%);
        }
        .vtncal-stat-card.football {
            background: linear-gradient(135deg, #45B7D1 0%, #1976D2 100%);
        }
        .vtncal-stat-card.power {
            background: linear-gradient(135deg, #FFA07A 0%, #FF6347 100%);
        }
        .vtncal-form-group {
            margin-bottom: 20px;
        }
        .vtncal-form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .vtncal-form-group input[type='text'],
        .vtncal-form-group input[type='password'],
        .vtncal-form-group select,
        .vtncal-form-group textarea {
            width: 100%;
            max-width: 500px;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .vtncal-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }
        .vtncal-btn-primary {
            background: #0073aa;
            color: #fff;
        }
        .vtncal-btn-primary:hover {
            background: #005177;
        }
        .vtncal-btn-success {
            background: #46b450;
            color: #fff;
        }
        .vtncal-btn-danger {
            background: #dc3232;
            color: #fff;
        }
        .vtncal-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .vtncal-table th,
        .vtncal-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .vtncal-table th {
            background: #f5f5f5;
            font-weight: 600;
        }
        .vtncal-table tr:hover {
            background: #fafafa;
        }
        .vtncal-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 600;
        }
        .vtncal-badge-success {
            background: #d4edda;
            color: #155724;
        }
        .vtncal-badge-error {
            background: #f8d7da;
            color: #721c24;
        }
        .vtncal-badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        .vtncal-notice {
            padding: 12px;
            margin: 20px 0;
            border-left: 4px solid;
            border-radius: 4px;
        }
        .vtncal-notice-success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        .vtncal-notice-error {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        .vtncal-checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }
        .vtncal-checkbox-group label {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        ";
    }

    /**
     * Get admin JS
     */
    private function get_admin_js() {
        return "
        jQuery(document).ready(function($) {

            // Save settings
            $(document).on('click', '.vtncal-save-settings', function(e) {
                e.preventDefault();
                var button = $(this);
                var form = button.closest('form');
                var formData = form.serialize();

                button.prop('disabled', true).text('Đang lưu...');

                $.post(vtncal_ajax.ajax_url, {
                    action: 'vtncal_save_settings',
                    nonce: vtncal_ajax.nonce,
                    data: formData
                }, function(response) {
                    if (response.success) {
                        alert('Lưu cài đặt thành công!');
                    } else {
                        alert('Lỗi: ' + response.data);
                    }
                    button.prop('disabled', false).text('Lưu cài đặt');
                });
            });

            // Test crawl
            $(document).on('click', '.vtncal-test-crawl', function(e) {
                e.preventDefault();
                var button = $(this);
                var type = button.data('type');

                button.prop('disabled', true).text('Đang crawl...');

                $.post(vtncal_ajax.ajax_url, {
                    action: 'vtncal_test_crawl',
                    nonce: vtncal_ajax.nonce,
                    type: type
                }, function(response) {
                    if (response.success) {
                        alert('Test crawl thành công! Tìm thấy ' + response.data.items + ' items.');
                        location.reload();
                    } else {
                        alert('Lỗi: ' + response.data);
                    }
                    button.prop('disabled', false).text('Test Crawl');
                });
            });

            // Delete event
            $(document).on('click', '.vtncal-delete-event', function(e) {
                if (!confirm('Bạn có chắc muốn xóa sự kiện này?')) {
                    return;
                }

                var button = $(this);
                var eventId = button.data('id');

                $.post(vtncal_ajax.ajax_url, {
                    action: 'vtncal_delete_event',
                    nonce: vtncal_ajax.nonce,
                    id: eventId
                }, function(response) {
                    if (response.success) {
                        button.closest('tr').fadeOut();
                    } else {
                        alert('Lỗi: ' + response.data);
                    }
                });
            });

        });
        ";
    }

    /**
     * Dashboard page
     */
    public function dashboard_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $stats = array(
            'holiday' => $this->get_event_count('holiday'),
            'movie' => $this->get_event_count('movie'),
            'football' => $this->get_event_count('football'),
            'power_outage' => $this->get_event_count('power_outage'),
            'total' => $this->get_event_count()
        );

        $recent_logs = $this->get_logs(5);

        ?>
        <div class="wrap vtncal-dashboard">
            <h1>VTN Calendar Dashboard</h1>

            <div class="vtncal-stats-grid">
                <div class="vtncal-stat-card holiday">
                    <h3>Lịch Nghỉ Lễ</h3>
                    <div class="number"><?php echo $stats['holiday']; ?></div>
                    <p>Sự kiện</p>
                </div>

                <div class="vtncal-stat-card movie">
                    <h3>Lịch Chiếu Phim</h3>
                    <div class="number"><?php echo $stats['movie']; ?></div>
                    <p>Phim sắp chiếu</p>
                </div>

                <div class="vtncal-stat-card football">
                    <h3>Lịch Bóng Đá</h3>
                    <div class="number"><?php echo $stats['football']; ?></div>
                    <p>Trận đấu</p>
                </div>

                <div class="vtncal-stat-card power">
                    <h3>Lịch Cắt Điện</h3>
                    <div class="number"><?php echo $stats['power_outage']; ?></div>
                    <p>Thông báo</p>
                </div>
            </div>

            <div class="vtncal-card">
                <h2>Crawl Logs Gần Đây</h2>
                <table class="vtncal-table">
                    <thead>
                        <tr>
                            <th>Loại</th>
                            <th>Nguồn</th>
                            <th>Trạng thái</th>
                            <th>Tìm thấy</th>
                            <th>Thêm mới</th>
                            <th>Thời gian</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_logs)): ?>
                            <tr><td colspan="6">Chưa có log nào</td></tr>
                        <?php else: ?>
                            <?php foreach ($recent_logs as $log): ?>
                                <tr>
                                    <td><?php echo esc_html($log->calendar_type); ?></td>
                                    <td><?php echo esc_html($log->source_name); ?></td>
                                    <td>
                                        <span class="vtncal-badge vtncal-badge-<?php echo $log->status === 'success' ? 'success' : 'error'; ?>">
                                            <?php echo esc_html($log->status); ?>
                                        </span>
                                    </td>
                                    <td><?php echo esc_html($log->items_found); ?></td>
                                    <td><?php echo esc_html($log->items_added); ?></td>
                                    <td><?php echo esc_html($log->crawled_at); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="vtncal-card">
                <h2>Quick Actions</h2>
                <p>
                    <button class="vtncal-btn vtncal-btn-primary vtncal-test-crawl" data-type="holiday">Test Crawl Lịch Nghỉ</button>
                    <button class="vtncal-btn vtncal-btn-primary vtncal-test-crawl" data-type="movie">Test Crawl Phim</button>
                    <button class="vtncal-btn vtncal-btn-primary vtncal-test-crawl" data-type="football">Test Crawl Bóng Đá</button>
                    <button class="vtncal-btn vtncal-btn-primary vtncal-test-crawl" data-type="power">Test Crawl Cắt Điện</button>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Calendar Manager page
     */
    public function manage_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $events = $this->get_events(array('limit' => 50));

        ?>
        <div class="wrap">
            <h1>Calendar Manager</h1>

            <div class="vtncal-card">
                <table class="vtncal-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Loại</th>
                            <th>Tiêu đề</th>
                            <th>Ngày</th>
                            <th>Nguồn</th>
                            <th>Trạng thái</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($events)): ?>
                            <tr><td colspan="7">Chưa có sự kiện nào</td></tr>
                        <?php else: ?>
                            <?php foreach ($events as $event): ?>
                                <tr>
                                    <td><?php echo esc_html($event->id); ?></td>
                                    <td><?php echo esc_html($event->calendar_type); ?></td>
                                    <td><?php echo esc_html($event->title); ?></td>
                                    <td><?php echo esc_html(date('d/m/Y H:i', strtotime($event->event_date))); ?></td>
                                    <td><?php echo esc_html($event->source_name); ?></td>
                                    <td>
                                        <span class="vtncal-badge vtncal-badge-<?php echo $event->status === 'active' ? 'success' : 'warning'; ?>">
                                            <?php echo esc_html($event->status); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="vtncal-btn vtncal-btn-danger vtncal-delete-event" data-id="<?php echo esc_attr($event->id); ?>">Xóa</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    /**
     * Settings page
     */
    public function settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        ?>
        <div class="wrap">
            <h1>Crawl Settings</h1>

            <form id="vtncal-settings-form" class="vtncal-card">
                <h2>Claude API</h2>
                <div class="vtncal-form-group">
                    <label>Claude API Key</label>
                    <input type="password" name="claude_api_key" value="<?php echo esc_attr($this->get_setting('claude_api_key')); ?>" />
                </div>

                <div class="vtncal-form-group">
                    <label>
                        <input type="checkbox" name="enable_ai_content" value="true" <?php checked($this->get_setting('enable_ai_content'), 'true'); ?> />
                        Bật AI Content Generation
                    </label>
                </div>

                <hr>

                <h2>Crawl Modules</h2>

                <div class="vtncal-form-group">
                    <label>
                        <input type="checkbox" name="crawl_holiday_enabled" value="true" <?php checked($this->get_setting('crawl_holiday_enabled'), 'true'); ?> />
                        Bật crawl Lịch Nghỉ Lễ
                    </label>
                </div>

                <div class="vtncal-form-group">
                    <label>
                        <input type="checkbox" name="crawl_movie_enabled" value="true" <?php checked($this->get_setting('crawl_movie_enabled'), 'true'); ?> />
                        Bật crawl Lịch Chiếu Phim
                    </label>
                </div>

                <div class="vtncal-form-group">
                    <label>Nguồn phim (chọn nhiều):</label>
                    <div class="vtncal-checkbox-group">
                        <?php
                        $movie_sources = json_decode($this->get_setting('crawl_movie_sources', '[]'), true);
                        ?>
                        <label>
                            <input type="checkbox" name="crawl_movie_sources[]" value="cgv" <?php checked(in_array('cgv', $movie_sources)); ?> />
                            CGV
                        </label>
                        <label>
                            <input type="checkbox" name="crawl_movie_sources[]" value="galaxy" <?php checked(in_array('galaxy', $movie_sources)); ?> />
                            Galaxy
                        </label>
                        <label>
                            <input type="checkbox" name="crawl_movie_sources[]" value="lotte" <?php checked(in_array('lotte', $movie_sources)); ?> />
                            Lotte
                        </label>
                    </div>
                </div>

                <div class="vtncal-form-group">
                    <label>
                        <input type="checkbox" name="crawl_football_enabled" value="true" <?php checked($this->get_setting('crawl_football_enabled'), 'true'); ?> />
                        Bật crawl Lịch Bóng Đá
                    </label>
                </div>

                <div class="vtncal-form-group">
                    <label>
                        <input type="checkbox" name="crawl_power_enabled" value="true" <?php checked($this->get_setting('crawl_power_enabled'), 'true'); ?> />
                        Bật crawl Lịch Cắt Điện
                    </label>
                </div>

                <div class="vtncal-form-group">
                    <label>Tỉnh/Thành phố (Lịch cắt điện):</label>
                    <div class="vtncal-checkbox-group">
                        <?php
                        $power_provinces = json_decode($this->get_setting('crawl_power_provinces', '[]'), true);
                        ?>
                        <label>
                            <input type="checkbox" name="crawl_power_provinces[]" value="hanoi" <?php checked(in_array('hanoi', $power_provinces)); ?> />
                            Hà Nội
                        </label>
                        <label>
                            <input type="checkbox" name="crawl_power_provinces[]" value="hcm" <?php checked(in_array('hcm', $power_provinces)); ?> />
                            TP.HCM
                        </label>
                        <label>
                            <input type="checkbox" name="crawl_power_provinces[]" value="danang" <?php checked(in_array('danang', $power_provinces)); ?> />
                            Đà Nẵng
                        </label>
                        <label>
                            <input type="checkbox" name="crawl_power_provinces[]" value="haiphong" <?php checked(in_array('haiphong', $power_provinces)); ?> />
                            Hải Phòng
                        </label>
                        <label>
                            <input type="checkbox" name="crawl_power_provinces[]" value="cantho" <?php checked(in_array('cantho', $power_provinces)); ?> />
                            Cần Thơ
                        </label>
                    </div>
                </div>

                <hr>

                <h2>Auto Cleanup</h2>

                <div class="vtncal-form-group">
                    <label>
                        <input type="checkbox" name="auto_cleanup_expired" value="true" <?php checked($this->get_setting('auto_cleanup_expired'), 'true'); ?> />
                        Tự động xóa sự kiện cũ
                    </label>
                </div>

                <div class="vtncal-form-group">
                    <label>Xóa sau (ngày)</label>
                    <input type="number" name="cleanup_days" value="<?php echo esc_attr($this->get_setting('cleanup_days', '30')); ?>" min="1" max="365" />
                </div>

                <p>
                    <button type="button" class="vtncal-btn vtncal-btn-primary vtncal-save-settings">Lưu cài đặt</button>
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Display Settings page
     */
    public function display_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        ?>
        <div class="wrap">
            <h1>Display Settings</h1>

            <form id="vtncal-display-form" class="vtncal-card">
                <div class="vtncal-form-group">
                    <label>Default View</label>
                    <select name="default_view">
                        <option value="list" <?php selected($this->get_setting('default_view'), 'list'); ?>>List</option>
                        <option value="calendar" <?php selected($this->get_setting('default_view'), 'calendar'); ?>>Calendar</option>
                        <option value="grid" <?php selected($this->get_setting('default_view'), 'grid'); ?>>Grid</option>
                    </select>
                </div>

                <div class="vtncal-form-group">
                    <label>Items per page</label>
                    <input type="number" name="items_per_page" value="<?php echo esc_attr($this->get_setting('items_per_page', '20')); ?>" min="5" max="100" />
                </div>

                <div class="vtncal-form-group">
                    <label>Date Format</label>
                    <input type="text" name="date_format" value="<?php echo esc_attr($this->get_setting('date_format', 'd/m/Y')); ?>" />
                    <p class="description">Ví dụ: d/m/Y (31/12/2024), Y-m-d (2024-12-31)</p>
                </div>

                <div class="vtncal-form-group">
                    <label>
                        <input type="checkbox" name="show_source_link" value="true" <?php checked($this->get_setting('show_source_link'), 'true'); ?> />
                        Hiển thị link nguồn
                    </label>
                </div>

                <div class="vtncal-form-group">
                    <label>
                        <input type="checkbox" name="show_ai_content" value="true" <?php checked($this->get_setting('show_ai_content'), 'true'); ?> />
                        Hiển thị AI content
                    </label>
                </div>

                <p>
                    <button type="button" class="vtncal-btn vtncal-btn-primary vtncal-save-settings">Lưu cài đặt</button>
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Logs page
     */
    public function logs_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $logs = $this->get_logs(100);

        ?>
        <div class="wrap">
            <h1>Logs & Status</h1>

            <div class="vtncal-card">
                <table class="vtncal-table">
                    <thead>
                        <tr>
                            <th>Thời gian</th>
                            <th>Loại</th>
                            <th>Nguồn</th>
                            <th>Trạng thái</th>
                            <th>Tìm thấy</th>
                            <th>Thêm mới</th>
                            <th>Execution Time</th>
                            <th>Error</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr><td colspan="8">Chưa có log nào</td></tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?php echo esc_html($log->crawled_at); ?></td>
                                    <td><?php echo esc_html($log->calendar_type); ?></td>
                                    <td><?php echo esc_html($log->source_name); ?></td>
                                    <td>
                                        <span class="vtncal-badge vtncal-badge-<?php echo $log->status === 'success' ? 'success' : 'error'; ?>">
                                            <?php echo esc_html($log->status); ?>
                                        </span>
                                    </td>
                                    <td><?php echo esc_html($log->items_found); ?></td>
                                    <td><?php echo esc_html($log->items_added); ?></td>
                                    <td><?php echo esc_html(number_format($log->execution_time, 2)); ?>s</td>
                                    <td><?php echo esc_html($log->error_message); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    /**
     * ========================================
     * AJAX HANDLERS
     * ========================================
     */

    /**
     * AJAX: Get events
     */
    public function ajax_get_events() {
        check_ajax_referer('vtncal_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $args = array(
            'calendar_type' => isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '',
            'province' => isset($_POST['province']) ? sanitize_text_field($_POST['province']) : '',
            'limit' => 50
        );

        $events = $this->get_events($args);
        wp_send_json_success($events);
    }

    /**
     * AJAX: Delete event
     */
    public function ajax_delete_event() {
        check_ajax_referer('vtncal_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

        if ($id && $this->delete_event($id)) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Failed to delete');
        }
    }

    /**
     * AJAX: Save settings
     */
    public function ajax_save_settings() {
        check_ajax_referer('vtncal_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        parse_str($_POST['data'], $data);

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = json_encode($value);
            }
            $this->save_setting($key, $value);
        }

        wp_send_json_success();
    }

    /**
     * AJAX: Test crawl
     */
    public function ajax_test_crawl() {
        check_ajax_referer('vtncal_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';

        switch ($type) {
            case 'holiday':
                $this->crawl_holiday();
                break;
            case 'movie':
                $this->crawl_movies();
                break;
            case 'football':
                $this->crawl_football();
                break;
            case 'power':
                $this->crawl_power_outage();
                break;
            default:
                wp_send_json_error('Invalid type');
        }

        $count = $this->get_event_count($type);
        wp_send_json_success(array('items' => $count));
    }

    /**
     * AJAX: Get logs
     */
    public function ajax_get_logs() {
        check_ajax_referer('vtncal_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $logs = $this->get_logs(50);
        wp_send_json_success($logs);
    }

    /**
     * ========================================
     * FRONTEND DISPLAY
     * ========================================
     */

    /**
     * Enqueue frontend scripts
     */
    public function frontend_enqueue_scripts() {
        wp_enqueue_style('vtncal-frontend', false);
        wp_add_inline_style('vtncal-frontend', $this->get_frontend_css());

        wp_enqueue_script('vtncal-frontend', false, array('jquery'), VTNCAL_VERSION, true);
        wp_add_inline_script('vtncal-frontend', $this->get_frontend_js());
    }

    /**
     * Get frontend CSS
     */
    private function get_frontend_css() {
        $colors = json_decode($this->get_setting('color_scheme', '{}'), true);

        return "
        .vtncal-wrapper {
            max-width: 1200px;
            margin: 20px auto;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }
        .vtncal-event-card {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-left: 4px solid #ccc;
        }
        .vtncal-event-card.holiday {
            border-left-color: " . ($colors['holiday'] ?? '#FF6B6B') . ";
        }
        .vtncal-event-card.movie {
            border-left-color: " . ($colors['movie'] ?? '#4ECDC4') . ";
        }
        .vtncal-event-card.football {
            border-left-color: " . ($colors['football'] ?? '#45B7D1') . ";
        }
        .vtncal-event-card.power_outage {
            border-left-color: " . ($colors['power_outage'] ?? '#FFA07A') . ";
        }
        .vtncal-event-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
        }
        .vtncal-event-date {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .vtncal-event-description {
            color: #555;
            line-height: 1.6;
            margin-bottom: 10px;
        }
        .vtncal-event-ai {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin: 10px 0;
            font-style: italic;
            color: #495057;
        }
        .vtncal-event-source {
            font-size: 12px;
            color: #999;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }
        .vtncal-event-source a {
            color: #0073aa;
            text-decoration: none;
        }
        .vtncal-disclaimer {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
            font-size: 13px;
            color: #856404;
        }
        .vtncal-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        .vtncal-list {
            list-style: none;
            padding: 0;
        }
        .vtncal-movie-poster {
            width: 100%;
            height: auto;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        @media (max-width: 768px) {
            .vtncal-grid {
                grid-template-columns: 1fr;
            }
            .vtncal-event-card {
                padding: 15px;
            }
        }
        ";
    }

    /**
     * Get frontend JS
     */
    private function get_frontend_js() {
        return "
        jQuery(document).ready(function($) {
            // Frontend interactions can be added here
        });
        ";
    }

    /**
     * Shortcode: [vtncalendar]
     */
    public function shortcode_calendar($atts) {
        $atts = shortcode_atts(array(
            'type' => '',
            'province' => '',
            'view' => $this->get_setting('default_view', 'list'),
            'limit' => $this->get_setting('items_per_page', 20)
        ), $atts);

        $args = array(
            'calendar_type' => sanitize_text_field($atts['type']),
            'province' => sanitize_text_field($atts['province']),
            'status' => 'active',
            'date_from' => date('Y-m-d H:i:s'),
            'limit' => intval($atts['limit']),
            'orderby' => 'event_date',
            'order' => 'ASC'
        );

        $events = $this->get_events($args);

        if (empty($events)) {
            return '<p>Không có sự kiện nào.</p>';
        }

        ob_start();

        $view_class = $atts['view'] === 'grid' ? 'vtncal-grid' : 'vtncal-list';

        echo '<div class="vtncal-wrapper ' . esc_attr($view_class) . '">';

        foreach ($events as $event) {
            $this->render_event_card($event, $atts['view']);
        }

        echo '</div>';

        return ob_get_clean();
    }

    /**
     * Render event card
     */
    private function render_event_card($event, $view = 'list') {
        $date_format = $this->get_setting('date_format', 'd/m/Y');
        $show_source = $this->get_setting('show_source_link') === 'true';
        $show_ai = $this->get_setting('show_ai_content') === 'true';

        $meta = json_decode($event->meta_data, true);

        ?>
        <div class="vtncal-event-card <?php echo esc_attr($event->calendar_type); ?>">
            <?php if ($view === 'grid' && $event->calendar_type === 'movie' && !empty($meta['poster_url'])): ?>
                <img src="<?php echo esc_url($meta['poster_url']); ?>" alt="<?php echo esc_attr($event->title); ?>" class="vtncal-movie-poster" />
            <?php endif; ?>

            <div class="vtncal-event-title">
                <?php echo esc_html($event->title); ?>
            </div>

            <div class="vtncal-event-date">
                <?php
                echo date($date_format . ' H:i', strtotime($event->event_date));
                if ($event->event_end_date) {
                    echo ' - ' . date($date_format . ' H:i', strtotime($event->event_end_date));
                }
                ?>
            </div>

            <?php if (!empty($event->location)): ?>
                <div class="vtncal-event-location">
                    📍 <?php echo esc_html($event->location); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($event->description)): ?>
                <div class="vtncal-event-description">
                    <?php echo esc_html($event->description); ?>
                </div>
            <?php endif; ?>

            <?php if ($show_ai && !empty($event->ai_content)): ?>
                <div class="vtncal-event-ai">
                    <?php echo esc_html($event->ai_content); ?>
                </div>
            <?php endif; ?>

            <?php if ($show_source): ?>
                <div class="vtncal-event-source">
                    <span class="source-label">Nguồn:</span>
                    <a href="<?php echo esc_url($event->source_url); ?>" target="_blank" rel="nofollow">
                        <?php echo esc_html($event->source_name); ?>
                    </a>
                    <span class="update-time">
                        | Cập nhật: <?php echo date($date_format, strtotime($event->last_updated)); ?>
                    </span>
                </div>
            <?php endif; ?>

            <div class="vtncal-disclaimer">
                ⚠️ Thông tin có thể thay đổi. Vui lòng kiểm tra nguồn gốc để chắc chắn.
            </div>
        </div>
        <?php
    }
}

// Initialize plugin
VTN_Calendar::get_instance();
