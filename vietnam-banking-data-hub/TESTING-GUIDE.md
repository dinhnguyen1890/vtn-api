# Hướng dẫn Test Plugin - Vietnam Banking Data Hub

Tài liệu này hướng dẫn bạn từng bước để test plugin một cách đầy đủ.

## 📋 Checklist Test

### ✅ Phase 1: Installation Testing

- [ ] Upload và activate plugin thành công
- [ ] Database tables được tạo
- [ ] Không có PHP errors
- [ ] Menu xuất hiện trong admin

### ✅ Phase 2: Banks Management Testing

- [ ] Thêm bank mới thành công
- [ ] Edit bank hoạt động
- [ ] Delete bank hoạt động
- [ ] Validation hoạt động (required fields)

### ✅ Phase 3: Data Sync Testing

- [ ] Manual sync hoạt động
- [ ] Data được lưu vào database
- [ ] Cache hoạt động đúng
- [ ] API logs được tạo

### ✅ Phase 4: Cron Jobs Testing

- [ ] Cron jobs được đăng ký
- [ ] Schedule hiển thị chính xác
- [ ] Manual trigger hoạt động

### ✅ Phase 5: UI/UX Testing

- [ ] Tabs chuyển đổi mượt
- [ ] Responsive trên mobile
- [ ] Buttons hoạt động
- [ ] AJAX calls không có errors

---

## 🧪 Chi tiết từng Test Case

### TEST 1: Plugin Installation

**Mục đích**: Đảm bảo plugin cài đặt và activate không lỗi

**Các bước**:

1. **Upload plugin**
   - Vào **Plugins** → **Add New** → **Upload Plugin**
   - Chọn file `vietnam-banking-data-hub.zip`
   - Nhấn **Install Now**

   **Kết quả mong đợi**:
   - ✅ Upload thành công
   - ✅ Hiển thị "Plugin installed successfully"

2. **Activate plugin**
   - Nhấn nút **Activate**

   **Kết quả mong đợi**:
   - ✅ Plugin kích hoạt không lỗi
   - ✅ Không có PHP warnings/errors
   - ✅ Redirect về Plugins page

3. **Kiểm tra menu**
   - Xem sidebar WordPress admin

   **Kết quả mong đợi**:
   - ✅ Menu **Banking Data** xuất hiện
   - ✅ Icon hiển thị đúng (chart-line)

4. **Kiểm tra database tables**
   - Vào phpMyAdmin
   - Chọn database WordPress
   - Xem list tables

   **Kết quả mong đợi**:
   - ✅ `wp_vn_banks` tồn tại
   - ✅ `wp_vn_interest_rates` tồn tại
   - ✅ `wp_vn_exchange_rates` tồn tại
   - ✅ `wp_vn_api_logs` tồn tại
   - ✅ `wp_vn_content_generation_queue` tồn tại

5. **Kiểm tra table structure**
   - Click vào table `wp_vn_banks`
   - Xem tab **Structure**

   **Kết quả mong đợi**:
   - ✅ Có đầy đủ columns: id, bank_code, bank_name, bank_name_en, bank_logo_url, api_endpoint, api_type, api_credentials, is_active, last_sync, created_at, updated_at
   - ✅ bank_code có UNIQUE index
   - ✅ Collation là utf8mb4_unicode_ci

---

### TEST 2: Banks Management

**Mục đích**: Test CRUD operations cho banks

#### Test 2.1: Add Bank

**Các bước**:

1. Vào **Banking Data** → **Quản lý Ngân hàng**
2. Điền form:
   - **Mã Ngân hàng**: `BIDV`
   - **Tên Ngân hàng**: `Ngân hàng TMCP Đầu tư và Phát triển Việt Nam`
   - **Tên tiếng Anh**: `Bank for Investment and Development of Vietnam`
   - **API Type**: Chọn `BIDV`
   - Tick **Kích hoạt ngân hàng này**
3. Nhấn **Thêm Ngân hàng**

**Kết quả mong đợi**:
- ✅ Page reload
- ✅ Hiển thị success message: "Ngân hàng đã được thêm thành công!"
- ✅ BIDV xuất hiện trong bảng danh sách bên phải
- ✅ Status là "Hoạt động"
- ✅ Loại API hiển thị badge "BIDV"

**Kiểm tra database**:
```sql
SELECT * FROM wp_vn_banks WHERE bank_code = 'BIDV';
```
- ✅ Record tồn tại
- ✅ is_active = 1

#### Test 2.2: Validation

**Các bước**:

1. Thử thêm bank mà không điền **Mã Ngân hàng**
2. Nhấn **Thêm Ngân hàng**

**Kết quả mong đợi**:
- ✅ Browser validation xuất hiện
- ✅ Không cho submit form
- ✅ Hiển thị "Please fill out this field"

