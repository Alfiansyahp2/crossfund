# Architectural Decisions Log

This document records the major architectural decisions made during the development of the White-label Multi-tenant P2P Crowdfunding Platform.

## 1. Multi-Tenant Architecture Strategy

**Decision**: Separate Central Database and isolated Tenant Databases.
**Reasoning**:
- **Data Isolation & Security**: Financial and project data for each platform operator (tenant) must be strictly isolated to prevent accidental cross-tenant data leaks.
- **Scalability**: Tenant databases can be moved to different physical servers or clusters as they grow, without affecting the central marketplace.
- **Simplified Credentials**: Investors and Agents are stored in the Central DB (allowing cross-tenant investments). Admins and Issuers are stored in Tenant DBs, eliminating the need for complex Multi-guard or Spatie Role setups on a single table.

## 2. Currency Handling and Precision

**Decision**: Use `BIGINT` for all monetary values and `DECIMAL(16,8)` for exchange rates.
**Reasoning**:
- **Floating-point Errors**: Storing money as `FLOAT` or `DECIMAL` can lead to rounding errors. By using `BIGINT` and storing the lowest denomination (minor units, e.g., cents), we ensure 100% mathematical precision.
- **Dynamic Decimals**: The `currencies` table stores a `decimals` column. When displaying to the user, the application divides the `BIGINT` value by `10^decimals` (e.g., JPY has 0 decimals, USD has 2).

## 3. Financial Audit Trail

**Decision**: Immutable `wallet_transactions` table with `balance_before` and `balance_after`.
**Reasoning**:
- **Trust & Compliance**: In a financial system, calculating balances dynamically by summing transactions is risky and slow. Storing exact snapshots (`before` and `after`) provides an absolute, immutable audit trail.
- **Concurrency**: Combined with Pessimistic Locking (`lockForUpdate()` in `WalletService`), this ensures race conditions cannot cause overdrafts or skipped transactions.

## 4. Historical Snapshots (No Dynamic Recalculation)

**Decision**: Store `exchange_rate_used` and `commission_rate_used` directly in the transaction/investment rows.
**Reasoning**:
- **Immutability of History**: As per the requirement, "old transactions must not change". If a currency rate changes tomorrow, or if an Agent is promoted from Silver to Gold tier, past commissions and investments must retain the exact rates applied at the time of creation.

## 5. Standalone Node.js Exchange Rate Scraper

**Decision**: Use a separate Node.js service with Axios + Cheerio to scrape rates and push to Laravel via an internal API.
**Reasoning**:
- **Requirement Compliance**: The specification explicitly prohibited exchange-rate APIs and mandated scraping via an external tool outside PHP.
- **Separation of Concerns**: Node.js handles the extraction; Laravel remains the sole owner of the database.
- **Simplicity over Puppeteer**: Axios + Cheerio was chosen over Puppeteer because the target financial websites (e.g., x-rates) render static HTML. Puppeteer would unnecessarily consume RAM and CPU for headless Chromium.
- **Orchestration**: The scheduling is managed by the Laravel Console Kernel (Scheduler). The Artisan command triggers the Node.js script. This prevents fragmented cron jobs and keeps monitoring centralized in Laravel.
- **Why HTTP API?**: The Node.js script pushes data via an internal HTTP API (`/api/system/exchange-rates`) rather than printing JSON to `stdout`. This treats the scraper as a true, independent microservice that can be decoupled and hosted on a separate container/server in the future without modifying Laravel's architecture.
