<?php
/**
 * Banks Management Tab
 */

if (!defined('ABSPATH')) {
    exit;
}

// Lấy danh sách banks
$banks = VBDH_Bank::get_all();
$editing_bank = null;

// Check if editing
if (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $editing_bank = VBDH_Bank::get_by_id(intval($_GET['edit']));
}
?>

<div class="vbdh-banks-tab">
    <?php settings_errors('vbdh_messages'); ?>

    <div class="vbdh-grid">
        <!-- Left: Bank Form -->
        <div class="vbdh-card">
            <h2>
                <?php echo $editing_bank ? __('Chỉnh sửa Ngân hàng', 'vbdh') : __('Thêm Ngân hàng mới', 'vbdh'); ?>
            </h2>

            <form method="post" class="vbdh-form">
                <?php wp_nonce_field('vbdh_admin_action', 'vbdh_nonce'); ?>
                <input type="hidden" name="action" value="<?php echo $editing_bank ? 'edit_bank' : 'add_bank'; ?>">
                <?php if ($editing_bank): ?>
                    <input type="hidden" name="bank_id" value="<?php echo esc_attr($editing_bank->id); ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="bank_code">
                        <?php _e('Mã Ngân hàng', 'vbdh'); ?>
                        <span class="required">*</span>
                    </label>
                    <input type="text"
                           id="bank_code"
                           name="bank_code"
                           class="regular-text"
                           value="<?php echo $editing_bank ? esc_attr($editing_bank->bank_code) : ''; ?>"
                           <?php echo $editing_bank ? 'readonly' : 'required'; ?>
                           placeholder="VD: BIDV">
                    <p class="description"><?php _e('Mã viết tắt của ngân hàng (không thể thay đổi sau khi tạo)', 'vbdh'); ?></p>
                </div>

                <div class="form-group">
                    <label for="bank_name">
                        <?php _e('Tên Ngân hàng (Tiếng Việt)', 'vbdh'); ?>
                        <span class="required">*</span>
                    </label>
                    <input type="text"
                           id="bank_name"
                           name="bank_name"
                           class="regular-text"
                           value="<?php echo $editing_bank ? esc_attr($editing_bank->bank_name) : ''; ?>"
                           required
                           placeholder="VD: Ngân hàng TMCP Đầu tư và Phát triển Việt Nam">
                </div>

                <div class="form-group">
                    <label for="bank_name_en"><?php _e('Tên Ngân hàng (Tiếng Anh)', 'vbdh'); ?></label>
                    <input type="text"
                           id="bank_name_en"
                           name="bank_name_en"
                           class="regular-text"
                           value="<?php echo $editing_bank ? esc_attr($editing_bank->bank_name_en) : ''; ?>"
                           placeholder="VD: Bank for Investment and Development of Vietnam">
                </div>

                <div class="form-group">
                    <label for="bank_logo_url"><?php _e('URL Logo', 'vbdh'); ?></label>
                    <input type="url"
                           id="bank_logo_url"
                           name="bank_logo_url"
                           class="regular-text"
                           value="<?php echo $editing_bank ? esc_url($editing_bank->bank_logo_url) : ''; ?>"
                           placeholder="https://example.com/logo.png">
                </div>

                <div class="form-group">
                    <label for="api_endpoint"><?php _e('API Endpoint', 'vbdh'); ?></label>
                    <input type="url"
                           id="api_endpoint"
                           name="api_endpoint"
                           class="regular-text"
                           value="<?php echo $editing_bank ? esc_url($editing_bank->api_endpoint) : ''; ?>"
                           placeholder="https://api.bank.com">
                </div>

                <div class="form-group">
                    <label for="api_type">
                        <?php _e('Loại API', 'vbdh'); ?>
                        <span class="required">*</span>
                    </label>
                    <select id="api_type" name="api_type" required>
                        <option value="bidv" <?php echo $editing_bank && $editing_bank->api_type === 'bidv' ? 'selected' : ''; ?>>
                            BIDV
                        </option>
                        <option value="vpbank" <?php echo $editing_bank && $editing_bank->api_type === 'vpbank' ? 'selected' : ''; ?>>
                            VPBank
                        </option>
                        <option value="techcombank" <?php echo $editing_bank && $editing_bank->api_type === 'techcombank' ? 'selected' : ''; ?>>
                            Techcombank
                        </option>
                        <option value="vietcombank" <?php echo $editing_bank && $editing_bank->api_type === 'vietcombank' ? 'selected' : ''; ?>>
                            Vietcombank
                        </option>
                        <option value="custom" <?php echo $editing_bank && $editing_bank->api_type === 'custom' ? 'selected' : ''; ?>>
                            Custom
                        </option>
                    </select>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox"
                               name="is_active"
                               value="1"
                               <?php echo !$editing_bank || $editing_bank->is_active ? 'checked' : ''; ?>>
                        <?php _e('Kích hoạt ngân hàng này', 'vbdh'); ?>
                    </label>
                </div>

                <div class="form-actions">
                    <?php submit_button($editing_bank ? __('Cập nhật', 'vbdh') : __('Thêm Ngân hàng', 'vbdh'), 'primary', 'submit', false); ?>
                    <?php if ($editing_bank): ?>
                        <a href="?page=vbdh-banking-data&tab=banks" class="button">
                            <?php _e('Hủy', 'vbdh'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Right: Banks List -->
        <div class="vbdh-card">
            <h2><?php _e('Danh sách Ngân hàng', 'vbdh'); ?></h2>

            <?php if (empty($banks)): ?>
                <p class="no-data"><?php _e('Chưa có ngân hàng nào. Hãy thêm ngân hàng đầu tiên!', 'vbdh'); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Mã', 'vbdh'); ?></th>
                            <th><?php _e('Tên Ngân hàng', 'vbdh'); ?></th>
                            <th><?php _e('Loại API', 'vbdh'); ?></th>
                            <th><?php _e('Trạng thái', 'vbdh'); ?></th>
                            <th><?php _e('Sync cuối', 'vbdh'); ?></th>
                            <th><?php _e('Hành động', 'vbdh'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($banks as $bank): ?>
                            <tr>
                                <td><strong><?php echo esc_html($bank->bank_code); ?></strong></td>
                                <td><?php echo esc_html($bank->bank_name); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo esc_attr($bank->api_type); ?>">
                                        <?php echo esc_html(strtoupper($bank->api_type)); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($bank->is_active): ?>
                                        <span class="status-badge status-active">
                                            <?php _e('Hoạt động', 'vbdh'); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge status-inactive">
                                            <?php _e('Tạm dừng', 'vbdh'); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    if ($bank->last_sync) {
                                        echo esc_html(human_time_diff(strtotime($bank->last_sync), current_time('timestamp'))) . ' ' . __('trước', 'vbdh');
                                    } else {
                                        _e('Chưa sync', 'vbdh');
                                    }
                                    ?>
                                </td>
                                <td class="actions">
                                    <a href="?page=vbdh-banking-data&tab=banks&edit=<?php echo esc_attr($bank->id); ?>"
                                       class="button button-small">
                                        <?php _e('Sửa', 'vbdh'); ?>
                                    </a>
                                    <button type="button"
                                            class="button button-small vbdh-sync-bank"
                                            data-bank-id="<?php echo esc_attr($bank->id); ?>"
                                            data-bank-name="<?php echo esc_attr($bank->bank_name); ?>">
                                        <?php _e('Sync', 'vbdh'); ?>
                                    </button>
                                    <button type="button"
                                            class="button button-small button-link-delete vbdh-delete-bank"
                                            data-bank-id="<?php echo esc_attr($bank->id); ?>"
                                            data-bank-name="<?php echo esc_attr($bank->bank_name); ?>">
                                        <?php _e('Xóa', 'vbdh'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
