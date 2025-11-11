<?php
/**
 * Content Generator Class
 * Tự động tạo nội dung từ dữ liệu ngân hàng
 */

if (!defined('ABSPATH')) {
    exit;
}

class VBDH_Content_Generator {

    /**
     * Generate daily rates post
     */
    public function generate_daily_rates_post() {
        // Check if already generated today
        if ($this->has_daily_post_today()) {
            return array(
                'success' => false,
                'message' => 'Đã có bài viết daily rates hôm nay'
            );
        }

        $date = current_time('d/m/Y');
        $title = "Lãi Suất Ngân Hàng Hôm Nay {$date} - Cập Nhật Mới Nhất";

        $content = $this->build_daily_content();

        $post_id = $this->create_post($title, $content, array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'post_category' => $this->get_or_create_categories(array('Lãi suất', 'Tin tức')),
            'tags_input' => $this->generate_tags('daily')
        ));

        if ($post_id) {
            update_post_meta($post_id, '_vbdh_content_type', 'daily_rates');
            update_post_meta($post_id, '_vbdh_generated_date', current_time('Y-m-d'));

            return array(
                'success' => true,
                'post_id' => $post_id,
                'message' => 'Tạo bài viết daily rates thành công'
            );
        }

        return array(
            'success' => false,
            'message' => 'Lỗi khi tạo bài viết'
        );
    }

    /**
     * Generate comparison post
     */
    public function generate_comparison_post($bank_id_1, $bank_id_2) {
        $bank1 = VBDH_Bank::get_by_id($bank_id_1);
        $bank2 = VBDH_Bank::get_by_id($bank_id_2);

        if (!$bank1 || !$bank2) {
            return array(
                'success' => false,
                'message' => 'Ngân hàng không tồn tại'
            );
        }

        $title = "So Sánh Lãi Suất {$bank1->bank_name} vs {$bank2->bank_name} " . current_time('Y');

        // Check duplicate
        if ($this->post_exists($title)) {
            return array(
                'success' => false,
                'message' => 'Bài viết so sánh này đã tồn tại'
            );
        }

        $content = $this->build_comparison_content($bank1, $bank2);

        $post_id = $this->create_post($title, $content, array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'post_category' => $this->get_or_create_categories(array('So sánh lãi suất')),
            'tags_input' => $this->generate_tags('comparison', array($bank1->bank_name, $bank2->bank_name))
        ));

        if ($post_id) {
            update_post_meta($post_id, '_vbdh_content_type', 'comparison');
            update_post_meta($post_id, '_vbdh_bank_ids', array($bank_id_1, $bank_id_2));

            return array(
                'success' => true,
                'post_id' => $post_id,
                'message' => 'Tạo bài viết so sánh thành công'
            );
        }

        return array(
            'success' => false,
            'message' => 'Lỗi khi tạo bài viết'
        );
    }

    /**
     * Generate ranking post
     */
    public function generate_ranking_post($rate_type = 'savings', $term_months = 12) {
        $title = "Top 10 Ngân Hàng Lãi Suất " . ucfirst($rate_type) . " Cao Nhất {$term_months} Tháng";

        // Check duplicate for this month
        $month_key = current_time('Y-m');
        $existing = get_posts(array(
            'post_type' => 'post',
            'meta_query' => array(
                array('key' => '_vbdh_content_type', 'value' => 'ranking'),
                array('key' => '_vbdh_ranking_month', 'value' => $month_key)
            )
        ));

        if (!empty($existing)) {
            return array(
                'success' => false,
                'message' => 'Đã có bài ranking tháng này'
            );
        }

        $content = $this->build_ranking_content($rate_type, $term_months);

        $post_id = $this->create_post($title, $content, array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'post_category' => $this->get_or_create_categories(array('Xếp hạng', 'Lãi suất')),
            'tags_input' => $this->generate_tags('ranking')
        ));

        if ($post_id) {
            update_post_meta($post_id, '_vbdh_content_type', 'ranking');
            update_post_meta($post_id, '_vbdh_ranking_month', $month_key);
            update_post_meta($post_id, '_vbdh_rate_type', $rate_type);

            return array(
                'success' => true,
                'post_id' => $post_id,
                'message' => 'Tạo bài viết ranking thành công'
            );
        }

        return array(
            'success' => false,
            'message' => 'Lỗi khi tạo bài viết'
        );
    }

    /**
     * Build daily content
     */
    private function build_daily_content() {
        $date = current_time('d/m/Y');

        // Get top rates
        $top_rates = $this->get_top_rates(10);
        $exchange_rates = VBDH_Exchange_Rate::get_latest(null, 10);

        $content = "<h2>Tổng quan lãi suất ngân hàng hôm nay {$date}</h2>\n\n";
        $content .= "<p>Cập nhật mới nhất về lãi suất tiết kiệm, vay và tỷ giá ngoại tệ từ các ngân hàng lớn tại Việt Nam.</p>\n\n";

        // Top 10 table
        $content .= "<h3>Top 10 Lãi Suất Tiết Kiệm Cao Nhất</h3>\n\n";
        $content .= $this->build_rates_table($top_rates);

        // Exchange rates
        $content .= "<h3>Tỷ Giá Ngoại Tệ Hôm Nay</h3>\n\n";
        $content .= $this->build_exchange_table($exchange_rates);

        // Conclusion
        $content .= "<h3>Kết luận</h3>\n\n";
        $content .= "<p>Trên đây là thông tin lãi suất và tỷ giá mới nhất. ";
        $content .= "Hãy so sánh kỹ để chọn được ngân hàng phù hợp nhất với nhu cầu của bạn.</p>\n\n";

        return $content;
    }

    /**
     * Build comparison content
     */
    private function build_comparison_content($bank1, $bank2) {
        $content = "<h2>So sánh {$bank1->bank_name} và {$bank2->bank_name}</h2>\n\n";

        $content .= "<p>Phân tích chi tiết lãi suất tiết kiệm, vay và dịch vụ của hai ngân hàng.</p>\n\n";

        // Savings rates comparison
        $rates1 = VBDH_Interest_Rate::get_by_bank($bank1->id, 'savings');
        $rates2 = VBDH_Interest_Rate::get_by_bank($bank2->id, 'savings');

        $content .= "<h3>So Sánh Lãi Suất Tiết Kiệm</h3>\n\n";
        $content .= $this->build_comparison_table($rates1, $rates2, $bank1->bank_name, $bank2->bank_name);

        // Pros/Cons
        $content .= "<h3>Ưu nhược điểm</h3>\n\n";
        $content .= "<h4>{$bank1->bank_name}</h4>\n";
        $content .= "<p><strong>Ưu điểm:</strong> Lãi suất cạnh tranh, mạng lưới rộng khắp.</p>\n";
        $content .= "<p><strong>Nhược điểm:</strong> Thủ tục có thể phức tạp hơn.</p>\n\n";

        $content .= "<h4>{$bank2->bank_name}</h4>\n";
        $content .= "<p><strong>Ưu điểm:</strong> Dịch vụ online tiện lợi, hỗ trợ nhanh.</p>\n";
        $content .= "<p><strong>Nhược điểm:</strong> Chi nhánh ít hơn.</p>\n\n";

        return $content;
    }

    /**
     * Build ranking content
     */
    private function build_ranking_content($rate_type, $term_months) {
        $comparison = VBDH_Interest_Rate::compare_banks($rate_type, $term_months);

        $content = "<h2>Top 10 Ngân Hàng Lãi Suất Cao Nhất</h2>\n\n";
        $content .= "<p>Xếp hạng các ngân hàng có lãi suất {$rate_type} kỳ hạn {$term_months} tháng tốt nhất.</p>\n\n";

        $content .= "<ol class='vbdh-ranking-list'>\n";

        $rank = 1;
        foreach (array_slice($comparison, 0, 10) as $rate) {
            $content .= "<li>\n";
            $content .= "<h3>#{$rank} - {$rate->bank_name}</h3>\n";
            $content .= "<p><strong>Lãi suất:</strong> {$rate->interest_rate}%/năm</p>\n";
            $content .= "<p><strong>Kỳ hạn:</strong> {$rate->term_months} tháng</p>\n";
            $content .= "<p><strong>Sản phẩm:</strong> {$rate->product_name}</p>\n";
            if ($rate->special_conditions) {
                $content .= "<p><em>{$rate->special_conditions}</em></p>\n";
            }
            $content .= "</li>\n\n";
            $rank++;
        }

        $content .= "</ol>\n\n";

        return $content;
    }

    /**
     * Build rates table
     */
    private function build_rates_table($rates) {
        $table = "<table class='vbdh-rates-table'>\n";
        $table .= "<thead><tr>";
        $table .= "<th>Ngân hàng</th>";
        $table .= "<th>Sản phẩm</th>";
        $table .= "<th>Kỳ hạn</th>";
        $table .= "<th>Lãi suất</th>";
        $table .= "</tr></thead>\n<tbody>\n";

        foreach ($rates as $rate) {
            $table .= "<tr>";
            $table .= "<td><strong>{$rate->bank_name}</strong></td>";
            $table .= "<td>{$rate->product_name}</td>";
            $table .= "<td>{$rate->term_months} tháng</td>";
            $table .= "<td class='rate-highlight'>{$rate->interest_rate}%</td>";
            $table .= "</tr>\n";
        }

        $table .= "</tbody></table>\n\n";
        return $table;
    }

    /**
     * Build exchange rates table
     */
    private function build_exchange_table($rates) {
        $table = "<table class='vbdh-exchange-table'>\n";
        $table .= "<thead><tr>";
        $table .= "<th>Ngân hàng</th>";
        $table .= "<th>Ngoại tệ</th>";
        $table .= "<th>Mua</th>";
        $table .= "<th>Bán</th>";
        $table .= "<th>Chuyển khoản</th>";
        $table .= "</tr></thead>\n<tbody>\n";

        foreach ($rates as $rate) {
            $table .= "<tr>";
            $table .= "<td><strong>{$rate->bank_name}</strong></td>";
            $table .= "<td>{$rate->currency_code}</td>";
            $table .= "<td>" . number_format($rate->buy_rate) . "</td>";
            $table .= "<td>" . number_format($rate->sell_rate) . "</td>";
            $table .= "<td>" . number_format($rate->transfer_rate) . "</td>";
            $table .= "</tr>\n";
        }

        $table .= "</tbody></table>\n\n";
        return $table;
    }

    /**
     * Build comparison table
     */
    private function build_comparison_table($rates1, $rates2, $bank1_name, $bank2_name) {
        $table = "<table class='vbdh-comparison-table'>\n";
        $table .= "<thead><tr>";
        $table .= "<th>Kỳ hạn</th>";
        $table .= "<th>{$bank1_name}</th>";
        $table .= "<th>{$bank2_name}</th>";
        $table .= "<th>Chênh lệch</th>";
        $table .= "</tr></thead>\n<tbody>\n";

        // Group by term
        $terms = array(1, 3, 6, 12, 24);
        foreach ($terms as $term) {
            $rate1 = $this->find_rate_by_term($rates1, $term);
            $rate2 = $this->find_rate_by_term($rates2, $term);

            if ($rate1 || $rate2) {
                $val1 = $rate1 ? $rate1->interest_rate : 0;
                $val2 = $rate2 ? $rate2->interest_rate : 0;
                $diff = $val1 - $val2;

                $table .= "<tr>";
                $table .= "<td>{$term} tháng</td>";
                $table .= "<td>" . ($val1 > 0 ? "{$val1}%" : "-") . "</td>";
                $table .= "<td>" . ($val2 > 0 ? "{$val2}%" : "-") . "</td>";
                $table .= "<td class='" . ($diff > 0 ? 'positive' : ($diff < 0 ? 'negative' : '')) . "'>";
                $table .= $diff != 0 ? sprintf("%+.2f%%", $diff) : "-";
                $table .= "</td>";
                $table .= "</tr>\n";
            }
        }

        $table .= "</tbody></table>\n\n";
        return $table;
    }

    /**
     * Helper: Find rate by term
     */
    private function find_rate_by_term($rates, $term) {
        foreach ($rates as $rate) {
            if ($rate->term_months == $term) {
                return $rate;
            }
        }
        return null;
    }

    /**
     * Get top rates
     */
    private function get_top_rates($limit = 10) {
        return VBDH_Interest_Rate::compare_banks('savings', null);
    }

    /**
     * Create post
     */
    private function create_post($title, $content, $args = array()) {
        $defaults = array(
            'post_title' => $title,
            'post_content' => $content,
            'post_status' => 'publish',
            'post_author' => get_current_user_id(),
            'post_type' => 'post'
        );

        $post_data = wp_parse_args($args, $defaults);

        return wp_insert_post($post_data);
    }

    /**
     * Get or create categories
     */
    private function get_or_create_categories($category_names) {
        $category_ids = array();

        foreach ($category_names as $name) {
            $term = get_term_by('name', $name, 'category');

            if (!$term) {
                $result = wp_insert_term($name, 'category');
                if (!is_wp_error($result)) {
                    $category_ids[] = $result['term_id'];
                }
            } else {
                $category_ids[] = $term->term_id;
            }
        }

        return $category_ids;
    }

    /**
     * Generate tags
     */
    private function generate_tags($type, $extra = array()) {
        $tags = array('lãi suất', 'ngân hàng', 'tiết kiệm');

        if ($type === 'daily') {
            $tags[] = 'cập nhật hôm nay';
            $tags[] = 'tỷ giá';
        } elseif ($type === 'comparison') {
            $tags[] = 'so sánh';
            $tags = array_merge($tags, $extra);
        } elseif ($type === 'ranking') {
            $tags[] = 'xếp hạng';
            $tags[] = 'top 10';
        }

        return implode(',', $tags);
    }

    /**
     * Check if daily post exists today
     */
    private function has_daily_post_today() {
        $today = current_time('Y-m-d');

        $posts = get_posts(array(
            'post_type' => 'post',
            'meta_query' => array(
                array('key' => '_vbdh_content_type', 'value' => 'daily_rates'),
                array('key' => '_vbdh_generated_date', 'value' => $today)
            )
        ));

        return !empty($posts);
    }

    /**
     * Check if post exists by title
     */
    private function post_exists($title) {
        $existing = get_page_by_title($title, OBJECT, 'post');
        return $existing !== null;
    }
}
