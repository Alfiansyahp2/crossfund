<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Currencies
        \App\Models\Currency::insert([
            ['code' => 'USD', 'name' => 'US Dollar', 'decimals' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'JPY', 'name' => 'Japanese Yen', 'decimals' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'KWD', 'name' => 'Kuwaiti Dinar', 'decimals' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'IDR', 'name' => 'Indonesian Rupiah', 'decimals' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 2. Agent Tiers
        \App\Models\AgentTier::insert([
            ['name' => 'Bronze', 'required_downlines' => 0, 'direct_commission_rate' => 4.00, 'override_commission_rate' => 0.00, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Silver', 'required_downlines' => 6, 'direct_commission_rate' => 5.00, 'override_commission_rate' => 1.50, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Gold', 'required_downlines' => 12, 'direct_commission_rate' => 6.00, 'override_commission_rate' => 2.00, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 3. System Settings (e.g. global platform config)
        \App\Models\SystemSetting::insert([
            ['key' => 'tenant_registration_fee_usd', 'value' => json_encode(100), 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 4. Create one initial test Tenant
        \App\Models\Tenant::create([
            'name' => 'Alpha Platform',
            'subdomain' => 'alpha',
            'db_name' => 'tenant_alpha',
            'country_code' => 'ID',
            'currency_id' => 4, // IDR
            'status' => 'active',
        ]);
        
        // Note: Actual Tenant DB seeding (like creating the Admin) should be done 
        // via a separate TenantSeeder that runs after the DB is created.
    }
}
