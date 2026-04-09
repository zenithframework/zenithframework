<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use Zenith\Database\QueryBuilder;

class TenantService
{
    public function createTenant(array $data): Tenant
    {
        $qb = new QueryBuilder();
        $qb->table('tenants');

        // Generate unique slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = $this->generateSlug($data['name']);
        }

        // Ensure slug is unique
        $data['slug'] = $this->ensureUniqueSlug($data['slug']);

        // Set default status
        if (empty($data['status'])) {
            $data['status'] = 'trial';
        }

        // Set default plan
        if (empty($data['plan'])) {
            $data['plan'] = 'starter';
        }

        // Set trial period (14 days by default)
        if (empty($data['trial_ends_at'])) {
            $data['trial_ends_at'] = date('Y-m-d H:i:s', strtotime('+14 days'));
        }

        $tenant = Tenant::create($data);

        return $tenant;
    }

    public function createOwner(Tenant $tenant, array $userData): User
    {
        $userData['tenant_id'] = $tenant->id;
        $userData['role'] = 'owner';
        $userData['status'] = 'active';

        return User::create($userData);
    }

    public function provisionTenant(string $name, string $email, string $password, ?string $subdomain = null): Tenant
    {
        // Create tenant
        $tenantData = [
            'name' => $name,
            'subdomain' => $subdomain ?? $this->generateSlug($name),
            'plan' => 'starter',
            'status' => 'trial',
        ];

        $tenant = $this->createTenant($tenantData);

        // Create owner user
        $ownerData = [
            'name' => $name,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'role' => 'owner',
            'status' => 'active',
        ];

        $this->createOwner($tenant, $ownerData);

        return $tenant;
    }

    public function suspendTenant(int $tenantId): bool
    {
        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            return false;
        }

        $tenant->status = 'suspended';
        return $tenant->save();
    }

    public function activateTenant(int $tenantId): bool
    {
        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            return false;
        }

        $tenant->status = 'active';
        return $tenant->save();
    }

    public function upgradePlan(int $tenantId, string $plan): bool
    {
        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            return false;
        }

        $tenant->plan = $plan;
        return $tenant->save();
    }

    protected function generateSlug(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        return trim($slug, '-');
    }

    protected function ensureUniqueSlug(string $slug): string
    {
        $originalSlug = $slug;
        $counter = 1;

        $qb = new QueryBuilder();
        while ($qb->table('tenants')->where('slug', $slug)->count() > 0) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    public function getAllTenants(): array
    {
        return Tenant::all();
    }

    public function getTenantById(int $id): ?Tenant
    {
        return Tenant::find($id);
    }

    public function getTenantBySlug(string $slug): ?Tenant
    {
        return Tenant::where('slug', $slug)->first();
    }

    public function getTenantBySubdomain(string $subdomain): ?Tenant
    {
        return Tenant::where('subdomain', $subdomain)->first();
    }
}
