-- Sample Data for Vietnam Banking Data Hub Plugin
-- Chạy file SQL này trong phpMyAdmin hoặc MySQL client để có dữ liệu test

-- Insert sample banks
INSERT INTO wp_vn_banks (bank_code, bank_name, bank_name_en, bank_logo_url, api_endpoint, api_type, is_active, created_at, updated_at)
VALUES
('BIDV', 'Ngân hàng TMCP Đầu tư và Phát triển Việt Nam', 'Bank for Investment and Development of Vietnam', 'https://via.placeholder.com/150', 'https://api.bidv.com.vn', 'bidv', 1, NOW(), NOW()),
('VPBank', 'Ngân hàng TMCP Việt Nam Thịnh Vượng', 'Vietnam Prosperity Joint Stock Commercial Bank', 'https://via.placeholder.com/150', 'https://api.vpbank.com.vn', 'vpbank', 1, NOW(), NOW()),
('Techcombank', 'Ngân hàng TMCP Kỹ Thương Việt Nam', 'Vietnam Technological and Commercial Joint Stock Bank', 'https://via.placeholder.com/150', 'https://api.techcombank.com.vn', 'techcombank', 1, NOW(), NOW()),
('Vietcombank', 'Ngân hàng TMCP Ngoại thương Việt Nam', 'Joint Stock Commercial Bank for Foreign Trade of Vietnam', 'https://via.placeholder.com/150', 'https://api.vietcombank.com.vn', 'vietcombank', 1, NOW(), NOW());

-- Get bank IDs (assuming auto increment starts from 1)
SET @bidv_id = 1;
SET @vpbank_id = 2;
SET @techcombank_id = 3;
SET @vietcombank_id = 4;

-- Insert sample interest rates for BIDV
INSERT INTO wp_vn_interest_rates (bank_id, rate_type, product_name, term_months, interest_rate, min_amount, special_conditions, effective_date, fetched_at, created_at)
VALUES
(@bidv_id, 'savings', 'Tiết kiệm không kỳ hạn', 0, 0.50, 0, 'Rút tiền bất kỳ lúc nào', CURDATE(), NOW(), NOW()),
(@bidv_id, 'savings', 'Tiết kiệm 1 tháng', 1, 2.50, 1000000, '', CURDATE(), NOW(), NOW()),
(@bidv_id, 'savings', 'Tiết kiệm 3 tháng', 3, 3.00, 1000000, '', CURDATE(), NOW(), NOW()),
(@bidv_id, 'savings', 'Tiết kiệm 6 tháng', 6, 4.50, 1000000, 'Nhận lãi cuối kỳ', CURDATE(), NOW(), NOW()),
(@bidv_id, 'savings', 'Tiết kiệm 12 tháng', 12, 5.25, 1000000, 'Nhận lãi cuối kỳ hoặc hàng tháng', CURDATE(), NOW(), NOW()),
(@bidv_id, 'loan', 'Vay tiêu dùng', 12, 8.50, 10000000, 'Không cần tài sản đảm bảo', CURDATE(), NOW(), NOW()),
(@bidv_id, 'loan', 'Vay mua nhà', 240, 7.00, 100000000, 'Có tài sản đảm bảo', CURDATE(), NOW(), NOW()),
(@bidv_id, 'credit_card', 'Thẻ BIDV Visa Classic', NULL, 18.00, 0, 'Miễn phí năm đầu', CURDATE(), NOW(), NOW());

-- Insert sample interest rates for VPBank
INSERT INTO wp_vn_interest_rates (bank_id, rate_type, product_name, term_months, interest_rate, min_amount, special_conditions, effective_date, fetched_at, created_at)
VALUES
(@vpbank_id, 'savings', 'Tiết kiệm không kỳ hạn', 0, 0.30, 0, 'Rút tiền bất kỳ lúc nào', CURDATE(), NOW(), NOW()),
(@vpbank_id, 'savings', 'Tiết kiệm 1 tháng', 1, 2.70, 1000000, '', CURDATE(), NOW(), NOW()),
(@vpbank_id, 'savings', 'Tiết kiệm 3 tháng', 3, 3.20, 1000000, '', CURDATE(), NOW(), NOW()),
(@vpbank_id, 'savings', 'Tiết kiệm 6 tháng', 6, 4.70, 1000000, 'Nhận lãi cuối kỳ', CURDATE(), NOW(), NOW()),
(@vpbank_id, 'savings', 'Tiết kiệm 12 tháng', 12, 5.50, 1000000, 'Tặng quà cho khách hàng mới', CURDATE(), NOW(), NOW()),
(@vpbank_id, 'loan', 'Vay tiêu dùng', 12, 8.20, 10000000, 'Duyệt nhanh 24h', CURDATE(), NOW(), NOW());

