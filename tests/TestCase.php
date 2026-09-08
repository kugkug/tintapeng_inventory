<?php

namespace Tests;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tymon\JwtAuth\Facades\JwtAuth;

abstract class TestCase extends BaseTestCase
{
    protected Tenant $tenant;
    protected User $adminUser;
    protected User $managerUser;
    protected User $staffUser;
    protected string $adminToken;
    protected string $managerToken;
    protected string $staffToken;

    /**
     * Set up test environment
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTestData();
    }

    /**
     * Set up test data (tenant, users, tokens)
     */
    protected function setUpTestData(): void
    {
        // Create tenant
        $this->tenant = Tenant::factory()->create();

        // Create users
        $this->adminUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role' => 'admin',
        ]);

        $this->managerUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role' => 'manager',
        ]);

        $this->staffUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role' => 'staff',
        ]);

        // Generate JWT tokens
        $this->adminToken = JwtAuth::claims([
            'tenant_id' => $this->tenant->id,
            'role' => 'admin',
        ])->fromUser($this->adminUser);

        $this->managerToken = JwtAuth::claims([
            'tenant_id' => $this->tenant->id,
            'role' => 'manager',
        ])->fromUser($this->managerUser);

        $this->staffToken = JwtAuth::claims([
            'tenant_id' => $this->tenant->id,
            'role' => 'staff',
        ])->fromUser($this->staffUser);
    }

    /**
     * Get API headers with JWT token
     */
    protected function getApiHeaders(?string $token = null): array
    {
        return [
            'Authorization' => 'Bearer ' . ($token ?? $this->adminToken),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
    }
}
