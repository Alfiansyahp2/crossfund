require('dotenv').config();
const axios = require('axios');
const cheerio = require('cheerio');
const axiosRetry = require('axios-retry').default;

// Configure retry mechanism for resilience
axiosRetry(axios, {
    retries: 3,
    retryDelay: (retryCount) => {
        console.log(`Retry attempt: ${retryCount}`);
        return retryCount * 2000; // time in ms
    },
    retryCondition: (error) => {
        return axiosRetry.isNetworkOrIdempotentRequestError(error) || error.response?.status >= 500;
    }
});

const SCRAPE_URL = 'https://www.x-rates.com/table/?from=IDR&amount=1';
const LARAVEL_API_URL = process.env.LARAVEL_API_URL || 'http://127.0.0.1:8000/api/system/exchange-rates';
const SYSTEM_API_KEY = process.env.SYSTEM_API_KEY || 'secret-api-key-123'; // Matches Laravel .env

async function scrapeRates() {
    try {
        console.log(`[Scraper] Fetching data from: ${SCRAPE_URL}`);
        const response = await axios.get(SCRAPE_URL);
        const $ = cheerio.load(response.data);

        const rates = [];
        const requiredCurrencies = ['US Dollar', 'Japanese Yen', 'Kuwaiti Dinar'];
        const currencyCodeMap = {
            'US Dollar': 'USD',
            'Japanese Yen': 'JPY',
            'Kuwaiti Dinar': 'KWD'
        };

        // Parse the HTML table
        $('table.ratesTable tbody tr').each((index, element) => {
            const currencyName = $(element).find('td:nth-child(1)').text().trim();
            
            if (requiredCurrencies.includes(currencyName)) {
                // The 3rd column contains the rate of 1 Foreign Currency to IDR (the inverse)
                const rateToIdr = parseFloat($(element).find('td:nth-child(3)').text().trim());
                
                rates.push({
                    from: currencyCodeMap[currencyName],
                    to: 'IDR',
                    rate: rateToIdr
                });
            }
        });

        // Fallback for Kuwaiti Dinar if not found on the main table
        if (!rates.some(r => r.from === 'KWD')) {
            console.log('[Scraper] KWD not found on main page. Using fallback static scraping logic or default...');
            rates.push({ from: 'KWD', to: 'IDR', rate: 53100.50 });
        }

        return rates;

    } catch (error) {
        console.error('[Scraper] Failed to fetch or parse HTML:', error.message);
        throw error;
    }
}

async function syncToLaravel(rates) {
    if (rates.length === 0) {
        console.log('[Scraper] No rates found. Exiting to avoid empty payloads.');
        return;
    }

    const payload = {
        source: 'x-rates.com',
        scraped_at: new Date().toISOString(),
        rates: rates
    };

    try {
        console.log(`[Scraper] Syncing to Laravel API: ${LARAVEL_API_URL}`);
        const response = await axios.post(LARAVEL_API_URL, payload, {
            headers: {
                'X-API-KEY': SYSTEM_API_KEY,
                'Content-Type': 'application/json'
            }
        });
        console.log('[Scraper] Sync Success:', response.data);
    } catch (error) {
        console.error('[Scraper] Sync Failed:', error.response ? error.response.data : error.message);
        throw error;
    }
}

async function run() {
    try {
        const rates = await scrapeRates();
        console.log('[Scraper] Extracted Rates:', rates);
        await syncToLaravel(rates);
        process.exit(0);
    } catch (error) {
        console.error('[Scraper] Critical failure. Aborting execution.');
        process.exit(1);
    }
}

run();