-- Insert sample exchange rates for BIDV
INSERT INTO wp_vn_exchange_rates (bank_id, currency_code, buy_rate, sell_rate, transfer_rate, effective_date, effective_time, fetched_at, created_at)
VALUES
(@bidv_id, 'USD', 23800.00, 24100.00, 24050.00, CURDATE(), CURTIME(), NOW(), NOW()),
(@bidv_id, 'EUR', 25800.00, 26200.00, 26000.00, CURDATE(), CURTIME(), NOW(), NOW()),
(@bidv_id, 'GBP', 29500.00, 30000.00, 29750.00, CURDATE(), CURTIME(), NOW(), NOW()),
(@bidv_id, 'JPY', 160.00, 165.00, 162.50, CURDATE(), CURTIME(), NOW(), NOW()),
(@bidv_id, 'AUD', 15800.00, 16200.00, 16000.00, CURDATE(), CURTIME(), NOW(), NOW());

-- Insert sample exchange rates for VPBank
INSERT INTO wp_vn_exchange_rates (bank_id, currency_code, buy_rate, sell_rate, transfer_rate, effective_date, effective_time, fetched_at, created_at)
VALUES
(@vpbank_id, 'USD', 23820.00, 24080.00, 24030.00, CURDATE(), CURTIME(), NOW(), NOW()),
(@vpbank_id, 'EUR', 25850.00, 26180.00, 26010.00, CURDATE(), CURTIME(), NOW(), NOW()),
(@vpbank_id, 'GBP', 29550.00, 29980.00, 29760.00, CURDATE(), CURTIME(), NOW(), NOW()),
(@vpbank_id, 'JPY', 161.00, 164.00, 162.00, CURDATE(), CURTIME(), NOW(), NOW());

-- Insert sample exchange rates for Techcombank
INSERT INTO wp_vn_exchange_rates (bank_id, currency_code, buy_rate, sell_rate, transfer_rate, effective_date, effective_time, fetched_at, created_at)
VALUES
(@techcombank_id, 'USD', 23810.00, 24090.00, 24040.00, CURDATE(), CURTIME(), NOW(), NOW()),
(@techcombank_id, 'EUR', 25830.00, 26190.00, 26000.00, CURDATE(), CURTIME(), NOW(), NOW()),
(@techcombank_id, 'GBP', 29520.00, 30010.00, 29760.00, CURDATE(), CURTIME(), NOW(), NOW());

-- Insert sample API logs
INSERT INTO wp_vn_api_logs (bank_id, api_endpoint, request_type, status, response_code, execution_time, created_at)
VALUES
(@bidv_id, 'https://api.bidv.com.vn/api/v1/interest-rates', 'GET', 'success', 200, 1.25, NOW()),
(@bidv_id, 'https://api.bidv.com.vn/api/v1/exchange-rates', 'GET', 'success', 200, 0.98, NOW()),
(@vpbank_id, 'https://api.vpbank.com.vn/api/v1/interest-rates', 'GET', 'success', 200, 1.45, NOW()),
(@vpbank_id, 'https://api.vpbank.com.vn/api/v1/exchange-rates', 'GET', 'success', 200, 1.12, NOW()),
(@techcombank_id, 'https://api.techcombank.com.vn/api/v1/exchange-rates', 'GET', 'error', 500, 2.50, NOW()),
(@vietcombank_id, 'https://api.vietcombank.com.vn/api/v1/exchange-rates', 'GET', 'timeout', NULL, 30.00, NOW());

-- Note: Sau khi chạy file SQL này, bạn sẽ có:
-- - 4 ngân hàng (BIDV, VPBank, Techcombank, Vietcombank)
-- - Dữ liệu lãi suất mẫu cho BIDV và VPBank
-- - Dữ liệu tỷ giá mẫu cho BIDV, VPBank, và Techcombank
-- - Logs mẫu cho tất cả các ngân hàng
