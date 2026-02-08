<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('securecat:verify-env', function () {
    $ok = true;
    try {
        DB::connection()->getPdo();
        $this->info('DB: OK');
    } catch (\Throwable $e) {
        $this->error('DB: FAIL - ' . $e->getMessage());
        $ok = false;
    }
    try {
        Redis::connection()->ping();
        $this->info('Redis: OK');
    } catch (\Throwable $e) {
        $this->error('Redis: FAIL - ' . $e->getMessage());
        $ok = false;
    }
    if ($ok) {
        $this->info('Environment verification passed.');
        return 0;
    }
    $this->warn('Fix DB/Redis and run: php artisan securecat:verify-env');
    return 1;
})->purpose('Verify PostgreSQL and Redis connectivity (Phase 0)');
