# Architecture Decisions - Multi-Tenant P2P Investment Platform

## 1. Multi-Tenant Architecture

### Decision: Database-Per-Tenant with Central User Database
**Rationale:**
- Each tenant needs complete data isolation for their projects, issuers, and transactions
- Cross-tenant users (investors/agents) need a single identity across all tenants
- Central database stores user accounts, tenant metadata, and cross-tenant relationships
- Tenant-specific databases store projects, investments, commissions, and tenant-specific settings

**Implementation:**
- `central` database: users, tenants, exchange_rates, system_settings
- `tenant_{id}` databases: projects, investments, issuers, commissions, wallets
- Dynamic connection switching based on subdomain
- Laravel's `DB::connection()` for tenant-specific queries

### Decision: Subdomain-Based Tenant Identification
**Rationale:**
- Clean separation between main app (root domain) and tenant apps (subdomain)
- Standard pattern for multi-tenant SaaS
- Easy to implement with Laravel's route groups

**Implementation:**
- Root domain (`localhost` or `domain.com`): Main marketplace app
- Subdomain (`tenant-a.domain.com`): Tenant-specific app
- Middleware to detect subdomain and set tenant context
- Tenant resolution via cached tenant lookup

## 2. Cross-Tenant User System

### Decision: Centralized User Management
**Rationale:**
- Investors and agents register once and operate across all tenants
- Single authentication system simplifies UX
- Central user table with role-based access control
- Tenant-specific relationships stored in tenant databases

**Implementation:**
- `users` table in central database with `role` column (investor, agent)
- `user_tenant_profiles` table for tenant-specific data (if needed)
- JWT or session-based authentication with tenant context
- User can access any tenant they're registered with

## 3. Multi-Currency System

### Decision: Decimal-Aware Currency Handling
**Rationale:**
- Currencies have different decimal places (JPY: 0, KWD: 3, most: 2)
- Floating-point arithmetic causes precision errors
- Need to store exact monetary values

**Implementation:**
- Use `brick/money` package for precise currency arithmetic
- Store amounts in smallest unit (cents, sen, etc.) as integers
- Currency metadata table with decimal places
- Conversion rates stored with timestamp for historical accuracy
- All conversions use rate at transaction time (stored with transaction)

### Decision: Wallet in Investor's Home Currency
**Rationale:**
- Simplifies withdrawal process (no conversion needed)
- Investors see balance in familiar currency
- Conversion only happens at invest/unlock boundaries

**Implementation:**
- `wallets` table in central database (one per investor)
- Balance stored in investor's home currency
- `wallet_transactions` table for audit trail
- Top-up requires admin approval
- Withdrawal from wallet (same currency, no conversion)

## 4. Exchange Rate Scraping

### Decision: Node.js Scraper with PHP API Integration
**Rationale:**
- Requirement: Must use tools outside PHP
- Node.js has excellent scraping libraries (Puppeteer, Cheerio)
- PHP can consume the scraper via HTTP API
- Scraper runs as separate service, can be scheduled independently

**Implementation:**
- Node.js microservice using Puppeteer to scrape rates
- Target: Multiple sources (e.g., XE.com, Yahoo Finance) for redundancy
- PHP endpoint to trigger scraping and retrieve rates
- Scheduled job (cron) to update rates daily
- Rates stored in central `exchange_rates` table with timestamp
- Fallback mechanism if scraping fails

### Decision: Rate Storage with Historical Accuracy
**Rationale:**
- Transactions must use rate at time of transaction
- Historical reports need accurate historical rates
- Cannot change old transaction values when rates change

**Implementation:**
- `exchange_rates` table: from_currency, to_currency, rate, effective_at
- Unique index on (from_currency, to_currency, effective_at)
- When investing, store the rate used in the transaction
- When unlocking, use current rate at that time

## 5. Commission System

### Decision: Separate Commission Tables per Type
**Rationale:**
- Investor referral (1 level) and agent tier (3 levels) have different logic
- Clear separation simplifies calculation and reporting
- Easy to add new commission types in future

