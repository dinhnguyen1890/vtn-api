<?php
/**
 * Admin Tab: SEO & Auto-Indexing
 * Quản lý SEO và tự động đánh dấu
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get IndexNow stats
$indexnow_stats = VBDH_IndexNow::get_stats();
$indexnow_setup = VBDH_IndexNow::get_setup_instructions();

// Get recent published posts for SEO score
$recent_posts = get_posts(array(
    'post_type' => 'post',
    'post_status' => 'publish',
    'numberposts' => 5,
    'orderby' => 'date',
    'order' => 'DESC'
));
?>

<div class="vbdh-tab-content">
    <!-- IndexNow Stats -->
    <div class="vbdh-stats-grid">
        <div class="stat-box stat-total">
            <div class="stat-icon">
                <span class="dashicons dashicons-chart-line"></span>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo number_format($indexnow_stats['total']); ?></div>
                <div class="stat-label">Tổng Submissions</div>
            </div>
        </div>

        <div class="stat-box stat-success">
            <div class="stat-icon">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo number_format($indexnow_stats['success']); ?></div>
                <div class="stat-label">Thành công</div>
                <div class="stat-percent"><?php echo $indexnow_stats['success_rate']; ?>%</div>
            </div>
        </div>

        <div class="stat-box stat-error">
            <div class="stat-icon">
                <span class="dashicons dashicons-warning"></span>
            </div>
            <div class="stat-content">
                <div class="stat-number"><?php echo number_format($indexnow_stats['failed']); ?></div>
                <div class="stat-label">Thất bại</div>
            </div>
        </div>

        <div class="stat-box stat-time">
            <div class="stat-icon">
                <span class="dashicons dashicons-clock"></span>
            </div>
            <div class="stat-content">
                <div class="stat-number">
                    <?php echo $indexnow_stats['last_submission'] ?
                        human_time_diff(strtotime($indexnow_stats['last_submission']), current_time('timestamp')) : 'N/A'; ?>
                </div>
                <div class="stat-label">Last Submission</div>
            </div>
        </div>
    </div>

    <div class="vbdh-grid">
        <!-- IndexNow Setup -->
        <div class="vbdh-card">
            <h2>
                <span class="dashicons dashicons-admin-site-alt3"></span>
                <?php _e('IndexNow - Tự Động Đánh Dấu', 'vbdh'); ?>
            </h2>

            <div class="sync-info-box">
                <h3><?php _e('Trạng thái:', 'vbdh'); ?></h3>
                <p>
                    <?php if (get_option('vbdh_indexnow_enabled', true)): ?>
                        <span class="status-badge status-active">
                            <?php _e('Đang hoạt động', 'vbdh'); ?>
                        </span>
                    <?php else: ?>
                        <span class="status-badge status-inactive">
                            <?php _e('Tắt', 'vbdh'); ?>
                        </span>
                    <?php endif; ?>
                </p>
                <p><strong>API Key:</strong> <code><?php echo esc_html($indexnow_setup['api_key']); ?></code></p>
                <p>
                    <strong>Key File:</strong>
                    <a href="<?php echo esc_url($indexnow_setup['key_url']); ?>" target="_blank">
                        <?php echo esc_url($indexnow_setup['key_url']); ?>
                    </a>
                </p>
            </div>

            <div class="info-box">
                <h4><?php _e('Hướng dẫn:', 'vbdh'); ?></h4>
                <ul>
                    <?php foreach ($indexnow_setup['instructions'] as $instruction): ?>
                        <li><?php echo esc_html($instruction); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <h3><?php _e('Hành động:', 'vbdh'); ?></h3>
            <form method="post" action="">
                <?php wp_nonce_field('vbdh_seo_action', 'vbdh_seo_nonce'); ?>

                <p>
                    <button type="submit" name="action" value="test_indexnow" class="button">
                        <span class="dashicons dashicons-admin-tools"></span>
                        <?php _e('Test IndexNow API', 'vbdh'); ?>
                    </button>

                    <button type="submit" name="action" value="submit_all_posts" class="button">
                        <span class="dashicons dashicons-upload"></span>
                        <?php _e('Submit All Posts', 'vbdh'); ?>
                    </button>

                    <button type="submit" name="action" value="toggle_indexnow" class="button">
                        <?php if (get_option('vbdh_indexnow_enabled', true)): ?>
                            <span class="dashicons dashicons-dismiss"></span>
                            <?php _e('Tắt Auto-Submit', 'vbdh'); ?>
                        <?php else: ?>
                            <span class="dashicons dashicons-yes"></span>
                            <?php _e('Bật Auto-Submit', 'vbdh'); ?>
                        <?php endif; ?>
                    </button>
                </p>
            </form>
        </div>

        <!-- Schema.org -->
        <div class="vbdh-card">
            <h2>
                <span class="dashicons dashicons-editor-code"></span>
                <?php _e('Schema.org Structured Data', 'vbdh'); ?>
            </h2>

            <div class="info-box">
                <h4><?php _e('Schema tự động được thêm:', 'vbdh'); ?></h4>
                <ul>
                    <li><strong>FinancialService:</strong> Cho bài viết daily rates</li>
                    <li><strong>ItemList:</strong> Cho bài viết comparison & ranking</li>
                    <li><strong>BankOrCreditUnion:</strong> Thông tin ngân hàng</li>
                    <li><strong>ExchangeRateSpecification:</strong> Dữ liệu tỷ giá</li>
                    <li><strong>BreadcrumbList:</strong> Breadcrumbs navigation</li>
                    <li><strong>Article:</strong> Schema mặc định cho bài viết</li>
                </ul>
            </div>

            <h3><?php _e('Kiểm tra Schema:', 'vbdh'); ?></h3>
            <p><?php _e('Sử dụng công cụ của Google để kiểm tra schema:', 'vbdh'); ?></p>
            <p>
                <a href="https://validator.schema.org/" target="_blank" class="button">
                    <span class="dashicons dashicons-external"></span>
                    Schema.org Validator
                </a>
                <a href="https://search.google.com/test/rich-results" target="_blank" class="button">
                    <span class="dashicons dashicons-external"></span>
                    Google Rich Results Test
                </a>
            </p>
        </div>
    </div>

    <!-- SEO Score Checker -->
    <div class="vbdh-card">
        <h2>
            <span class="dashicons dashicons-analytics"></span>
            <?php _e('SEO Score - Bài Viết Gần Đây', 'vbdh'); ?>
        </h2>

        <?php if (empty($recent_posts)): ?>
            <p class="no-data"><?php _e('Chưa có bài viết nào', 'vbdh'); ?></p>
        <?php else: ?>
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php _e('Bài viết', 'vbdh'); ?></th>
                        <th><?php _e('Điểm SEO', 'vbdh'); ?></th>
                        <th><?php _e('Xếp loại', 'vbdh'); ?></th>
                        <th><?php _e('Vấn đề', 'vbdh'); ?></th>
                        <th><?php _e('Hành động', 'vbdh'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_posts as $post):
                        $seo_score = VBDH_SEO_Optimizer::get_seo_score($post->ID);
                        $grade_class = '';
                        if ($seo_score['score'] >= 80) $grade_class = 'status-success';
                        elseif ($seo_score['score'] >= 60) $grade_class = 'status-idle';
                        else $grade_class = 'status-error';
                    ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($post->post_title); ?></strong><br>
                                <small class="muted"><?php echo get_the_date('d/m/Y H:i', $post); ?></small>
                            </td>
                            <td>
                                <strong style="font-size: 18px;"><?php echo $seo_score['score']; ?></strong> / <?php echo $seo_score['max_score']; ?>
                                <br>
                                <small class="muted"><?php echo $seo_score['percentage']; ?>%</small>
                            </td>
                            <td>
                                <span class="status-badge <?php echo $grade_class; ?>">
                                    <?php echo $seo_score['grade']; ?>
                                </span>
                            </td>
                            <td>
                                <?php if (empty($seo_score['issues'])): ?>
                                    <span style="color: #00a32a;">✓ Hoàn hảo!</span>
                                <?php else: ?>
                                    <ul style="margin: 0; padding-left: 20px; font-size: 12px;">
                                        <?php foreach (array_slice($seo_score['issues'], 0, 3) as $issue): ?>
                                            <li><?php echo esc_html($issue); ?></li>
                                        <?php endforeach; ?>
                                        <?php if (count($seo_score['issues']) > 3): ?>
                                            <li><em>+<?php echo count($seo_score['issues']) - 3; ?> vấn đề khác...</em></li>
                                        <?php endif; ?>
                                    </ul>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?php echo get_edit_post_link($post->ID); ?>" class="button button-small">
                                    <?php _e('Chỉnh sửa', 'vbdh'); ?>
                                </a>
                                <a href="<?php echo get_permalink($post->ID); ?>" class="button button-small" target="_blank">
                                    <?php _e('Xem', 'vbdh'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Sitemap & Meta Tags -->
    <div class="vbdh-grid">
        <div class="vbdh-card">
            <h2>
                <span class="dashicons dashicons-networking"></span>
                <?php _e('XML Sitemap', 'vbdh'); ?>
            </h2>

            <p><?php _e('Sitemap giúp search engines hiểu cấu trúc website và crawl hiệu quả hơn.', 'vbdh'); ?></p>

            <form method="post" action="">
                <?php wp_nonce_field('vbdh_seo_action', 'vbdh_seo_nonce'); ?>
                <p>
                    <button type="submit" name="action" value="generate_sitemap" class="button button-primary">
                        <span class="dashicons dashicons-update"></span>
                        <?php _e('Tạo Sitemap', 'vbdh'); ?>
                    </button>
                </p>
            </form>

            <?php
            $sitemap_file = ABSPATH . 'sitemap-vbdh.xml';
            if (file_exists($sitemap_file)):
            ?>
                <div class="sync-info-box">
                    <p>
                        <strong><?php _e('Sitemap URL:', 'vbdh'); ?></strong><br>
                        <a href="<?php echo home_url('sitemap-vbdh.xml'); ?>" target="_blank">
                            <?php echo home_url('sitemap-vbdh.xml'); ?>
                        </a>
                    </p>
                    <p>
                        <small class="muted">
                            <?php _e('Cập nhật:', 'vbdh'); ?> <?php echo date('d/m/Y H:i', filemtime($sitemap_file)); ?>
                        </small>
                    </p>
                </div>

                <p><?php _e('Submit sitemap tới search engines:', 'vbdh'); ?></p>
                <ul>
                    <li><a href="https://www.google.com/webmasters/tools/sitemap-list" target="_blank">Google Search Console</a></li>
                    <li><a href="https://www.bing.com/webmasters/home" target="_blank">Bing Webmaster Tools</a></li>
                </ul>
            <?php endif; ?>
        </div>

        <div class="vbdh-card">
            <h2>
                <span class="dashicons dashicons-admin-settings"></span>
                <?php _e('Cài Đặt SEO', 'vbdh'); ?>
            </h2>

            <form method="post" action="">
                <?php wp_nonce_field('vbdh_seo_action', 'vbdh_seo_nonce'); ?>
                <input type="hidden" name="action" value="save_seo_settings">

                <p>
                    <label>
                        <strong><?php _e('Twitter Handle:', 'vbdh'); ?></strong><br>
                        <input type="text"
                               name="vbdh_twitter_handle"
                               value="<?php echo esc_attr(get_option('vbdh_twitter_handle', '')); ?>"
                               class="regular-text"
                               placeholder="username (không có @)">
                    </label>
                    <br><small class="description"><?php _e('Tên tài khoản Twitter/X của website (không cần @)', 'vbdh'); ?></small>
                </p>

                <p>
                    <label>
                        <input type="checkbox"
                               name="vbdh_seo_auto_meta"
                               value="1"
                               <?php checked(get_option('vbdh_seo_auto_meta', true)); ?>>
                        <?php _e('Tự động tạo meta description từ excerpt/content', 'vbdh'); ?>
                    </label>
                </p>

                <p>
                    <label>
                        <input type="checkbox"
                               name="vbdh_seo_auto_keywords"
                               value="1"
                               <?php checked(get_option('vbdh_seo_auto_keywords', true)); ?>>
                        <?php _e('Tự động tạo meta keywords từ tags', 'vbdh'); ?>
                    </label>
                </p>

                <p>
                    <button type="submit" class="button button-primary">
                        <span class="dashicons dashicons-saved"></span>
                        <?php _e('Lưu Cài Đặt', 'vbdh'); ?>
                    </button>
                </p>
            </form>
        </div>
    </div>

    <!-- SEO Tips -->
    <div class="vbdh-card">
        <h2>
            <span class="dashicons dashicons-lightbulb"></span>
            <?php _e('Mẹo SEO', 'vbdh'); ?>
        </h2>

        <div class="vbdh-grid">
            <div class="info-box">
                <h4><?php _e('On-Page SEO:', 'vbdh'); ?></h4>
                <ul>
                    <li>Tiêu đề 30-60 ký tự, có từ khóa chính</li>
                    <li>Meta description 120-160 ký tự</li>
                    <li>URL ngắn gọn, có từ khóa</li>
                    <li>Sử dụng heading tags (H1, H2, H3)</li>
                    <li>Thêm alt text cho hình ảnh</li>
                    <li>Nội dung tối thiểu 300 từ</li>
                    <li>Có ít nhất 2-3 liên kết nội bộ</li>
                </ul>
            </div>

            <div class="info-box">
                <h4><?php _e('Technical SEO:', 'vbdh'); ?></h4>
                <ul>
                    <li>Website tải nhanh (&lt; 3 giây)</li>
                    <li>Mobile-friendly, responsive design</li>
                    <li>Có SSL certificate (HTTPS)</li>
                    <li>Submit sitemap tới Google/Bing</li>
                    <li>Sử dụng Schema.org markup</li>
                    <li>Fix broken links và 404 errors</li>
                    <li>Optimize hình ảnh (WebP, lazy load)</li>
                </ul>
            </div>

            <div class="info-box">
                <h4><?php _e('Content Strategy:', 'vbdh'); ?></h4>
                <ul>
                    <li>Publish nội dung thường xuyên (daily/weekly)</li>
                    <li>Cập nhật bài viết cũ với dữ liệu mới</li>
                    <li>Dùng từ khóa long-tail</li>
                    <li>Trả lời câu hỏi của người dùng</li>
                    <li>Thêm FAQ section</li>
                    <li>Tạo content cluster</li>
                    <li>Analyze competitors</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
.sync-info-box a {
    word-break: break-all;
}

.widefat td ul {
    color: #646970;
}

.button .dashicons {
    line-height: inherit;
}
</style>
