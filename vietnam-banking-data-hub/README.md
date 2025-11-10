# Vietnam Banking Data Hub - Plugin WordPress

## Giới thiệu

**Vietnam Banking Data Hub** là plugin WordPress mạnh mẽ giúp bạn tự động tổng hợp và cập nhật dữ liệu ngân hàng Việt Nam bao gồm:
- 💰 Lãi suất tiết kiệm, vay, thẻ tín dụng
- 💱 Tỷ giá ngoại tệ (USD, EUR, GBP, JPY, v.v.)
- 🤖 Tự động đồng bộ theo lịch
- 📊 Dashboard quản lý trực quan

## Tính năng Phase 1

### ✅ Core Features
- [x] Quản lý danh sách ngân hàng (CRUD)
- [x] Kết nối API BIDV (với mock mode để testing)
- [x] Tự động đồng bộ dữ liệu (cron jobs)
- [x] Cache thông minh (1 giờ)
- [x] API Logs với filters
- [x] Responsive admin interface
- [x] Security best practices

### 📊 Database Tables
- `wp_vn_banks` - Thông tin ngân hàng
- `wp_vn_interest_rates` - Lãi suất
- `wp_vn_exchange_rates` - Tỷ giá
- `wp_vn_api_logs` - Logs API
- `wp_vn_content_generation_queue` - Queue tạo nội dung (chuẩn bị cho Phase 2)

## Yêu cầu Hệ thống

- **WordPress**: 6.4 trở lên
- **PHP**: 8.0 trở lên
- **MySQL**: 8.0 trở lên
- **Quyền**: Administrator

## Cài đặt

### Bước 1: Upload Plugin

1. Tải file `vietnam-banking-data-hub.zip`
2. Đăng nhập WordPress Admin
3. Vào **Plugins** → **Add New** → **Upload Plugin**
4. Chọn file ZIP và nhấn **Install Now**
5. Nhấn **Activate** để kích hoạt plugin

### Bước 2: Kiểm tra Database

Plugin sẽ tự động tạo 5 tables khi activate. Kiểm tra bằng cách:

1. Vào phpMyAdmin
2. Chọn database của WordPress
3. Tìm các tables bắt đầu với `wp_vn_`

Nếu không thấy tables, vui lòng:
- Kiểm tra quyền user database
- Xem error logs tại `wp-content/debug.log`

### Bước 3: Cấu hình Ban đầu

1. Vào **Banking Data** trong WordPress admin
2. Tab **Quản lý Ngân hàng**: Thêm ngân hàng đầu tiên
3. Tab **Đồng bộ dữ liệu**: Chạy sync thủ công lần đầu
4. Kiểm tra **API Logs** để xem kết quả

## Hướng dẫn Sử dụng

### 1. Thêm Ngân hàng Mới

**Banking Data** → **Quản lý Ngân hàng**

1. Điền các thông tin:
   - **Mã Ngân hàng**: VD: `BIDV` (không thể đổi sau khi tạo)
   - **Tên Ngân hàng**: VD: `Ngân hàng TMCP Đầu tư và Phát triển Việt Nam`
   - **API Type**: Chọn `BIDV` (hiện tại chỉ hỗ trợ BIDV mock)
   - **Trạng thái**: Bật để ngân hàng hoạt động

2. Nhấn **Thêm Ngân hàng**

3. Ngân hàng sẽ xuất hiện trong danh sách bên phải

### 2. Đồng bộ Dữ liệu

#### Đồng bộ Thủ công

**Banking Data** → **Đồng bộ dữ liệu**

1. Chọn loại dữ liệu cần đồng bộ:
   - **Cả hai**: Tỷ giá + Lãi suất
   - **Chỉ Tỷ giá**: Nhanh hơn
   - **Chỉ Lãi suất**: Ít thay đổi

2. Nhấn **Đồng bộ ngay**

3. Chờ 10-30 giây (tùy số lượng ngân hàng)

4. Xem kết quả trong **API Logs**

#### Đồng bộ Tự động (Cron Jobs)

Plugin tự động chạy:
- **Tỷ giá**: Mỗi 1 giờ
- **Lãi suất**: Mỗi 6 giờ

Kiểm tra lịch chạy trong tab **Đồng bộ dữ liệu**.

**Lưu ý**: Cron jobs chỉ chạy nếu WordPress Cron được kích hoạt. Nếu site có ít traffic, cân nhắc setup server cron:

```bash
# Thêm vào crontab
*/15 * * * * wget -q -O - https://yoursite.com/wp-cron.php?doing_wp_cron >/dev/null 2>&1
```

### 3. Xem API Logs

**Banking Data** → **API Logs**

Bạn có thể:
- Xem tất cả API calls
- Filter theo Ngân hàng
- Filter theo Trạng thái (Success/Error/Timeout)
- Xem thống kê 7 ngày gần nhất

### 4. Sử dụng Dữ liệu trong Code

#### Lấy danh sách ngân hàng

```php
$banks = VBDH_Bank::get_all(true); // Chỉ active banks

foreach ($banks as $bank) {
    echo $bank->bank_name . '<br>';
}
```

#### Lấy lãi suất tiết kiệm

```php
$rates = VBDH_Interest_Rate::get_by_bank($bank_id, 'savings', 10);

foreach ($rates as $rate) {
    echo $rate->product_name . ': ' . $rate->interest_rate . '%<br>';
}
```

#### Lấy tỷ giá mới nhất

