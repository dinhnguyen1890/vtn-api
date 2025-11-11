<?php
/**
 * Admin Tab: Shortcodes & Widgets
 * Hướng dẫn sử dụng shortcodes và widgets
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="vbdh-tab-content">
    <div class="vbdh-card">
        <h2><?php _e('Shortcodes & Widgets - Hướng Dẫn Sử Dụng', 'vbdh'); ?></h2>
        <p><?php _e('Plugin cung cấp 3 shortcodes và 1 widget để hiển thị dữ liệu ngân hàng trên website của bạn.', 'vbdh'); ?></p>
    </div>

    <!-- Comparison Table Shortcode -->
    <div class="vbdh-card">
        <h3>
            <span class="dashicons dashicons-editor-table"></span>
            1. Bảng So Sánh Lãi Suất - <code>[vbdh_comparison_table]</code>
        </h3>

        <div class="shortcode-description">
            <p><?php _e('Hiển thị bảng so sánh lãi suất giữa các ngân hàng với khả năng sắp xếp.', 'vbdh'); ?></p>
        </div>

        <div class="shortcode-params">
            <h4><?php _e('Tham số:', 'vbdh'); ?></h4>
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php _e('Tham số', 'vbdh'); ?></th>
                        <th><?php _e('Mô tả', 'vbdh'); ?></th>
                        <th><?php _e('Giá trị mặc định', 'vbdh'); ?></th>
                        <th><?php _e('Ví dụ', 'vbdh'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>type</code></td>
                        <td>Loại lãi suất (savings/loan)</td>
                        <td>savings</td>
                        <td>type="savings"</td>
                    </tr>
                    <tr>
                        <td><code>term</code></td>
                        <td>Kỳ hạn (tháng)</td>
                        <td>12</td>
                        <td>term="6"</td>
                    </tr>
                    <tr>
                        <td><code>banks</code></td>
                        <td>Lọc ngân hàng (bank codes, phân cách bằng dấu phẩy)</td>
                        <td>(tất cả)</td>
                        <td>banks="BIDV,VPBank"</td>
                    </tr>
                    <tr>
                        <td><code>limit</code></td>
                        <td>Số lượng hiển thị tối đa</td>
                        <td>10</td>
                        <td>limit="5"</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="shortcode-examples">
            <h4><?php _e('Ví dụ sử dụng:', 'vbdh'); ?></h4>
            <div class="example-block">
                <strong><?php _e('Cơ bản:', 'vbdh'); ?></strong>
                <pre><code>[vbdh_comparison_table]</code></pre>
                <p class="description"><?php _e('Hiển thị bảng so sánh lãi suất tiết kiệm kỳ hạn 12 tháng của 10 ngân hàng.', 'vbdh'); ?></p>
            </div>

            <div class="example-block">
                <strong><?php _e('Tùy chỉnh kỳ hạn:', 'vbdh'); ?></strong>
                <pre><code>[vbdh_comparison_table term="6" limit="5"]</code></pre>
                <p class="description"><?php _e('Hiển thị top 5 ngân hàng có lãi suất cao nhất cho kỳ hạn 6 tháng.', 'vbdh'); ?></p>
            </div>

            <div class="example-block">
                <strong><?php _e('Lọc ngân hàng:', 'vbdh'); ?></strong>
                <pre><code>[vbdh_comparison_table banks="BIDV,VPBank,Techcombank"]</code></pre>
                <p class="description"><?php _e('So sánh lãi suất của 3 ngân hàng cụ thể.', 'vbdh'); ?></p>
            </div>
        </div>
    </div>

    <!-- Calculator Shortcode -->
    <div class="vbdh-card">
        <h3>
            <span class="dashicons dashicons-calculator"></span>
            2. Máy Tính Lãi Suất - <code>[vbdh_calculator]</code>
        </h3>

        <div class="shortcode-description">
            <p><?php _e('Hiển thị form tính toán lãi suất tương tác cho người dùng.', 'vbdh'); ?></p>
        </div>

        <div class="shortcode-params">
            <h4><?php _e('Tham số:', 'vbdh'); ?></h4>
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php _e('Tham số', 'vbdh'); ?></th>
                        <th><?php _e('Mô tả', 'vbdh'); ?></th>
                        <th><?php _e('Giá trị mặc định', 'vbdh'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>type</code></td>
                        <td>Loại lãi suất (savings/loan)</td>
                        <td>savings</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="shortcode-examples">
            <h4><?php _e('Ví dụ sử dụng:', 'vbdh'); ?></h4>
            <div class="example-block">
                <pre><code>[vbdh_calculator]</code></pre>
                <p class="description"><?php _e('Hiển thị máy tính lãi suất tiết kiệm với form nhập số tiền, chọn kỳ hạn và ngân hàng.', 'vbdh'); ?></p>
            </div>
        </div>

        <div class="info-box">
            <strong><?php _e('Lưu ý:', 'vbdh'); ?></strong>
            <p><?php _e('Calculator sử dụng AJAX để tính toán real-time. Đảm bảo JavaScript được bật trên trình duyệt.', 'vbdh'); ?></p>
        </div>
    </div>

    <!-- Exchange Rate Shortcode -->
    <div class="vbdh-card">
        <h3>
            <span class="dashicons dashicons-money-alt"></span>
            3. Tỷ Giá Ngoại Tệ - <code>[vbdh_exchange_rate]</code>
        </h3>

        <div class="shortcode-description">
            <p><?php _e('Hiển thị tỷ giá ngoại tệ của các ngân hàng với nhiều giao diện khác nhau.', 'vbdh'); ?></p>
        </div>

        <div class="shortcode-params">
            <h4><?php _e('Tham số:', 'vbdh'); ?></h4>
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php _e('Tham số', 'vbdh'); ?></th>
                        <th><?php _e('Mô tả', 'vbdh'); ?></th>
                        <th><?php _e('Giá trị mặc định', 'vbdh'); ?></th>
                        <th><?php _e('Ví dụ', 'vbdh'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>currency</code></td>
                        <td>Loại tiền tệ (USD, EUR, GBP, JPY, CNY, AUD, THB, KRW)</td>
                        <td>USD</td>
                        <td>currency="EUR"</td>
                    </tr>
                    <tr>
                        <td><code>banks</code></td>
                        <td>Lọc ngân hàng (bank codes, phân cách bằng dấu phẩy)</td>
                        <td>(tất cả)</td>
                        <td>banks="BIDV,VPBank"</td>
                    </tr>
                    <tr>
                        <td><code>display</code></td>
                        <td>Kiểu hiển thị (table/compact/list)</td>
                        <td>table</td>
                        <td>display="compact"</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="shortcode-examples">
            <h4><?php _e('Ví dụ sử dụng:', 'vbdh'); ?></h4>
            <div class="example-block">
                <strong><?php _e('Dạng bảng đầy đủ:', 'vbdh'); ?></strong>
                <pre><code>[vbdh_exchange_rate currency="USD"]</code></pre>
                <p class="description"><?php _e('Hiển thị bảng tỷ giá USD với cột mua vào, bán ra, chuyển khoản.', 'vbdh'); ?></p>
            </div>

            <div class="example-block">
                <strong><?php _e('Dạng compact (cards):', 'vbdh'); ?></strong>
                <pre><code>[vbdh_exchange_rate currency="EUR" display="compact"]</code></pre>
                <p class="description"><?php _e('Hiển thị tỷ giá EUR dưới dạng cards gọn gàng, phù hợp sidebar.', 'vbdh'); ?></p>
            </div>

            <div class="example-block">
                <strong><?php _e('Dạng danh sách:', 'vbdh'); ?></strong>
                <pre><code>[vbdh_exchange_rate currency="JPY" display="list"]</code></pre>
                <p class="description"><?php _e('Hiển thị tỷ giá JPY dưới dạng danh sách đơn giản.', 'vbdh'); ?></p>
            </div>
        </div>
    </div>

    <!-- WordPress Widget -->
    <div class="vbdh-card">
        <h3>
            <span class="dashicons dashicons-admin-generic"></span>
            4. WordPress Widget - Banking Rates Widget
        </h3>

        <div class="shortcode-description">
            <p><?php _e('Widget có thể thêm vào sidebar hoặc footer để hiển thị lãi suất/tỷ giá nhanh.', 'vbdh'); ?></p>
        </div>

        <div class="widget-instructions">
            <h4><?php _e('Cách sử dụng:', 'vbdh'); ?></h4>
            <ol>
                <li><?php _e('Vào <strong>Giao diện > Widgets</strong>', 'vbdh'); ?></li>
                <li><?php _e('Tìm widget <strong>"Banking Rates Widget"</strong>', 'vbdh'); ?></li>
                <li><?php _e('Kéo vào vùng widget mong muốn (Sidebar, Footer...)', 'vbdh'); ?></li>
                <li><?php _e('Cấu hình các tùy chọn:', 'vbdh'); ?>
                    <ul>
                        <li><strong><?php _e('Tiêu đề:', 'vbdh'); ?></strong> <?php _e('Tên hiển thị của widget', 'vbdh'); ?></li>
                        <li><strong><?php _e('Loại hiển thị:', 'vbdh'); ?></strong> <?php _e('Lãi suất hoặc Tỷ giá', 'vbdh'); ?></li>
                        <li><strong><?php _e('Số lượng:', 'vbdh'); ?></strong> <?php _e('Từ 1-10 ngân hàng', 'vbdh'); ?></li>
                        <li><strong><?php _e('Loại tiền:', 'vbdh'); ?></strong> <?php _e('Chỉ hiện khi chọn Tỷ giá', 'vbdh'); ?></li>
                    </ul>
                </li>
                <li><?php _e('Lưu widget', 'vbdh'); ?></li>
            </ol>
        </div>

        <div class="info-box">
            <strong><?php _e('Tính năng:', 'vbdh'); ?></strong>
            <ul>
                <li><?php _e('Tự động cập nhật dữ liệu theo lịch đồng bộ', 'vbdh'); ?></li>
                <li><?php _e('Cache 1 giờ để tối ưu performance', 'vbdh'); ?></li>
                <li><?php _e('Responsive, hiển thị tốt trên mọi thiết bị', 'vbdh'); ?></li>
                <li><?php _e('Inline CSS để không ảnh hưởng theme', 'vbdh'); ?></li>
            </ul>
        </div>
    </div>

    <!-- Performance & Cache -->
    <div class="vbdh-card">
        <h3>
            <span class="dashicons dashicons-performance"></span>
            <?php _e('Performance & Cache', 'vbdh'); ?>
        </h3>

        <div class="info-box">
            <h4><?php _e('Tối ưu hóa hiệu suất:', 'vbdh'); ?></h4>
            <ul>
                <li><strong><?php _e('Transient Cache:', 'vbdh'); ?></strong> <?php _e('Tất cả shortcodes được cache 1 giờ', 'vbdh'); ?></li>
                <li><strong><?php _e('Lazy Loading:', 'vbdh'); ?></strong> <?php _e('CSS/JS chỉ load khi có shortcode trên trang', 'vbdh'); ?></li>
                <li><strong><?php _e('Database Index:', 'vbdh'); ?></strong> <?php _e('Các bảng có index để query nhanh', 'vbdh'); ?></li>
                <li><strong><?php _e('Minified Assets:', 'vbdh'); ?></strong> <?php _e('CSS/JS được tối ưu kích thước', 'vbdh'); ?></li>
            </ul>

            <p>
                <strong><?php _e('Xóa cache thủ công:', 'vbdh'); ?></strong><br>
                <?php _e('Cache sẽ tự động xóa sau mỗi lần đồng bộ dữ liệu. Để xóa thủ công, sử dụng:', 'vbdh'); ?>
            </p>
            <pre><code>delete_transient('vbdh_comparison_*');
delete_transient('vbdh_widget_*');</code></pre>
        </div>
    </div>

    <!-- Code Examples -->
    <div class="vbdh-card">
        <h3>
            <span class="dashicons dashicons-media-code"></span>
            <?php _e('Sử Dụng Trong Code PHP', 'vbdh'); ?>
        </h3>

        <div class="code-examples">
            <p><?php _e('Bạn cũng có thể gọi shortcode trực tiếp trong theme PHP:', 'vbdh'); ?></p>

            <div class="example-block">
                <strong><?php _e('Single shortcode:', 'vbdh'); ?></strong>
                <pre><code>&lt;?php echo do_shortcode('[vbdh_comparison_table term="12"]'); ?&gt;</code></pre>
            </div>

            <div class="example-block">
                <strong><?php _e('Conditional display:', 'vbdh'); ?></strong>
                <pre><code>&lt;?php
if (is_page('lai-suat')) {
    echo do_shortcode('[vbdh_comparison_table]');
    echo do_shortcode('[vbdh_calculator]');
}
?&gt;</code></pre>
            </div>

            <div class="example-block">
                <strong><?php _e('Template part:', 'vbdh'); ?></strong>
                <pre><code>&lt;?php
// In sidebar.php or footer.php
echo do_shortcode('[vbdh_exchange_rate currency="USD" display="compact"]');
?&gt;</code></pre>
            </div>
        </div>
    </div>

    <!-- Support & Resources -->
    <div class="vbdh-card">
        <h3>
            <span class="dashicons dashicons-sos"></span>
            <?php _e('Hỗ Trợ & Tài Nguyên', 'vbdh'); ?>
        </h3>

        <div class="support-info">
            <p><strong><?php _e('Gặp vấn đề?', 'vbdh'); ?></strong></p>
            <ul>
                <li><?php _e('Kiểm tra log tại tab "API Logs" để xem lỗi chi tiết', 'vbdh'); ?></li>
                <li><?php _e('Đảm bảo đã đồng bộ dữ liệu từ ngân hàng', 'vbdh'); ?></li>
                <li><?php _e('Clear cache WordPress nếu không thấy cập nhật', 'vbdh'); ?></li>
                <li><?php _e('Kiểm tra console browser để debug JavaScript', 'vbdh'); ?></li>
            </ul>

            <p>
                <a href="?page=vbdh-banking-data&tab=banks" class="button button-primary">
                    <?php _e('Quản lý Ngân hàng', 'vbdh'); ?>
                </a>
                <a href="?page=vbdh-banking-data&tab=sync" class="button">
                    <?php _e('Đồng bộ Dữ liệu', 'vbdh'); ?>
                </a>
                <a href="?page=vbdh-banking-data&tab=logs" class="button">
                    <?php _e('Xem Logs', 'vbdh'); ?>
                </a>
            </p>
        </div>
    </div>
</div>

<style>
.shortcode-params table,
.shortcode-params th,
.shortcode-params td {
    border: 1px solid #dcdcde;
}

.shortcode-params th {
    background: #f0f0f1;
    font-weight: 600;
    padding: 10px;
}

.shortcode-params td {
    padding: 10px;
}

.shortcode-params code {
    background: #f0f0f1;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 13px;
}

.shortcode-examples,
.code-examples {
    margin-top: 20px;
}

.example-block {
    margin: 15px 0;
    padding: 15px;
    background: #f9f9f9;
    border-left: 4px solid #0073aa;
    border-radius: 4px;
}

.example-block strong {
    display: block;
    margin-bottom: 8px;
    color: #0073aa;
}

.example-block pre {
    background: #1d2327;
    color: #f0f0f1;
    padding: 12px;
    border-radius: 4px;
    overflow-x: auto;
    margin: 10px 0;
}

.example-block code {
    font-family: 'Courier New', Courier, monospace;
    font-size: 13px;
}

.example-block .description {
    margin: 10px 0 0;
    color: #646970;
    font-style: italic;
}

.widget-instructions ol {
    margin-left: 20px;
}

.widget-instructions li {
    margin-bottom: 10px;
}

.widget-instructions ul {
    margin-left: 30px;
    margin-top: 10px;
}

.support-info ul {
    list-style: disc;
    margin-left: 20px;
}

.support-info li {
    margin-bottom: 8px;
}

.support-info .button {
    margin-right: 10px;
}
</style>
