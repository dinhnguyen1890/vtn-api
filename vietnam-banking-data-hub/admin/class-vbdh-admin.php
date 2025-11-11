<?php
/**
 * Admin Interface
 * Quản lý giao diện admin của plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class VBDH_Admin {

    /**
     * Thêm admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Banking Data', 'vbdh'),
            __('Banking Data', 'vbdh'),
            'manage_options',
            'vbdh-banking-data',
            array($this, 'render_admin_page'),
            'dashicons-chart-line',
            30
        );
    }

    /**
     * Enqueue CSS
     */
    public function enqueue_styles($hook) {
        if (strpos($hook, 'vbdh-banking-data') === false) {
            return;
        }

        wp_enqueue_style(
            'vbdh-admin',
            VBDH_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            VBDH_VERSION
        );
    }

    /**
     * Enqueue JS
     */
    public function enqueue_scripts($hook) {
        if (strpos($hook, 'vbdh-banking-data') === false) {
            return;
        }

        wp_enqueue_script(
            'vbdh-admin',
            VBDH_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            VBDH_VERSION,
            true
        );

        // Localize script
        wp_localize_script('vbdh-admin', 'vbdhAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('vbdh_admin_nonce'),
            'strings' => array(
                'confirm_delete' => __('Bạn có chắc muốn xóa?', 'vbdh'),
                'saving' => __('Đang lưu...', 'vbdh'),
                'success' => __('Thành công!', 'vbdh'),
                'error' => __('Có lỗi xảy ra!', 'vbdh'),
                'syncing' => __('Đang đồng bộ...', 'vbdh')
            )
        ));
    }

    /**
     * Render admin page
     */
    public function render_admin_page() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('Bạn không có quyền truy cập trang này.', 'vbdh'));
        }

        // Handle actions
        $this->handle_actions();

        // Get current tab
        $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'banks';

        ?>
        <div class="wrap vbdh-admin-wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <!-- Tabs -->
            <nav class="nav-tab-wrapper wp-clearfix">
                <a href="?page=vbdh-banking-data&tab=banks"
                   class="nav-tab <?php echo $current_tab === 'banks' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-admin-multisite"></span>
                    <?php _e('Quản lý Ngân hàng', 'vbdh'); ?>
                </a>
                <a href="?page=vbdh-banking-data&tab=sync"
                   class="nav-tab <?php echo $current_tab === 'sync' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-update"></span>
                    <?php _e('Đồng bộ dữ liệu', 'vbdh'); ?>
                </a>
                <a href="?page=vbdh-banking-data&tab=logs"
                   class="nav-tab <?php echo $current_tab === 'logs' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-list-view"></span>
                    <?php _e('API Logs', 'vbdh'); ?>
                </a>
                <a href="?page=vbdh-banking-data&tab=content"
                   class="nav-tab <?php echo $current_tab === 'content' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-welcome-write-blog"></span>
                    <?php _e('Tạo Nội dung', 'vbdh'); ?>
                </a>
                <a href="?page=vbdh-banking-data&tab=shortcodes"
                   class="nav-tab <?php echo $current_tab === 'shortcodes' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-shortcode"></span>
                    <?php _e('Shortcodes & Widgets', 'vbdh'); ?>
                </a>
                <a href="?page=vbdh-banking-data&tab=seo"
                   class="nav-tab <?php echo $current_tab === 'seo' ? 'nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-search"></span>
                    <?php _e('SEO & Indexing', 'vbdh'); ?>
                </a>
            </nav>

            <!-- Tab Content -->
            <div class="vbdh-tab-content">
                <?php
                switch ($current_tab) {
                    case 'banks':
                        $this->render_banks_tab();
                        break;

                    case 'sync':
                        $this->render_sync_tab();
                        break;

                    case 'logs':
                        $this->render_logs_tab();
                        break;

                    case 'content':
                        $this->render_content_tab();
                        break;

                    case 'shortcodes':
                        $this->render_shortcodes_tab();
                        break;

                    case 'seo':
                        $this->render_seo_tab();
                        break;

                    default:
                        $this->render_banks_tab();
                }
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render Banks Management Tab
     */
    private function render_banks_tab() {
        require_once VBDH_PLUGIN_DIR . 'admin/views/tab-banks.php';
    }

    /**
     * Render Sync Tab
     */
    private function render_sync_tab() {
        require_once VBDH_PLUGIN_DIR . 'admin/views/tab-sync.php';
    }

    /**
     * Render Logs Tab
     */
    private function render_logs_tab() {
        require_once VBDH_PLUGIN_DIR . 'admin/views/tab-logs.php';
    }

    /**
     * Render Content Generation Tab
     */
    private function render_content_tab() {
        require_once VBDH_PLUGIN_DIR . 'admin/views/tab-content.php';
    }

    /**
     * Render shortcodes tab
     */
    private function render_shortcodes_tab() {
        require_once VBDH_PLUGIN_DIR . 'admin/views/tab-shortcodes.php';
    }

    /**
     * Render SEO tab
     */
    private function render_seo_tab() {
        // Handle SEO actions
        $this->handle_seo_actions();
        require_once VBDH_PLUGIN_DIR . 'admin/views/tab-seo.php';
    }

    /**
     * Handle SEO actions
     */
    private function handle_seo_actions() {
        if (!isset($_POST['vbdh_seo_nonce']) || !wp_verify_nonce($_POST['vbdh_seo_nonce'], 'vbdh_seo_action')) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        $action = isset($_POST['action']) ? sanitize_text_field($_POST['action']) : '';

        switch ($action) {
            case 'test_indexnow':
                $result = VBDH_IndexNow::test_api();
                if ($result) {
                    add_settings_error('vbdh_messages', 'vbdh_message', 'IndexNow API test thành công!', 'success');
                } else {
                    add_settings_error('vbdh_messages', 'vbdh_message', 'IndexNow API test thất bại. Kiểm tra logs.', 'error');
                }
                break;

            case 'submit_all_posts':
                $result = VBDH_IndexNow::submit_site_pages();
                if ($result) {
                    add_settings_error('vbdh_messages', 'vbdh_message', 'Đã submit tất cả posts tới IndexNow!', 'success');
                } else {
                    add_settings_error('vbdh_messages', 'vbdh_message', 'Submit failed. Kiểm tra logs.', 'error');
                }
                break;

            case 'toggle_indexnow':
                $current = get_option('vbdh_indexnow_enabled', true);
                update_option('vbdh_indexnow_enabled', !$current);
                $message = !$current ? 'Đã bật auto-submit' : 'Đã tắt auto-submit';
                add_settings_error('vbdh_messages', 'vbdh_message', $message, 'success');
                break;

            case 'generate_sitemap':
                $sitemap_path = VBDH_SEO_Optimizer::save_sitemap();
                add_settings_error('vbdh_messages', 'vbdh_message', 'Đã tạo sitemap: ' . $sitemap_path, 'success');
                break;

            case 'save_seo_settings':
                update_option('vbdh_twitter_handle', sanitize_text_field($_POST['vbdh_twitter_handle']));
                update_option('vbdh_seo_auto_meta', isset($_POST['vbdh_seo_auto_meta']));
                update_option('vbdh_seo_auto_keywords', isset($_POST['vbdh_seo_auto_keywords']));
                add_settings_error('vbdh_messages', 'vbdh_message', 'Đã lưu cài đặt SEO!', 'success');
                break;
        }
    }

    /**
     * Handle form actions
     */
    private function handle_actions() {
        // Check nonce
        if (!isset($_POST['vbdh_nonce']) || !wp_verify_nonce($_POST['vbdh_nonce'], 'vbdh_admin_action')) {
            return;
        }

        $action = isset($_POST['action']) ? sanitize_text_field($_POST['action']) : '';

        switch ($action) {
            case 'add_bank':
                $this->handle_add_bank();
                break;

            case 'edit_bank':
                $this->handle_edit_bank();
                break;

            case 'delete_bank':
                $this->handle_delete_bank();
                break;

            case 'sync_now':
                $this->handle_sync_now();
                break;
        }
    }

    /**
     * Handle add bank
     */
    private function handle_add_bank() {
        $data = array(
            'bank_code' => sanitize_text_field($_POST['bank_code']),
            'bank_name' => sanitize_text_field($_POST['bank_name']),
            'bank_name_en' => sanitize_text_field($_POST['bank_name_en']),
            'bank_logo_url' => esc_url_raw($_POST['bank_logo_url']),
            'api_endpoint' => esc_url_raw($_POST['api_endpoint']),
            'api_type' => sanitize_text_field($_POST['api_type']),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        );

        $result = VBDH_Bank::create($data);

        if ($result) {
            add_settings_error('vbdh_messages', 'vbdh_message', __('Ngân hàng đã được thêm thành công!', 'vbdh'), 'success');
        } else {
            add_settings_error('vbdh_messages', 'vbdh_message', __('Có lỗi khi thêm ngân hàng!', 'vbdh'), 'error');
        }
    }

    /**
     * Handle edit bank
     */
    private function handle_edit_bank() {
        $bank_id = intval($_POST['bank_id']);

        $data = array(
            'bank_name' => sanitize_text_field($_POST['bank_name']),
            'bank_name_en' => sanitize_text_field($_POST['bank_name_en']),
            'bank_logo_url' => esc_url_raw($_POST['bank_logo_url']),
            'api_endpoint' => esc_url_raw($_POST['api_endpoint']),
            'api_type' => sanitize_text_field($_POST['api_type']),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        );

        $result = VBDH_Bank::update($bank_id, $data);

        if ($result !== false) {
            add_settings_error('vbdh_messages', 'vbdh_message', __('Ngân hàng đã được cập nhật!', 'vbdh'), 'success');
        } else {
            add_settings_error('vbdh_messages', 'vbdh_message', __('Có lỗi khi cập nhật!', 'vbdh'), 'error');
        }
    }

    /**
     * Handle delete bank
     */
    private function handle_delete_bank() {
        $bank_id = intval($_POST['bank_id']);
        $result = VBDH_Bank::delete($bank_id);

        if ($result) {
            add_settings_error('vbdh_messages', 'vbdh_message', __('Ngân hàng đã được xóa!', 'vbdh'), 'success');
        } else {
            add_settings_error('vbdh_messages', 'vbdh_message', __('Có lỗi khi xóa!', 'vbdh'), 'error');
        }
    }

    /**
     * Handle manual sync
     */
    private function handle_sync_now() {
        $type = sanitize_text_field($_POST['sync_type']);
        $result = VBDH_Cron::manual_trigger($type);

        if ($result['success']) {
            add_settings_error('vbdh_messages', 'vbdh_message', __('Đồng bộ thành công!', 'vbdh'), 'success');
        } else {
            add_settings_error('vbdh_messages', 'vbdh_message', __('Đồng bộ thất bại: ', 'vbdh') . $result['message'], 'error');
        }
    }
}

