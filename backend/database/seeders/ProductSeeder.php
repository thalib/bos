<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing products to avoid duplicates
        \App\Models\Product::truncate();

        // Create featured products
        \App\Models\Product::factory(5)
            ->active()
            ->simple()
            ->featured()
            ->onSale()
            ->create();

        // Create bestseller products
        \App\Models\Product::factory(8)
            ->active()
            ->simple()
            ->bestseller()
            ->tracked()
            ->create();

        // Create variable products with variations
        \App\Models\Product::factory(3)
            ->active()
            ->variable()
            ->tracked()
            ->create();

        // Create grouped products
        \App\Models\Product::factory(2)
            ->active()
            ->state(['type' => 'grouped'])
            ->create();

        // Create external products
        \App\Models\Product::factory(2)
            ->active()
            ->state(['type' => 'external'])
            ->create();

        // Create products with different stock statuses
        \App\Models\Product::factory(5)
            ->active()
            ->simple()
            ->lowStock()
            ->create();

        \App\Models\Product::factory(3)
            ->active()
            ->simple()
            ->outOfStock()
            ->create();

        // Create products with different publication_statuses
        \App\Models\Product::factory(10)
            ->simple()
            ->state(['publication_status' => 'draft'])
            ->create();

        \App\Models\Product::factory(5)
            ->simple()
            ->state(['publication_status' => 'private'])
            ->create();

        \App\Models\Product::factory(3)
            ->simple()
            ->state(['publication_status' => 'discontinued'])
            ->inactive()
            ->create();

        // Create regular products for bulk testing
        \App\Models\Product::factory(50)
            ->active()
            ->simple()
            ->create();

        // Create some products with specific brands
        $brands = ['ASENSAR', 'Brand A', 'Brand B', 'Brand C', 'Generic'];
        foreach ($brands as $brand) {
            \App\Models\Product::factory(3)
                ->active()
                ->simple()
                ->state(['brand' => $brand])
                ->create();
        }

        // Create products with high stock for testing
        \App\Models\Product::factory(10)
            ->active()
            ->simple()
            ->tracked()
            ->state([
                'stock_quantity' => fake()->numberBetween(500, 2000),
                'stock_low_threshold' => 50,
            ])
            ->create();

        $this->command->info('Products seeded successfully!');
        $this->command->info('Total products created: '.\App\Models\Product::count());
        $this->command->info('Active products: '.\App\Models\Product::active()->count());

        // Count featured products using a different approach for SQLite compatibility
        $featuredCount = \App\Models\Product::whereNotNull('meta_data')
            ->get()
            ->filter(function ($product) {
                if (! $product->meta_data) {
                    return false;
                }

                return collect($product->meta_data)->contains(function ($item) {
                    return isset($item['key']) && $item['key'] === 'featured' && isset($item['value']) && $item['value'] === true;
                });
            })
            ->count();

        $this->command->info('Featured products: '.$featuredCount);
        $this->command->info('Products on sale: '.\App\Models\Product::where('sale_price', '>', 0)->count());
    }
}
