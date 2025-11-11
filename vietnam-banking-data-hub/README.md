# Vietnam Banking Data Hub WordPress Plugin

**Plugin tổng hợp và tự động hóa dữ liệu lãi suất, tỷ giá từ các ngân hàng Việt Nam**

[![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)](https://github.com/dinhnguyen1890/vietnam-banking-data-hub)
[![WordPress](https://img.shields.io/badge/WordPress-6.4%2B-blue.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-8.0%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/license-GPL--2.0-green.svg)](LICENSE)

## 📋 Tính Năng Chính

### ✅ Phase 1: Core Infrastructure (HOÀN TẤT)
- **Quản lý ngân hàng**: Thêm, sửa, xóa thông tin ngân hàng
- **API Connectors**: Tích hợp API ngân hàng (BIDV, VPBank, v.v.)
- **Data Fetcher**: Tự động lấy dữ liệu với cache 1 giờ
- **Cron Jobs**: Tỷ giá mỗi giờ, Lãi suất mỗi 6 giờ
- **Database**: 5 bảng với utf8mb4_unicode_ci, indexes tối ưu
- **Admin Interface**: 6 tabs quản lý đầy đủ

### ✅ Phase 2: Auto Content Generation (HOÀN TẤT)
- **3 loại bài viết tự động**: Daily Rates, Comparison, Ranking
- Duplicate detection, Auto categories & tags, SEO-friendly

### ✅ Phase 3: Shortcodes & Widgets (HOÀN TẤT)
- **3 Shortcodes**: comparison_table, calculator, exchange_rate
- **1 WordPress Widget**: Banking Rates Widget
- Responsive design, AJAX real-time

### ✅ Phase 4: SEO & Auto-Indexing (HOÀN TẤT)
- **Schema.org**: 7 schema types
- **IndexNow API**: Auto-submit to Bing/Yandex
- **SEO Tools**: Meta tags, Open Graph, Twitter Cards, Sitemap, SEO Score

### ✅ Phase 5: Polish & Optimize (HOÀN TẤT)
- Performance optimization, Documentation, Production ready

## 🚀 Cài Đặt Nhanh

1. Upload plugin ZIP vào `/wp-content/plugins/`
2. Activate trong WordPress Admin
3. Vào **Banking Data > Quản lý Ngân hàng** > Add banks
4. **Đồng bộ dữ liệu** > Sync All
5. **Tạo Nội dung** > Generate posts

## 📖 Sử Dụng Shortcodes

```php
[vbdh_comparison_table term="12" limit="10"]
[vbdh_calculator]
[vbdh_exchange_rate currency="USD" display="table"]
```

Xem chi tiết: **Banking Data > Shortcodes & Widgets** (trong Admin)

## 🔧 Yêu Cầu

- WordPress 6.4+
- PHP 8.0+
- MySQL 5.7+

## 📚 Documentation

- README.md (file này)
- USER-GUIDE.md
- CHANGELOG.md
- Admin UI documentation

## 📝 License

GPL-2.0-or-later

**Made with ❤️ for Vietnamese WordPress Community**