**Implementation:**
- `investor_referrals` table: referrer_id, referred_id, commission_rate
- `agent_tiers` table: agent_id, level (bronze/silver/gold), downline_count
- `agent_commissions` table: agent_id, investment_id, type (direct/override), amount
- Commission calculation triggered on investment
- Configurable rates via admin settings

### Decision: Agent Level-Up Based on Downline Count
**Rationale:**
- Simple, transparent progression
- Easy to calculate and verify
- Matches specification (multiples of 6)

**Implementation:**
- Downline count = number of sub-agents (not investors)
- Bronze: 0-5 downlines
- Silver: 6-11 downlines
- Gold: 12+ downlines
- Automatic level-up on downline count change
- Manual override possible via admin

## 6. Project Lifecycle

### Decision: Status-Based Workflow
**Rationale:**
- Clear state transitions prevent invalid operations
- Easy to implement validation per state
- Matches business requirements

**Implementation:**
- Project statuses: `submitted`, `published`, `funded`, `locked`, `completed`, `cancelled`
- Status transitions validated
- Admin can edit return variables only in `submitted` state
- Projects visible to investors only in `published` state

### Decision: Return Structure Validation at Publish
**Rationale:**
- Prevents invalid projects from being published
- Ensures platform margin covers all commissions
- Business rule: investor_return_rate ≤ 50% of gross_return_rate

**Implementation:**
- Validation before publish:
  - investor_return_rate ≤ gross_return_rate * 0.5
  - platform_margin = gross_return_rate - investor_return_rate
  - Total commissions (referral + direct + override) ≤ platform_margin
- Store calculated values in project record
- Commission rates taken from admin settings

## 7. Lock/Unlock Investment

### Decision: Scheduled Job for Auto-Unlock
**Rationale:**
- Automatic transfer required after lock period
- Laravel's scheduler is reliable and built-in
- Queue-based processing for scalability

**Implementation:**
- `investments` table with locked_until datetime
- Daily scheduled job to find investments past lock period
- Queue job to process each investment:
  - Calculate return (principal + investor_return)
  - Convert from tenant currency to investor home currency
  - Transfer to investor wallet
  - Update investment status to `unlocked`
- Transactional integrity (all-or-nothing)

## 8. Issuer Country Eligibility

### Decision: Admin-Configurable Country List
**Rationale:**
- Flexible for regulatory changes
- Default: Southeast Asian countries
- Easy to implement and maintain

**Implementation:**
- `system_settings` table with JSON for allowed_issuer_countries
- Validation during issuer registration
- Admin UI to enable/disable countries
- Default: ID, MY, SG, TH, VN, PH, KH, MM, LA, BN

## 9. Payment System

### Decision: Manual Approval Workflow
**Rationale:**
- No payment gateway integration required
- Simple to implement
- Suitable for technical test scope

**Implementation:**
- `payment_requests` table: user_id, type (topup/registration), amount, status
- Statuses: `pending`, `approved`, `rejected`
- Admin dashboard to review and approve/reject
- On approval: credit wallet or activate tenant
- Audit trail for all payment operations

## 10. Technology Stack

### Decision: Laravel 11 with MySQL
**Rationale:**
- Laravel 11 is latest stable version
- MySQL is widely used, reliable, supports JSON
- Good for multi-tenant with dynamic connections
- Eloquent ORM simplifies complex relationships

### Decision: Brick/Money for Currency
**Rationale:**
- Industry-standard PHP money library
- Handles different decimal places correctly
- Immutable objects prevent errors
- Good Laravel integration

### Decision: Node.js + Puppeteer for Scraping
**Rationale:**
- Requirement: Must use non-PHP tool
- Puppeteer handles dynamic content well
- Can scrape JavaScript-rendered pages
- Easy to deploy as microservice

### Decision: Laravel Scheduler + Queue
**Rationale:**
- Built-in, no external dependencies
- Reliable for scheduled tasks
- Queue for async processing (unlock investments)
- Easy to monitor and debug

