<?php
/**
 * SEO Optimizer
 * Tối ưu hóa SEO cho posts và pages
 */

if (!defined('ABSPATH')) {
    exit;
}

class VBDH_SEO_Optimizer {

    /**
     * Initialize SEO hooks
     */
    public static function init() {
        // Meta tags
        add_action('wp_head', array(__CLASS__, 'output_meta_tags'), 1);

        // Open Graph tags
        add_action('wp_head', array(__CLASS__, 'output_og_tags'), 5);

        // Twitter Card tags
        add_action('wp_head', array(__CLASS__, 'output_twitter_tags'), 6);

        // Canonical URL
        add_action('wp_head', array(__CLASS__, 'output_canonical'), 2);

        // Optimize title
        add_filter('wp_title', array(__CLASS__, 'optimize_title'), 10, 2);
        add_filter('document_title_parts', array(__CLASS__, 'optimize_title_parts'));
    }

    /**
     * Output meta tags
     */
    public static function output_meta_tags() {
        if (!is_singular()) {
            return;
        }

        $post_id = get_the_ID();
        $description = self::get_meta_description($post_id);
        $keywords = self::get_meta_keywords($post_id);

        if ($description) {
            echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
        }

        if ($keywords) {
            echo '<meta name="keywords" content="' . esc_attr($keywords) . '">' . "\n";
        }

        // Robots meta
        $robots = self::get_robots_meta($post_id);
        if ($robots) {
            echo '<meta name="robots" content="' . esc_attr($robots) . '">' . "\n";
        }
    }

    /**
     * Get meta description
     */
    private static function get_meta_description($post_id) {
        // Check for custom meta description
        $custom_desc = get_post_meta($post_id, '_vbdh_meta_description', true);
        if ($custom_desc) {
            return $custom_desc;
        }

        // Auto-generate from excerpt or content
        $post = get_post($post_id);
        if ($post->post_excerpt) {
            $description = $post->post_excerpt;
        } else {
            $description = $post->post_content;
        }

        // Clean and truncate
        $description = wp_strip_all_tags($description);
        $description = preg_replace('/\s+/', ' ', $description);
        $description = mb_substr($description, 0, 160);

        return trim($description);
    }

    /**
     * Get meta keywords
     */
    private static function get_meta_keywords($post_id) {
        // Get tags
        $tags = get_the_tags($post_id);
        if (empty($tags)) {
            return '';
        }

        $keywords = array();
        foreach ($tags as $tag) {
            $keywords[] = $tag->name;
        }

        // Add content type keyword
        $content_type = get_post_meta($post_id, '_vbdh_content_type', true);
        if ($content_type) {
            $keywords[] = $content_type;
        }

        // Add banking keywords
        $keywords[] = 'lãi suất ngân hàng';
        $keywords[] = 'tỷ giá';
        $keywords[] = 'ngân hàng việt nam';

        return implode(', ', array_unique($keywords));
    }

    /**
     * Get robots meta
     */
    private static function get_robots_meta($post_id) {
        $robots = array();

        // Check post status
        $post = get_post($post_id);
        if ($post->post_status !== 'publish') {
            return 'noindex, nofollow';
        }

        // Default: index, follow
        $robots[] = 'index';
        $robots[] = 'follow';

        // Add max-snippet, max-image-preview, max-video-preview
        $robots[] = 'max-snippet:-1';
        $robots[] = 'max-image-preview:large';
        $robots[] = 'max-video-preview:-1';

        return implode(', ', $robots);
    }

