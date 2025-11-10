Xin chào! Tôi cần bạn giúp build một WordPress plugin để tổng hợp dữ liệu ngân hàng Việt Nam (lãi suất, tỷ giá).

TÔI LÀ NGƯỜI KHÔNG BIẾT CODE, vì vậy bạn cần:
1. Tự động setup mọi thứ
2. Test và fix bugs
3. Giải thích bằng tiếng Việt đơn giản
4. Cung cấp plugin ready-to-use

YÊU CẦU CHO PHASE 1 - CORE FOUNDATION:

1. SETUP ENVIRONMENT:
   - Tạo local WordPress environment (sử dụng wp-env hoặc Docker)
   - WordPress version: 6.4+
   - PHP version: 8.0+
   - Database: MySQL 8.0+

2. BUILD PLUGIN STRUCTURE:
   - Plugin name: "Vietnam Banking Data Hub"
   - Text domain: vbdh
   - Tạo folder structure chuẩn WordPress
   - Main plugin file với proper headers
   - Activation/deactivation hooks
   - Uninstall cleanup

3. DATABASE IMPLEMENTATION:
   Tạo 5 tables với utf8mb4_unicode_ci:
   
   a) wp_vn_banks - Thông tin ngân hàng
   b) wp_vn_interest_rates - Lãi suất (tiết kiệm, vay, thẻ tín dụng)
   c) wp_vn_exchange_rates - Tỷ giá ngoại tệ
   d) wp_vn_api_logs - Log API calls
   e) wp_vn_content_generation_queue - Queue cho auto-content

4. ADMIN INTERFACE:
   - Menu: "Banking Data" trong WordPress admin
   - Tab 1: Banks Management (CRUD banks)
   - Tab 2: Sync Schedule (cron settings + manual sync button)
   - Tab 3: API Logs (với filters)
   - Responsive, WordPress admin style
   - Toast notifications cho user actions

5. API CONNECTOR - BIDV (Priority đầu tiên):
   - Class: VBDH_API_BIDV extends VBDH_API_Base
   - OAuth 2.0 authentication (mock mode cho testing)
   - Endpoints:
     * GET /api/v1/interest-rates
     * GET /api/v1/exchange-rates
   - Error handling comprehensive
   - Retry mechanism (3 attempts)
   - Rate limit handling
   - Log mọi API calls

6. DATA FETCHER:
   - Class: VBDH_Data_Fetcher
   - Method: fetch_all_banks_rates()
   - Parse API responses → standardized format
   - Store vào database
   - Caching với WordPress Transients (1 hour)
   - Detect data changes

7. CRON JOBS:
   - Custom schedule: every 1 hour (exchange rates)
   - Custom schedule: every 6 hours (interest rates)
   - Hooks:
     * vbdh_fetch_exchange_rates
     * vbdh_fetch_interest_rates
   - Manual trigger option trong admin

8. SECURITY & BEST PRACTICES:
   - Sanitize all inputs
   - Escape all outputs
   - Use nonces for forms
   - Prepare all SQL queries
   - Capability checks
   - WordPress Coding Standards

9. TESTING REQUIREMENTS:
   - Test activation (no errors)
   - Test database creation (all 5 tables)
   - Test admin interface (accessible & working)
   - Test BIDV API (mock mode)
   - Test data storage
   - Test cron registration
   - Test deactivation (no errors)

OUTPUT YÊU CẦU:

1. Plugin ZIP file ready to upload
2. README.md với:
   - Installation instructions (Vietnamese)
   - Configuration guide
   - Troubleshooting section
3. TESTING-GUIDE.md với step-by-step testing
4. Sample data SQL (để test)
5. Screenshots của admin interface

QUAN TRỌNG:
- Code comments bằng tiếng Việt
- All strings i18n ready (text domain: 'vbdh')
- Comprehensive error handling
- Performance optimized (indexes, caching)
- No hardcoded credentials (use settings)

DATABASE SCHEMA CHI TIẾT:

Table 1: wp_vn_banks
- id (bigint, primary key, auto_increment)
- bank_code (varchar 20, unique) - VD: BIDV, VPBank
- bank_name (varchar 255) - Tên tiếng Việt
- bank_name_en (varchar 255) - Tên tiếng Anh
- bank_logo_url (varchar 500)
- api_endpoint (varchar 500)
- api_type (enum: bidv, vpbank, techcombank, vietcombank, custom)
- api_credentials (text, JSON format, encrypted)
- is_active (boolean, default 1)
- last_sync (datetime)
- created_at, updated_at

Table 2: wp_vn_interest_rates
- id (bigint, primary key)
- bank_id (foreign key → wp_vn_banks)
- rate_type (enum: savings, loan, credit_card)
- product_name (varchar 255)
- term_months (int) - VD: 1, 3, 6, 12, 24
- interest_rate (decimal 5,2) - VD: 5.25 = 5.25%
- min_amount (decimal 15,2)
- max_amount (decimal 15,2)
- special_conditions (text)
- effective_date (date)
- fetched_at (datetime)
- created_at, updated_at

Table 3: wp_vn_exchange_rates
- id (bigint, primary key)
- bank_id (foreign key)
- currency_code (varchar 10) - USD, EUR, JPY...
- buy_rate (decimal 12,2)
- sell_rate (decimal 12,2)
- transfer_rate (decimal 12,2)
- effective_date (date)
- effective_time (time)
- fetched_at (datetime)
- created_at

Table 4: wp_vn_api_logs
- id (bigint, primary key)
- bank_id (foreign key)
- api_endpoint (varchar 500)
- request_type (varchar 50)
- status (enum: success, error, timeout)
- response_code (int)
- error_message (text)
- execution_time (float) - seconds
- created_at

Table 5: wp_vn_content_generation_queue
- id (bigint, primary key)
- content_type (enum: daily_rates, comparison, ranking, analysis)
- trigger_type (enum: schedule, data_change, manual)
- parameters (text, JSON)
- status (enum: pending, processing, completed, failed)
- post_id (bigint) - WordPress post ID
- error_message (text)
- created_at, processed_at

BẮT ĐẦU NÀO! 

Hãy:
1. Setup WordPress environment
2. Create plugin structure
3. Implement database schema
4. Build admin interface
5. Implement BIDV API connector (mock mode OK)
6. Test everything thoroughly
7. Package và cung cấp cho tôi

Thông báo progress sau mỗi major step. Nếu có lỗi, tự động fix và test lại.

GO! 🚀
