<?php
/**
 * IndexNow API Integration
 * Tự động thông báo search engines khi có nội dung mới
 */

if (!defined('ABSPATH')) {
    exit;
}

class VBDH_IndexNow {

    /**
     * IndexNow API endpoint
     */
    const API_ENDPOINT = 'https://api.indexnow.org/indexnow';

    /**
     * Supported search engines
     */
    const SEARCH_ENGINES = array(
        'bing' => 'https://www.bing.com/indexnow',
        'yandex' => 'https://yandex.com/indexnow'
    );

    /**
     * Get or generate API key
     */
    public static function get_api_key() {
        $api_key = get_option('vbdh_indexnow_key');

        if (!$api_key) {
            // Generate new 32-character hex key
            $api_key = bin2hex(random_bytes(16));
            update_option('vbdh_indexnow_key', $api_key);

            // Create key file
            self::create_key_file($api_key);
        }

        return $api_key;
    }

    /**
     * Create key verification file
     */
    private static function create_key_file($api_key) {
        $uploads_dir = wp_upload_dir();
        $file_path = trailingslashit($uploads_dir['basedir']) . $api_key . '.txt';

        // Create file with API key as content
        file_put_contents($file_path, $api_key);

        // Save file path for later reference
        update_option('vbdh_indexnow_key_file', $file_path);
    }

    /**
     * Get key file URL
     */
    public static function get_key_file_url() {
        $api_key = self::get_api_key();
        $uploads_dir = wp_upload_dir();
        return trailingslashit($uploads_dir['baseurl']) . $api_key . '.txt';
    }

    /**
     * Submit single URL to IndexNow
     */
    public static function submit_url($url) {
        $api_key = self::get_api_key();
        $host = parse_url(home_url(), PHP_URL_HOST);

        $data = array(
            'host' => $host,
            'key' => $api_key,
            'keyLocation' => self::get_key_file_url(),
            'urlList' => array($url)
        );

        return self::send_request($data);
    }

    /**
     * Submit multiple URLs to IndexNow
     */
    public static function submit_urls($urls) {
        if (empty($urls)) {
            return false;
        }

        // Limit to 10000 URLs per request (IndexNow limit)
        $urls = array_slice($urls, 0, 10000);

        $api_key = self::get_api_key();
        $host = parse_url(home_url(), PHP_URL_HOST);

        $data = array(
            'host' => $host,
            'key' => $api_key,
            'keyLocation' => self::get_key_file_url(),
            'urlList' => $urls
        );

        return self::send_request($data);
    }

    /**
     * Send request to IndexNow API
     */
    private static function send_request($data) {
        $response = wp_remote_post(self::API_ENDPOINT, array(
            'headers' => array(
                'Content-Type' => 'application/json; charset=utf-8'
            ),
            'body' => wp_json_encode($data),
            'timeout' => 10
        ));

        if (is_wp_error($response)) {
            self::log_error('IndexNow request failed: ' . $response->get_error_message());
            return false;
        }

        $status_code = wp_remote_retrieve_response_code($response);

        // 200 = Success
        // 202 = Accepted (URLs added to queue)
        if (in_array($status_code, array(200, 202))) {
            self::log_success(count($data['urlList']) . ' URLs submitted successfully');
            return true;
        }

        // Log error
        $error_message = "IndexNow returned status $status_code";
        if ($status_code === 400) {
            $error_message .= ' - Invalid request format';
        } elseif ($status_code === 403) {
            $error_message .= ' - API key validation failed';
        } elseif ($status_code === 422) {
            $error_message .= ' - URL does not belong to host or malformed URL';
        } elseif ($status_code === 429) {
            $error_message .= ' - Too many requests (rate limited)';
        }

        self::log_error($error_message);
        return false;
    }

