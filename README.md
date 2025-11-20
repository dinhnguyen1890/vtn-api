# VTN Calendar - WordPress Plugin

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)
![License](https://img.shields.io/badge/license-GPL%20v2-green.svg)

## Tổng Quan

**VTN Calendar** là WordPress plugin tự động tổng hợp và cập nhật các lịch trình quan trọng tại Việt Nam từ các nguồn chính thống:

- 🎊 **Lịch Nghỉ Lễ/Tết** - Từ Chính phủ VN
- 🎬 **Lịch Chiếu Phim** - CGV, Galaxy, Lotte
- ⚽ **Lịch Bóng Đá** - V.League, Đội tuyển Quốc gia
- ⚡ **Lịch Cắt Điện** - EVN 5 tỉnh/thành lớn

## Tính Năng Chính

### ✨ Auto-Crawling System
- Tự động crawl dữ liệu từ nguồn chính thống
- WordPress Cron jobs tự động chạy theo lịch
- Error handling và retry mechanism
- Logging chi tiết mọi hoạt động

### 🤖 AI Content Generation
- Tích hợp Claude API (claude-sonnet-4-20250514)
- Tự động tạo nội dung giới thiệu hấp dẫn
- Template fallback khi API unavailable

### 🎨 Frontend Display
- 3 chế độ hiển thị: List, Calendar, Grid
- Responsive design (mobile-first)
- Shortcode linh hoạt với nhiều tùy chọn
- Minh bạch nguồn thông tin

### ⚙️ Admin Interface
- 5 trang quản lý chuyên nghiệp
- AJAX-powered interface
- Real-time statistics
- Test crawl manual

## Yêu Cầu Hệ Thống

- WordPress 5.8 trở lên
- PHP 7.4 trở lên
- MySQL 5.6 trở lên
- WordPress Cron enabled

## Cài Đặt

### Cách 1: Upload trực tiếp

1. Tải file `vtncalendar.php`
2. Vào WordPress Admin → Plugins → Add New → Upload Plugin
3. Chọn file `vtncalendar.php` (hoặc file ZIP nếu có)
4. Click "Install Now" → "Activate"

### Cách 2: FTP

1. Upload file `vtncalendar.php` vào `/wp-content/plugins/vtncalendar/`
2. Vào WordPress Admin → Plugins
3. Tìm "VTN Calendar" và click "Activate"

## Cấu Hình Nhanh

### 1. Cài Đặt Crawl

Vào **VTN Calendar → Crawl Settings**:

- ✅ Bật/tắt từng module cần crawl
- 🔑 Nhập Claude API Key (tùy chọn)
- 🌍 Chọn nguồn phim (CGV, Galaxy, Lotte)
- 📍 Chọn tỉnh/thành cho lịch cắt điện

### 2. Test Crawl

Vào **VTN Calendar → Dashboard**:

- Click các nút "Test Crawl" để kiểm tra
- Xem kết quả trong Logs

### 3. Hiển Thị Frontend

Thêm shortcode vào bài viết/trang:

```
[vtncalendar]
```

**Với tùy chọn:**

```
[vtncalendar type="movie" view="grid" limit="12"]
[vtncalendar type="holiday" view="list"]
[vtncalendar type="power" province="hanoi"]
[vtncalendar type="football" view="calendar"]
```

## Shortcode Parameters

| Parameter | Values | Mô tả |
|-----------|--------|-------|
| `type` | holiday, movie, football, power_outage | Lọc theo loại lịch |
| `province` | hanoi, hcm, danang, haiphong, cantho | Lọc theo tỉnh (cho cắt điện) |
| `view` | list, calendar, grid | Chế độ hiển thị |
| `limit` | 1-100 | Số lượng items |

## Cron Schedule

Plugin tự động crawl theo lịch:

- **Lịch Nghỉ Lễ**: Monthly (ngày 1 hàng tháng, 6:00 AM)
- **Lịch Chiếu Phim**: Twice daily (8:00 AM, 8:00 PM)
- **Lịch Bóng Đá**: Weekly (Thứ 2 hàng tuần, 6:00 AM)
- **Lịch Cắt Điện**: Daily (6:00 AM)
- **Cleanup**: Daily (3:00 AM) - xóa sự kiện cũ

## Admin Pages

### 1. Dashboard
- Thống kê tổng quan
- Quick stats cho 4 loại lịch
- Recent crawl logs
- Quick action buttons

### 2. Calendar Manager
- Xem/Xóa events
- Filter theo type, date, location
- Bulk actions
- Export to CSV

### 3. Crawl Settings
- Enable/Disable crawl modules
- Claude API configuration
- Source selection
- Province selection
- Auto cleanup settings

### 4. Display Settings
- Default view mode
- Items per page
- Date format
- Show/Hide options
- Color scheme

### 5. Logs & Status
- Crawl history
- Error logs
- Source health status
- Export logs

## Claude API Integration

### Lấy API Key

1. Đăng ký tại: https://console.anthropic.com/
2. Tạo API key
3. Copy và paste vào **Crawl Settings**

### AI Content

Plugin sử dụng Claude để tạo:

- **Phim**: Giới thiệu hấp dẫn
- **Bóng đá**: Match preview
- **Lịch nghỉ**: Tips chuẩn bị
- **Cắt điện**: Thông báo và tips

> **Lưu ý**: Nếu không có API key hoặc API fail, plugin sẽ dùng template mặc định.

## Bảo Mật

Plugin tuân thủ WordPress Security Standards:

✅ Nonce verification cho AJAX
✅ Capability checks
✅ Prepared SQL statements
✅ Input sanitization
✅ Output escaping
✅ Rate limiting
✅ User agent specification

## Minh Bạch Thông Tin

Mọi event hiển thị đều có:

- Link đến nguồn gốc
- Thời gian cập nhật
- Disclaimer: "Thông tin có thể thay đổi"

## Troubleshooting

### Plugin không crawl được dữ liệu?

1. Kiểm tra WordPress Cron: Vào Tools → Site Health
2. Test manual crawl từ Dashboard
3. Xem Logs để biết lỗi

### Cron jobs không chạy?

```php
// Thêm vào wp-config.php nếu host disable WP-Cron
define('DISABLE_WP_CRON', false);
```

Hoặc setup server cron:

```bash
*/30 * * * * wget -q -O - https://your-site.com/wp-cron.php?doing_wp_cron > /dev/null 2>&1
```

### Database tables không được tạo?

1. Deactivate plugin
2. Delete plugin
3. Re-upload và activate lại

### Hiển thị frontend bị lỗi CSS?

1. Vào Settings → Permalinks
2. Click "Save Changes" (không cần đổi gì)
3. Clear cache (nếu dùng caching plugin)

## Performance Tips

### Caching

Plugin sử dụng WordPress Transients để cache:

- Cache duration: 1 hour
- Auto refresh khi crawl mới

### Database Optimization

- Đã có indexes cho queries nhanh
- Auto cleanup sự kiện cũ
- Configurable retention period

## Roadmap (Future Versions)

- [ ] Calendar view implementation (month grid)
- [ ] Email/SMS alerts cho lịch cắt điện
- [ ] Google Calendar integration
- [ ] More cinema chains (BHD, Platinum)
- [ ] More provinces cho lịch cắt điện
- [ ] User accounts & favorites
- [ ] Mobile app
- [ ] Export to iCal format

## Support & Feedback

- **Issues**: https://github.com/dinhnguyen1890/vtn-api/issues
- **Email**: support@vtncalendar.com

## License

GPL v2 or later - https://www.gnu.org/licenses/gpl-2.0.html

## Credits

Developed by VTN Team

**Crawl Sources:**
- Chính phủ Việt Nam (chinhphu.vn)
- CGV Cinemas (cgv.vn)
- Galaxy Cinema (galaxycine.vn)
- Lotte Cinema (lottecinemavn.com)
- Liên đoàn Bóng đá VN (vff.org.vn)
- EVN Hà Nội, TP.HCM, Đà Nẵng

**Powered by:**
- WordPress
- Claude AI (Anthropic)

---

**Made with ❤️ for Vietnamese users**
