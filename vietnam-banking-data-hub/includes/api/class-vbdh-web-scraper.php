<?php
/**
 * Web Scraper for Vietnamese Banks
 * Scrape interest rates and exchange rates from bank websites
 */

if (!defined('ABSPATH')) {
    exit;
}

class VBDH_Web_Scraper {

    /**
     * User agents for rotation (avoid blocking)
     */
    private static $user_agents = array(
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.1 Safari/605.1.15'
    );

    /**
     * Last request timestamp (for rate limiting)
     */
    private static $last_request_time = 0;

    /**
     * Rate limit delay (seconds)
     */
    const RATE_LIMIT_DELAY = 1;

    /**
     * Cache duration (6 hours)
     */
    const CACHE_DURATION = 6 * HOUR_IN_SECONDS;

    /**
     * Get random User-Agent
     */
    private static function get_user_agent() {
        return self::$user_agents[array_rand(self::$user_agents)];
    }

    /**
     * Rate limiting - Wait if needed
     */
    private static function rate_limit() {
        $time_since_last = microtime(true) - self::$last_request_time;
        if ($time_since_last < self::RATE_LIMIT_DELAY) {
            $sleep_time = (self::RATE_LIMIT_DELAY - $time_since_last) * 1000000;
            usleep((int)$sleep_time);
        }
        self::$last_request_time = microtime(true);
    }

    /**
     * Fetch HTML content from URL
     */
    private static function fetch_html($url) {
        // Rate limiting
        self::rate_limit();

        // Check cache
        $cache_key = 'vbdh_scrape_' . md5($url);
        $cached = get_transient($cache_key);
        if (false !== $cached) {
            return $cached;
        }

        // Fetch with WordPress HTTP API
        $response = wp_remote_get($url, array(
            'timeout' => 15,
            'user-agent' => self::get_user_agent(),
            'headers' => array(
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'vi-VN,vi;q=0.9,en-US;q=0.8,en;q=0.7'
            )
        ));

        if (is_wp_error($response)) {
            error_log('VBDH Scraper Error: ' . $response->get_error_message());
            return false;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            error_log("VBDH Scraper Error: HTTP $status_code for $url");
            return false;
        }

        $html = wp_remote_retrieve_body($response);

        // Cache for 6 hours
        set_transient($cache_key, $html, self::CACHE_DURATION);

        return $html;
    }

    /**
     * Parse HTML with DOMDocument
     */
    private static function parse_html($html) {
        if (empty($html)) {
            return false;
        }

        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();

        return $dom;
    }

    /**
     * Scrape BIDV Interest Rates
     */
    public static function scrape_bidv_interest_rates() {
        $url = 'https://www.bidv.com.vn/lai-suat';
        $html = self::fetch_html($url);

        if (!$html) {
            return array('success' => false, 'message' => 'Failed to fetch BIDV website');
        }

        $dom = self::parse_html($html);
        if (!$dom) {
            return array('success' => false, 'message' => 'Failed to parse HTML');
        }

        $rates = array();

        // Parse table (adjust selectors based on actual BIDV website structure)
        $xpath = new DOMXPath($dom);
        $rows = $xpath->query("//table[@class='interest-rate-table']//tr");

        foreach ($rows as $row) {
            $cells = $row->getElementsByTagName('td');

            if ($cells->length >= 3) {
                $term = trim($cells->item(0)->textContent);
                $rate = trim($cells->item(1)->textContent);

                // Extract numeric values
                preg_match('/(\d+)/', $term, $term_matches);
                preg_match('/(\d+\.?\d*)/', $rate, $rate_matches);

                if (!empty($term_matches[1]) && !empty($rate_matches[1])) {
                    $rates[] = array(
                        'rate_type' => 'savings',
                        'product_name' => "Tiết kiệm $term",
                        'term_months' => intval($term_matches[1]),
                        'interest_rate' => floatval($rate_matches[1]),
                        'min_amount' => 0,
                        'max_amount' => null,
                        'effective_date' => current_time('mysql')
                    );
                }
            }
        }

        if (empty($rates)) {
            return array('success' => false, 'message' => 'No rates found on BIDV website');
        }

        return array('success' => true, 'data' => $rates, 'source' => 'web_scraping');
    }

