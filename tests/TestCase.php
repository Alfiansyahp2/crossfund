<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Run Tenant DB migrations fresh (RefreshDatabase only handles the central DB automatically)
        $this->artisan('migrate:fresh', ['--path' => 'database/migrations/tenant', '--database' => 'tenant']);
    }
}
