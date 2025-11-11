<?php
/**
 * Banking Rates Widget
 * Widget hiển thị lãi suất/tỷ giá ngân hàng
 */

if (!defined('ABSPATH')) {
    exit;
}

class VBDH_Rates_Widget extends WP_Widget {

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct(
            'vbdh_rates_widget',
            __('Banking Rates Widget', 'vbdh'),
            array(
                'description' => __('Hiển thị lãi suất hoặc tỷ giá ngân hàng', 'vbdh'),
                'classname' => 'vbdh-rates-widget'
            )
        );
    }

    /**
     * Front-end display of widget
     */
    public function widget($args, $instance) {
        echo $args['before_widget'];

        // Widget title
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }

        // Get widget settings
        $display_type = isset($instance['display_type']) ? $instance['display_type'] : 'interest';
        $limit = isset($instance['limit']) ? intval($instance['limit']) : 5;
        $currency = isset($instance['currency']) ? sanitize_text_field($instance['currency']) : 'USD';

        // Generate cache key
        $cache_key = 'vbdh_widget_' . $display_type . '_' . $limit . '_' . $currency;
        $output = get_transient($cache_key);

        if (false === $output) {
            ob_start();

            if ($display_type === 'interest') {
                $this->render_interest_rates($limit);
            } elseif ($display_type === 'exchange') {
                $this->render_exchange_rates($currency, $limit);
            }

            $output = ob_get_clean();
            set_transient($cache_key, $output, HOUR_IN_SECONDS);
        }

        echo $output;

        echo $args['after_widget'];
    }

    /**
     * Render interest rates
     */
    private function render_interest_rates($limit) {
        // Get top interest rates (12 months term)
        $rates = VBDH_Interest_Rate::compare_banks('savings', 12);

        if (empty($rates)) {
            echo '<p class="vbdh-no-data">' . __('Chưa có dữ liệu', 'vbdh') . '</p>';
            return;
        }

        $rates = array_slice($rates, 0, $limit);

        ?>
        <div class="vbdh-widget-rates">
            <ul class="vbdh-widget-list">
                <?php foreach ($rates as $rate): ?>
                    <li class="vbdh-widget-item">
                        <div class="widget-bank-info">
                            <strong class="bank-name"><?php echo esc_html($rate->bank_name); ?></strong>
                            <span class="term-info"><?php echo esc_html($rate->term_months); ?> tháng</span>
                        </div>
                        <div class="widget-rate">
                            <span class="rate-value"><?php echo number_format($rate->interest_rate, 2); ?>%</span>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p class="widget-footer">
                <small><?php _e('Cập nhật:', 'vbdh'); ?> <?php echo current_time('H:i d/m'); ?></small>
            </p>
        </div>
        <style>
            .vbdh-widget-rates {
                font-size: 14px;
            }
            .vbdh-widget-list {
                list-style: none;
                margin: 0;
                padding: 0;
            }
            .vbdh-widget-item {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 10px 0;
                border-bottom: 1px solid #e5e5e5;
            }
            .vbdh-widget-item:last-child {
                border-bottom: none;
            }
            .widget-bank-info {
                flex: 1;
                display: flex;
                flex-direction: column;
            }
            .bank-name {
                font-size: 14px;
                color: #0073aa;
                margin-bottom: 3px;
            }
            .term-info {
                font-size: 12px;
                color: #646970;
            }
            .widget-rate {
                text-align: right;
            }
            .rate-value {
                font-size: 16px;
                font-weight: 700;
                color: #00a32a;
            }
            .widget-footer {
                margin: 10px 0 0;
                padding-top: 10px;
                border-top: 1px solid #e5e5e5;
                text-align: right;
            }
            .widget-footer small {
                color: #646970;
                font-size: 11px;
            }
            .vbdh-no-data {
                text-align: center;
                color: #646970;
                font-style: italic;
                padding: 20px 0;
            }
        </style>
        <?php
    }

    /**
     * Render exchange rates
     */
    private function render_exchange_rates($currency, $limit) {
        // Get latest exchange rates for currency
        $rates = VBDH_Exchange_Rate::get_latest($currency, $limit);

        if (empty($rates)) {
            echo '<p class="vbdh-no-data">' . __('Chưa có dữ liệu', 'vbdh') . '</p>';
            return;
        }

        ?>
        <div class="vbdh-widget-exchange">
            <div class="currency-header">
                <h4><?php echo esc_html($currency); ?></h4>
            </div>
            <ul class="vbdh-widget-list">
                <?php foreach ($rates as $rate): ?>
                    <li class="vbdh-widget-item">
                        <div class="widget-bank-info">
                            <strong class="bank-name"><?php echo esc_html($rate->bank_name); ?></strong>
                        </div>
                        <div class="widget-rates">
                            <div class="rate-row">
                                <span class="rate-label">Mua:</span>
                                <span class="rate-value buy"><?php echo number_format($rate->buy_rate); ?></span>
                            </div>
                            <div class="rate-row">
                                <span class="rate-label">Bán:</span>
                                <span class="rate-value sell"><?php echo number_format($rate->sell_rate); ?></span>
                            </div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p class="widget-footer">
                <small><?php _e('Cập nhật:', 'vbdh'); ?> <?php echo current_time('H:i d/m'); ?></small>
            </p>
        </div>
        <style>
            .vbdh-widget-exchange {
                font-size: 13px;
            }
            .currency-header {
                background: #0073aa;
                color: #fff;
                padding: 10px;
                margin: 0 -12px 10px;
                text-align: center;
            }
            .currency-header h4 {
                margin: 0;
                font-size: 18px;
                font-weight: 700;
            }
            .vbdh-widget-list {
                list-style: none;
                margin: 0;
                padding: 0;
            }
            .vbdh-widget-item {
                padding: 12px 0;
                border-bottom: 1px solid #e5e5e5;
            }
            .vbdh-widget-item:last-child {
                border-bottom: none;
            }
            .widget-bank-info {
                margin-bottom: 8px;
            }
            .bank-name {
                font-size: 14px;
                color: #0073aa;
            }
            .widget-rates {
                display: flex;
                justify-content: space-between;
                gap: 10px;
            }
            .rate-row {
                flex: 1;
                display: flex;
                flex-direction: column;
                align-items: center;
                background: #f9f9f9;
                padding: 5px;
                border-radius: 3px;
            }
            .rate-label {
                font-size: 11px;
                color: #646970;
                margin-bottom: 3px;
            }
            .rate-value {
                font-size: 13px;
                font-weight: 600;
            }
            .rate-value.buy {
                color: #00a32a;
            }
            .rate-value.sell {
                color: #d63638;
            }
            .widget-footer {
                margin: 10px 0 0;
                padding-top: 10px;
                border-top: 1px solid #e5e5e5;
                text-align: right;
            }
            .widget-footer small {
                color: #646970;
                font-size: 11px;
            }
        </style>
        <?php
    }

    /**
     * Back-end widget form
     */
    public function form($instance) {
        $title = isset($instance['title']) ? $instance['title'] : __('Lãi Suất Ngân Hàng', 'vbdh');
        $display_type = isset($instance['display_type']) ? $instance['display_type'] : 'interest';
        $limit = isset($instance['limit']) ? intval($instance['limit']) : 5;
        $currency = isset($instance['currency']) ? $instance['currency'] : 'USD';
        ?>

        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">
                <?php _e('Tiêu đề:', 'vbdh'); ?>
            </label>
            <input class="widefat"
                   id="<?php echo $this->get_field_id('title'); ?>"
                   name="<?php echo $this->get_field_name('title'); ?>"
                   type="text"
                   value="<?php echo esc_attr($title); ?>">
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('display_type'); ?>">
                <?php _e('Loại hiển thị:', 'vbdh'); ?>
            </label>
            <select class="widefat"
                    id="<?php echo $this->get_field_id('display_type'); ?>"
                    name="<?php echo $this->get_field_name('display_type'); ?>">
                <option value="interest" <?php selected($display_type, 'interest'); ?>>
                    <?php _e('Lãi suất tiết kiệm', 'vbdh'); ?>
                </option>
                <option value="exchange" <?php selected($display_type, 'exchange'); ?>>
                    <?php _e('Tỷ giá ngoại tệ', 'vbdh'); ?>
                </option>
            </select>
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('limit'); ?>">
                <?php _e('Số lượng hiển thị:', 'vbdh'); ?>
            </label>
            <input class="widefat"
                   id="<?php echo $this->get_field_id('limit'); ?>"
                   name="<?php echo $this->get_field_name('limit'); ?>"
                   type="number"
                   min="1"
                   max="10"
                   value="<?php echo esc_attr($limit); ?>">
            <small><?php _e('Từ 1-10 ngân hàng', 'vbdh'); ?></small>
        </p>

        <p id="<?php echo $this->get_field_id('currency_field'); ?>"
           style="<?php echo $display_type === 'exchange' ? '' : 'display:none;'; ?>">
            <label for="<?php echo $this->get_field_id('currency'); ?>">
                <?php _e('Loại tiền:', 'vbdh'); ?>
            </label>
            <select class="widefat"
                    id="<?php echo $this->get_field_id('currency'); ?>"
                    name="<?php echo $this->get_field_name('currency'); ?>">
                <option value="USD" <?php selected($currency, 'USD'); ?>>USD</option>
                <option value="EUR" <?php selected($currency, 'EUR'); ?>>EUR</option>
                <option value="GBP" <?php selected($currency, 'GBP'); ?>>GBP</option>
                <option value="JPY" <?php selected($currency, 'JPY'); ?>>JPY</option>
                <option value="CNY" <?php selected($currency, 'CNY'); ?>>CNY</option>
                <option value="AUD" <?php selected($currency, 'AUD'); ?>>AUD</option>
                <option value="THB" <?php selected($currency, 'THB'); ?>>THB</option>
                <option value="KRW" <?php selected($currency, 'KRW'); ?>>KRW</option>
            </select>
        </p>

        <script>
        jQuery(document).ready(function($) {
            $('#<?php echo $this->get_field_id('display_type'); ?>').on('change', function() {
                var currencyField = $('#<?php echo $this->get_field_id('currency_field'); ?>');
                if ($(this).val() === 'exchange') {
                    currencyField.show();
                } else {
                    currencyField.hide();
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Sanitize widget form values as they are saved
     */
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ?
            sanitize_text_field($new_instance['title']) : '';
        $instance['display_type'] = (!empty($new_instance['display_type'])) ?
            sanitize_text_field($new_instance['display_type']) : 'interest';
        $instance['limit'] = (!empty($new_instance['limit'])) ?
            intval($new_instance['limit']) : 5;
        $instance['currency'] = (!empty($new_instance['currency'])) ?
            sanitize_text_field($new_instance['currency']) : 'USD';

        // Clear widget cache
        $cache_keys = array(
            'vbdh_widget_interest_' . $instance['limit'],
            'vbdh_widget_exchange_' . $instance['limit'] . '_' . $instance['currency']
        );
        foreach ($cache_keys as $key) {
            delete_transient($key);
        }

        return $instance;
    }
}

/**
 * Register widget
 */
function vbdh_register_rates_widget() {
    register_widget('VBDH_Rates_Widget');
}
add_action('widgets_init', 'vbdh_register_rates_widget');