#### Test 2.3: Duplicate Bank Code

**Các bước**:

1. Thêm bank với code `BIDV` (đã tồn tại)
2. Nhấn submit

**Kết quả mong đợi**:
- ✅ Database error (duplicate entry)
- ✅ Hiển thị error message

#### Test 2.4: Edit Bank

**Các bước**:

1. Nhấn nút **Sửa** bên cạnh BIDV
2. Form được fill sẵn data
3. Thay đổi **Tên Ngân hàng** thành `BIDV - Updated`
4. Nhấn **Cập nhật**

**Kết quả mong đợi**:
- ✅ Page reload
- ✅ Success message: "Ngân hàng đã được cập nhật!"
- ✅ Tên mới hiển thị trong bảng
- ✅ Mã ngân hàng KHÔNG thay đổi được (readonly)

#### Test 2.5: Sync Single Bank (AJAX)

**Các bước**:

1. Nhấn nút **Sync** bên cạnh BIDV
2. Confirm dialog xuất hiện
3. Nhấn OK

**Kết quả mong đợi**:
- ✅ Button disable và text đổi thành "Đang đồng bộ..."
- ✅ Sau 2-3 giây hiển thị success message
- ✅ Page reload tự động
- ✅ Cột "Sync cuối" cập nhật (VD: "2 giây trước")

**Check console (F12)**:
- ✅ Không có JavaScript errors
- ✅ AJAX request thành công (200 OK)
- ✅ Response có data

#### Test 2.6: Delete Bank (AJAX)

**Các bước**:

1. Thêm bank test: code `TEST`, name `Test Bank`
2. Nhấn nút **Xóa** màu đỏ
3. Confirm dialog xuất hiện
4. Nhấn OK

**Kết quả mong đợi**:
- ✅ Row fade out trong 300ms
- ✅ Row biến mất khỏi table
- ✅ Success message hiển thị
- ✅ Database không còn record

---

### TEST 3: Data Synchronization

**Mục đích**: Test việc fetch và lưu data

#### Test 3.1: Manual Sync - Both

**Các bước**:

1. Vào **Banking Data** → **Đồng bộ dữ liệu**
2. Đảm bảo có ít nhất 1 bank active
3. Chọn **Cả hai (Tỷ giá + Lãi suất)**
4. Nhấn **Đồng bộ ngay**

**Kết quả mong đợi**:
- ✅ Page reload sau vài giây
- ✅ Success message: "Đồng bộ thành công!"

**Kiểm tra database**:

```sql
-- Check interest rates
SELECT COUNT(*) FROM wp_vn_interest_rates WHERE bank_id = 1;
-- Kết quả mong đợi: > 0 (VD: 10 records cho BIDV mock)

-- Check exchange rates
SELECT COUNT(*) FROM wp_vn_exchange_rates WHERE bank_id = 1;
-- Kết quả mong đợi: > 0 (VD: 7 records cho BIDV mock)

-- Check API logs
SELECT * FROM wp_vn_api_logs ORDER BY created_at DESC LIMIT 5;
-- Kết quả mong đợi: Có logs mới với status = 'success'
```

#### Test 3.2: Manual Sync - Only Exchange Rates

**Các bước**:

1. Xóa hết exchange rates cũ:
   ```sql
   DELETE FROM wp_vn_exchange_rates WHERE bank_id = 1;
   ```
2. Chọn **Chỉ Tỷ giá**
3. Nhấn **Đồng bộ ngay**

**Kết quả mong đợi**:
- ✅ Chỉ exchange rates được tạo mới
- ✅ Interest rates KHÔNG thay đổi

#### Test 3.3: Cache Functionality

**Các bước**:

1. Sync bank BIDV lần đầu
2. Check execution time trong API logs (VD: 1.5s)
3. Ngay lập tức sync BIDV lần 2 (trong vòng 1 giờ)
4. Check logs

**Kết quả mong đợi**:
- ✅ Lần 2 KHÔNG tạo API call mới
- ✅ Data được lấy từ cache (transient)
- ✅ Nhanh hơn lần 1

**Verify cache**:
```php
// Thêm vào functions.php tạm thời
$cached = get_transient('vbdh_bank_1_both');
var_dump($cached);
```
- ✅ Có data trong cache

#### Test 3.4: Force Fetch (Bypass Cache)

**Các bước**:

1. Vào **Quản lý Ngân hàng**
2. Nhấn **Sync** button (AJAX sync)
3. Check logs

**Kết quả mong đợi**:
- ✅ API call mới được tạo
- ✅ Cache được clear
- ✅ Data fresh từ API

---

### TEST 4: API Logs

**Mục đích**: Test log viewer và filters

#### Test 4.1: View Logs

**Các bước**:

1. Vào **Banking Data** → **API Logs**
2. Xem statistics boxes ở đầu trang

