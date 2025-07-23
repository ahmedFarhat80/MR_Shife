<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AdminPerformanceService;
use App\Models\Admin;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TestAdminPerformance extends Command
{
    protected $signature = 'admin:test-performance';
    protected $description = 'Test admin panel performance and measure improvements';

    public function handle()
    {
        $this->info('🚀 Testing Admin Panel Performance...');
        $this->newLine();

        // Test 1: Database Query Performance
        $this->testDatabaseQueries();

        // Test 2: Cache Performance
        $this->testCachePerformance();

        // Test 3: Memory Usage
        $this->testMemoryUsage();

        // Test 4: Widget Performance
        $this->testWidgetPerformance();

        // Test 5: Resource Loading Performance
        $this->testResourcePerformance();

        $this->newLine();
        $this->info('✅ Performance testing completed!');
    }

    private function testDatabaseQueries()
    {
        $this->info('📊 Testing Database Query Performance...');

        DB::enableQueryLog();
        $startTime = microtime(true);

        // Test admin queries
        $admins = Admin::with(['roles:id,name'])->limit(10)->get();
        $roles = Role::with(['permissions:id,name'])->where('guard_name', 'admin')->limit(10)->get();
        $permissions = Permission::where('guard_name', 'admin')->limit(20)->get();

        $endTime = microtime(true);
        $executionTime = round(($endTime - $startTime) * 1000, 2);
        $queryCount = count(DB::getQueryLog());

        $this->line("  ⏱️  Execution Time: {$executionTime}ms");
        $this->line("  🔍 Query Count: {$queryCount}");
        
        if ($executionTime < 100) {
            $this->line("  ✅ Excellent performance!");
        } elseif ($executionTime < 300) {
            $this->line("  ⚠️  Good performance");
        } else {
            $this->line("  ❌ Needs optimization");
        }

        DB::disableQueryLog();
        $this->newLine();
    }

    private function testCachePerformance()
    {
        $this->info('💾 Testing Cache Performance...');

        // Test cache warming
        $startTime = microtime(true);
        AdminPerformanceService::warmCache();
        $warmTime = round((microtime(true) - $startTime) * 1000, 2);

        // Test cached data retrieval
        $startTime = microtime(true);
        $permissions = AdminPerformanceService::getCachedPermissions();
        $roles = AdminPerformanceService::getCachedRoles();
        $stats = AdminPerformanceService::getCachedAdminStats();
        $cacheTime = round((microtime(true) - $startTime) * 1000, 2);

        $this->line("  🔥 Cache Warming Time: {$warmTime}ms");
        $this->line("  ⚡ Cache Retrieval Time: {$cacheTime}ms");
        $this->line("  📈 Cached Permissions: " . count($permissions));
        $this->line("  🎭 Cached Roles: " . count($roles));

        if ($cacheTime < 10) {
            $this->line("  ✅ Excellent cache performance!");
        } elseif ($cacheTime < 50) {
            $this->line("  ⚠️  Good cache performance");
        } else {
            $this->line("  ❌ Cache needs optimization");
        }

        $this->newLine();
    }

    private function testMemoryUsage()
    {
        $this->info('🧠 Testing Memory Usage...');

        $memoryUsage = AdminPerformanceService::getMemoryUsage();
        
        $currentMB = round($memoryUsage['current'] / 1024 / 1024, 2);
        $peakMB = round($memoryUsage['peak'] / 1024 / 1024, 2);
        $limitMB = $this->parseMemoryLimit($memoryUsage['limit']);

        $this->line("  📊 Current Memory: {$currentMB}MB");
        $this->line("  📈 Peak Memory: {$peakMB}MB");
        $this->line("  🎯 Memory Limit: {$limitMB}MB");

        $usagePercentage = round(($peakMB / $limitMB) * 100, 1);
        $this->line("  📋 Usage: {$usagePercentage}%");

        if ($usagePercentage < 30) {
            $this->line("  ✅ Excellent memory usage!");
        } elseif ($usagePercentage < 60) {
            $this->line("  ⚠️  Good memory usage");
        } else {
            $this->line("  ❌ High memory usage - needs optimization");
        }

        $this->newLine();
    }

    private function testWidgetPerformance()
    {
        $this->info('📊 Testing Widget Performance...');

        // Test widget data loading
        $startTime = microtime(true);
        
        // Simulate widget data loading
        $adminCount = Admin::count();
        $roleCount = Role::where('guard_name', 'admin')->count();
        $permissionCount = Permission::where('guard_name', 'admin')->count();
        
        $widgetTime = round((microtime(true) - $startTime) * 1000, 2);

        $this->line("  ⏱️  Widget Loading Time: {$widgetTime}ms");
        $this->line("  👥 Admin Count: {$adminCount}");
        $this->line("  🎭 Role Count: {$roleCount}");
        $this->line("  🔐 Permission Count: {$permissionCount}");

        if ($widgetTime < 50) {
            $this->line("  ✅ Excellent widget performance!");
        } elseif ($widgetTime < 150) {
            $this->line("  ⚠️  Good widget performance");
        } else {
            $this->line("  ❌ Widget loading needs optimization");
        }

        $this->newLine();
    }

    private function testResourcePerformance()
    {
        $this->info('🏗️ Testing Resource Performance...');

        // Test paginated data loading
        $startTime = microtime(true);
        $paginatedAdmins = AdminPerformanceService::getPaginatedAdmins(10);
        $paginatedRoles = AdminPerformanceService::getPaginatedRoles(10);
        $resourceTime = round((microtime(true) - $startTime) * 1000, 2);

        $this->line("  ⏱️  Resource Loading Time: {$resourceTime}ms");
        $this->line("  👥 Paginated Admins: {$paginatedAdmins->count()}");
        $this->line("  🎭 Paginated Roles: {$paginatedRoles->count()}");

        if ($resourceTime < 100) {
            $this->line("  ✅ Excellent resource performance!");
        } elseif ($resourceTime < 250) {
            $this->line("  ⚠️  Good resource performance");
        } else {
            $this->line("  ❌ Resource loading needs optimization");
        }

        $this->newLine();
    }

    private function parseMemoryLimit($limit)
    {
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit)-1]);
        $limit = (int) $limit;

        switch($last) {
            case 'g':
                $limit *= 1024;
            case 'm':
                $limit *= 1024;
            case 'k':
                $limit *= 1024;
        }

        return round($limit / 1024 / 1024, 2);
    }
}