    /**
     * Output Open Graph tags
     */
    public static function output_og_tags() {
        if (!is_singular()) {
            return;
        }

        $post_id = get_the_ID();
        $post = get_post($post_id);

        // OG Type
        $content_type = get_post_meta($post_id, '_vbdh_content_type', true);
        $og_type = in_array($content_type, array('daily_rates', 'comparison', 'ranking')) ? 'article' : 'website';

        echo '<meta property="og:type" content="' . esc_attr($og_type) . '">' . "\n";
        echo '<meta property="og:title" content="' . esc_attr(get_the_title()) . '">' . "\n";
        echo '<meta property="og:description" content="' . esc_attr(self::get_meta_description($post_id)) . '">' . "\n";
        echo '<meta property="og:url" content="' . esc_url(get_permalink()) . '">' . "\n";
        echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '">' . "\n";

        // OG Image
        if (has_post_thumbnail($post_id)) {
            $image_url = get_the_post_thumbnail_url($post_id, 'large');
            echo '<meta property="og:image" content="' . esc_url($image_url) . '">' . "\n";
            echo '<meta property="og:image:width" content="1200">' . "\n";
            echo '<meta property="og:image:height" content="630">' . "\n";
        }

        // Article specific tags
        if ($og_type === 'article') {
            echo '<meta property="article:published_time" content="' . esc_attr(get_the_date('c')) . '">' . "\n";
            echo '<meta property="article:modified_time" content="' . esc_attr(get_the_modified_date('c')) . '">' . "\n";

            // Categories
            $categories = get_the_category($post_id);
            foreach ($categories as $category) {
                echo '<meta property="article:section" content="' . esc_attr($category->name) . '">' . "\n";
            }

            // Tags
            $tags = get_the_tags($post_id);
            if ($tags) {
                foreach ($tags as $tag) {
                    echo '<meta property="article:tag" content="' . esc_attr($tag->name) . '">' . "\n";
                }
            }
        }

        // Locale
        echo '<meta property="og:locale" content="vi_VN">' . "\n";
    }

    /**
     * Output Twitter Card tags
     */
    public static function output_twitter_tags() {
        if (!is_singular()) {
            return;
        }

        $post_id = get_the_ID();

        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr(get_the_title()) . '">' . "\n";
        echo '<meta name="twitter:description" content="' . esc_attr(self::get_meta_description($post_id)) . '">' . "\n";

        if (has_post_thumbnail($post_id)) {
            $image_url = get_the_post_thumbnail_url($post_id, 'large');
            echo '<meta name="twitter:image" content="' . esc_url($image_url) . '">' . "\n";
        }

        // Twitter site handle (if configured)
        $twitter_handle = get_option('vbdh_twitter_handle');
        if ($twitter_handle) {
            echo '<meta name="twitter:site" content="@' . esc_attr($twitter_handle) . '">' . "\n";
        }
    }

    /**
     * Output canonical URL
     */
    public static function output_canonical() {
        if (!is_singular()) {
            return;
        }

        $canonical = get_permalink();
        echo '<link rel="canonical" href="' . esc_url($canonical) . '">' . "\n";
    }

    /**
     * Optimize title
     */
    public static function optimize_title($title, $sep = '-') {
        // Add site name if not present
        if (strpos($title, get_bloginfo('name')) === false) {
            $title .= " $sep " . get_bloginfo('name');
        }

        return $title;
    }

    /**
     * Optimize title parts (WordPress 4.4+)
     */
    public static function optimize_title_parts($parts) {
        if (is_singular()) {
            $post_id = get_the_ID();
            $content_type = get_post_meta($post_id, '_vbdh_content_type', true);

            // Add content type to title for better SEO
            if ($content_type === 'daily_rates') {
                $parts['title'] .= ' - Cập Nhật ' . current_time('d/m/Y');
            } elseif ($content_type === 'comparison') {
                $parts['title'] .= ' - So Sánh Chi Tiết';
            } elseif ($content_type === 'ranking') {
                $parts['title'] .= ' - Xếp Hạng ' . current_time('Y');
            }
        }

        return $parts;
    }

    /**
     * Generate sitemap (basic XML sitemap)
     */
    public static function generate_sitemap() {
        $posts = get_posts(array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'modified',
            'order' => 'DESC'
        ));

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // Homepage
        $xml .= '  <url>' . "\n";
        $xml .= '    <loc>' . home_url('/') . '</loc>' . "\n";
        $xml .= '    <lastmod>' . current_time('c') . '</lastmod>' . "\n";
        $xml .= '    <changefreq>daily</changefreq>' . "\n";
        $xml .= '    <priority>1.0</priority>' . "\n";
        $xml .= '  </url>' . "\n";

