<?php
/**
 * Shortcodes Handler
 * Đăng ký và xử lý các shortcodes của plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

class VBDH_Shortcodes {

    /**
     * Constructor - Register all shortcodes
     */
    public function __construct() {
        add_shortcode('vbdh_comparison_table', array($this, 'comparison_table_shortcode'));
        add_shortcode('vbdh_calculator', array($this, 'calculator_shortcode'));
        add_shortcode('vbdh_exchange_rate', array($this, 'exchange_rate_shortcode'));

        // Enqueue frontend styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
    }

    /**
     * Enqueue frontend CSS/JS
     */
    public function enqueue_frontend_assets() {
        // Only load if shortcode is present
        if (has_shortcode(get_the_content(), 'vbdh_') || is_active_widget(false, false, 'vbdh_rates_widget')) {
            wp_enqueue_style('vbdh-frontend', VBDH_PLUGIN_URL . 'assets/css/frontend.css', array(), VBDH_VERSION);
            wp_enqueue_script('vbdh-frontend', VBDH_PLUGIN_URL . 'assets/js/frontend.js', array(), VBDH_VERSION, true);

            wp_localize_script('vbdh-frontend', 'vbdhFrontend', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('vbdh_frontend_nonce')
            ));
        }
    }

    /**
     * Shortcode: Comparison Table
     * Usage: [vbdh_comparison_table type="savings" term="12" limit="10"]
     */
    public function comparison_table_shortcode($atts) {
        $atts = shortcode_atts(array(
            'type' => 'savings',
            'term' => '12',
            'banks' => '',
            'limit' => '10'
        ), $atts, 'vbdh_comparison_table');

        // Get data from cache or database
        $cache_key = 'vbdh_comparison_' . md5(serialize($atts));
        $html = get_transient($cache_key);

        if (false === $html) {
            $html = $this->render_comparison_table($atts);
            set_transient($cache_key, $html, HOUR_IN_SECONDS);
        }

        return $html;
    }

    /**
     * Render comparison table
     */
    private function render_comparison_table($atts) {
        $type = sanitize_text_field($atts['type']);
        $term = intval($atts['term']);
        $limit = intval($atts['limit']);
        $banks = !empty($atts['banks']) ? array_map('trim', explode(',', $atts['banks'])) : array();

        // Fetch rates
        $rates = VBDH_Interest_Rate::compare_banks($type, $term);

        // Filter by banks if specified
        if (!empty($banks)) {
            $rates = array_filter($rates, function($rate) use ($banks) {
                return in_array($rate->bank_code, $banks);
            });
        }

        // Limit results
        $rates = array_slice($rates, 0, $limit);

        if (empty($rates)) {
            return '<p class="vbdh-no-data">' . __('Không có dữ liệu lãi suất.', 'vbdh') . '</p>';
        }

        // Build table HTML
        ob_start();
        ?>
        <div class="vbdh-shortcode-wrapper vbdh-comparison-wrapper">
            <table class="vbdh-comparison-shortcode-table">
                <thead>
                    <tr>
                        <th class="sortable" data-sort="rank">#</th>
                        <th class="sortable" data-sort="bank"><?php _e('Ngân hàng', 'vbdh'); ?></th>
                        <th class="sortable" data-sort="product"><?php _e('Sản phẩm', 'vbdh'); ?></th>
                        <th class="sortable" data-sort="term"><?php _e('Kỳ hạn', 'vbdh'); ?></th>
                        <th class="sortable" data-sort="rate"><?php _e('Lãi suất', 'vbdh'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $rank = 1;
                    foreach ($rates as $rate):
                        $is_highest = ($rank === 1);
                    ?>
                        <tr <?php echo $is_highest ? 'class="highest-rate"' : ''; ?>>
                            <td><?php echo $rank; ?></td>
                            <td>
                                <strong><?php echo esc_html($rate->bank_name); ?></strong>
                                <?php if ($rate->bank_logo_url): ?>
                                    <br><img src="<?php echo esc_url($rate->bank_logo_url); ?>" alt="" style="max-height:20px;">
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($rate->product_name); ?></td>
                            <td><?php echo esc_html($rate->term_months); ?> <?php _e('tháng', 'vbdh'); ?></td>
                            <td class="rate-value <?php echo $is_highest ? 'highlight' : ''; ?>">
                                <?php echo number_format($rate->interest_rate, 2); ?>%
                            </td>
                        </tr>
                    <?php
                    $rank++;
                    endforeach;
                    ?>
                </tbody>
            </table>
            <p class="vbdh-table-footer">
                <small><?php _e('Cập nhật:', 'vbdh'); ?> <?php echo current_time('d/m/Y H:i'); ?></small>
            </p>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Interest Rate Calculator
     * Usage: [vbdh_calculator type="savings"]
     */
    public function calculator_shortcode($atts) {
        $atts = shortcode_atts(array(
            'type' => 'savings'
        ), $atts, 'vbdh_calculator');

        $type = sanitize_text_field($atts['type']);

        // Get active banks
        $banks = VBDH_Bank::get_all(true);

        ob_start();
        ?>
        <div class="vbdh-shortcode-wrapper vbdh-calculator-wrapper">
            <h3><?php _e('Máy Tính Lãi Suất', 'vbdh'); ?></h3>

            <form class="vbdh-calculator-form" id="vbdh-calculator-form">
                <div class="calc-row">
                    <label><?php _e('Số tiền gửi (VNĐ):', 'vbdh'); ?></label>
                    <input type="number"
                           id="calc-amount"
                           class="calc-input"
                           min="1000000"
                           step="1000000"
                           value="10000000"
                           required>
                </div>

                <div class="calc-row">
                    <label><?php _e('Kỳ hạn:', 'vbdh'); ?></label>
                    <select id="calc-term" class="calc-input">
                        <option value="1">1 <?php _e('tháng', 'vbdh'); ?></option>
                        <option value="3">3 <?php _e('tháng', 'vbdh'); ?></option>
                        <option value="6">6 <?php _e('tháng', 'vbdh'); ?></option>
                        <option value="12" selected>12 <?php _e('tháng', 'vbdh'); ?></option>
                        <option value="24">24 <?php _e('tháng', 'vbdh'); ?></option>
                    </select>
                </div>

                <div class="calc-row">
                    <label><?php _e('Ngân hàng:', 'vbdh'); ?></label>
                    <select id="calc-bank" class="calc-input">
                        <option value=""><?php _e('-- Chọn ngân hàng --', 'vbdh'); ?></option>
                        <?php foreach ($banks as $bank): ?>
                            <option value="<?php echo esc_attr($bank->id); ?>">
                                <?php echo esc_html($bank->bank_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="calc-row">
                    <button type="button" id="calc-submit" class="calc-button">
                        <?php _e('Tính Lãi', 'vbdh'); ?>
                    </button>
                </div>
            </form>

            <div id="calc-result" class="calc-result" style="display:none;">
                <h4><?php _e('Kết quả:', 'vbdh'); ?></h4>
                <div class="result-item">
                    <span><?php _e('Lãi suất:', 'vbdh'); ?></span>
                    <strong id="result-rate"></strong>
                </div>
                <div class="result-item">
                    <span><?php _e('Tiền lãi đơn:', 'vbdh'); ?></span>
                    <strong id="result-interest"></strong>
                </div>
                <div class="result-item">
                    <span><?php _e('Tổng tiền nhận:', 'vbdh'); ?></span>
                    <strong id="result-total" class="highlight"></strong>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Exchange Rate Widget
     * Usage: [vbdh_exchange_rate currency="USD" banks="BIDV,VPBank"]
     */
    public function exchange_rate_shortcode($atts) {
        $atts = shortcode_atts(array(
            'currency' => 'USD',
            'banks' => '',
            'display' => 'table' // table, compact, list
        ), $atts, 'vbdh_exchange_rate');

        $currency = sanitize_text_field($atts['currency']);
        $display = sanitize_text_field($atts['display']);
        $banks = !empty($atts['banks']) ? array_map('trim', explode(',', $atts['banks'])) : array();

        // Get latest exchange rates
        $rates = VBDH_Exchange_Rate::get_latest($currency, 20);

        // Filter by banks if specified
        if (!empty($banks)) {
            $rates = array_filter($rates, function($rate) use ($banks) {
                return in_array($rate->bank_code, $banks);
            });
        }

        if (empty($rates)) {
            return '<p class="vbdh-no-data">' . __('Không có dữ liệu tỷ giá.', 'vbdh') . '</p>';
        }

        ob_start();
        ?>
        <div class="vbdh-shortcode-wrapper vbdh-exchange-wrapper">
            <h3><?php echo esc_html($currency); ?> - <?php _e('Tỷ Giá Hôm Nay', 'vbdh'); ?></h3>

            <?php if ($display === 'table'): ?>
                <table class="vbdh-exchange-shortcode-table">
                    <thead>
                        <tr>
                            <th><?php _e('Ngân hàng', 'vbdh'); ?></th>
                            <th><?php _e('Mua vào', 'vbdh'); ?></th>
                            <th><?php _e('Bán ra', 'vbdh'); ?></th>
                            <th><?php _e('Chuyển khoản', 'vbdh'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rates as $rate): ?>
                            <tr>
                                <td><strong><?php echo esc_html($rate->bank_name); ?></strong></td>
                                <td><?php echo number_format($rate->buy_rate); ?></td>
                                <td><?php echo number_format($rate->sell_rate); ?></td>
                                <td><?php echo number_format($rate->transfer_rate); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php elseif ($display === 'compact'): ?>
                <div class="vbdh-exchange-compact">
                    <?php foreach ($rates as $rate): ?>
                        <div class="exchange-item">
                            <div class="bank-name"><?php echo esc_html($rate->bank_name); ?></div>
                            <div class="rates">
                                <span class="buy"><?php _e('Mua:', 'vbdh'); ?> <?php echo number_format($rate->buy_rate); ?></span>
                                <span class="sell"><?php _e('Bán:', 'vbdh'); ?> <?php echo number_format($rate->sell_rate); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: // list ?>
                <ul class="vbdh-exchange-list">
                    <?php foreach ($rates as $rate): ?>
                        <li>
                            <strong><?php echo esc_html($rate->bank_name); ?>:</strong>
                            Mua <?php echo number_format($rate->buy_rate); ?> -
                            Bán <?php echo number_format($rate->sell_rate); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <p class="vbdh-table-footer">
                <small><?php _e('Cập nhật:', 'vbdh'); ?> <?php echo current_time('d/m/Y H:i'); ?></small>
            </p>
        </div>
        <?php
        return ob_get_clean();
    }
}

// Initialize shortcodes
new VBDH_Shortcodes();