**Kết quả mong đợi**:
- ✅ Hiển thị 4 stat boxes:
  - Tổng API Calls
  - Thành công (với % màu xanh)
  - Lỗi
  - Thời gian trung bình
- ✅ Numbers chính xác với data trong database

#### Test 4.2: Filter by Bank

**Các bước**:

1. Dropdown **Ngân hàng**: Chọn BIDV
2. Nhấn **Lọc**

**Kết quả mong đợi**:
- ✅ Chỉ hiển thị logs của BIDV
- ✅ URL có `?filter_bank=1`
- ✅ Dropdown giữ nguyên selected value

#### Test 4.3: Filter by Status

**Các bước**:

1. Dropdown **Trạng thái**: Chọn **Thành công**
2. Nhấn **Lọc**

**Kết quả mong đợi**:
- ✅ Chỉ hiển thị logs có status = success
- ✅ Badge màu xanh "Thành công"

#### Test 4.4: Clear Filters

**Các bước**:

1. Apply filters (bank + status)
2. Nhấn **Xóa bộ lọc**

**Kết quả mong đợi**:
- ✅ Quay về hiển thị tất cả logs
- ✅ Dropdowns reset về "Tất cả"

#### Test 4.5: Pagination

**Các bước**:

1. Đảm bảo có > 50 logs (chạy sync nhiều lần)
2. Xem pagination links dưới table

**Kết quả mong đợi**:
- ✅ Hiển thị số trang
- ✅ Click page 2 hoạt động
- ✅ URL có `?paged=2`

---

### TEST 5: Cron Jobs

**Mục đích**: Test scheduled tasks

#### Test 5.1: Check Cron Registration

**Các bước**:

1. Vào **Đồng bộ dữ liệu** tab
2. Xem 2 schedule boxes

**Kết quả mong đợi**:
- ✅ **Tỷ giá Ngoại tệ**:
  - Tần suất: Mỗi 1 giờ
  - Lần chạy tiếp theo: Có datetime
  - Trạng thái: "Chờ lịch tiếp theo"
- ✅ **Lãi suất**:
  - Tần suất: Mỗi 6 giờ
  - Lần chạy tiếp theo: Có datetime

**Verify trong database**:

```sql
SELECT * FROM wp_options WHERE option_name LIKE '%vbdh%cron%';
```
- ✅ Có các options lưu cron info

**Verify WP Cron**:

Install plugin "WP Crontrol" để xem:
- ✅ `vbdh_fetch_exchange_rates` có schedule `hourly`
- ✅ `vbdh_fetch_interest_rates` có schedule `vbdh_every_six_hours`

#### Test 5.2: Manual Trigger Cron

**Các bước**:

Thêm code vào `functions.php` tạm thời:

```php
add_action('init', function() {
    if (isset($_GET['test_cron'])) {
        $cron = new VBDH_Cron();
        $result = $cron->fetch_exchange_rates();
        var_dump($result);
        die();
    }
});
```

Truy cập: `https://yoursite.com/?test_cron`

**Kết quả mong đợi**:
- ✅ Hiển thị array result
- ✅ `success` = true
- ✅ `total_banks` = số banks active
- ✅ Data mới trong database

#### Test 5.3: Cron Cleanup

**Các bước**:

1. Insert data cũ vào database:
   ```sql
   INSERT INTO wp_vn_api_logs (bank_id, api_endpoint, status, created_at)
   VALUES (1, 'test', 'success', DATE_SUB(NOW(), INTERVAL 35 DAY));
   ```

2. Trigger cron interest_rates (có cleanup):
   ```php
   $cron = new VBDH_Cron();
   $cron->fetch_interest_rates();
   ```

3. Check database:
   ```sql
   SELECT COUNT(*) FROM wp_vn_api_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY);
   ```

**Kết quả mong đợi**:
- ✅ Logs cũ hơn 30 ngày đã bị xóa
- ✅ Count = 0

---

### TEST 6: UI/UX Testing

**Mục đích**: Test responsive và user experience

#### Test 6.1: Tabs Navigation

**Các bước**:

1. Vào Banking Data
2. Click từng tab: Quản lý Ngân hàng → Đồng bộ → API Logs → Quản lý Ngân hàng

**Kết quả mong đợi**:
- ✅ Tabs chuyển đổi không reload page
- ✅ URL thay đổi (`?tab=banks`, `?tab=sync`, `?tab=logs`)
- ✅ Active tab có class `nav-tab-active`
- ✅ Content thay đổi tương ứng

#### Test 6.2: Responsive Mobile

**Các bước**:

1. Mở Chrome DevTools (F12)
2. Toggle device toolbar (Ctrl+Shift+M)
3. Chọn iPhone X
4. Test tất cả tabs

**Kết quả mong đợi**:
- ✅ Grid chuyển thành 1 column
- ✅ Tables có horizontal scroll
- ✅ Buttons không bị che khuất
- ✅ Forms không bị vỡ layout

