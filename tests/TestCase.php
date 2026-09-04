<?php

namespace Tests;

use App\Services\StorePermissionService;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Flush the StorePermissionService in-memory request cache before every
     * test.  RefreshDatabase rolls back the DB transaction so auto-increment
     * IDs restart at 1 for each test method; without this flush a stale cache
     * entry from a previous test class (same store_id:user_id key) would
     * return wrong effective-permissions for the freshly-created users.
     */
    protected function setUp(): void
    {
        parent::setUp();
        StorePermissionService::invalidateCache();
    }
}