## 11. Database Schema Overview

### Central Database
- `users` - Cross-tenant users (investors, agents)
- `tenants` - Tenant metadata (subdomain, currency, status)
- `exchange_rates` - Historical exchange rates
- `system_settings` - Configurable settings (country eligibility, commission rates)
- `wallets` - Investor wallets (home currency)
- `wallet_transactions` - Wallet audit trail
- `payment_requests` - Manual payment approvals
- `investor_referrals` - Referral relationships
- `agent_tiers` - Agent level tracking

### Tenant Database (per tenant)
- `issuers` - Tenant-specific issuers
- `projects` - Projects with return structure
- `investments` - Investment records
- `project_commissions` - Commission calculations per project
- `agent_commissions` - Agent commission records
- `tenant_settings` - Tenant-specific settings

## 12. Security Considerations

### Decision: Tenant Isolation at Database Level
**Rationale:**
- Complete data separation
- Prevents cross-tenant data leakage
- Easier to comply with data regulations

**Implementation:**
- Separate database per tenant
- Connection switching middleware
- Validation that queries use correct connection
- No cross-tenant joins in application code

### Decision: Row-Level Security for Sensitive Data
**Rationale:**
- Additional protection within tenant database
- Prevents unauthorized access even with SQL injection

**Implementation:**
- User-scoped queries (investor sees only their investments)
- Admin role validation
- Audit logging for sensitive operations

## 13. Timezone Handling

### Decision: UTC Storage with User Timezone Display
**Rationale:**
- UTC is standard for database storage
- Avoids daylight saving time issues
- Users see times in their timezone

**Implementation:**
- All datetimes stored in UTC
- User profile stores timezone preference
- Display conversion using Carbon
- Lock periods calculated in UTC for consistency

## 14. Testing Strategy

### Decision: Feature Tests for Critical Paths
**Rationale:**
- Laravel's built-in testing is excellent
- Focus on business logic validation
- Multi-tenant setup requires careful testing

**Implementation:**
- Tests for: tenant resolution, currency conversion, commission calculation, project validation
- Database transactions for test isolation
- Factory classes for test data
- Separate test database configuration

## 15. Deployment Considerations

### Decision: Environment-Based Configuration
**Rationale:**
- Laravel standard practice
- Easy to manage different environments
- Sensitive data in .env files

**Implementation:**
- Separate .env for local, staging, production
- Database credentials in environment
- Scraper service URL configurable
- Queue worker configuration per environment

## 16. Recent Architectural Refinements (Iterative Design)

### Decision: Strict Auth Separation (Central vs Tenant)
**Rationale:**
- Central users (Investors/Agents) need one login for everything.
- Admins and Issuers are strictly bound to their Tenant.
- Mixing them into one users table causes Spatie Permission nightmares (multi-guard, multi-db relationships).
- Better to create dmins and issuers tables inside the Tenant DB.

### Decision: Margin Validation in Service Layer
**Rationale:**
- The rule 	otal_commissions <= platform_margin happens at publish time.
- We don't store platform_margin in the DB as it's derived (gross - investor).
- Validation happens in ProjectPublishingService to keep DB normalized.

### Decision: Audit Trail for Wallets
**Rationale:**
- We store alance_before, alance_after, and exchange_rate_used directly in wallet_transactions to ensure a perfect immutable audit trail for financial movements.


## Exchange Rate Scraper

Exchange rates are scraped using a standalone Node.js service.

Reason:
- Requirement prohibits exchange-rate APIs.
- Keeps scraping logic outside Laravel.
- Laravel remains the only application allowed to write to the database.
- Communication uses an authenticated internal HTTP endpoint (/api/system/exchange-rates).
- Axios + Cheerio is chosen over Puppeteer for efficiency as the target pages do not require complex JavaScript rendering.
- Scheduling is centralized in Laravel (via Artisan Scheduler triggering the Node script).