#### Test 6.3: Browser Compatibility

Test trên:
- ✅ Chrome (latest)
- ✅ Firefox (latest)
- ✅ Safari (latest)
- ✅ Edge (latest)

Tất cả phải hoạt động tốt.

---

### TEST 7: Security Testing

**Mục đích**: Test security measures

#### Test 7.1: Nonce Verification

**Các bước**:

1. Mở form Add Bank
2. View page source (Ctrl+U)
3. Tìm `vbdh_nonce`

**Kết quả mong đợi**:
- ✅ Input hidden với name `vbdh_nonce` tồn tại
- ✅ Value là string random

**Test bypass**:

Dùng Postman/cURL gửi request mà KHÔNG có nonce:

```bash
curl -X POST 'https://yoursite.com/wp-admin/admin.php?page=vbdh-banking-data' \
  -d 'action=add_bank&bank_code=TEST'
```

**Kết quả mong đợi**:
- ✅ Request bị reject
- ✅ Không tạo bank mới

#### Test 7.2: Capability Check

**Các bước**:

1. Logout khỏi admin
2. Login với user role **Subscriber**
3. Truy cập: `/wp-admin/admin.php?page=vbdh-banking-data`

**Kết quả mong đợi**:
- ✅ "You do not have sufficient permissions"
- ✅ Không thấy menu Banking Data

#### Test 7.3: SQL Injection

**Các bước**:

Thử input SQL injection vào **Mã Ngân hàng**:
```
BIDV'; DROP TABLE wp_vn_banks; --
```

**Kết quả mong đợi**:
- ✅ Input được sanitize
- ✅ Table KHÔNG bị drop
- ✅ Bank code được lưu an toàn (hoặc validation fail)

---

### TEST 8: Performance Testing

**Mục đích**: Test performance và optimization

#### Test 8.1: Page Load Time

**Các bước**:

1. Clear cache
2. Open Chrome DevTools → Network tab
3. Load Banking Data page
4. Check load time

**Kết quả mong đợi**:
- ✅ Total load time < 2s
- ✅ CSS/JS files loaded
- ✅ No 404 errors

#### Test 8.2: Database Queries

Install plugin "Query Monitor"

**Các bước**:

1. Activate Query Monitor
2. Load Banking Data page
3. Check queries panel

**Kết quả mong đợi**:
- ✅ Total queries < 50
- ✅ Không có slow queries (> 0.5s)
- ✅ Không có duplicate queries

#### Test 8.3: Cache Hit Rate

**Các bước**:

1. Sync bank lần 1 → ghi log execution time
2. Sync lần 2 ngay lập tức → ghi log
3. So sánh

**Kết quả mong đợi**:
- ✅ Lần 2 nhanh hơn lần 1 đáng kể
- ✅ Không có API call thật ở lần 2

---

## 🐛 Common Issues & Solutions

### Issue 1: Tables không được tạo

**Debug**:
```php
// Thêm vào wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Check `wp-content/debug.log` để xem errors.

### Issue 2: AJAX không hoạt động

**Debug**:
- F12 → Console → Xem errors
- Network tab → Xem request/response
- Check `vbdhAdmin` object có tồn tại không:
  ```js
  console.log(vbdhAdmin);
  ```

### Issue 3: CSS không load

**Debug**:
- Check URL của CSS file: View source → tìm `admin.css`
- Verify file tồn tại: Paste URL vào browser
- Clear browser cache (Ctrl+Shift+R)

---

## ✅ Final Checklist

Trước khi đánh dấu plugin là "ready for production":

- [ ] Tất cả tests đều pass
- [ ] Không có PHP errors/warnings
- [ ] Không có JavaScript errors
- [ ] Database tables structure đúng
- [ ] Cache hoạt động
- [ ] Security measures work
- [ ] Responsive trên mobile
- [ ] Cross-browser compatibility
- [ ] Performance acceptable
- [ ] Documentation đầy đủ

---

## 📊 Test Results Log

Ghi lại kết quả tests của bạn:

| Test Case | Status | Date | Notes |
|-----------|--------|------|-------|
| Installation | ✅ | 2025-11-10 | All tables created |
| Add Bank | ✅ | 2025-11-10 | Working |
| Edit Bank | ✅ | 2025-11-10 | Working |
| Delete Bank | ✅ | 2025-11-10 | AJAX working |
| Manual Sync | ✅ | 2025-11-10 | Mock data populated |
| API Logs | ✅ | 2025-11-10 | Filters working |
| Cron Jobs | ⏳ | - | Need live WP Cron |
| Mobile | ✅ | 2025-11-10 | Responsive |
| Security | ✅ | 2025-11-10 | Nonces working |

---

Chúc bạn test thành công! 🚀
