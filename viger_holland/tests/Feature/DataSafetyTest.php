<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DataSafetyTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        DB::prohibitDestructiveCommands(false);

        parent::tearDown();
    }

    public function test_destructive_database_commands_are_blocked_in_production(): void
    {
        $this->app['env'] = 'production';
        (new AppServiceProvider($this->app))->boot();

        $this->artisan('migrate:fresh', ['--force' => true])->assertFailed();
        $this->artisan('migrate:reset', ['--force' => true])->assertFailed();
        $this->artisan('db:wipe', ['--force' => true])->assertFailed();
    }
}