// AJAX handlers
add_action('wp_ajax_vbdh_sync_bank', 'vbdh_ajax_sync_bank');
add_action('wp_ajax_vbdh_delete_bank', 'vbdh_ajax_delete_bank');
add_action('wp_ajax_vbdh_generate_content', 'vbdh_ajax_generate_content');
add_action('wp_ajax_vbdh_generate_comparison', 'vbdh_ajax_generate_comparison');

// Frontend AJAX handlers (available to non-logged-in users)
add_action('wp_ajax_vbdh_calculate_interest', 'vbdh_ajax_calculate_interest');
add_action('wp_ajax_nopriv_vbdh_calculate_interest', 'vbdh_ajax_calculate_interest');

function vbdh_ajax_sync_bank() {
    check_ajax_referer('vbdh_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized'));
    }

    $bank_id = intval($_POST['bank_id']);
    $type = sanitize_text_field($_POST['type']);

    $fetcher = new VBDH_Data_Fetcher();
    $result = $fetcher->force_fetch($bank_id, $type);

    wp_send_json_success($result);
}

function vbdh_ajax_delete_bank() {
    check_ajax_referer('vbdh_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized'));
    }

    $bank_id = intval($_POST['bank_id']);
    $result = VBDH_Bank::delete($bank_id);

    if ($result) {
        wp_send_json_success(array('message' => 'Bank deleted'));
    } else {
        wp_send_json_error(array('message' => 'Failed to delete'));
    }
}