    /**
     * Scrape VPBank Interest Rates
     */
    public static function scrape_vpbank_interest_rates() {
        $url = 'https://www.vpbank.com.vn/lai-suat';
        $html = self::fetch_html($url);

        if (!$html) {
            return array('success' => false, 'message' => 'Failed to fetch VPBank website');
        }

        $dom = self::parse_html($html);
        if (!$dom) {
            return array('success' => false, 'message' => 'Failed to parse HTML');
        }

        $rates = array();

        // Parse VPBank structure (customize based on actual website)
        $xpath = new DOMXPath($dom);

        // Try multiple possible table selectors
        $selectors = array(
            "//table[@class='rate-table']//tr",
            "//table[contains(@class, 'interest')]//tr",
            "//div[contains(@class, 'rate-table')]//tr"
        );

        foreach ($selectors as $selector) {
            $rows = $xpath->query($selector);
            if ($rows->length > 0) {
                foreach ($rows as $row) {
                    $cells = $row->getElementsByTagName('td');

                    if ($cells->length >= 2) {
                        $term_text = trim($cells->item(0)->textContent);
                        $rate_text = trim($cells->item(1)->textContent);

                        preg_match('/(\d+)/', $term_text, $term_matches);
                        preg_match('/(\d+\.?\d*)/', $rate_text, $rate_matches);

                        if (!empty($term_matches[1]) && !empty($rate_matches[1])) {
                            $rates[] = array(
                                'rate_type' => 'savings',
                                'product_name' => "Tiết kiệm $term_text",
                                'term_months' => intval($term_matches[1]),
                                'interest_rate' => floatval($rate_matches[1]),
                                'min_amount' => 0,
                                'effective_date' => current_time('mysql')
                            );
                        }
                    }
                }
                break; // Found data, stop trying other selectors
            }
        }

        if (empty($rates)) {
            return array('success' => false, 'message' => 'No rates found on VPBank website');
        }

        return array('success' => true, 'data' => $rates, 'source' => 'web_scraping');
    }

    /**
     * Scrape Vietcombank Exchange Rates
     */
    public static function scrape_vietcombank_exchange_rates() {
        $url = 'https://portal.vietcombank.com.vn/Usercontrols/TVPortal.TyGia/pXML.aspx';
        $html = self::fetch_html($url);

        if (!$html) {
            return array('success' => false, 'message' => 'Failed to fetch Vietcombank data');
        }

        // Vietcombank returns XML
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($html);
        libxml_clear_errors();

        if (!$xml) {
            return array('success' => false, 'message' => 'Failed to parse XML');
        }

        $rates = array();

        foreach ($xml->Exrate as $item) {
            $currency = (string)$item['CurrencyCode'];
            $buy = (string)$item['Buy'];
            $sell = (string)$item['Sell'];
            $transfer = (string)$item['Transfer'];

            // Clean and convert
            $buy = str_replace(',', '', $buy);
            $sell = str_replace(',', '', $sell);
            $transfer = str_replace(',', '', $transfer);

            if (!empty($currency) && !empty($buy)) {
                $rates[] = array(
                    'currency_code' => $currency,
                    'buy_rate' => floatval($buy),
                    'sell_rate' => floatval($sell),
                    'transfer_rate' => floatval($transfer),
                    'effective_date' => current_time('mysql')
                );
            }
        }

        if (empty($rates)) {
            return array('success' => false, 'message' => 'No exchange rates found');
        }

        return array('success' => true, 'data' => $rates, 'source' => 'web_scraping');
    }

