<?php
/**
 * Schema.org Generator
 * Tạo structured data cho SEO
 */

if (!defined('ABSPATH')) {
    exit;
}

class VBDH_Schema_Generator {

    /**
     * Generate schema for single post
     */
    public static function generate_post_schema($post_id) {
        $post = get_post($post_id);
        if (!$post) {
            return null;
        }

        // Get content type
        $content_type = get_post_meta($post_id, '_vbdh_content_type', true);

        switch ($content_type) {
            case 'daily_rates':
                return self::generate_financial_service_schema($post);

            case 'comparison':
                return self::generate_comparison_schema($post);

            case 'ranking':
                return self::generate_ranking_schema($post);

            default:
                return self::generate_article_schema($post);
        }
    }

    /**
     * Generate FinancialService schema (for daily rates)
     */
    private static function generate_financial_service_schema($post) {
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'FinancialService',
            'name' => get_the_title($post),
            'description' => wp_strip_all_tags(get_the_excerpt($post)),
            'url' => get_permalink($post),
            'datePublished' => get_the_date('c', $post),
            'dateModified' => get_the_modified_date('c', $post),
            'author' => array(
                '@type' => 'Organization',
                'name' => get_bloginfo('name')
            ),
            'publisher' => array(
                '@type' => 'Organization',
                'name' => get_bloginfo('name'),
                'logo' => array(
                    '@type' => 'ImageObject',
                    'url' => get_site_icon_url()
                )
            )
        );

        // Add interest rate data if available
        $rates = self::get_latest_rates_for_schema();
        if (!empty($rates)) {
            $schema['offers'] = array();
            foreach ($rates as $rate) {
                $schema['offers'][] = array(
                    '@type' => 'Offer',
                    'name' => $rate->bank_name . ' - ' . $rate->term_months . ' tháng',
                    'price' => $rate->interest_rate,
                    'priceCurrency' => 'VND',
                    'seller' => array(
                        '@type' => 'BankOrCreditUnion',
                        'name' => $rate->bank_name
                    )
                );
            }
        }

