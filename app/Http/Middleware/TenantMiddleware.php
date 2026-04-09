<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Zenith\Http\Request;
use Zenith\Http\Response;
use App\Models\Tenant;

class TenantMiddleware
{
    public function handle(Request $request, callable $next): Response
    {
        $tenant = $this->resolveTenant($request);

        if (!$tenant) {
            return response('Tenant not found', 404);
        }

        if (!$tenant->isActive() && !$tenant->isTrial()) {
            return response('Tenant account is suspended', 403);
        }

        // Store tenant in container for later access
        app()->singleton('tenant', fn() => $tenant);
        
        // Set tenant context
        config(['app.tenant_id' => $tenant->id]);
        config(['app.tenant_name' => $tenant->name]);

        return $next($request);
    }

    protected function resolveTenant(Request $request): ?Tenant
    {
        // Try to resolve tenant from subdomain
        $host = $request->header('HOST', '');
        $parts = explode('.', $host);
        
        if (count($parts) >= 2) {
            $subdomain = $parts[0];
            
            // Ignore common subdomains like www
            if (!in_array($subdomain, ['www', 'app', 'api'])) {
                $tenant = Tenant::where('subdomain', $subdomain)->first();
                if ($tenant) {
                    return $tenant;
                }
            }
        }

        // Try to resolve from custom domain
        $tenant = Tenant::where('domain', $host)->first();
        if ($tenant) {
            return $tenant;
        }

        // Try to resolve from request header (for API calls)
        $tenantId = $request->header('X-Tenant-ID');
        if ($tenantId) {
            return Tenant::find((int) $tenantId);
        }

        // Try to resolve from route parameter
        $tenantId = $request->route('tenant_id');
        if ($tenantId) {
            return Tenant::find((int) $tenantId);
        }

        return null;
    }
}