```php
$exchange_rates = VBDH_Exchange_Rate::get_latest('USD', 10);

foreach ($exchange_rates as $rate) {
    echo $rate->bank_name . ': ';
    echo 'Mua ' . $rate->buy_rate . ' - Bán ' . $rate->sell_rate . '<br>';
}
```

#### So sánh lãi suất giữa các ngân hàng

```php
// So sánh lãi suất tiết kiệm kỳ hạn 12 tháng
$comparison = VBDH_Interest_Rate::compare_banks('savings', 12);

foreach ($comparison as $rate) {
    echo $rate->bank_name . ': ' . $rate->interest_rate . '%<br>';
}
```

## Mock Mode (Testing)

Hiện tại BIDV API đang chạy ở **mock mode** - trả về dữ liệu giả để testing. Dữ liệu bao gồm:

### Mock Interest Rates
- Tiết kiệm: 0 tháng, 1, 3, 6, 12, 24 tháng
- Vay: Tiêu dùng, Mua nhà
- Thẻ tín dụng: Visa, Mastercard

### Mock Exchange Rates
- USD, EUR, GBP, JPY, AUD, CNY, THB
- Có đầy đủ: Mua/Bán/Chuyển khoản

Để tắt mock mode (khi có API thật):

```php
// File: includes/api/class-vbdh-api-bidv.php
// Line ~13
private $mock_mode = false; // Đổi thành false
```

## Troubleshooting

### Plugin không tạo tables sau khi activate

**Nguyên nhân**: Quyền database hoặc collation không đúng

**Giải pháp**:
1. Kiểm tra quyền user database (phải có CREATE TABLE)
2. Kiểm tra collation: plugin yêu cầu `utf8mb4_unicode_ci`
3. Deactivate và activate lại plugin
4. Kiểm tra `wp-content/debug.log`

### Cron jobs không chạy

**Nguyên nhân**: WordPress Cron bị disable hoặc site không có traffic

**Giải pháp**:
1. Kiểm tra `wp-config.php`:
   ```php
   // Không được có dòng này
   define('DISABLE_WP_CRON', true);
   ```

2. Setup server cron thay thế (recommended cho production):
   ```bash
   # Chạy mỗi 15 phút
   */15 * * * * wget -q -O - https://yoursite.com/wp-cron.php?doing_wp_cron
   ```

3. Test cron thủ công:
   ```php
   // Thêm vào functions.php tạm thời
   VBDH_Cron::manual_trigger('both');
   ```

### API Logs hiển thị toàn errors

**Nguyên nhân**:
- Mock mode chưa bật
- API credentials sai (nếu dùng API thật)
- Network issues

**Giải pháp**:
1. Kiểm tra mock mode trong `class-vbdh-api-bidv.php`
2. Xem chi tiết error trong API Logs
3. Test connectivity: `curl https://api.bank.com`

### Cache không update

**Giải pháp**:
1. Clear cache thủ công:
   ```php
   $fetcher = new VBDH_Data_Fetcher();
   $fetcher->clear_cache(); // Clear all
   $fetcher->clear_cache($bank_id); // Clear specific bank
   ```

2. Force fetch (bỏ qua cache):
   ```php
   $fetcher->force_fetch($bank_id, 'both');
   ```

## Sample Data

Plugin đi kèm file `sample-data.sql` với dữ liệu mẫu:
- 4 ngân hàng: BIDV, VPBank, Techcombank, Vietcombank
- Lãi suất tiết kiệm/vay
- Tỷ giá 5-7 loại ngoại tệ
- API logs mẫu

**Cách import**:
1. Mở phpMyAdmin
2. Chọn database WordPress
3. Tab **Import**
4. Chọn file `sample-data.sql`
5. Nhấn **Go**

## Security

Plugin đã implement:
- ✅ Nonce verification cho forms
- ✅ Capability checks (manage_options)
- ✅ Input sanitization
- ✅ Output escaping
- ✅ Prepared SQL statements
- ✅ Credentials encryption (base64 - nên nâng cấp production)

**Recommendations cho Production**:
- Dùng encryption mạnh hơn cho API credentials (VD: openssl)
- Setup SSL/TLS cho API endpoints
- Rate limiting
- IP whitelist

## Roadmap

### Phase 2 (Upcoming)
- [ ] Hỗ trợ thêm APIs: VPBank, Techcombank, Vietcombank
- [ ] AI Content Generation (ChatGPT/Claude)
- [ ] Shortcodes hiển thị dữ liệu
- [ ] Widgets cho sidebar
- [ ] REST API endpoints
- [ ] Notification khi dữ liệu thay đổi

### Phase 3
- [ ] Multi-language support
- [ ] Advanced analytics
- [ ] Export dữ liệu (CSV, Excel)
- [ ] Comparison tools
- [ ] Email reports

## Support

Nếu bạn gặp vấn đề:

1. Đọc **Troubleshooting** section
2. Kiểm tra `wp-content/debug.log`
3. Xem **API Logs** trong plugin
4. Tạo issue trên GitHub (nếu có)

## Credits

- Developed by: Vietnam Banking Data Hub Team
- Version: 1.0.0
- License: GPL v2 or later

## Changelog

### 1.0.0 (2025-11-10)
- Initial release
- BIDV API connector (mock mode)
- Database schema (5 tables)
- Admin interface (3 tabs)
- Cron jobs (hourly + 6-hourly)
- API logging system
- Cache system (transients)
- Sample data
