<?php
/**
 * Content Generation Tab
 * Auto-generate posts từ banking data
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get statistics
$total_posts = wp_count_posts('post')->publish;
$today_posts = get_posts(array(
    'post_type' => 'post',
    'meta_query' => array(
        array('key' => '_vbdh_generated_date', 'value' => current_time('Y-m-d'))
    ),
    'fields' => 'ids'
));

// Get recent generated posts
$recent_posts = get_posts(array(
    'post_type' => 'post',
    'meta_key' => '_vbdh_content_type',
    'posts_per_page' => 10,
    'orderby' => 'date',
    'order' => 'DESC'
));

// Get active banks for comparison
$banks = VBDH_Bank::get_all(true);
?>

<div class="vbdh-content-tab">
    <?php settings_errors('vbdh_messages'); ?>

    <!-- Statistics -->
    <div class="vbdh-stats-grid">
        <div class="stat-box">
            <div class="stat-icon">
                <span class="dashicons dashicons-admin-post"></span>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo number_format($total_posts); ?></div>
                <div class="stat-label"><?php _e('Tổng bài viết', 'vbdh'); ?></div>
            </div>
        </div>

        <div class="stat-box stat-success">
            <div class="stat-icon">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo count($today_posts); ?></div>
                <div class="stat-label"><?php _e('Đã tạo hôm nay', 'vbdh'); ?></div>
            </div>
        </div>

        <div class="stat-box">
            <div class="stat-icon">
                <span class="dashicons dashicons-clock"></span>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo count($banks); ?></div>
                <div class="stat-label"><?php _e('Ngân hàng active', 'vbdh'); ?></div>
            </div>
        </div>
    </div>

    <div class="vbdh-grid">
        <!-- Left: Generation Tools -->
        <div class="vbdh-card">
            <h2><?php _e('Tạo Nội dung Tự động', 'vbdh'); ?></h2>

            <p class="description">
                <?php _e('Tự động tạo bài viết từ dữ liệu ngân hàng. Nội dung bao gồm bảng so sánh, thống kê và phân tích.', 'vbdh'); ?>
            </p>

            <!-- Daily Rates Post -->
            <div class="content-type-box">
                <h3>
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <?php _e('Bài Viết Lãi Suất Hôm Nay', 'vbdh'); ?>
                </h3>
                <p><?php _e('Tổng hợp lãi suất và tỷ giá mới nhất từ tất cả ngân hàng.', 'vbdh'); ?></p>

                <ul class="feature-list">
                    <li>✅ Top 10 lãi suất cao nhất</li>
                    <li>✅ Bảng so sánh theo kỳ hạn</li>
                    <li>✅ Tỷ giá ngoại tệ</li>
                    <li>✅ SEO-optimized</li>
                </ul>

                <button type="button"
                        class="button button-primary button-hero vbdh-generate-content"
                        data-type="daily_rates">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php _e('Tạo Bài Daily Rates', 'vbdh'); ?>
                </button>
            </div>

            <!-- Comparison Post -->
            <div class="content-type-box">
                <h3>
                    <span class="dashicons dashicons-image-flip-horizontal"></span>
                    <?php _e('Bài So Sánh Ngân Hàng', 'vbdh'); ?>
                </h3>
                <p><?php _e('So sánh chi tiết lãi suất giữa 2 ngân hàng.', 'vbdh'); ?></p>

                <?php if (count($banks) >= 2): ?>
                    <div class="comparison-form">
                        <select id="compare_bank_1" class="regular-text">
                            <option value=""><?php _e('Chọn ngân hàng 1', 'vbdh'); ?></option>
                            <?php foreach ($banks as $bank): ?>
                                <option value="<?php echo esc_attr($bank->id); ?>">
                                    <?php echo esc_html($bank->bank_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <span class="vs-text">VS</span>

                        <select id="compare_bank_2" class="regular-text">
                            <option value=""><?php _e('Chọn ngân hàng 2', 'vbdh'); ?></option>
                            <?php foreach ($banks as $bank): ?>
                                <option value="<?php echo esc_attr($bank->id); ?>">
                                    <?php echo esc_html($bank->bank_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="button"
                            class="button button-primary button-hero vbdh-generate-comparison"
                            id="generate-comparison-btn">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <?php _e('Tạo Bài So Sánh', 'vbdh'); ?>
                    </button>
                <?php else: ?>
                    <div class="notice notice-warning inline">
                        <p><?php _e('Cần ít nhất 2 ngân hàng active để tạo bài so sánh.', 'vbdh'); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Ranking Post -->
            <div class="content-type-box">
                <h3>
                    <span class="dashicons dashicons-awards"></span>
                    <?php _e('Bài Xếp Hạng Top 10', 'vbdh'); ?>
                </h3>
                <p><?php _e('Xếp hạng các ngân hàng có lãi suất tốt nhất.', 'vbdh'); ?></p>

                <button type="button"
                        class="button button-primary button-hero vbdh-generate-content"
                        data-type="ranking">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php _e('Tạo Bài Ranking', 'vbdh'); ?>
                </button>
            </div>
        </div>

        <!-- Right: Recent Posts -->
        <div class="vbdh-card">
            <h2><?php _e('Bài Viết Đã Tạo Gần Đây', 'vbdh'); ?></h2>

            <?php if (empty($recent_posts)): ?>
                <p class="no-data"><?php _e('Chưa có bài viết nào được tạo tự động.', 'vbdh'); ?></p>
            <?php else: ?>
                <div class="recent-posts-list">
                    <?php foreach ($recent_posts as $post):
                        $content_type = get_post_meta($post->ID, '_vbdh_content_type', true);
                        $post_url = get_permalink($post->ID);
                        $edit_url = get_edit_post_link($post->ID);
                    ?>
                        <div class="recent-post-item">
                            <div class="post-icon">
                                <?php if ($content_type === 'daily_rates'): ?>
                                    <span class="dashicons dashicons-calendar-alt"></span>
                                <?php elseif ($content_type === 'comparison'): ?>
                                    <span class="dashicons dashicons-image-flip-horizontal"></span>
                                <?php elseif ($content_type === 'ranking'): ?>
                                    <span class="dashicons dashicons-awards"></span>
                                <?php else: ?>
                                    <span class="dashicons dashicons-admin-post"></span>
                                <?php endif; ?>
                            </div>

                            <div class="post-details">
                                <h4>
                                    <a href="<?php echo esc_url($edit_url); ?>" target="_blank">
                                        <?php echo esc_html($post->post_title); ?>
                                    </a>
                                </h4>
                                <div class="post-meta">
                                    <span class="post-type-badge badge-<?php echo esc_attr($content_type); ?>">
                                        <?php echo esc_html(ucfirst($content_type)); ?>
                                    </span>
                                    <span class="post-date">
                                        <?php echo human_time_diff(strtotime($post->post_date), current_time('timestamp')); ?>
                                        <?php _e('trước', 'vbdh'); ?>
                                    </span>
                                </div>
                            </div>

                            <div class="post-actions">
                                <a href="<?php echo esc_url($post_url); ?>"
                                   target="_blank"
                                   class="button button-small">
                                    <?php _e('Xem', 'vbdh'); ?>
                                </a>
                                <a href="<?php echo esc_url($edit_url); ?>"
                                   target="_blank"
                                   class="button button-small">
                                    <?php _e('Sửa', 'vbdh'); ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Settings Section -->
    <div class="vbdh-card">
        <h2><?php _e('Cài đặt Tạo Nội dung', 'vbdh'); ?></h2>

        <form method="post" class="vbdh-settings-form">
            <?php wp_nonce_field('vbdh_admin_action', 'vbdh_nonce'); ?>
            <input type="hidden" name="action" value="save_content_settings">

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label><?php _e('Tự động tạo Daily Rates', 'vbdh'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox"
                                   name="auto_daily_rates"
                                   value="1"
                                   <?php checked(get_option('vbdh_auto_daily_rates', 0), 1); ?>>
                            <?php _e('Tự động tạo bài daily rates mỗi ngày lúc 6:00 AM', 'vbdh'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label><?php _e('Auto-publish', 'vbdh'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox"
                                   name="auto_publish"
                                   value="1"
                                   <?php checked(get_option('vbdh_auto_publish', 1), 1); ?>>
                            <?php _e('Tự động publish bài viết (không cần draft)', 'vbdh'); ?>
                        </label>
                    </td>
                </tr>
            </table>

            <?php submit_button(__('Lưu Cài đặt', 'vbdh')); ?>
        </form>
    </div>
</div>
