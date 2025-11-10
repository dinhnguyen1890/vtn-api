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
