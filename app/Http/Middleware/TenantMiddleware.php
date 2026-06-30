<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use App\Models\Tenant;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class TenantMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $baseDomain = parse_url(config('app.url'), PHP_URL_HOST) ?? 'localhost';

        if ($host !== $baseDomain) {
            // Extract subdomain
            $subdomain = str_replace('.' . $baseDomain, '', $host);

            // Find tenant
            $tenant = Tenant::where('subdomain', $subdomain)->where('status', 'active')->first();

            if (! $tenant) {
                abort(404, 'Tenant not found or inactive.');
            }

            // Switch database connection
            Config::set('database.connections.tenant.database', $tenant->db_name);
            DB::purge('tenant');
            DB::reconnect('tenant');

            // Set default connection for this request
            DB::setDefaultConnection('tenant');

            // Bind tenant to container for easy access later
            app()->instance('tenant', $tenant);
        }

        return $next($request);
    }
}
