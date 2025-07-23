<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Customer;
use App\Models\Merchant;
use App\Models\Product;
use App\Models\OptionGroup;
use App\Models\Option;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🛒 Starting Order Seeding...');

        // Get customers and merchants
        $customers = Customer::all();
        $merchants = Merchant::all();

        if ($customers->isEmpty() || $merchants->isEmpty()) {
            $this->command->error('No customers or merchants found. Please run their seeders first.');
            return;
        }

        // Create orders for each customer
        foreach ($customers->take(10) as $customer) {
            $this->createOrdersForCustomer($customer, $merchants);
        }

        $this->command->info('✅ Order Seeding completed successfully!');
    }

    /**
     * Create orders for a specific customer.
     */
    private function createOrdersForCustomer(Customer $customer, $merchants): void
    {
        // Create 2-5 orders per customer
        $orderCount = rand(2, 5);

        for ($i = 0; $i < $orderCount; $i++) {
            $merchant = $merchants->random();
            $this->createOrder($customer, $merchant);
        }
    }

    /**
     * Create a single order.
     */
    private function createOrder(Customer $customer, Merchant $merchant): void
    {
        // Get products for this merchant
        $products = Product::where('merchant_id', $merchant->id)
            ->where('is_available', true)
            ->with(['optionGroups.options'])
            ->get();

        if ($products->isEmpty()) {
            return;
        }

        // Create order
        $order = Order::create([
            'customer_id' => $customer->id,
            'merchant_id' => $merchant->id,
            'order_number' => 'ORD-' . strtoupper(substr(md5(uniqid()), 0, 8)),
            'status' => $this->getRandomOrderStatus(),
            'payment_status' => $this->getRandomPaymentStatus(),
            'payment_method' => $this->getRandomPaymentMethod(),
            'subtotal' => 0, // Will be calculated
            'tax_amount' => 0, // Will be calculated
            'delivery_fee' => rand(0, 1) ? rand(5, 15) : 0,
            'total_amount' => 0, // Will be calculated
            'delivery_address' => $this->generateDeliveryAddress(),
            'notes' => $this->getRandomDeliveryInstructions(),
            'estimated_delivery_time' => now()->addMinutes(rand(30, 90)),
            'created_at' => now()->subDays(rand(0, 30))->subHours(rand(0, 23)),
        ]);

        // Add order items
        $this->createOrderItems($order, $products);

        // Calculate totals
        $this->calculateOrderTotals($order);

        $customerName = is_array($customer->name) ? ($customer->name['en'] ?? $customer->name['ar'] ?? 'Customer') : $customer->name;
        $merchantName = is_array($merchant->business_name) ? ($merchant->business_name['en'] ?? $merchant->business_name['ar'] ?? 'Merchant') : $merchant->business_name;
        $this->command->info("  ✅ Created order {$order->order_number} for {$customerName} from {$merchantName}");
    }

    /**
     * Create order items for an order.
     */
    private function createOrderItems(Order $order, $products): void
    {
        // Add 1-4 products to the order (but not more than available)
        $maxItems = min(4, $products->count());
        $itemCount = rand(1, $maxItems);
        $selectedProducts = $products->random($itemCount);

        foreach ($selectedProducts as $product) {
            $quantity = rand(1, 3);

            // Generate customizations and options summary
            $customizations = $this->generateCustomizations($product);
            $optionsSummary = $this->generateOptionsSummary($customizations);

            // Calculate unit price with options
            $unitPrice = $this->calculateUnitPriceWithOptions($product, $customizations);

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $unitPrice * $quantity,
                'product_snapshot' => $this->createProductSnapshot($product),
                'customizations' => $customizations,
                'options_summary' => $optionsSummary,
                'special_instructions' => $this->getRandomSpecialInstructions(),
            ]);
        }
    }

    /**
     * Generate customizations for a product.
     */
    private function generateCustomizations(Product $product): array
    {
        $customizations = [];

        foreach ($product->optionGroups as $group) {
            if ($group->options->isEmpty()) continue;

            $selectedOptions = [];

            if ($group->is_required || rand(1, 3) === 1) {
                // Determine how many options to select
                $minSelections = max(1, $group->min_selections);
                $maxSelections = $group->max_selections == 0 ? 3 : min($group->max_selections, $group->options->count());
                $selectCount = rand($minSelections, $maxSelections);

                $selectedOptionsList = $group->options->random(min($selectCount, $group->options->count()));

                foreach ($selectedOptionsList as $option) {
                    $selectedOptions[] = [
                        'option_id' => $option->id,
                        'name' => $option->name,
                        'price_modifier' => $option->price_modifier,
                    ];
                }
            }

            if (!empty($selectedOptions)) {
                $customizations[$group->type . '_' . $group->id] = [
                    'group_id' => $group->id,
                    'group_name' => $group->name,
                    'group_type' => $group->type,
                    'selected_options' => $selectedOptions,
                ];
            }
        }

        return $customizations;
    }

    /**
     * Generate options summary from customizations.
     */
    private function generateOptionsSummary(array $customizations): array
    {
        $summaryEn = [];
        $summaryAr = [];

        foreach ($customizations as $group) {
            foreach ($group['selected_options'] as $option) {
                $nameEn = is_array($option['name']) ? ($option['name']['en'] ?? '') : $option['name'];
                $nameAr = is_array($option['name']) ? ($option['name']['ar'] ?? '') : $option['name'];

                if ($nameEn) $summaryEn[] = $nameEn;
                if ($nameAr) $summaryAr[] = $nameAr;
            }
        }

        return [
            'en' => implode(', ', $summaryEn),
            'ar' => implode('، ', $summaryAr),
        ];
    }

    /**
     * Calculate unit price with selected options.
     */
    private function calculateUnitPriceWithOptions(Product $product, array $customizations): float
    {
        $basePrice = $product->discounted_price ?? $product->base_price;
        $optionsPrice = 0;

        foreach ($customizations as $group) {
            foreach ($group['selected_options'] as $option) {
                $optionsPrice += $option['price_modifier'];
            }
        }

        return $basePrice + $optionsPrice;
    }

    /**
     * Create product snapshot.
     */
    private function createProductSnapshot(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'base_price' => $product->base_price,
            'discounted_price' => $product->discounted_price,
            'image' => $product->images[0] ?? null,
            'merchant_name' => $product->merchant->business_name,
            'category_name' => $product->internalCategory->name ?? null,
        ];
    }

    /**
     * Calculate order totals.
     */
    private function calculateOrderTotals(Order $order): void
    {
        $subtotal = $order->items()->sum('total_price');
        $taxRate = 0.15; // 15% tax
        $taxAmount = $subtotal * $taxRate;
        $totalAmount = $subtotal + $taxAmount + $order->delivery_fee;

        $order->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
        ]);
    }

    /**
     * Get random order status.
     */
    private function getRandomOrderStatus(): string
    {
        $statuses = ['pending', 'confirmed', 'preparing', 'ready', 'out_for_delivery', 'delivered', 'cancelled'];
        $weights = [10, 15, 10, 8, 12, 40, 5]; // Higher weight for delivered orders

        return $this->getWeightedRandom($statuses, $weights);
    }

    /**
     * Get random payment status.
     */
    private function getRandomPaymentStatus(): string
    {
        $statuses = ['pending', 'paid', 'failed', 'refunded'];
        $weights = [5, 80, 10, 5];

        return $this->getWeightedRandom($statuses, $weights);
    }

    /**
     * Get random payment method.
     */
    private function getRandomPaymentMethod(): string
    {
        $methods = ['cash', 'card', 'wallet', 'bank_transfer'];
        $weights = [30, 40, 20, 10];

        return $this->getWeightedRandom($methods, $weights);
    }

    /**
     * Generate delivery address.
     */
    private function generateDeliveryAddress(): array
    {
        $addresses = [
            ['en' => '123 King Fahd Road, Riyadh', 'ar' => '123 طريق الملك فهد، الرياض'],
            ['en' => '456 Prince Mohammed Street, Jeddah', 'ar' => '456 شارع الأمير محمد، جدة'],
            ['en' => '789 Al Khobar Corniche, Dammam', 'ar' => '789 كورنيش الخبر، الدمام'],
            ['en' => '321 University Avenue, Riyadh', 'ar' => '321 شارع الجامعة، الرياض'],
        ];

        return $addresses[array_rand($addresses)];
    }

    /**
     * Get random delivery instructions.
     */
    private function getRandomDeliveryInstructions(): ?string
    {
        if (rand(1, 3) !== 1) return null; // 33% chance of having instructions

        $instructions = [
            'Ring the doorbell twice',
            'Leave at the door',
            'Call when you arrive',
            'Building entrance code: 1234',
            'Apartment 5B, second floor',
        ];

        return $instructions[array_rand($instructions)];
    }

    /**
     * Get random special instructions.
     */
    private function getRandomSpecialInstructions(): ?string
    {
        if (rand(1, 4) !== 1) return null; // 25% chance of having instructions

        $instructions = [
            'Extra spicy please',
            'No onions',
            'Well done',
            'Extra sauce on the side',
            'Light on the salt',
        ];

        return $instructions[array_rand($instructions)];
    }

    /**
     * Get weighted random selection.
     */
    private function getWeightedRandom(array $values, array $weights): string
    {
        $totalWeight = array_sum($weights);
        $random = rand(1, $totalWeight);

        $currentWeight = 0;
        foreach ($values as $index => $value) {
            $currentWeight += $weights[$index];
            if ($random <= $currentWeight) {
                return $value;
            }
        }

        return $values[0]; // Fallback
    }
}
