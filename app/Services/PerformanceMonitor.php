<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class PerformanceMonitor
{
    private static $startTime;
    private static $queries = [];
    private static $slowQueries = [];

    /**
     * بدء مراقبة الأداء
     */
    public static function start(): void
    {
        self::$startTime = microtime(true);
        self::$queries = [];
        self::$slowQueries = [];

        // مراقبة الاستعلامات
        DB::listen(function ($query) {
            $executionTime = $query->time;
            
            self::$queries[] = [
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'time' => $executionTime,
                'connection' => $query->connectionName,
            ];

            // تسجيل الاستعلامات البطيئة (أبطأ من 100ms)
            if ($executionTime > 100) {
                self::$slowQueries[] = [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $executionTime,
                    'connection' => $query->connectionName,
                ];

                Log::warning('Slow Query Detected', [
                    'sql' => $query->sql,
                    'time' => $executionTime . 'ms',
                    'bindings' => $query->bindings
                ]);
            }
        });
    }

    /**
     * إنهاء مراقبة الأداء وإرجاع النتائج
     */
    public static function end(): array
    {
        $endTime = microtime(true);
        $totalTime = ($endTime - self::$startTime) * 1000; // بالميلي ثانية

        $results = [
            'total_time' => round($totalTime, 2),
            'total_queries' => count(self::$queries),
            'slow_queries_count' => count(self::$slowQueries),
            'slow_queries' => self::$slowQueries,
            'memory_usage' => self::getMemoryUsage(),
            'cache_hits' => self::getCacheStats(),
        ];

        // تسجيل النتائج
        Log::info('Performance Monitor Results', $results);

        return $results;
    }

    /**
     * الحصول على إحصائيات الذاكرة
     */
    private static function getMemoryUsage(): array
    {
        return [
            'current' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
            'peak' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB',
        ];
    }

    /**
     * الحصول على إحصائيات الكاش
     */
    private static function getCacheStats(): array
    {
        // محاولة الحصول على إحصائيات الكاش
        try {
            return [
                'status' => 'enabled',
                'driver' => config('cache.default'),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * تحليل الاستعلامات البطيئة
     */
    public static function analyzeSlowQueries(): array
    {
        $analysis = [];
        
        foreach (self::$slowQueries as $query) {
            $sql = $query['sql'];
            
            // تحديد نوع المشكلة
            $issues = [];
            
            if (strpos($sql, 'SELECT *') !== false) {
                $issues[] = 'استخدام SELECT * بدلاً من تحديد الحقول';
            }
            
            if (strpos($sql, 'JOIN') === false && strpos($sql, 'WHERE') !== false) {
                $issues[] = 'قد تحتاج إلى eager loading للعلاقات';
            }
            
            if (strpos($sql, 'ORDER BY') !== false && strpos($sql, 'LIMIT') === false) {
                $issues[] = 'ترتيب بدون تحديد limit';
            }
            
            $analysis[] = [
                'sql' => $sql,
                'time' => $query['time'],
                'potential_issues' => $issues,
                'suggestions' => self::getSuggestions($sql),
            ];
        }
        
        return $analysis;
    }

    /**
     * اقتراحات لتحسين الاستعلامات
     */
    private static function getSuggestions(string $sql): array
    {
        $suggestions = [];
        
        if (strpos($sql, 'SELECT *') !== false) {
            $suggestions[] = 'استخدم select() لتحديد الحقول المطلوبة فقط';
        }
        
        if (strpos($sql, 'roles') !== false || strpos($sql, 'permissions') !== false) {
            $suggestions[] = 'استخدم with() لتحميل العلاقات مسبقاً';
        }
        
        if (strpos($sql, 'COUNT(*)') !== false) {
            $suggestions[] = 'فكر في استخدام كاش للعدادات';
        }
        
        return $suggestions;
    }

    /**
     * تقرير مفصل عن الأداء
     */
    public static function generateReport(): string
    {
        $results = self::end();
        $analysis = self::analyzeSlowQueries();
        
        $report = "📊 تقرير الأداء\n";
        $report .= "================\n\n";
        $report .= "⏱️ الوقت الإجمالي: {$results['total_time']} ms\n";
        $report .= "🔍 عدد الاستعلامات: {$results['total_queries']}\n";
        $report .= "🐌 الاستعلامات البطيئة: {$results['slow_queries_count']}\n";
        $report .= "💾 استخدام الذاكرة: {$results['memory_usage']['current']} (الذروة: {$results['memory_usage']['peak']})\n\n";
        
        if (!empty($analysis)) {
            $report .= "🔍 تحليل الاستعلامات البطيئة:\n";
            $report .= "================================\n\n";
            
            foreach ($analysis as $i => $item) {
                $report .= "استعلام #" . ($i + 1) . " ({$item['time']} ms):\n";
                $report .= "SQL: " . substr($item['sql'], 0, 100) . "...\n";
                
                if (!empty($item['potential_issues'])) {
                    $report .= "المشاكل المحتملة:\n";
                    foreach ($item['potential_issues'] as $issue) {
                        $report .= "  - $issue\n";
                    }
                }
                
                if (!empty($item['suggestions'])) {
                    $report .= "الاقتراحات:\n";
                    foreach ($item['suggestions'] as $suggestion) {
                        $report .= "  ✅ $suggestion\n";
                    }
                }
                
                $report .= "\n";
            }
        }
        
        return $report;
    }
}
