<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AdminPanelServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Initialize performance optimizations
        $this->initializePerformanceOptimizations();

        // Warm cache on admin panel access
        $this->warmCacheOnAdminAccess();

        // Add performance monitoring
        $this->addPerformanceMonitoring();

        // Optimize Filament configuration
        $this->optimizeFilamentConfiguration();
    }

    /**
     * Initialize performance optimizations
     */
    private function initializePerformanceOptimizations(): void
    {
        // Performance optimizations removed - using Laravel defaults
    }

    /**
     * Warm cache when admin panel is accessed
     */
    private function warmCacheOnAdminAccess(): void
    {
        // Cache warming removed - using Laravel defaults
    }

    /**
     * Add performance monitoring
     */
    private function addPerformanceMonitoring(): void
    {
        // Performance monitoring removed - using Laravel defaults
    }

    /**
     * Optimize Filament configuration
     */
    private function optimizeFilamentConfiguration(): void
    {
        // Configure Filament for better performance
        config([
            'filament.default_filesystem_disk' => 'public',
            'filament.assets.combine' => true,
            'filament.assets.minify' => !app()->environment('local'),
            'filament.database_notifications.enabled' => false,
            'filament.broadcasting.echo' => false,
        ]);
    }
}