    /**
     * Auto-submit on post publish/update
     */
    public static function on_post_save($post_id, $post, $update) {
        // Check if auto-submit is enabled
        if (!get_option('vbdh_indexnow_enabled', true)) {
            return;
        }

        // Only for published posts
        if ($post->post_status !== 'publish') {
            return;
        }

        // Skip autosaves and revisions
        if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
            return;
        }

        // Get permalink
        $url = get_permalink($post_id);

        // Submit to IndexNow
        $result = self::submit_url($url);

        // Save submission timestamp
        if ($result) {
            update_post_meta($post_id, '_vbdh_indexnow_submitted', current_time('mysql'));
            update_post_meta($post_id, '_vbdh_indexnow_status', 'success');
        } else {
            update_post_meta($post_id, '_vbdh_indexnow_status', 'failed');
        }
    }

    /**
     * Submit homepage and important pages
     */
    public static function submit_site_pages() {
        $urls = array();

        // Homepage
        $urls[] = home_url('/');

        // Get all published posts
        $posts = get_posts(array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'numberposts' => 100,
            'orderby' => 'modified',
            'order' => 'DESC'
        ));

        foreach ($posts as $post) {
            $urls[] = get_permalink($post);
        }

        // Submit all URLs
        return self::submit_urls($urls);
    }

    /**
     * Log success
     */
    private static function log_success($message) {
        error_log('VBDH IndexNow SUCCESS: ' . $message);

        // Also save to database
        global $wpdb;
        $table = $wpdb->prefix . 'vn_api_logs';

        $wpdb->insert($table, array(
            'api_name' => 'IndexNow',
            'endpoint' => self::API_ENDPOINT,
            'status' => 'success',
            'message' => $message,
            'created_at' => current_time('mysql')
        ));
    }

    /**
     * Log error
     */
    private static function log_error($message) {
        error_log('VBDH IndexNow ERROR: ' . $message);

        // Also save to database
        global $wpdb;
        $table = $wpdb->prefix . 'vn_api_logs';

        $wpdb->insert($table, array(
            'api_name' => 'IndexNow',
            'endpoint' => self::API_ENDPOINT,
            'status' => 'error',
            'message' => $message,
            'created_at' => current_time('mysql')
        ));
    }

    /**
     * Get submission stats
     */
    public static function get_stats() {
        global $wpdb;
        $table = $wpdb->prefix . 'vn_api_logs';

        $total = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM $table
            WHERE api_name = %s
        ", 'IndexNow'));

        $success = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM $table
            WHERE api_name = %s AND status = %s
        ", 'IndexNow', 'success'));

        $failed = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM $table
            WHERE api_name = %s AND status = %s
        ", 'IndexNow', 'error'));

        $last_submission = $wpdb->get_var($wpdb->prepare("
            SELECT created_at
            FROM $table
            WHERE api_name = %s
            ORDER BY created_at DESC
            LIMIT 1
        ", 'IndexNow'));

        return array(
            'total' => intval($total),
            'success' => intval($success),
            'failed' => intval($failed),
            'last_submission' => $last_submission,
            'success_rate' => $total > 0 ? round(($success / $total) * 100, 2) : 0
        );
    }

    /**
     * Test IndexNow API
     */
    public static function test_api() {
        $url = home_url('/');
        return self::submit_url($url);
    }

    /**
     * Generate setup instructions
     */
    public static function get_setup_instructions() {
        $api_key = self::get_api_key();
        $key_url = self::get_key_file_url();

        return array(
            'api_key' => $api_key,
            'key_url' => $key_url,
            'instructions' => array(
                'Khóa API đã được tạo tự động',
                'File xác minh đã được tạo tại: ' . $key_url,
                'Truy cập URL trên để kiểm tra file có tồn tại',
                'IndexNow sẽ tự động gửi thông báo khi có bài viết mới/cập nhật',
                'Xem log tại tab "API Logs" để theo dõi trạng thái'
            )
        );
    }
}

// Hook to submit URLs on post save
add_action('save_post', array('VBDH_IndexNow', 'on_post_save'), 10, 3);