/**
 * AJAX: Generate content (daily_rates or ranking)
 */
function vbdh_ajax_generate_content() {
    check_ajax_referer('vbdh_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized'));
    }

    $type = sanitize_text_field($_POST['content_type']);
    $generator = new VBDH_Content_Generator();

    if ($type === 'daily_rates') {
        $result = $generator->generate_daily_rates_post();
    } elseif ($type === 'ranking') {
        $result = $generator->generate_ranking_post('savings', 12);
    } else {
        wp_send_json_error(array('message' => 'Invalid content type'));
        return;
    }

    if ($result['success']) {
        $result['edit_url'] = get_edit_post_link($result['post_id']);
        $result['view_url'] = get_permalink($result['post_id']);
        wp_send_json_success($result);
    } else {
        wp_send_json_error($result);
    }
}

/**
 * AJAX: Generate comparison post
 */
function vbdh_ajax_generate_comparison() {
    check_ajax_referer('vbdh_admin_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Unauthorized'));
    }

    $bank_id_1 = intval($_POST['bank_id_1']);
    $bank_id_2 = intval($_POST['bank_id_2']);

    if (!$bank_id_1 || !$bank_id_2) {
        wp_send_json_error(array('message' => 'Vui lòng chọn 2 ngân hàng'));
        return;
    }

    if ($bank_id_1 === $bank_id_2) {
        wp_send_json_error(array('message' => 'Vui lòng chọn 2 ngân hàng khác nhau'));
        return;
    }

    $generator = new VBDH_Content_Generator();
    $result = $generator->generate_comparison_post($bank_id_1, $bank_id_2);

    if ($result['success']) {
        $result['edit_url'] = get_edit_post_link($result['post_id']);
        $result['view_url'] = get_permalink($result['post_id']);
        wp_send_json_success($result);
    } else {
        wp_send_json_error($result);
    }
}