        // Posts
        foreach ($posts as $post) {
            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . get_permalink($post) . '</loc>' . "\n";
            $xml .= '    <lastmod>' . get_the_modified_date('c', $post) . '</lastmod>' . "\n";

            // Set change frequency based on content type
            $content_type = get_post_meta($post->ID, '_vbdh_content_type', true);
            if ($content_type === 'daily_rates') {
                $changefreq = 'daily';
                $priority = '0.9';
            } else {
                $changefreq = 'weekly';
                $priority = '0.7';
            }

            $xml .= '    <changefreq>' . $changefreq . '</changefreq>' . "\n";
            $xml .= '    <priority>' . $priority . '</priority>' . "\n";
            $xml .= '  </url>' . "\n";
        }

        $xml .= '</urlset>';

        return $xml;
    }

    /**
     * Save sitemap to file
     */
    public static function save_sitemap() {
        $sitemap_xml = self::generate_sitemap();
        $sitemap_path = ABSPATH . 'sitemap-vbdh.xml';

        file_put_contents($sitemap_path, $sitemap_xml);

        return $sitemap_path;
    }

    /**
     * Get SEO score for a post
     */
    public static function get_seo_score($post_id) {
        $score = 0;
        $max_score = 100;
        $issues = array();

        $post = get_post($post_id);

        // Title length (10 points)
        $title = get_the_title($post_id);
        $title_length = mb_strlen($title);
        if ($title_length >= 30 && $title_length <= 60) {
            $score += 10;
        } else {
            $issues[] = 'Tiêu đề nên từ 30-60 ký tự (hiện tại: ' . $title_length . ')';
        }

        // Meta description (15 points)
        $description = self::get_meta_description($post_id);
        $desc_length = mb_strlen($description);
        if ($desc_length >= 120 && $desc_length <= 160) {
            $score += 15;
        } else {
            $issues[] = 'Mô tả nên từ 120-160 ký tự (hiện tại: ' . $desc_length . ')';
        }

        // Has featured image (10 points)
        if (has_post_thumbnail($post_id)) {
            $score += 10;
        } else {
            $issues[] = 'Nên có ảnh đại diện';
        }

        // Content length (20 points)
        $content_length = str_word_count(strip_tags($post->post_content));
        if ($content_length >= 300) {
            $score += 20;
        } else {
            $issues[] = 'Nội dung nên >= 300 từ (hiện tại: ' . $content_length . ')';
        }

        // Has categories (10 points)
        $categories = get_the_category($post_id);
        if (!empty($categories)) {
            $score += 10;
        } else {
            $issues[] = 'Nên có danh mục';
        }

        // Has tags (10 points)
        $tags = get_the_tags($post_id);
        if (!empty($tags) && count($tags) >= 3) {
            $score += 10;
        } else {
            $issues[] = 'Nên có ít nhất 3 tags';
        }

        // Internal links (15 points)
        $internal_links = preg_match_all('/<a[^>]+href=["\']' . preg_quote(home_url(), '/') . '[^"\']*["\'][^>]*>/i', $post->post_content);
        if ($internal_links >= 2) {
            $score += 15;
        } else {
            $issues[] = 'Nên có ít nhất 2 liên kết nội bộ';
        }

        // Has excerpt (10 points)
        if ($post->post_excerpt) {
            $score += 10;
        } else {
            $issues[] = 'Nên có trích dẫn (excerpt)';
        }

        return array(
            'score' => $score,
            'max_score' => $max_score,
            'percentage' => round(($score / $max_score) * 100),
            'grade' => self::get_grade_from_score($score),
            'issues' => $issues
        );
    }

    /**
     * Get grade from score
     */
    private static function get_grade_from_score($score) {
        if ($score >= 90) return 'A+';
        if ($score >= 80) return 'A';
        if ($score >= 70) return 'B';
        if ($score >= 60) return 'C';
        if ($score >= 50) return 'D';
        return 'F';
    }
}

// Initialize SEO Optimizer
VBDH_SEO_Optimizer::init();
