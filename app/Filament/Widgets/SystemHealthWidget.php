<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\{Cache, DB, Storage};
use Carbon\Carbon;

class SystemHealthWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected static bool $isLazy = true;
    protected int $cacheTime = 60; // Cache for 1 minute for system health

    protected function getStats(): array
    {
        return Cache::remember('system_health_stats', $this->cacheTime, function () {
            $health = $this->getSystemHealth();

            return [
                Stat::make(__('system.database_status'), $health['database']['status'])
                    ->description($health['database']['description'])
                    ->descriptionIcon($health['database']['icon'])
                    ->color($health['database']['color']),

                Stat::make(__('system.storage_space'), $health['storage']['percentage'] . '%')
                    ->description($health['storage']['description'])
                    ->descriptionIcon($health['storage']['icon'])
                    ->color($health['storage']['color']),

                Stat::make(__('system.system_performance'), $health['performance']['status'])
                    ->description($health['performance']['description'])
                    ->descriptionIcon($health['performance']['icon'])
                    ->color($health['performance']['color']),

                Stat::make(__('system.last_backup'), $health['backup']['status'])
                    ->description($health['backup']['description'])
                    ->descriptionIcon($health['backup']['icon'])
                    ->color($health['backup']['color']),
            ];
        });
    }

    private function getSystemHealth(): array
    {
        return [
            'database' => $this->getDatabaseHealth(),
            'storage' => $this->getStorageHealth(),
            'performance' => $this->getPerformanceHealth(),
            'backup' => $this->getBackupHealth(),
        ];
    }

    private function getDatabaseHealth(): array
    {
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            $responseTime = round((microtime(true) - $start) * 1000, 2);

            if ($responseTime < 100) {
                return [
                    'status' => __('system.excellent'),
                    'description' => __('system.response_time', ['time' => $responseTime]),
                    'icon' => 'heroicon-m-check-circle',
                    'color' => 'success'
                ];
            } elseif ($responseTime < 500) {
                return [
                    'status' => __('system.good'),
                    'description' => __('system.response_time', ['time' => $responseTime]),
                    'icon' => 'heroicon-m-exclamation-triangle',
                    'color' => 'warning'
                ];
            } else {
                return [
                    'status' => __('system.slow'),
                    'description' => __('system.response_time', ['time' => $responseTime]),
                    'icon' => 'heroicon-m-x-circle',
                    'color' => 'danger'
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => __('system.error'),
                'description' => __('system.database_connection_failed'),
                'icon' => 'heroicon-m-x-circle',
                'color' => 'danger'
            ];
        }
    }

    private function getStorageHealth(): array
    {
        try {
            $totalSpace = disk_total_space(storage_path());
            $freeSpace = disk_free_space(storage_path());
            $usedSpace = $totalSpace - $freeSpace;
            $percentage = round(($usedSpace / $totalSpace) * 100, 1);

            $freeSpaceGB = round($freeSpace / (1024 * 1024 * 1024), 2);

            if ($percentage < 70) {
                $color = 'success';
                $icon = 'heroicon-m-check-circle';
            } elseif ($percentage < 85) {
                $color = 'warning';
                $icon = 'heroicon-m-exclamation-triangle';
            } else {
                $color = 'danger';
                $icon = 'heroicon-m-x-circle';
            }

            return [
                'percentage' => $percentage,
                'description' => __('system.available_space', ['space' => $freeSpaceGB]),
                'icon' => $icon,
                'color' => $color
            ];
        } catch (\Exception $e) {
            return [
                'percentage' => __('system.unknown'),
                'description' => __('system.storage_read_failed'),
                'icon' => 'heroicon-m-x-circle',
                'color' => 'danger'
            ];
        }
    }

    private function getPerformanceHealth(): array
    {
        try {
            // Check average response time from recent logs or cache
            $avgResponseTime = $this->getAverageResponseTime();

            if ($avgResponseTime < 200) {
                return [
                    'status' => 'سريع',
                    'description' => "متوسط الاستجابة: {$avgResponseTime}ms",
                    'icon' => 'heroicon-m-bolt',
                    'color' => 'success'
                ];
            } elseif ($avgResponseTime < 500) {
                return [
                    'status' => __('system.medium'),
                    'description' => __('system.avg_response_time', ['time' => $avgResponseTime]),
                    'icon' => 'heroicon-m-clock',
                    'color' => 'warning'
                ];
            } else {
                return [
                    'status' => __('system.slow'),
                    'description' => __('system.avg_response_time', ['time' => $avgResponseTime]),
                    'icon' => 'heroicon-m-exclamation-triangle',
                    'color' => 'danger'
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => __('system.unknown'),
                'description' => __('system.performance_check_failed'),
                'icon' => 'heroicon-m-question-mark-circle',
                'color' => 'gray'
            ];
        }
    }

    private function getBackupHealth(): array
    {
        try {
            // Check if backup files exist (this is a mock implementation)
            $backupPath = storage_path('app/backups');

            if (is_dir($backupPath)) {
                $files = glob($backupPath . '/*.sql');
                if (!empty($files)) {
                    $latestBackup = max(array_map('filemtime', $files));
                    $daysSince = Carbon::createFromTimestamp($latestBackup)->diffInDays(now());

                    if ($daysSince <= 1) {
                        return [
                            'status' => 'حديثة',
                            'description' => 'آخر نسخة احتياطية: اليوم',
                            'icon' => 'heroicon-m-shield-check',
                            'color' => 'success'
                        ];
                    } elseif ($daysSince <= 7) {
                        return [
                            'status' => 'قديمة',
                            'description' => "آخر نسخة احتياطية: منذ {$daysSince} أيام",
                            'icon' => 'heroicon-m-exclamation-triangle',
                            'color' => 'warning'
                        ];
                    } else {
                        return [
                            'status' => 'قديمة جداً',
                            'description' => "آخر نسخة احتياطية: منذ {$daysSince} أيام",
                            'icon' => 'heroicon-m-x-circle',
                            'color' => 'danger'
                        ];
                    }
                }
            }

            return [
                'status' => __('system.backup_not_found'),
                'description' => __('system.no_backups_found_desc'),
                'icon' => 'heroicon-m-exclamation-triangle',
                'color' => 'warning'
            ];
        } catch (\Exception $e) {
            return [
                'status' => __('system.error'),
                'description' => __('system.no_backups_found'),
                'icon' => 'heroicon-m-x-circle',
                'color' => 'danger'
            ];
        }
    }

    private function getAverageResponseTime(): int
    {
        // Mock implementation - in real scenario, you'd check logs or monitoring data
        return rand(50, 300);
    }
}
