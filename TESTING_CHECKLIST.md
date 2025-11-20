# VTN Calendar - Testing Checklist

## Mục Lục

1. [Pre-Installation Tests](#pre-installation-tests)
2. [Installation Tests](#installation-tests)
3. [Admin Interface Tests](#admin-interface-tests)
4. [Crawling Function Tests](#crawling-function-tests)
5. [Frontend Display Tests](#frontend-display-tests)
6. [Security Tests](#security-tests)
7. [Performance Tests](#performance-tests)
8. [Compatibility Tests](#compatibility-tests)
9. [Final Checklist](#final-checklist)

---

## Pre-Installation Tests

### Environment Check

- [ ] WordPress version ≥ 5.8
- [ ] PHP version ≥ 7.4
- [ ] MySQL version ≥ 5.6
- [ ] PHP Extensions installed:
  - [ ] `json`
  - [ ] `curl`
  - [ ] `dom`
- [ ] WordPress Debug Mode enabled (for testing)

```php
// Add to wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

- [ ] Check error logs location: `wp-content/debug.log`

### Backup

- [ ] Full database backup created
- [ ] Files backup created (optional, but recommended)
- [ ] Backup restoration tested

---

## Installation Tests

### Upload & Activation

- [ ] File uploaded successfully via:
  - [ ] WordPress Admin (Upload Plugin)
  - [ ] FTP
  - [ ] SSH
- [ ] Plugin appears in **Plugins** list
- [ ] Plugin activated without errors
- [ ] No PHP warnings/notices during activation
- [ ] Admin menu "VTN Calendar" appears

### Database Creation

- [ ] Check tables created:
  ```sql
  SHOW TABLES LIKE '%vtncalendar%';
  ```

Expected results:

- [ ] `wp_vtncalendar_events` exists
- [ ] `wp_vtncalendar_logs` exists
- [ ] `wp_vtncalendar_settings` exists

- [ ] Check table structures:

```sql
DESCRIBE wp_vtncalendar_events;
DESCRIBE wp_vtncalendar_logs;
DESCRIBE wp_vtncalendar_settings;
```

- [ ] Verify indexes created:

```sql
SHOW INDEX FROM wp_vtncalendar_events;
```

Expected indexes:

- [ ] `idx_type`
- [ ] `idx_date`
- [ ] `idx_location`
- [ ] `idx_province`
- [ ] `idx_status`
- [ ] `idx_type_date`

### Default Settings

- [ ] Check default settings inserted:

```sql
SELECT * FROM wp_vtncalendar_settings;
```

Expected settings (minimum):

- [ ] `claude_api_key` (empty)
- [ ] `enable_ai_content` = false
- [ ] `crawl_holiday_enabled` = true
- [ ] `crawl_movie_enabled` = true
- [ ] `crawl_football_enabled` = true
- [ ] `crawl_power_enabled` = true
- [ ] `auto_cleanup_expired` = true
- [ ] `cleanup_days` = 30

### Cron Jobs Setup

- [ ] Install **WP Crontrol** plugin (for testing)
- [ ] Check cron events scheduled:

**VTN Calendar → Tools → Cron Events**

Expected events:

- [ ] `vtncalendar_crawl_holiday` (monthly)
- [ ] `vtncalendar_crawl_movies` (twicedaily)
- [ ] `vtncalendar_crawl_football` (weekly)
- [ ] `vtncalendar_crawl_power` (daily)
- [ ] `vtncalendar_cleanup_expired` (daily)

- [ ] Verify next run times are set correctly

---

## Admin Interface Tests

### Dashboard Page

**URL**: `admin.php?page=vtncalendar`

- [ ] Page loads without errors
- [ ] 4 stat cards display:
  - [ ] Holiday card (red gradient)
  - [ ] Movie card (green gradient)
  - [ ] Football card (blue gradient)
  - [ ] Power card (orange gradient)
- [ ] Stats show correct numbers (0 initially)
- [ ] "Crawl Logs Gần Đây" table displays
- [ ] Quick action buttons present:
  - [ ] Test Crawl Lịch Nghỉ
  - [ ] Test Crawl Phim
  - [ ] Test Crawl Bóng Đá
  - [ ] Test Crawl Cắt Điện
- [ ] Buttons are clickable
- [ ] CSS styles load correctly
- [ ] Responsive on mobile (test with DevTools)

### Calendar Manager Page

**URL**: `admin.php?page=vtncalendar-manage`

- [ ] Page loads without errors
- [ ] Events table displays
- [ ] Table headers correct:
  - ID | Loại | Tiêu đề | Ngày | Nguồn | Trạng thái | Actions
- [ ] "Chưa có sự kiện nào" message shows (initially)
- [ ] Delete buttons present (after crawl)
- [ ] Table responsive on mobile

### Crawl Settings Page

**URL**: `admin.php?page=vtncalendar-settings`

- [ ] Page loads without errors
- [ ] Claude API Key field present
- [ ] Enable AI Content checkbox present
- [ ] All crawl enable checkboxes present:
  - [ ] Holiday
  - [ ] Movie
  - [ ] Football
  - [ ] Power
- [ ] Movie sources checkboxes:
  - [ ] CGV
  - [ ] Galaxy
  - [ ] Lotte
- [ ] Power provinces checkboxes:
  - [ ] Hà Nội
  - [ ] TP.HCM
  - [ ] Đà Nẵng
  - [ ] Hải Phòng
  - [ ] Cần Thơ
- [ ] Auto cleanup checkbox present
- [ ] Cleanup days field present
- [ ] "Lưu cài đặt" button present
- [ ] Form submittable

### Display Settings Page

**URL**: `admin.php?page=vtncalendar-display`

- [ ] Page loads without errors
- [ ] Default view dropdown present
- [ ] Items per page field present
- [ ] Date format field present
- [ ] Show source link checkbox present
- [ ] Show AI content checkbox present
- [ ] "Lưu cài đặt" button present
- [ ] Form submittable

### Logs & Status Page

**URL**: `admin.php?page=vtncalendar-logs`

- [ ] Page loads without errors
- [ ] Logs table displays
- [ ] Table headers correct
- [ ] "Chưa có log nào" message shows (initially)
- [ ] Table updates after crawl

---

## Crawling Function Tests

### Test Crawl - Holiday

1. [ ] Go to Dashboard
2. [ ] Click "Test Crawl Lịch Nghỉ"
3. [ ] Wait for response (may take 5-30 seconds)
4. [ ] Alert shows success message
5. [ ] Page reloads automatically
6. [ ] Holiday stat card updates (number > 0)
7. [ ] Check Calendar Manager:
   - [ ] Events appear
   - [ ] Type = "holiday"
   - [ ] Title present
   - [ ] Date present
   - [ ] Source = "Chính phủ VN"
8. [ ] Check Logs & Status:
   - [ ] New log entry
   - [ ] Status = "success"
   - [ ] Items found > 0
   - [ ] Items added > 0
9. [ ] Check database:

```sql
SELECT * FROM wp_vtncalendar_events WHERE calendar_type = 'holiday';
```

Expected fields populated:

- [ ] `title`
- [ ] `event_date`
- [ ] `source_name`
- [ ] `source_url`
- [ ] `meta_data` (JSON)

### Test Crawl - Movies

1. [ ] Go to Crawl Settings
2. [ ] Enable all movie sources (CGV, Galaxy, Lotte)
3. [ ] Save settings
4. [ ] Go to Dashboard
5. [ ] Click "Test Crawl Phim"
6. [ ] Wait for response
7. [ ] Alert shows success
8. [ ] Movie stat card updates
9. [ ] Check Calendar Manager:
   - [ ] Movie events appear
   - [ ] Type = "movie"
   - [ ] Meta data includes:
     - [ ] `poster_url`
     - [ ] `genre`
     - [ ] `director`
10. [ ] Check Logs:
    - [ ] Multiple entries (1 per source: CGV, Galaxy, Lotte)
    - [ ] All status = "success" (or partial if source unavailable)
11. [ ] Verify database:

```sql
SELECT * FROM wp_vtncalendar_events WHERE calendar_type = 'movie';
```

### Test Crawl - Football

1. [ ] Go to Dashboard
2. [ ] Click "Test Crawl Bóng Đá"
3. [ ] Wait for response
4. [ ] Alert shows success
5. [ ] Football stat card updates
6. [ ] Check Calendar Manager:
   - [ ] Football events appear
   - [ ] Title format: "Team A vs Team B"
   - [ ] Location present
   - [ ] Meta data includes:
     - [ ] `home_team`
     - [ ] `away_team`
     - [ ] `tournament`
     - [ ] `stadium`
7. [ ] Check database

### Test Crawl - Power Outage

1. [ ] Go to Crawl Settings
2. [ ] Enable provinces (e.g., Hà Nội, TP.HCM)
3. [ ] Save settings
4. [ ] Go to Dashboard
5. [ ] Click "Test Crawl Cắt Điện"
6. [ ] Wait for response
7. [ ] Alert shows success
8. [ ] Power stat card updates
9. [ ] Check Calendar Manager:
   - [ ] Power events appear
   - [ ] Province populated
   - [ ] Location populated
   - [ ] Start/end dates present
10. [ ] Check Logs:
    - [ ] Multiple entries (1 per province)
11. [ ] Verify database

### Crawl Error Handling

- [ ] Test with invalid URL (edit source URL in code temporarily)
- [ ] Verify error logged correctly
- [ ] Status = "failed"
- [ ] Error message populated
- [ ] No PHP errors/warnings

### Cron Auto-Crawl Test

Using **WP Crontrol**:

1. [ ] Find event: `vtncalendar_crawl_movies`
2. [ ] Click "Run Now"
3. [ ] Wait for completion
4. [ ] Check Logs for new entry
5. [ ] Verify events added

---

## Frontend Display Tests

### Shortcode - Basic

1. [ ] Create new page: "Test Calendar"
2. [ ] Add shortcode: `[vtncalendar]`
3. [ ] Publish page
4. [ ] View page frontend

**Checks:**

- [ ] Events display
- [ ] No PHP errors
- [ ] CSS loads correctly
- [ ] Cards have proper styling
- [ ] Border colors match event types:
  - [ ] Holiday = red
  - [ ] Movie = green
  - [ ] Football = blue
  - [ ] Power = orange

### Shortcode - Type Filter

- [ ] `[vtncalendar type="holiday"]` - only holidays show
- [ ] `[vtncalendar type="movie"]` - only movies show
- [ ] `[vtncalendar type="football"]` - only football show
- [ ] `[vtncalendar type="power_outage"]` - only power outages show

### Shortcode - Province Filter

- [ ] `[vtncalendar type="power_outage" province="hanoi"]` - only Hanoi events
- [ ] `[vtncalendar type="power_outage" province="hcm"]` - only HCM events

### Shortcode - View Modes

- [ ] `[vtncalendar view="list"]` - list view displays
- [ ] `[vtncalendar view="grid"]` - grid layout displays (cards)
- [ ] `[vtncalendar view="calendar"]` - calendar view (may not be fully implemented)

### Shortcode - Limit

- [ ] `[vtncalendar limit="5"]` - only 5 events show
- [ ] `[vtncalendar limit="20"]` - 20 events show

### Event Card Components

For each event card, verify:

- [ ] Title displays
- [ ] Date displays (correct format)
- [ ] Location displays (if applicable)
- [ ] Description displays
- [ ] AI content displays (if enabled and available)
- [ ] Source link displays:
  - [ ] Label: "Nguồn:"
  - [ ] Link to source URL
  - [ ] Opens in new tab (`target="_blank"`)
  - [ ] `rel="nofollow"`
- [ ] Last updated timestamp displays
- [ ] Disclaimer message displays:
  - "⚠️ Thông tin có thể thay đổi. Vui lòng kiểm tra nguồn gốc để chắc chắn."

### Movie Specific

For movie events:

- [ ] Poster image displays (in grid view)
- [ ] Image loads correctly
- [ ] Image is responsive
- [ ] Genre displays
- [ ] Age rating displays (if available)

### Responsive Design

Test on multiple screen sizes:

**Desktop (1920px)**

- [ ] Grid shows 3-4 columns
- [ ] Cards display properly
- [ ] Images not pixelated

**Tablet (768px)**

- [ ] Grid shows 2 columns
- [ ] Layout adapts
- [ ] Touch-friendly

**Mobile (375px)**

- [ ] Grid shows 1 column
- [ ] Cards stack vertically
- [ ] Text readable
- [ ] Buttons touchable
- [ ] No horizontal scroll

### Frontend Performance

- [ ] Page load time < 3 seconds
- [ ] CSS inline (no external file)
- [ ] JS inline (no external file)
- [ ] No console errors (F12 → Console)
- [ ] No CSS warnings

---

## AJAX Tests

### Save Settings

1. [ ] Go to Crawl Settings
2. [ ] Change a setting (e.g., uncheck CGV)
3. [ ] Click "Lưu cài đặt"
4. [ ] Wait for response
5. [ ] Alert shows "Lưu cài đặt thành công!"
6. [ ] Page does NOT reload (AJAX)
7. [ ] Verify database:

```sql
SELECT * FROM wp_vtncalendar_settings WHERE setting_key = 'crawl_movie_sources';
```

- [ ] Value updated correctly

### Delete Event

1. [ ] Go to Calendar Manager
2. [ ] Click "Xóa" on any event
3. [ ] Confirm dialog appears
4. [ ] Click OK
5. [ ] Row fades out
6. [ ] Page does NOT reload (AJAX)
7. [ ] Verify database:

```sql
SELECT * FROM wp_vtncalendar_events WHERE id = [deleted_id];
```

- [ ] Event deleted (no rows)

### Test Crawl

1. [ ] Click "Test Crawl" button
2. [ ] Button text changes to "Đang crawl..."
3. [ ] Button disabled during request
4. [ ] Alert shows result
5. [ ] Page reloads after success

### AJAX Security

- [ ] Check nonce verification:
  - Open DevTools → Network
  - Trigger AJAX request
  - Check payload includes `nonce` parameter
  - Try replaying request with invalid nonce → should fail

---

## Security Tests

### SQL Injection

Try malicious inputs:

1. [ ] Manual event creation (if implemented):
   - Title: `'; DROP TABLE wp_vtncalendar_events; --`
   - Expected: Sanitized, no SQL executed

2. [ ] Shortcode parameters:
   - `[vtncalendar type="movie'; DROP TABLE wp_posts; --"]`
   - Expected: No SQL injection

### XSS (Cross-Site Scripting)

1. [ ] Event with malicious script in title:
   - Title: `<script>alert('XSS')</script>`
   - Expected: Escaped, no alert popup on frontend

2. [ ] Event description with HTML:
   - Description: `<img src=x onerror=alert('XSS')>`
   - Expected: Escaped

### CSRF (Cross-Site Request Forgery)

- [ ] All forms have nonce fields
- [ ] AJAX requests verify nonce
- [ ] Direct POST requests without nonce fail

### Capability Checks

1. [ ] Logout from admin
2. [ ] Try accessing admin pages directly:
   - `admin.php?page=vtncalendar`
   - Expected: "Unauthorized" or redirect to login

3. [ ] Login as Editor (not Admin)
4. [ ] Try accessing VTN Calendar pages
   - Expected: Should work if capability is `edit_posts`
   - Or "Unauthorized" if capability is `manage_options`

### File Access

- [ ] Try accessing plugin file directly:
  - `https://your-site.com/wp-content/plugins/vtncalendar/vtncalendar.php`
  - Expected: Blank page or "Access Denied" (due to `if (!defined('ABSPATH'))`)

---

## Performance Tests

### Database Query Optimization

1. [ ] Enable Query Monitor plugin
2. [ ] Load frontend page with shortcode
3. [ ] Check queries:
   - [ ] Queries < 50
   - [ ] Query time < 0.5s
   - [ ] No duplicate queries
   - [ ] Indexes used (check with `EXPLAIN`)

```sql
EXPLAIN SELECT * FROM wp_vtncalendar_events
WHERE calendar_type = 'movie' AND status = 'active'
ORDER BY event_date ASC LIMIT 20;
```

- [ ] `key` column shows index used (e.g., `idx_type_date`)

### Large Dataset

1. [ ] Insert 1000+ events (can use SQL):

```sql
INSERT INTO wp_vtncalendar_events (calendar_type, title, event_date, source_name, source_url, status, created_at, last_updated)
SELECT
  'movie',
  CONCAT('Test Movie ', id),
  NOW() + INTERVAL FLOOR(RAND() * 100) DAY,
  'Test Source',
  'https://example.com',
  'active',
  NOW(),
  NOW()
FROM wp_posts LIMIT 1000;
```

2. [ ] Load frontend page
3. [ ] Check performance:
   - [ ] Page load time < 3 seconds
   - [ ] No timeout errors
   - [ ] Pagination/limit works

### Memory Usage

- [ ] Check PHP memory usage during crawl:

```php
echo memory_get_peak_usage(true) / 1024 / 1024 . ' MB';
```

- [ ] Expected: < 100MB

### Crawl Performance

- [ ] Test crawl with timer:

```php
$start = microtime(true);
// Crawl function
$end = microtime(true);
echo 'Execution time: ' . ($end - $start) . ' seconds';
```

- [ ] Expected: < 30 seconds per source

---

## Compatibility Tests

### WordPress Versions

Test on:

- [ ] WordPress 5.8 (minimum)
- [ ] WordPress 6.0
- [ ] WordPress 6.4 (latest)

### PHP Versions

Test on:

- [ ] PHP 7.4 (minimum)
- [ ] PHP 8.0
- [ ] PHP 8.1
- [ ] PHP 8.2

### Themes

Test with popular themes:

- [ ] Twenty Twenty-One
- [ ] Twenty Twenty-Two
- [ ] Twenty Twenty-Three
- [ ] Astra
- [ ] GeneratePress

**Checks:**

- [ ] Shortcode displays correctly
- [ ] CSS doesn't conflict
- [ ] Responsive works

### Plugins Compatibility

Test with popular plugins:

**Caching:**

- [ ] WP Super Cache - events display correctly after cache
- [ ] W3 Total Cache - no conflicts
- [ ] WP Rocket - no conflicts

**SEO:**

- [ ] Yoast SEO - no conflicts
- [ ] Rank Math - no conflicts

**Page Builders:**

- [ ] Elementor - shortcode works in widget
- [ ] Gutenberg - shortcode block works
- [ ] Classic Editor - shortcode works

**Security:**

- [ ] Wordfence - no false positives
- [ ] Sucuri - no conflicts

### Multisite

If testing on multisite:

- [ ] Network activate works
- [ ] Per-site activate works
- [ ] Tables created per site
- [ ] No cross-site data leakage

---

## Claude API Tests

### API Integration

1. [ ] Get valid API key from https://console.anthropic.com/
2. [ ] Enter API key in settings
3. [ ] Enable AI content generation
4. [ ] Save settings
5. [ ] Test crawl movies
6. [ ] Check event:
   - [ ] `ai_content` field populated
   - [ ] Content is relevant (100-150 words)
   - [ ] Vietnamese language
   - [ ] No API errors in logs

### API Error Handling

**Test 1: Invalid API Key**

1. [ ] Enter invalid API key: `sk-ant-invalid123`
2. [ ] Test crawl
3. [ ] Expected: Falls back to template, no errors

**Test 2: API Rate Limit**

1. [ ] Trigger many crawls rapidly
2. [ ] Expected: Graceful handling, template fallback

**Test 3: Network Timeout**

1. [ ] Simulate slow network
2. [ ] Expected: Timeout handled, template fallback

### Template Fallback

1. [ ] Disable AI content (uncheck in settings)
2. [ ] Test crawl
3. [ ] Check events:
   - [ ] `ai_content` uses template
   - [ ] Format: "Bộ phim {title} sẽ khởi chiếu..."
   - [ ] No API calls made

---

## Final Checklist

### Pre-Deployment

- [ ] All tests passed
- [ ] No PHP errors in debug.log
- [ ] No JavaScript console errors
- [ ] Database optimized (no orphaned data)
- [ ] Cron jobs scheduled correctly
- [ ] Documentation complete:
  - [ ] README.md
  - [ ] INSTALLATION_GUIDE.md
  - [ ] USER_GUIDE.md
  - [ ] TESTING_CHECKLIST.md (this file)

### Code Quality

- [ ] No hardcoded credentials
- [ ] All strings use proper escaping (`esc_html`, `esc_url`, etc.)
- [ ] SQL queries use `$wpdb->prepare()`
- [ ] WordPress Coding Standards followed
- [ ] Code comments present (in Vietnamese)
- [ ] Functions documented

### User Experience

- [ ] Admin interface intuitive
- [ ] Error messages clear and helpful
- [ ] Success messages display
- [ ] Loading states shown (button text changes)
- [ ] Responsive on all devices
- [ ] Accessibility considerations (alt text, ARIA labels)

### Security Audit

- [ ] Nonce verification on all forms
- [ ] Capability checks on all admin pages
- [ ] Input sanitization
- [ ] Output escaping
- [ ] SQL injection protected
- [ ] XSS protected
- [ ] CSRF protected

### Performance Baseline

Record baseline metrics:

- [ ] Plugin file size: _______ KB
- [ ] Database size (3 tables): _______ MB
- [ ] Frontend page load time: _______ seconds
- [ ] Admin dashboard load time: _______ seconds
- [ ] Average crawl time per source: _______ seconds

### Production Readiness

- [ ] Staging environment tested
- [ ] Production backup created
- [ ] Rollback plan documented
- [ ] Monitoring setup (logs)
- [ ] Support channel ready
- [ ] Launch announcement prepared

---

## Test Results Log

### Tester Information

- **Name**: ___________________
- **Date**: ___________________
- **WordPress Version**: ___________________
- **PHP Version**: ___________________
- **Environment**: Staging / Production

### Summary

- **Total Tests**: _____ / _____
- **Passed**: _____
- **Failed**: _____
- **Skipped**: _____

### Failed Tests (if any)

| Test Name | Expected | Actual | Severity | Notes |
|-----------|----------|--------|----------|-------|
|           |          |        |          |       |

### Sign-off

- [ ] All critical tests passed
- [ ] All blockers resolved
- [ ] Ready for production deployment

**Signed**: ___________________
**Date**: ___________________

---

## Troubleshooting During Testing

### Common Issues

**Issue 1: Tables not created**

- Solution: Deactivate → Delete → Re-upload → Activate

**Issue 2: Crawl returns 0 items**

- Check: Network connectivity, source website status
- Solution: Verify source URLs, check HTML structure

**Issue 3: Frontend CSS broken**

- Solution: Clear cache, regenerate permalinks

**Issue 4: Cron jobs not running**

- Solution: Setup server cron, disable WP_CRON

**Issue 5: AJAX not working**

- Check: Browser console for errors
- Solution: Verify nonce, check AJAX URL

---

## Continuous Testing

### Regression Tests

Run this checklist:

- [ ] After every plugin update
- [ ] After WordPress core update
- [ ] After PHP version change
- [ ] Monthly (minimum)

### Monitoring

Setup monitoring for:

- [ ] Crawl success rate (Logs & Status)
- [ ] Database growth
- [ ] Error logs (wp-content/debug.log)
- [ ] Frontend uptime
- [ ] API usage (if using Claude)

---

**Testing Complete! 🎉**

Ready to deploy VTN Calendar to production.
