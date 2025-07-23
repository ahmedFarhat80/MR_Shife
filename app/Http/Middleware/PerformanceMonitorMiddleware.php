<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\PerformanceMonitor;
use Illuminate\Support\Facades\Log;

class PerformanceMonitorMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // بدء مراقبة الأداء للصفحات الإدارية فقط
        if ($this->shouldMonitor($request)) {
            PerformanceMonitor::start();
        }

        $response = $next($request);

        // إنهاء مراقبة الأداء وتسجيل النتائج
        if ($this->shouldMonitor($request)) {
            $results = PerformanceMonitor::end();
            
            // تسجيل النتائج إذا كانت الصفحة بطيئة (أكثر من 1 ثانية)
            if ($results['total_time'] > 1000) {
                Log::warning('Slow Page Detected', [
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                    'results' => $results
                ]);
            }

            // إضافة النتائج إلى الاستجابة في بيئة التطوير
            if (app()->environment('local') && $request->wantsJson()) {
                $response->headers->set('X-Performance-Time', $results['total_time'] . 'ms');
                $response->headers->set('X-Performance-Queries', $results['total_queries']);
                $response->headers->set('X-Performance-Slow-Queries', $results['slow_queries_count']);
            }
        }

        return $response;
    }

    /**
     * تحديد ما إذا كان يجب مراقبة هذا الطلب
     */
    private function shouldMonitor(Request $request): bool
    {
        $path = $request->path();
        
        // مراقبة الصفحات الإدارية فقط
        $adminPaths = [
            'admin/roles',
            'admin/admins',
            'admin/dashboard',
            'livewire',
        ];

        foreach ($adminPaths as $adminPath) {
            if (str_contains($path, $adminPath)) {
                return true;
            }
        }

        return false;
    }
}
