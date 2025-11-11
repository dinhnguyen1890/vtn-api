# Changelog

All notable changes to Vietnam Banking Data Hub will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2025-11-11

### Added - PHASE 1: Core Infrastructure
- **Database Schema**: 5 tables with utf8mb4_unicode_ci collation
  - vn_banks: Bank information
  - vn_interest_rates: Interest rate products
  - vn_exchange_rates: Foreign exchange rates
  - vn_api_logs: API call tracking
  - vn_content_generation_queue: Content queue management
- **Models**: CRUD operations for all entities
- **API Connectors**: Base API class + BIDV connector with mock mode
- **Data Fetcher**: Automatic data fetching with 1-hour cache
- **Cron Jobs**: Hourly exchange rates, 6-hourly interest rates
- **Admin Interface**: 6 tabs (Banks, Sync, Logs, Content, Shortcodes, SEO)
- **Security**: Nonces, sanitization, escaping, prepared statements

### Added - PHASE 2: Auto Content Generation
- **Content Generator Class**: 3 post types automation
  - Daily Rates: Daily interest rate summary
  - Bank Comparison: Compare 2 banks side-by-side
  - Ranking: Top banks by interest rate
- **Smart Features**:
  - Duplicate detection (prevents same-day duplicates)
  - Auto categories and tags
  - SEO-friendly titles and content
  - Meta data tracking (_vbdh_content_type, _vbdh_generated_date)
- **Admin Integration**:
  - Content generation tab with statistics
  - One-click generation buttons
  - Recent posts list with quick actions
  - AJAX handlers for real-time generation

### Added - PHASE 3: Shortcodes & Widgets
- **Shortcodes**:
  - `[vbdh_comparison_table]`: Sortable comparison table
  - `[vbdh_calculator]`: AJAX-powered interest calculator
  - `[vbdh_exchange_rate]`: Multi-display exchange widget
- **WordPress Widget**: Banking Rates Widget
  - Sidebar/footer compatible
  - Configurable display type (interest/exchange)
  - 1-10 banks selection
  - Currency selection for exchange rates
- **Frontend Assets**:
  - Responsive CSS (mobile-first design)
  - JavaScript with AJAX calculator
  - Table sorting functionality
  - Loading states and error handling
- **Admin Documentation Tab**: Complete usage guide

### Added - PHASE 4: SEO & Auto-Indexing
- **Schema.org Generator**:
  - FinancialService schema for daily rates
  - ItemList schema for comparisons and rankings
  - BankOrCreditUnion schema for banks
  - ExchangeRateSpecification for forex data
  - Article, Breadcrumb, FAQ schemas
  - Auto-injection into wp_head
- **IndexNow API Integration**:
  - Auto-submit on post publish/update
  - Bulk submission tool
  - API key auto-generation and verification file
  - Stats tracking (total, success, failed, success rate)
  - Error logging and retry mechanism
- **SEO Optimizer**:
  - Auto meta descriptions (120-160 chars)
  - Auto meta keywords from tags
  - Open Graph tags for social sharing
  - Twitter Cards support
  - Canonical URLs
  - Robots meta tags with max-snippet
  - SEO Score checker (10 criteria, A-F grading)
  - XML Sitemap generator
- **SEO Admin Tab**:
  - IndexNow stats dashboard
  - Test API and bulk submit tools
  - SEO score for recent posts (with issue detection)
  - Settings management (Twitter handle, auto-meta)
  - SEO tips and best practices

### Added - PHASE 5: Polish & Optimize
- **Performance Optimizations**:
  - Database indexes on all foreign keys and query columns
  - Transient caching (1 hour) for all shortcodes and widgets
  - Lazy loading of frontend assets (only when shortcode present)
  - Optimized SQL queries with prepared statements
- **Documentation**:
  - README.md: Quick start and feature overview
  - USER-GUIDE.md: Detailed usage instructions
  - CHANGELOG.md: Version history
  - Inline code documentation (PHPDoc)
  - Admin UI documentation tabs
- **Production Ready**:
  - Tested with WordPress 6.4+
  - PHP 8.0+ compatibility
  - Secure coding practices (OWASP top 10)
  - Error handling and logging
  - Uninstall cleanup

### Fixed
- Mock API data normalization (bank_id and effective_date were missing)
- Currency data expanded to 8 currencies (added KRW)
- Frontend asset loading optimization

### Security
- All user inputs sanitized
- All outputs escaped
- Nonce verification on all AJAX and form submissions
- Capability checks (manage_options)
- SQL injection prevention (prepared statements)
- XSS prevention (esc_html, esc_attr, esc_url)

### Technical Details
- **Total Files**: 30+ PHP files, 2 CSS, 2 JS
- **Lines of Code**: ~8,000+ lines
- **Database Tables**: 5
- **Admin Tabs**: 6
- **Shortcodes**: 3
- **Widgets**: 1
- **API Integrations**: 2 (Bank APIs + IndexNow)
- **Schema Types**: 7
- **Cron Jobs**: 2

## [Unreleased]

### Planned Features
- Support for more banks (Vietcombank, ACB, Vietinbank, etc.)
- Google Indexing API integration
- Advanced comparison filters
- Historical data charts
- Email notifications for rate changes
- Multi-language support (English, Vietnamese)
- REST API endpoints
- Mobile app integration

---

For more details, see the [README](README.md).
