# VTN Calendar - Hướng Dẫn Cài Đặt Chi Tiết

## Mục Lục

1. [Yêu Cầu Hệ Thống](#yêu-cầu-hệ-thống)
2. [Cài Đặt Plugin](#cài-đặt-plugin)
3. [Cấu Hình Sau Cài Đặt](#cấu-hình-sau-cài-đặt)
4. [Thiết Lập Cron Jobs](#thiết-lập-cron-jobs)
5. [Cấu Hình Claude API](#cấu-hình-claude-api)
6. [Test & Verify](#test--verify)
7. [Troubleshooting](#troubleshooting)

---

## Yêu Cầu Hệ Thống

### Minimum Requirements

- **WordPress**: 5.8 hoặc cao hơn
- **PHP**: 7.4 hoặc cao hơn
- **MySQL**: 5.6 hoặc cao hơn
- **WordPress Cron**: Enabled
- **PHP Extensions**:
  - `json` (thường có sẵn)
  - `curl` (cho wp_remote_get)
  - `dom` (cho HTML parsing)

### Recommended Requirements

- **WordPress**: 6.0+
- **PHP**: 8.0+
- **MySQL**: 8.0+
- **Server**: Apache hoặc Nginx
- **Memory Limit**: 128MB+

### Kiểm Tra Requirements

1. Đăng nhập WordPress Admin
2. Vào **Tools → Site Health**
3. Click tab **Info**
4. Kiểm tra:
   - WordPress Version
   - PHP Version
   - Database Version

---

## Cài Đặt Plugin

### Phương Pháp 1: Upload Qua WordPress Admin (Khuyến Nghị)

#### Bước 1: Chuẩn Bị File

1. Tải file `vtncalendar.php` về máy
2. (Tùy chọn) Nén thành ZIP:
   ```bash
   # Tạo folder
   mkdir vtncalendar

   # Copy file vào folder
   cp vtncalendar.php vtncalendar/

   # Nén thành ZIP
   zip -r vtncalendar.zip vtncalendar/
   ```

#### Bước 2: Upload

1. Đăng nhập WordPress Admin
2. Vào **Plugins → Add New**
3. Click **Upload Plugin**
4. Click **Choose File** → chọn file ZIP (hoặc PHP)
5. Click **Install Now**
6. Đợi upload hoàn tất

#### Bước 3: Activate

1. Sau khi install xong, click **Activate Plugin**
2. Hoặc vào **Plugins → Installed Plugins** → tìm "VTN Calendar" → click **Activate**

### Phương Pháp 2: Upload Qua FTP

#### Bước 1: Kết Nối FTP

Sử dụng FTP client (FileZilla, WinSCP, etc.):

- **Host**: ftp.your-domain.com
- **Username**: your-ftp-username
- **Password**: your-ftp-password
- **Port**: 21 (hoặc 22 cho SFTP)

#### Bước 2: Upload File

1. Đi đến folder: `/public_html/wp-content/plugins/`
2. Tạo folder mới: `vtncalendar`
3. Upload file `vtncalendar.php` vào folder `vtncalendar/`

Cấu trúc sau khi upload:

```
/wp-content/
  /plugins/
    /vtncalendar/
      vtncalendar.php
```

#### Bước 3: Activate

1. Vào WordPress Admin → **Plugins**
2. Tìm "VTN Calendar"
3. Click **Activate**

### Phương Pháp 3: SSH / Command Line

```bash
# SSH vào server
ssh username@your-server.com

# Đi đến plugins folder
cd /path/to/wordpress/wp-content/plugins

# Tạo folder
mkdir vtncalendar
cd vtncalendar

# Upload file (dùng wget, scp, hoặc git)
wget https://your-url.com/vtncalendar.php
# Hoặc
scp user@local:/path/vtncalendar.php ./

# Set permissions
chmod 644 vtncalendar.php

# Activate qua WP-CLI (nếu có)
wp plugin activate vtncalendar
```

---

## Cấu Hình Sau Cài Đặt

### Bước 1: Verify Database Tables

Sau khi activate, plugin tự động tạo 3 tables:

- `wp_vtncalendar_events`
- `wp_vtncalendar_logs`
- `wp_vtncalendar_settings`

**Kiểm tra:**

1. Vào **phpMyAdmin** (hoặc database tool)
2. Chọn database của WordPress
3. Xem danh sách tables, tìm 3 tables trên

**Nếu không thấy tables:**

1. Deactivate plugin
2. Activate lại
3. Nếu vẫn không được, check error logs

### Bước 2: Cấu Hình Crawl Settings

1. Vào **VTN Calendar → Crawl Settings**

2. **Claude API** (Tùy chọn):
   - Nhập API Key (nếu có)
   - Tick ✅ "Bật AI Content Generation"

3. **Crawl Modules**:
   - ✅ Bật crawl Lịch Nghỉ Lễ
   - ✅ Bật crawl Lịch Chiếu Phim
   - Chọn nguồn: CGV, Galaxy, Lotte
   - ✅ Bật crawl Lịch Bóng Đá
   - ✅ Bật crawl Lịch Cắt Điện
   - Chọn tỉnh/thành: Hà Nội, TP.HCM, Đà Nẵng, etc.

4. **Auto Cleanup**:
   - ✅ Tự động xóa sự kiện cũ
   - Xóa sau: 30 ngày (hoặc tùy chỉnh)

5. Click **Lưu cài đặt**

### Bước 3: Cấu Hình Display Settings

1. Vào **VTN Calendar → Display Settings**

2. **Default View**: List (hoặc Calendar, Grid)

3. **Items per page**: 20 (hoặc tùy chỉnh)

4. **Date Format**: d/m/Y (31/12/2024)

5. **Show/Hide**:
   - ✅ Hiển thị link nguồn
   - ✅ Hiển thị AI content

6. Click **Lưu cài đặt**

---

## Thiết Lập Cron Jobs

Plugin sử dụng WordPress Cron (WP-Cron) để tự động crawl.

### Kiểm Tra WP-Cron

**Cách 1: Qua Plugin**

1. Install plugin **WP Crontrol**
2. Vào **Tools → Cron Events**
3. Tìm các events:
   - `vtncalendar_crawl_holiday`
   - `vtncalendar_crawl_movies`
   - `vtncalendar_crawl_football`
   - `vtncalendar_crawl_power`
   - `vtncalendar_cleanup_expired`

**Cách 2: Code Check**

Thêm vào `functions.php`:

```php
add_action('admin_notices', function() {
    $next_holiday = wp_next_scheduled('vtncalendar_crawl_holiday');
    $next_movies = wp_next_scheduled('vtncalendar_crawl_movies');

    echo '<div class="notice notice-info">';
    echo '<p>Next Holiday Crawl: ' . date('Y-m-d H:i:s', $next_holiday) . '</p>';
    echo '<p>Next Movies Crawl: ' . date('Y-m-d H:i:s', $next_movies) . '</p>';
    echo '</div>';
});
```

### Cải Thiện WP-Cron Performance

WordPress Cron chỉ chạy khi có visitor. Để đảm bảo chạy đúng lịch:

**Bước 1: Disable WP-Cron**

Thêm vào `wp-config.php`:

```php
define('DISABLE_WP_CRON', true);
```

**Bước 2: Setup Server Cron**

SSH vào server và edit crontab:

```bash
crontab -e
```

Thêm dòng:

```bash
# WordPress Cron - chạy mỗi 15 phút
*/15 * * * * wget -q -O - https://your-domain.com/wp-cron.php?doing_wp_cron > /dev/null 2>&1
```

Hoặc dùng curl:

```bash
*/15 * * * * curl https://your-domain.com/wp-cron.php?doing_wp_cron > /dev/null 2>&1
```

Hoặc dùng WP-CLI:

```bash
*/15 * * * * cd /path/to/wordpress && wp cron event run --due-now > /dev/null 2>&1
```

**Lưu ý**: Thay `your-domain.com` bằng domain thực của bạn.

---

## Cấu Hình Claude API

### Bước 1: Lấy API Key

1. Truy cập: https://console.anthropic.com/
2. Đăng ký/Đăng nhập
3. Vào **API Keys**
4. Click **Create Key**
5. Đặt tên (ví dụ: "VTN Calendar")
6. Copy API key (bắt đầu bằng `sk-ant-...`)

### Bước 2: Nhập Vào Plugin

1. Vào **VTN Calendar → Crawl Settings**
2. Dán API Key vào field **Claude API Key**
3. Tick ✅ **Bật AI Content Generation**
4. Click **Lưu cài đặt**

### Bước 3: Test AI Generation

1. Vào **VTN Calendar → Dashboard**
2. Click **Test Crawl Phim** (hoặc module khác)
3. Vào **Calendar Manager**
4. Xem events, check xem có AI content không

### Chi Phí API

- **Model**: claude-sonnet-4-20250514
- **Cost**: ~$3 per 1M input tokens, ~$15 per 1M output tokens
- **Estimate**:
  - 1 event = ~150 tokens output = ~$0.00225
  - 1000 events = ~$2.25

**Tips tiết kiệm:**

- Chỉ bật AI cho movies và football (nội dung quan trọng)
- Tắt AI cho holidays và power (nội dung đơn giản, dùng template)
- Set cleanup_days = 30 (không lưu quá lâu)

---

## Test & Verify

### 1. Test Activation

✅ Plugin activated không có lỗi
✅ Không có warning/notice trong admin

### 2. Test Database

✅ 3 tables được tạo
✅ Default settings được insert

Kiểm tra:

```sql
SELECT * FROM wp_vtncalendar_settings;
```

### 3. Test Manual Crawl

1. Vào **VTN Calendar → Dashboard**
2. Click từng nút **Test Crawl**:
   - Test Crawl Lịch Nghỉ
   - Test Crawl Phim
   - Test Crawl Bóng Đá
   - Test Crawl Cắt Điện

3. Xem kết quả trong **Logs & Status**

### 4. Test Frontend Display

1. Tạo page mới: **Test Calendar**
2. Thêm shortcode:

```
[vtncalendar]

[vtncalendar type="movie" view="grid"]

[vtncalendar type="holiday"]
```

3. Publish và xem page
4. Kiểm tra:
   - ✅ Events hiển thị đúng
   - ✅ Responsive trên mobile
   - ✅ CSS load đúng
   - ✅ Link nguồn hoạt động

### 5. Test AJAX

1. Vào **Calendar Manager**
2. Test xóa 1 event
3. Xem có reload page không (AJAX nên không reload)

### 6. Test Cron Schedule

Install plugin **WP Crontrol**:

1. Vào **Tools → Cron Events**
2. Tìm event: `vtncalendar_crawl_movies`
3. Click **Run Now**
4. Vào **Logs & Status** xem kết quả

---

## Troubleshooting

### Lỗi 1: Plugin Activation Failed

**Triệu chứng**: Lỗi khi activate

**Nguyên nhân**:
- PHP version thấp
- Memory limit thấp
- Syntax error

**Giải pháp**:

1. Check PHP version:
```php
<?php phpinfo(); ?>
```

2. Tăng memory limit trong `wp-config.php`:
```php
define('WP_MEMORY_LIMIT', '256M');
```

3. Check error logs:
```bash
tail -f /path/to/wordpress/wp-content/debug.log
```

### Lỗi 2: Database Tables Not Created

**Triệu chứng**: Tables không được tạo sau khi activate

**Giải pháp**:

1. Deactivate plugin
2. Delete plugin
3. Re-upload và activate lại

Hoặc chạy SQL manually:

```sql
-- Copy SQL từ hàm create_tables() trong vtncalendar.php
-- Chạy trong phpMyAdmin
```

### Lỗi 3: Crawl Không Hoạt Động

**Triệu chứng**: Test crawl fail, không có data

**Nguyên nhân**:
- Website nguồn chặn IP
- Network timeout
- HTML structure thay đổi

**Giải pháp**:

1. Xem **Logs & Status** để biết error message
2. Test kết nối:
```php
$response = wp_remote_get('https://www.cgv.vn');
var_dump($response);
```

3. Tăng timeout trong code (nếu cần)

### Lỗi 4: Cron Jobs Không Chạy

**Triệu chứng**: Không tự động crawl

**Giải pháp**:

1. Check WP-Cron hoạt động:
   - Install **WP Crontrol**
   - Xem danh sách events

2. Nếu WP-Cron không chạy:
   - Setup server cron (xem phần trên)

3. Test manual:
```bash
wget https://your-domain.com/wp-cron.php?doing_wp_cron
```

### Lỗi 5: Frontend Display Broken

**Triệu chứng**: CSS/JS không load, hiển thị lỗi

**Giải pháp**:

1. Clear cache:
   - Browser cache
   - WordPress cache (nếu dùng caching plugin)
   - CDN cache (nếu có)

2. Regenerate permalinks:
   - Vào **Settings → Permalinks**
   - Click **Save Changes**

3. Check console errors (F12 trong browser)

### Lỗi 6: Claude API Errors

**Triệu chứng**: AI content không được tạo

**Nguyên nhân**:
- API key sai
- Hết credit
- Rate limit

**Giải pháp**:

1. Verify API key:
   - Login https://console.anthropic.com/
   - Check API key còn hoạt động

2. Check credit balance

3. Xem error message trong **Logs & Status**

4. Tạm thời tắt AI:
   - Vào **Crawl Settings**
   - Uncheck "Bật AI Content Generation"
   - Plugin sẽ dùng template fallback

---

## Production Checklist

Trước khi deploy lên production:

- [ ] Test trên staging environment trước
- [ ] Backup database
- [ ] Test all 4 crawl modules
- [ ] Verify cron jobs scheduled
- [ ] Test frontend display trên nhiều devices
- [ ] Setup monitoring (Logs & Status)
- [ ] Configure auto cleanup (để tránh database quá lớn)
- [ ] (Tùy chọn) Setup Claude API với production key
- [ ] (Khuyến nghị) Setup server cron thay WP-Cron
- [ ] Test performance với large dataset

---

## Tối Ưu Performance

### 1. Database Indexes

Plugin đã include indexes, nhưng nếu cần thêm:

```sql
CREATE INDEX idx_custom ON wp_vtncalendar_events(calendar_type, province, event_date);
```

### 2. Caching

Plugin dùng WordPress Transients (1 hour cache).

Để tăng cache time, edit code:

```php
// Tìm dòng này và thay 3600 = 1 hour
set_transient('vtncal_cache_' . $type, $data, 3600);

// Thành 7200 = 2 hours
set_transient('vtncal_cache_' . $type, $data, 7200);
```

### 3. Object Caching

Nếu server hỗ trợ Redis/Memcached:

1. Install object cache plugin
2. Plugin sẽ tự động faster

### 4. CDN

- Enable CDN cho images (posters phim)
- Cache static assets

---

## Hỗ Trợ

Nếu gặp vấn đề không giải quyết được:

1. Check **Logs & Status** trước
2. Enable WordPress Debug:
```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

3. Report issue: https://github.com/dinhnguyen1890/vtn-api/issues

---

**Chúc bạn cài đặt thành công! 🎉**