    /**
     * Scrape BIDV Exchange Rates
     */
    public static function scrape_bidv_exchange_rates() {
        $url = 'https://www.bidv.com.vn/ty-gia';
        $html = self::fetch_html($url);

        if (!$html) {
            return array('success' => false, 'message' => 'Failed to fetch BIDV exchange rates');
        }

        $dom = self::parse_html($html);
        if (!$dom) {
            return array('success' => false, 'message' => 'Failed to parse HTML');
        }

        $rates = array();
        $xpath = new DOMXPath($dom);

        // Parse exchange rate table
        $rows = $xpath->query("//table[@class='exchange-rate-table']//tr");

        foreach ($rows as $row) {
            $cells = $row->getElementsByTagName('td');

            if ($cells->length >= 4) {
                $currency = trim($cells->item(0)->textContent);
                $buy = trim($cells->item(1)->textContent);
                $transfer = trim($cells->item(2)->textContent);
                $sell = trim($cells->item(3)->textContent);

                // Clean values
                $buy = str_replace(array(',', ' '), '', $buy);
                $sell = str_replace(array(',', ' '), '', $sell);
                $transfer = str_replace(array(',', ' '), '', $transfer);

                if (preg_match('/^[A-Z]{3}$/', $currency)) {
                    $rates[] = array(
                        'currency_code' => $currency,
                        'buy_rate' => floatval($buy),
                        'sell_rate' => floatval($sell),
                        'transfer_rate' => floatval($transfer),
                        'effective_date' => current_time('mysql')
                    );
                }
            }
        }

        if (empty($rates)) {
            return array('success' => false, 'message' => 'No exchange rates found');
        }

        return array('success' => true, 'data' => $rates, 'source' => 'web_scraping');
    }

    /**
     * Generic scraper for custom URL
     */
    public static function scrape_custom_url($url, $type = 'interest_rates') {
        $html = self::fetch_html($url);

        if (!$html) {
            return array('success' => false, 'message' => "Failed to fetch $url");
        }

        $dom = self::parse_html($html);
        if (!$dom) {
            return array('success' => false, 'message' => 'Failed to parse HTML');
        }

        // Generic table parsing
        $xpath = new DOMXPath($dom);
        $tables = $xpath->query("//table");

        $data = array();

        foreach ($tables as $table) {
            $rows = $table->getElementsByTagName('tr');

            foreach ($rows as $index => $row) {
                if ($index === 0) continue; // Skip header

                $cells = $row->getElementsByTagName('td');
                $row_data = array();

                foreach ($cells as $cell) {
                    $row_data[] = trim($cell->textContent);
                }

                if (count($row_data) >= 2) {
                    $data[] = $row_data;
                }
            }
        }

        if (empty($data)) {
            return array('success' => false, 'message' => 'No data found in tables');
        }

        return array('success' => true, 'data' => $data, 'source' => 'web_scraping', 'raw' => true);
    }

    /**
     * Clear scraping cache
     */
    public static function clear_cache($bank_code = null) {
        global $wpdb;

        $pattern = 'vbdh_scrape_';
        if ($bank_code) {
            $pattern .= $bank_code . '_';
        }

        // Delete matching transients
        $wpdb->query($wpdb->prepare("
            DELETE FROM {$wpdb->options}
            WHERE option_name LIKE %s
        ", '_transient_' . $pattern . '%'));

        $wpdb->query($wpdb->prepare("
            DELETE FROM {$wpdb->options}
            WHERE option_name LIKE %s
        ", '_transient_timeout_' . $pattern . '%'));

        return true;
    }

    /**
     * Log scraping activity
     */
    private static function log($bank_code, $type, $result) {
        global $wpdb;
        $table = $wpdb->prefix . 'vn_api_logs';

        $wpdb->insert($table, array(
            'api_name' => 'Web Scraper - ' . strtoupper($bank_code),
            'endpoint' => $type,
            'status' => $result['success'] ? 'success' : 'error',
            'message' => $result['message'] ?? ($result['success'] ? 'Scraped successfully' : 'Scraping failed'),
            'response_data' => isset($result['data']) ? wp_json_encode($result['data']) : null,
            'created_at' => current_time('mysql')
        ));
    }

    /**
     * Get scraping stats
     */
    public static function get_stats() {
        global $wpdb;
        $table = $wpdb->prefix . 'vn_api_logs';

        $total = $wpdb->get_var("
            SELECT COUNT(*)
            FROM $table
            WHERE api_name LIKE 'Web Scraper%'
        ");

        $success = $wpdb->get_var("
            SELECT COUNT(*)
            FROM $table
            WHERE api_name LIKE 'Web Scraper%' AND status = 'success'
        ");

        return array(
            'total' => intval($total),
            'success' => intval($success),
            'failed' => intval($total) - intval($success),
            'success_rate' => $total > 0 ? round(($success / $total) * 100, 2) : 0
        );
    }
}
