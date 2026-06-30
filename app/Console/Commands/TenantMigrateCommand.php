<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TenantMigrateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:migrate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run migrations for all tenant databases';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenants = \App\Models\Tenant::where('status', 'active')->get();

        foreach ($tenants as $tenant) {
            $this->info("Migrating tenant: {$tenant->name} ({$tenant->db_name})");

            \Illuminate\Support\Facades\Config::set('database.connections.tenant.database', $tenant->db_name);
            \Illuminate\Support\Facades\DB::purge('tenant');

            $this->call('migrate', [
                '--database' => 'tenant',
                '--path' => 'database/migrations/tenant',
                '--force' => true,
            ]);
        }

        $this->info('All tenant databases migrated successfully!');
    }
}
