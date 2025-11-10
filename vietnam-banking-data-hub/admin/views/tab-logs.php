<?php
/**
 * API Logs Tab
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get filter params
$filter_bank = isset($_GET['filter_bank']) ? intval($_GET['filter_bank']) : null;
$filter_status = isset($_GET['filter_status']) ? sanitize_text_field($_GET['filter_status']) : null;
$page = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
$per_page = 50;

// Get logs
$logs = VBDH_API_Log::get_logs(array(
    'bank_id' => $filter_bank,
    'status' => $filter_status,
    'limit' => $per_page,
    'offset' => ($page - 1) * $per_page
));

$total_logs = VBDH_API_Log::count_logs(array(
    'bank_id' => $filter_bank,
    'status' => $filter_status
));

// Get stats
$stats = VBDH_API_Log::get_stats(null, 7);

// Get all banks for filter
$banks = VBDH_Bank::get_all();
?>

<div class="vbdh-logs-tab">
    <!-- Stats -->
    <div class="vbdh-stats-grid">
        <div class="stat-box stat-total">
            <div class="stat-icon">
                <span class="dashicons dashicons-networking"></span>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo number_format($stats->total_calls); ?></div>
                <div class="stat-label"><?php _e('Tổng API Calls (7 ngày)', 'vbdh'); ?></div>
            </div>
        </div>

        <div class="stat-box stat-success">
            <div class="stat-icon">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo number_format($stats->success_calls); ?></div>
                <div class="stat-label"><?php _e('Thành công', 'vbdh'); ?></div>
                <div class="stat-percent">
                    <?php
                    $success_rate = $stats->total_calls > 0 ? ($stats->success_calls / $stats->total_calls) * 100 : 0;
                    echo number_format($success_rate, 1);
                    ?>%
                </div>
            </div>
        </div>

        <div class="stat-box stat-error">
            <div class="stat-icon">
                <span class="dashicons dashicons-dismiss"></span>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo number_format($stats->error_calls); ?></div>
                <div class="stat-label"><?php _e('Lỗi', 'vbdh'); ?></div>
            </div>
        </div>

        <div class="stat-box stat-time">
            <div class="stat-icon">
                <span class="dashicons dashicons-clock"></span>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo number_format($stats->avg_execution_time, 2); ?>s</div>
                <div class="stat-label"><?php _e('Thời gian trung bình', 'vbdh'); ?></div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="vbdh-card">
        <form method="get" class="vbdh-filters">
            <input type="hidden" name="page" value="vbdh-banking-data">
            <input type="hidden" name="tab" value="logs">

            <div class="filter-group">
                <label for="filter_bank"><?php _e('Ngân hàng:', 'vbdh'); ?></label>
                <select name="filter_bank" id="filter_bank">
                    <option value=""><?php _e('Tất cả', 'vbdh'); ?></option>
                    <?php foreach ($banks as $bank): ?>
                        <option value="<?php echo esc_attr($bank->id); ?>"
                                <?php selected($filter_bank, $bank->id); ?>>
                            <?php echo esc_html($bank->bank_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-group">
                <label for="filter_status"><?php _e('Trạng thái:', 'vbdh'); ?></label>
                <select name="filter_status" id="filter_status">
                    <option value=""><?php _e('Tất cả', 'vbdh'); ?></option>
                    <option value="success" <?php selected($filter_status, 'success'); ?>>
                        <?php _e('Thành công', 'vbdh'); ?>
                    </option>
                    <option value="error" <?php selected($filter_status, 'error'); ?>>
                        <?php _e('Lỗi', 'vbdh'); ?>
                    </option>
                    <option value="timeout" <?php selected($filter_status, 'timeout'); ?>>
                        <?php _e('Timeout', 'vbdh'); ?>
                    </option>
                </select>
            </div>

            <?php submit_button(__('Lọc', 'vbdh'), 'secondary', 'submit', false); ?>

            <?php if ($filter_bank || $filter_status): ?>
                <a href="?page=vbdh-banking-data&tab=logs" class="button">
                    <?php _e('Xóa bộ lọc', 'vbdh'); ?>
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Logs Table -->
    <div class="vbdh-card">
        <h2>
            <?php _e('API Logs', 'vbdh'); ?>
            <span class="count">(<?php echo number_format($total_logs); ?>)</span>
        </h2>

        <?php if (empty($logs)): ?>
            <p class="no-data"><?php _e('Không có logs nào.', 'vbdh'); ?></p>
        <?php else: ?>
            <div class="table-wrapper">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th width="150"><?php _e('Thời gian', 'vbdh'); ?></th>
                            <th width="150"><?php _e('Ngân hàng', 'vbdh'); ?></th>
                            <th><?php _e('API Endpoint', 'vbdh'); ?></th>
                            <th width="100"><?php _e('Trạng thái', 'vbdh'); ?></th>
                            <th width="80"><?php _e('Code', 'vbdh'); ?></th>
                            <th width="100"><?php _e('Thời gian', 'vbdh'); ?></th>
                            <th><?php _e('Lỗi', 'vbdh'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td>
                                    <?php echo esc_html(date('Y-m-d H:i:s', strtotime($log->created_at))); ?>
                                    <br>
                                    <small class="muted">
                                        <?php echo human_time_diff(strtotime($log->created_at), current_time('timestamp')); ?>
                                        <?php _e('trước', 'vbdh'); ?>
                                    </small>
                                </td>
                                <td>
                                    <strong><?php echo esc_html($log->bank_name ?? 'N/A'); ?></strong>
                                    <br>
                                    <small class="muted"><?php echo esc_html($log->bank_code ?? ''); ?></small>
                                </td>
                                <td>
                                    <code class="endpoint"><?php echo esc_html($log->api_endpoint); ?></code>
                                </td>
                                <td>
                                    <?php
                                    $status_class = 'status-' . $log->status;
                                    $status_text = '';
                                    switch ($log->status) {
                                        case 'success':
                                            $status_text = __('Thành công', 'vbdh');
                                            break;
                                        case 'error':
                                            $status_text = __('Lỗi', 'vbdh');
                                            break;
                                        case 'timeout':
                                            $status_text = __('Timeout', 'vbdh');
                                            break;
                                    }
                                    ?>
                                    <span class="status-badge <?php echo esc_attr($status_class); ?>">
                                        <?php echo esc_html($status_text); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($log->response_code): ?>
                                        <code><?php echo esc_html($log->response_code); ?></code>
                                    <?php else: ?>
                                        <span class="muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($log->execution_time): ?>
                                        <?php echo number_format($log->execution_time, 2); ?>s
                                    <?php else: ?>
                                        <span class="muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($log->error_message): ?>
                                        <span class="error-message" title="<?php echo esc_attr($log->error_message); ?>">
                                            <?php echo esc_html(substr($log->error_message, 0, 50)) . (strlen($log->error_message) > 50 ? '...' : ''); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="muted">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_logs > $per_page): ?>
                <div class="tablenav">
                    <div class="tablenav-pages">
                        <?php
                        $total_pages = ceil($total_logs / $per_page);
                        $base_url = add_query_arg(array(
                            'page' => 'vbdh-banking-data',
                            'tab' => 'logs',
                            'filter_bank' => $filter_bank,
                            'filter_status' => $filter_status
                        ), admin_url('admin.php'));

                        echo paginate_links(array(
                            'base' => add_query_arg('paged', '%#%', $base_url),
                            'format' => '',
                            'current' => $page,
                            'total' => $total_pages,
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;'
                        ));
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