/**
 * AJAX: Calculate interest (Frontend)
 * Tính toán lãi suất dựa trên số tiền, kỳ hạn và ngân hàng
 */
function vbdh_ajax_calculate_interest() {
    check_ajax_referer('vbdh_frontend_nonce', 'nonce');

    $amount = floatval($_POST['amount']);
    $term = intval($_POST['term']);
    $bank_id = intval($_POST['bank_id']);

    // Validation
    if ($amount < 1000000) {
        wp_send_json_error(array('message' => 'Số tiền tối thiểu là 1,000,000 VNĐ'));
        return;
    }

    if (!in_array($term, array(1, 3, 6, 12, 24))) {
        wp_send_json_error(array('message' => 'Kỳ hạn không hợp lệ'));
        return;
    }

    if (!$bank_id) {
        wp_send_json_error(array('message' => 'Vui lòng chọn ngân hàng'));
        return;
    }

    // Get interest rate for the selected bank and term
    global $wpdb;
    $table_name = $wpdb->prefix . 'vn_interest_rates';

    $rate = $wpdb->get_row($wpdb->prepare("
        SELECT interest_rate, product_name
        FROM $table_name
        WHERE bank_id = %d
          AND term_months = %d
          AND rate_type = 'savings'
        ORDER BY effective_date DESC
        LIMIT 1
    ", $bank_id, $term));

    if (!$rate) {
        wp_send_json_error(array('message' => 'Không tìm thấy lãi suất cho ngân hàng và kỳ hạn này'));
        return;
    }

    // Calculate simple interest
    $interest_rate = floatval($rate->interest_rate);
    $months = intval($term);

    // Simple interest formula: Interest = Principal × Rate × Time
    $interest = $amount * ($interest_rate / 100) * ($months / 12);
    $total = $amount + $interest;

    wp_send_json_success(array(
        'rate' => number_format($interest_rate, 2),
        'interest' => $interest,
        'total' => $total,
        'product_name' => $rate->product_name,
        'formatted' => array(
            'rate' => number_format($interest_rate, 2) . '%',
            'interest' => number_format($interest, 0, ',', '.') . ' VNĐ',
            'total' => number_format($total, 0, ',', '.') . ' VNĐ'
        )
    ));
}
