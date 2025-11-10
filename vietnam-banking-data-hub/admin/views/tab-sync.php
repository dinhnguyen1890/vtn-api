<?php
/**
 * Sync Schedule Tab
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get cron info
$exchange_rates_info = VBDH_Cron::get_last_run_info('exchange_rates');
$interest_rates_info = VBDH_Cron::get_last_run_info('interest_rates');

$next_exchange = VBDH_Cron::get_next_scheduled('vbdh_fetch_exchange_rates');
$next_interest = VBDH_Cron::get_next_scheduled('vbdh_fetch_interest_rates');

// Get banks
$banks = VBDH_Bank::get_all(true);
?>

<div class="vbdh-sync-tab">
    <?php settings_errors('vbdh_messages'); ?>

    <div class="vbdh-grid">
        <!-- Left: Manual Sync -->
        <div class="vbdh-card">
            <h2><?php _e('Đồng bộ Thủ công', 'vbdh'); ?></h2>

            <p class="description">
                <?php _e('Nhấn nút bên dưới để đồng bộ dữ liệu ngay lập tức từ tất cả ngân hàng đang hoạt động.', 'vbdh'); ?>
            </p>

            <form method="post" class="vbdh-sync-form">
                <?php wp_nonce_field('vbdh_admin_action', 'vbdh_nonce'); ?>
                <input type="hidden" name="action" value="sync_now">

                <div class="form-group">
                    <label for="sync_type">
                        <?php _e('Chọn loại dữ liệu cần đồng bộ:', 'vbdh'); ?>
                    </label>
                    <select name="sync_type" id="sync_type" class="regular-text">
                        <option value="both"><?php _e('Cả hai (Tỷ giá + Lãi suất)', 'vbdh'); ?></option>
                        <option value="exchange_rates"><?php _e('Chỉ Tỷ giá', 'vbdh'); ?></option>
                        <option value="interest_rates"><?php _e('Chỉ Lãi suất', 'vbdh'); ?></option>
                    </select>
                </div>

                <div class="sync-info-box">
                    <p>
                        <strong><?php _e('Số ngân hàng đang hoạt động:', 'vbdh'); ?></strong>
                        <?php echo count($banks); ?>
                    </p>
                    <p class="description">
                        <?php _e('Quá trình đồng bộ có thể mất vài phút tùy thuộc vào số lượng ngân hàng.', 'vbdh'); ?>
                    </p>
                </div>

                <?php submit_button(__('Đồng bộ ngay', 'vbdh'), 'primary large', 'submit', false); ?>
            </form>

            <hr>

            <h3><?php _e('Danh sách Ngân hàng sẽ đồng bộ', 'vbdh'); ?></h3>

            <?php if (empty($banks)): ?>
                <p class="no-data"><?php _e('Không có ngân hàng nào đang hoạt động.', 'vbdh'); ?></p>
            <?php else: ?>
                <ul class="vbdh-banks-list">
                    <?php foreach ($banks as $bank): ?>
                        <li>
                            <span class="bank-icon"><?php echo esc_html(strtoupper(substr($bank->bank_code, 0, 2))); ?></span>
                            <strong><?php echo esc_html($bank->bank_name); ?></strong>
                            <span class="bank-meta">
                                (<?php echo esc_html($bank->bank_code); ?> - <?php echo esc_html(strtoupper($bank->api_type)); ?>)
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <!-- Right: Schedule Info -->
        <div class="vbdh-card">
            <h2><?php _e('Lịch Đồng bộ Tự động', 'vbdh'); ?></h2>

            <!-- Exchange Rates Schedule -->
            <div class="schedule-box">
                <h3>
                    <span class="dashicons dashicons-money-alt"></span>
                    <?php _e('Tỷ giá Ngoại tệ', 'vbdh'); ?>
                </h3>

                <table class="schedule-table">
                    <tr>
                        <th><?php _e('Tần suất:', 'vbdh'); ?></th>
                        <td><strong><?php _e('Mỗi 1 giờ', 'vbdh'); ?></strong></td>
                    </tr>
                    <tr>
                        <th><?php _e('Lần chạy tiếp theo:', 'vbdh'); ?></th>
                        <td>
                            <?php if ($next_exchange): ?>
                                <?php echo esc_html($next_exchange['datetime']); ?>
                                <br>
                                <small>(<?php echo esc_html($next_exchange['human']); ?> <?php _e('nữa', 'vbdh'); ?>)</small>
                            <?php else: ?>
                                <span class="error"><?php _e('Không có lịch', 'vbdh'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Lần chạy cuối:', 'vbdh'); ?></th>
                        <td>
                            <?php if ($exchange_rates_info['end']): ?>
                                <?php echo esc_html($exchange_rates_info['end']); ?>
                                <br>
                                <small>
                                    (<?php echo human_time_diff(strtotime($exchange_rates_info['end']), current_time('timestamp')); ?>
                                    <?php _e('trước', 'vbdh'); ?>)
                                </small>
                            <?php else: ?>
                                <?php _e('Chưa chạy', 'vbdh'); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Trạng thái:', 'vbdh'); ?></th>
                        <td>
                            <?php if ($exchange_rates_info['running']): ?>
                                <span class="status-badge status-running">
                                    <?php _e('Đang chạy...', 'vbdh'); ?>
                                </span>
                            <?php else: ?>
                                <span class="status-badge status-idle">
                                    <?php _e('Chờ lịch tiếp theo', 'vbdh'); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Interest Rates Schedule -->
            <div class="schedule-box">
                <h3>
                    <span class="dashicons dashicons-chart-line"></span>
                    <?php _e('Lãi suất Tiết kiệm/Vay', 'vbdh'); ?>
                </h3>

                <table class="schedule-table">
                    <tr>
                        <th><?php _e('Tần suất:', 'vbdh'); ?></th>
                        <td><strong><?php _e('Mỗi 6 giờ', 'vbdh'); ?></strong></td>
                    </tr>
                    <tr>
                        <th><?php _e('Lần chạy tiếp theo:', 'vbdh'); ?></th>
                        <td>
                            <?php if ($next_interest): ?>
                                <?php echo esc_html($next_interest['datetime']); ?>
                                <br>
                                <small>(<?php echo esc_html($next_interest['human']); ?> <?php _e('nữa', 'vbdh'); ?>)</small>
                            <?php else: ?>
                                <span class="error"><?php _e('Không có lịch', 'vbdh'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Lần chạy cuối:', 'vbdh'); ?></th>
                        <td>
                            <?php if ($interest_rates_info['end']): ?>
                                <?php echo esc_html($interest_rates_info['end']); ?>
                                <br>
                                <small>
                                    (<?php echo human_time_diff(strtotime($interest_rates_info['end']), current_time('timestamp')); ?>
                                    <?php _e('trước', 'vbdh'); ?>)
                                </small>
                            <?php else: ?>
                                <?php _e('Chưa chạy', 'vbdh'); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Trạng thái:', 'vbdh'); ?></th>
                        <td>
                            <?php if ($interest_rates_info['running']): ?>
                                <span class="status-badge status-running">
                                    <?php _e('Đang chạy...', 'vbdh'); ?>
                                </span>
                            <?php else: ?>
                                <span class="status-badge status-idle">
                                    <?php _e('Chờ lịch tiếp theo', 'vbdh'); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="info-box">
                <h4><?php _e('Lưu ý', 'vbdh'); ?></h4>
                <ul>
                    <li><?php _e('Cron jobs sẽ tự động chạy theo lịch WordPress.', 'vbdh'); ?></li>
                    <li><?php _e('Tỷ giá được cập nhật thường xuyên hơn vì thay đổi liên tục.', 'vbdh'); ?></li>
                    <li><?php _e('Lãi suất ít thay đổi nên cập nhật 6 giờ/lần là đủ.', 'vbdh'); ?></li>
                    <li><?php _e('Dữ liệu cũ sẽ tự động xóa sau 7-30 ngày.', 'vbdh'); ?></li>
                </ul>
            </div>
        </div>
    </div>
</div>
