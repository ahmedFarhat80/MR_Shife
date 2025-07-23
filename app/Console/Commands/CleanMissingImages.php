<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\InternalCategory;
use App\Models\Product;
use App\Models\Merchant;
use App\Models\Customer;

class CleanMissingImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'images:clean-missing {--dry-run : Show what would be cleaned without actually doing it}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean missing image references from database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('🔍 Running in dry-run mode. No changes will be made.');
        }

        $this->info('🧹 Starting to clean missing images...');

        // تنظيف صور الفئات
        $this->cleanCategoryImages($dryRun);

        // تنظيف صور المنتجات
        $this->cleanProductImages($dryRun);

        // تنظيف صور التجار
        $this->cleanMerchantImages($dryRun);

        // تنظيف صور العملاء
        $this->cleanCustomerImages($dryRun);

        $this->info('✅ Image cleaning completed!');
    }

    private function cleanCategoryImages($dryRun)
    {
        $this->info('📁 Checking category images...');

        $categories = InternalCategory::whereNotNull('image')->get();
        $cleaned = 0;

        foreach ($categories as $category) {
            if ($category->image) {
                $imagePath = storage_path('app/public/' . $category->image);
                if (!file_exists($imagePath)) {
                    $this->warn("Missing: {$category->image} for category: {$category->name}");
                    if (!$dryRun) {
                        $category->update(['image' => null]);
                    }
                    $cleaned++;
                }
            }
        }

        $this->info("📁 Categories: {$cleaned} missing images " . ($dryRun ? 'found' : 'cleaned'));
    }

    private function cleanProductImages($dryRun)
    {
        $this->info('🍕 Checking product images...');

        $products = Product::where('background_type', 'image')
                          ->whereNotNull('background_value')
                          ->get();
        $cleaned = 0;

        foreach ($products as $product) {
            if ($product->background_value) {
                $imagePath = storage_path('app/public/' . $product->background_value);
                if (!file_exists($imagePath)) {
                    $this->warn("Missing: {$product->background_value} for product: {$product->name}");
                    if (!$dryRun) {
                        $product->update(['background_value' => null, 'background_type' => 'color']);
                    }
                    $cleaned++;
                }
            }
        }

        $this->info("🍕 Products: {$cleaned} missing images " . ($dryRun ? 'found' : 'cleaned'));
    }

    private function cleanMerchantImages($dryRun)
    {
        $this->info('🏪 Checking merchant images...');

        $merchants = Merchant::whereNotNull('business_logo')->get();
        $cleaned = 0;

        foreach ($merchants as $merchant) {
            if ($merchant->business_logo) {
                $imagePath = storage_path('app/public/' . $merchant->business_logo);
                if (!file_exists($imagePath)) {
                    $businessName = is_array($merchant->business_name)
                        ? ($merchant->business_name['ar'] ?? $merchant->business_name['en'] ?? 'Unknown')
                        : $merchant->business_name;
                    $this->warn("Missing: {$merchant->business_logo} for merchant: {$businessName}");
                    if (!$dryRun) {
                        $merchant->update(['business_logo' => null]);
                    }
                    $cleaned++;
                }
            }
        }

        $this->info("🏪 Merchants: {$cleaned} missing images " . ($dryRun ? 'found' : 'cleaned'));
    }

    private function cleanCustomerImages($dryRun)
    {
        $this->info('👤 Checking customer images...');

        $customers = Customer::whereNotNull('avatar')->get();
        $cleaned = 0;

        foreach ($customers as $customer) {
            if ($customer->avatar) {
                $imagePath = storage_path('app/public/' . $customer->avatar);
                if (!file_exists($imagePath)) {
                    $this->warn("Missing: {$customer->avatar} for customer: {$customer->name}");
                    if (!$dryRun) {
                        $customer->update(['avatar' => null]);
                    }
                    $cleaned++;
                }
            }
        }

        $this->info("👤 Customers: {$cleaned} missing images " . ($dryRun ? 'found' : 'cleaned'));
    }
}
