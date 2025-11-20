# VTN Calendar - Hướng Dẫn Sử Dụng

## Mục Lục

1. [Giới Thiệu](#giới-thiệu)
2. [Dashboard](#dashboard)
3. [Calendar Manager](#calendar-manager)
4. [Crawl Settings](#crawl-settings)
5. [Display Settings](#display-settings)
6. [Logs & Status](#logs--status)
7. [Sử Dụng Shortcodes](#sử-dụng-shortcodes)
8. [Tips & Tricks](#tips--tricks)

---

## Giới Thiệu

VTN Calendar giúp bạn tự động tổng hợp và hiển thị 4 loại lịch quan trọng:

- 🎊 **Lịch Nghỉ Lễ** - Từ Chính phủ VN
- 🎬 **Lịch Chiếu Phim** - CGV, Galaxy, Lotte
- ⚽ **Lịch Bóng Đá** - V.League, Đội tuyển
- ⚡ **Lịch Cắt Điện** - EVN các tỉnh/thành

Plugin hoạt động tự động qua WordPress Cron, không cần can thiệp thủ công.

---

## Dashboard

### Truy Cập

**VTN Calendar → Dashboard**

### Thống Kê Tổng Quan

Dashboard hiển thị 4 cards màu sắc:

1. **Lịch Nghỉ Lễ** (Đỏ) - Số sự kiện lịch nghỉ
2. **Lịch Chiếu Phim** (Xanh lá) - Số phim sắp chiếu
3. **Lịch Bóng Đá** (Xanh dương) - Số trận đấu
4. **Lịch Cắt Điện** (Cam) - Số thông báo cắt điện

### Crawl Logs Gần Đây

Bảng hiển thị 5 logs mới nhất:

- **Loại**: holiday, movie, football, power_outage
- **Nguồn**: CGV, VFF, EVN, etc.
- **Trạng thái**: success (xanh), failed (đỏ)
- **Tìm thấy**: Số items crawl được
- **Thêm mới**: Số items được thêm vào database
- **Thời gian**: Timestamp

### Quick Actions

Các nút test crawl manual:

- **Test Crawl Lịch Nghỉ** - Crawl ngay lập tức
- **Test Crawl Phim** - Crawl phim từ các rạp
- **Test Crawl Bóng Đá** - Crawl lịch thi đấu
- **Test Crawl Cắt Điện** - Crawl lịch mất điện

**Cách dùng:**

1. Click nút test
2. Đợi alert hiển thị kết quả
3. Page sẽ reload tự động
4. Xem stats cập nhật

---

## Calendar Manager

### Truy Cập

**VTN Calendar → Calendar Manager**

### Xem Danh Sách Events

Bảng hiển thị tất cả events:

| Cột | Mô Tả |
|-----|-------|
| ID | ID tự động |
| Loại | holiday, movie, football, power_outage |
| Tiêu đề | Tên sự kiện |
| Ngày | Ngày diễn ra (dd/mm/yyyy HH:ii) |
| Nguồn | CGV, VFF, EVN, etc. |
| Trạng thái | active (xanh), expired (vàng) |
| Actions | Nút xóa |

### Xóa Event

1. Click nút **Xóa** bên phải event
2. Confirm dialog xuất hiện
3. Click OK để xóa
4. Row sẽ fade out (AJAX, không reload page)

### Filter Events (Future)

Hiện tại chưa có filter UI, nhưng có thể filter qua database:

```sql
SELECT * FROM wp_vtncalendar_events
WHERE calendar_type = 'movie'
AND event_date >= NOW()
ORDER BY event_date ASC;
```

### Export to CSV (Future)

Tính năng này sẽ được thêm trong version sau.

---

## Crawl Settings

### Truy Cập

**VTN Calendar → Crawl Settings**

### Claude API

#### Claude API Key

- **Mục đích**: Tạo AI content cho events
- **Lấy key**: https://console.anthropic.com/
- **Format**: `sk-ant-api03-xxx...`
- **Chi phí**: ~$3-15 per 1M tokens

**Cách nhập:**

1. Paste API key vào field
2. Tick ✅ "Bật AI Content Generation"
3. Click **Lưu cài đặt**

**Nếu không có API key:**

- Không sao! Plugin sẽ dùng template mặc định
- Events vẫn crawl bình thường
- Chỉ thiếu AI-generated preview

### Crawl Modules

#### 1. Lịch Nghỉ Lễ

- ✅ **Bật crawl Lịch Nghỉ Lễ**
- **Nguồn**: Chính phủ VN (chinhphu.vn)
- **Tần suất**: Monthly (ngày 1 hàng tháng, 6AM)
- **Data**: Ngày nghỉ chính thức, ngày bù

#### 2. Lịch Chiếu Phim

- ✅ **Bật crawl Lịch Chiếu Phim**
- **Nguồn**: Chọn 1 hoặc nhiều:
  - ✅ CGV
  - ✅ Galaxy
  - ✅ Lotte
- **Tần suất**: Twice daily (8AM, 8PM)
- **Data**: Phim sắp chiếu, ngày khởi chiếu, poster

**Tips:**

- Bật cả 3 nguồn để có nhiều phim nhất
- Nếu chỉ quan tâm 1 rạp, chọn 1 nguồn

#### 3. Lịch Bóng Đá

- ✅ **Bật crawl Lịch Bóng Đá**
- **Nguồn**: VFF (vff.org.vn)
- **Tần suất**: Weekly (Thứ 2, 6AM)
- **Data**: V.League, Đội tuyển Quốc gia

#### 4. Lịch Cắt Điện

- ✅ **Bật crawl Lịch Cắt Điện**
- **Tỉnh/Thành phố**: Chọn 1 hoặc nhiều:
  - ✅ Hà Nội
  - ✅ TP.HCM
  - ✅ Đà Nẵng
  - ✅ Hải Phòng
  - ✅ Cần Thơ
- **Tần suất**: Daily (6AM)
- **Data**: Khu vực, thời gian cắt, lý do

**Tips:**

- Chỉ chọn tỉnh/thành bạn quan tâm
- Càng nhiều tỉnh = càng nhiều data = database lớn hơn

### Auto Cleanup

- ✅ **Tự động xóa sự kiện cũ**
- **Xóa sau (ngày)**: 30 (mặc định)

**Mục đích:**

- Tránh database quá lớn
- Xóa events đã qua (expired)

**Khuyến nghị:**

- 30 ngày cho movies, football
- 90 ngày cho holidays (để tra cứu lịch sử)

**Cách hoạt động:**

- Cron chạy daily (3AM)
- Xóa events có `event_date` < (today - cleanup_days)

---

## Display Settings

### Truy Cập

**VTN Calendar → Display Settings**

### Default View

Chọn cách hiển thị mặc định cho shortcode `[vtncalendar]`:

- **List**: Danh sách (table-like)
- **Calendar**: Calendar view (chưa implement đầy đủ)
- **Grid**: Card layout (tốt cho movies)

**Khuyến nghị:**

- List: Cho holidays, football, power
- Grid: Cho movies (có poster)

### Items Per Page

- **Mặc định**: 20
- **Range**: 5-100
- **Mục đích**: Số events hiển thị trên 1 page

**Khuyến nghị:**

- 10-20: Cho mobile
- 20-50: Cho desktop

### Date Format

- **Mặc định**: d/m/Y (31/12/2024)
- **Khác**:
  - Y-m-d (2024-12-31)
  - m/d/Y (12/31/2024)
  - d-m-Y (31-12-2024)

**PHP Date Format**: https://www.php.net/manual/en/datetime.format.php

### Show/Hide Options

#### Hiển thị link nguồn

- ✅ Bật: Hiển thị "Nguồn: CGV" với link
- ❌ Tắt: Không hiển thị nguồn

**Khuyến nghị**: BẬT (để minh bạch và SEO)

#### Hiển thị AI content

- ✅ Bật: Hiển thị AI-generated preview
- ❌ Tắt: Chỉ hiển thị description gốc

**Khuyến nghị**: BẬT (nếu có Claude API)

---

## Logs & Status

### Truy Cập

**VTN Calendar → Logs & Status**

### Xem Crawl Logs

Bảng hiển thị 100 logs gần nhất:

| Cột | Mô Tả |
|-----|-------|
| Thời gian | Timestamp crawl |
| Loại | holiday, movie, football, power_outage |
| Nguồn | CGV, VFF, EVN, etc. |
| Trạng thái | success, failed, partial |
| Tìm thấy | Số items crawl được |
| Thêm mới | Số items thêm vào database |
| Execution Time | Thời gian thực thi (seconds) |
| Error | Error message (nếu failed) |

### Phân Tích Logs

#### Status: Success

- Crawl thành công
- Items được thêm vào database
- Không có error

#### Status: Failed

- Crawl thất bại
- Check cột **Error** để biết lý do
- Có thể do:
  - Network timeout
  - Website nguồn down
  - HTML structure thay đổi
  - IP bị chặn

#### Status: Partial

- Crawl thành công 1 phần
- Có items được thêm, nhưng có lỗi
- Check logs để debug

### Troubleshooting Qua Logs

**Ví dụ 1: Error "HTTP Error: 403"**

- **Nguyên nhân**: IP bị chặn bởi website nguồn
- **Giải pháp**:
  - Đợi 1-2 giờ rồi thử lại
  - Liên hệ hosting để check IP

**Ví dụ 2: Error "Operation timed out"**

- **Nguyên nhân**: Network chậm
- **Giải pháp**:
  - Tăng timeout trong code
  - Check server network

**Ví dụ 3: Items Found: 0**

- **Nguyên nhân**: HTML structure thay đổi
- **Giải pháp**:
  - Cần update parser code
  - Report issue

---

## Sử Dụng Shortcodes

### Shortcode Cơ Bản

```
[vtncalendar]
```

Hiển thị tất cả events (active) với default settings.

### Parameters

#### type

Filter theo loại lịch:

```
[vtncalendar type="holiday"]
[vtncalendar type="movie"]
[vtncalendar type="football"]
[vtncalendar type="power_outage"]
```

#### province

Filter theo tỉnh/thành (chỉ cho power_outage):

```
[vtncalendar type="power_outage" province="hanoi"]
[vtncalendar type="power_outage" province="hcm"]
```

**Values**: hanoi, hcm, danang, haiphong, cantho

#### view

Chọn cách hiển thị:

```
[vtncalendar view="list"]
[vtncalendar view="grid"]
[vtncalendar view="calendar"]
```

**Khuyến nghị**:

- `grid`: Cho movies (có poster)
- `list`: Cho holidays, football, power

#### limit

Số lượng items hiển thị:

```
[vtncalendar limit="10"]
[vtncalendar limit="50"]
```

**Mặc định**: 20 (từ Display Settings)

### Shortcode Examples

#### 1. Trang Lịch Chiếu Phim

```
# Phim Sắp Chiếu

[vtncalendar type="movie" view="grid" limit="12"]
```

#### 2. Trang Lịch Nghỉ

```
# Lịch Nghỉ Lễ 2025

[vtncalendar type="holiday" view="list"]
```

#### 3. Trang Lịch Bóng Đá

```
# Lịch Thi Đấu V.League

[vtncalendar type="football" view="list" limit="20"]
```

#### 4. Trang Lịch Cắt Điện (Hà Nội)

```
# Lịch Cắt Điện Hà Nội

[vtncalendar type="power_outage" province="hanoi" view="list"]
```

#### 5. Trang Tổng Hợp

```
# Lịch Nghỉ

[vtncalendar type="holiday" limit="5"]

# Phim Sắp Chiếu

[vtncalendar type="movie" view="grid" limit="6"]

# Lịch Bóng Đá

[vtncalendar type="football" limit="5"]
```

### Customize CSS

Nếu muốn tùy chỉnh giao diện, thêm vào theme CSS:

```css
/* Custom VTN Calendar CSS */

.vtncal-event-card {
  /* Tùy chỉnh event card */
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.vtncal-event-title {
  /* Tùy chỉnh tiêu đề */
  font-size: 22px;
  color: #333;
}

.vtncal-grid {
  /* Tùy chỉnh grid layout */
  gap: 30px;
}

/* Mobile responsive */
@media (max-width: 768px) {
  .vtncal-event-card {
    padding: 10px;
  }
}
```

---

## Tips & Tricks

### 1. SEO Optimization

**Create Dedicated Pages:**

- `/lich-nghi-tet/` - [vtncalendar type="holiday"]
- `/lich-chieu-phim/` - [vtncalendar type="movie" view="grid"]
- `/lich-bong-da/` - [vtncalendar type="football"]
- `/lich-cat-dien-ha-noi/` - [vtncalendar type="power_outage" province="hanoi"]

**Add Meta Description:**

```
Lịch nghỉ Tết 2025 chính thức từ Chính phủ. Cập nhật liên tục, minh bạch nguồn gốc.
```

**Target Keywords:**

- "lịch nghỉ 2025"
- "phim chiếu rạp tháng 12"
- "lịch thi đấu V.League"
- "lịch cắt điện Hà Nội"

### 2. Monetization với AdSense

**Best Placements:**

1. Above the fold (trên shortcode)
2. Between events (sau mỗi 5 events)
3. Sidebar

**High RPM Keywords:**

- Lịch nghỉ lễ
- Lịch chiếu phim
- Lịch thi đấu

### 3. Social Sharing

Thêm social share buttons cho events:

```html
<div class="social-share">
  <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(get_permalink()); ?>">
    Share Facebook
  </a>
</div>
```

### 4. Email Notifications (Custom)

Tạo custom function để gửi email khi có event mới:

```php
add_action('vtncalendar_crawl_movies', function() {
  // Logic gửi email cho subscribers
});
```

### 5. Widget Integration

Tạo widget hiển thị upcoming events trong sidebar:

```php
// Trong functions.php của theme
function vtncal_upcoming_widget() {
  echo do_shortcode('[vtncalendar limit="5"]');
}

// Register widget
```

### 6. Cache Optimization

Nếu dùng caching plugin (WP Super Cache, W3 Total Cache):

1. **Exclude** admin pages khỏi cache
2. **Cache** frontend shortcode pages
3. **Set TTL**: 1 hour (events update 2 times/day)

### 7. Mobile App Deep Links

Link đến apps:

```html
<!-- CGV App -->
<a href="cgv://movie/12345">Đặt vé CGV</a>

<!-- Calendar App -->
<a href="data:text/calendar;charset=utf8,<?php echo $ical_data; ?>">
  Thêm vào Calendar
</a>
```

### 8. Analytics Tracking

Track shortcode views:

```php
add_action('vtncalendar_shortcode_render', function($type) {
  // Google Analytics event
  echo "<script>
    gtag('event', 'calendar_view', {
      'event_category': 'VTN Calendar',
      'event_label': '{$type}'
    });
  </script>";
});
```

### 9. User Favorites (Future)

Cho phép users save favorite events:

- Cần user accounts
- Lưu vào user meta
- Hiển thị "My Calendar"

### 10. API Endpoint (Future)

Expose data qua REST API:

```
GET /wp-json/vtncal/v1/events?type=movie
```

---

## FAQ

### Q1: Tôi có thể crawl thêm nguồn khác không?

**A**: Có! Edit hàm `crawl_movies()` và thêm nguồn mới:

```php
$sources = array(
  'cgv' => array(...),
  'galaxy' => array(...),
  'bhd' => array(
    'name' => 'BHD Star',
    'url' => 'https://bhd.com.vn',
    'parser' => 'parse_movies_bhd'
  )
);
```

Sau đó implement hàm `parse_movies_bhd()`.

### Q2: Tôi có thể thay đổi tần suất crawl không?

**A**: Có! Edit hàm `setup_cron_jobs()`:

```php
// Thay vì 'twicedaily' (2 lần/ngày)
wp_schedule_event(..., 'hourly', 'vtncalendar_crawl_movies');
// Thành 'hourly' (mỗi giờ)
```

### Q3: Làm sao để backup data?

**A**: Export database tables:

```bash
mysqldump -u user -p database_name wp_vtncalendar_events > backup.sql
```

Hoặc dùng phpMyAdmin → Export.

### Q4: Plugin có multilingual không?

**A**: Hiện tại chỉ tiếng Việt. Để add English:

1. Dùng plugin WPML hoặc Polylang
2. Translate strings
3. Create separate pages cho mỗi ngôn ngữ

### Q5: Tôi có thể customize event card design không?

**A**: Có! Override CSS trong theme hoặc edit hàm `render_event_card()` trong plugin.

---

## Changelog

### Version 1.0.0 (2024-12-20)

- ✅ Initial release
- ✅ 4 crawl modules: holiday, movie, football, power
- ✅ Admin interface với 5 pages
- ✅ Shortcode system
- ✅ Claude API integration
- ✅ Auto cleanup
- ✅ Responsive design

### Coming Soon (v1.1.0)

- [ ] Calendar view (full month grid)
- [ ] Filter UI trong Calendar Manager
- [ ] Export to CSV
- [ ] Import sample data
- [ ] Email notifications
- [ ] More cinema chains

---

## Liên Hệ & Support

- **GitHub**: https://github.com/dinhnguyen1890/vtn-api
- **Issues**: https://github.com/dinhnguyen1890/vtn-api/issues
- **Email**: support@vtncalendar.com

---

**Happy Calendaring! 📅**