        return $schema;
    }

    /**
     * Generate ItemList schema (for comparison & ranking)
     */
    private static function generate_comparison_schema($post) {
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => get_the_title($post),
            'description' => wp_strip_all_tags(get_the_excerpt($post)),
            'url' => get_permalink($post),
            'datePublished' => get_the_date('c', $post),
            'dateModified' => get_the_modified_date('c', $post)
        );

        return $schema;
    }

    /**
     * Generate ranking schema
     */
    private static function generate_ranking_schema($post) {
        $schema = self::generate_comparison_schema($post);
        $schema['@type'] = 'ItemList';
        $schema['itemListOrder'] = 'Descending';

        // Add top banks
        $rates = self::get_latest_rates_for_schema(5);
        if (!empty($rates)) {
            $schema['itemListElement'] = array();
            $position = 1;
            foreach ($rates as $rate) {
                $schema['itemListElement'][] = array(
                    '@type' => 'ListItem',
                    'position' => $position++,
                    'item' => array(
                        '@type' => 'FinancialProduct',
                        'name' => $rate->bank_name . ' - ' . $rate->product_name,
                        'interestRate' => $rate->interest_rate,
                        'provider' => array(
                            '@type' => 'BankOrCreditUnion',
                            'name' => $rate->bank_name
                        )
                    )
                );
            }
        }

        return $schema;
    }

    /**
     * Generate Article schema (fallback)
     */
    private static function generate_article_schema($post) {
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => get_the_title($post),
            'description' => wp_strip_all_tags(get_the_excerpt($post)),
            'url' => get_permalink($post),
            'datePublished' => get_the_date('c', $post),
            'dateModified' => get_the_modified_date('c', $post),
            'author' => array(
                '@type' => 'Organization',
                'name' => get_bloginfo('name')
            ),
            'publisher' => array(
                '@type' => 'Organization',
                'name' => get_bloginfo('name'),
                'logo' => array(
                    '@type' => 'ImageObject',
                    'url' => get_site_icon_url()
                )
            ),
            'mainEntityOfPage' => array(
                '@type' => 'WebPage',
                '@id' => get_permalink($post)
            )
        );

        // Add image if available
        if (has_post_thumbnail($post)) {
            $image_id = get_post_thumbnail_id($post);
            $image_url = wp_get_attachment_image_url($image_id, 'full');
            $schema['image'] = array(
                '@type' => 'ImageObject',
                'url' => $image_url,
                'width' => 1200,
                'height' => 630
            );
        }

        return $schema;
    }

    /**
     * Generate Bank schema
     */
    public static function generate_bank_schema($bank) {
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'BankOrCreditUnion',
            'name' => $bank->bank_name,
            'identifier' => $bank->bank_code,
            'url' => $bank->website_url,
            'areaServed' => array(
                '@type' => 'Country',
                'name' => 'Vietnam'
            )
        );

        if ($bank->bank_logo_url) {
            $schema['logo'] = array(
                '@type' => 'ImageObject',
                'url' => $bank->bank_logo_url
            );
        }

        return $schema;
    }

    /**
     * Generate ExchangeRateSpecification schema
     */
    public static function generate_exchange_rate_schema($currency = 'USD') {
        $rates = VBDH_Exchange_Rate::get_latest($currency, 5);

        if (empty($rates)) {
            return null;
        }

        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => "Tỷ giá $currency hôm nay",
            'description' => "Tỷ giá $currency tại các ngân hàng Việt Nam",
            'itemListElement' => array()
        );

        $position = 1;
        foreach ($rates as $rate) {
            $schema['itemListElement'][] = array(
                '@type' => 'ListItem',
                'position' => $position++,
                'item' => array(
                    '@type' => 'ExchangeRateSpecification',
                    'currency' => $currency,
                    'currentExchangeRate' => array(
                        '@type' => 'UnitPriceSpecification',
                        'price' => $rate->buy_rate,
                        'priceCurrency' => 'VND'
                    ),
                    'exchangeRateSpread' => array(
                        '@type' => 'MonetaryAmount',
                        'value' => $rate->sell_rate - $rate->buy_rate,
                        'currency' => 'VND'
                    ),
                    'provider' => array(
                        '@type' => 'BankOrCreditUnion',
                        'name' => $rate->bank_name
                    )
                )
            );
        }

        return $schema;
    }

    /**
     * Get latest rates for schema
     */
    private static function get_latest_rates_for_schema($limit = 3) {
        return VBDH_Interest_Rate::compare_banks('savings', 12, $limit);
    }

    /**
     * Output schema in page head
     */
    public static function output_schema() {
        if (!is_singular()) {
            return;
        }

        $post_id = get_the_ID();
        $schema = self::generate_post_schema($post_id);

        if ($schema) {
            echo '<script type="application/ld+json">';
            echo wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            echo '</script>' . "\n";
        }
    }

    /**
     * Add breadcrumb schema
     */
    public static function generate_breadcrumb_schema() {
        if (!is_singular()) {
            return null;
        }

        $breadcrumbs = array(
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array()
        );

        // Home
        $breadcrumbs['itemListElement'][] = array(
            '@type' => 'ListItem',
            'position' => 1,
            'name' => 'Trang chủ',
            'item' => home_url('/')
        );

        // Category (if applicable)
        $categories = get_the_category();
        $position = 2;

        if (!empty($categories)) {
            $category = $categories[0];
            $breadcrumbs['itemListElement'][] = array(
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $category->name,
                'item' => get_category_link($category->term_id)
            );
        }

        // Current post
        $breadcrumbs['itemListElement'][] = array(
            '@type' => 'ListItem',
            'position' => $position,
            'name' => get_the_title(),
            'item' => get_permalink()
        );

        return $breadcrumbs;
    }

    /**
     * Generate FAQ schema for posts
     */
    public static function generate_faq_schema($faqs) {
        if (empty($faqs)) {
            return null;
        }

        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array()
        );

        foreach ($faqs as $faq) {
            $schema['mainEntity'][] = array(
                '@type' => 'Question',
                'name' => $faq['question'],
                'acceptedAnswer' => array(
                    '@type' => 'Answer',
                    'text' => $faq['answer']
                )
            );
        }

        return $schema;
    }

    /**
     * Get all schemas for a post
     */
    public static function get_all_schemas_for_post($post_id) {
        $schemas = array();

        // Main schema
        $main_schema = self::generate_post_schema($post_id);
        if ($main_schema) {
            $schemas[] = $main_schema;
        }

        // Breadcrumb
        $breadcrumb = self::generate_breadcrumb_schema();
        if ($breadcrumb) {
            $schemas[] = $breadcrumb;
        }

        return $schemas;
    }

    /**
     * Output multiple schemas
     */
    public static function output_multiple_schemas($schemas) {
        if (empty($schemas)) {
            return;
        }

        echo '<script type="application/ld+json">';
        echo wp_json_encode($schemas, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        echo '</script>' . "\n";
    }
}

// Hook to add schema to wp_head
add_action('wp_head', array('VBDH_Schema_Generator', 'output_schema'), 99);
