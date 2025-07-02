<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->words(2, true);
        $cost = $this->faker->randomFloat(2, 10, 500);
        $mrp = $cost * $this->faker->randomFloat(2, 1.2, 2.5);
        $price = $mrp * $this->faker->randomFloat(2, 0.8, 0.95);
        $salePrice = $this->faker->boolean(30) ? $price * $this->faker->randomFloat(2, 0.7, 0.9) : 0;

        return [
            'name' => ucwords($name),
            'slug' => \Illuminate\Support\Str::slug($name) . '-' . $this->faker->randomNumber(4),
            'type' => $this->faker->randomElement(['simple', 'variable', 'grouped', 'external']),
            'publication_status' => $this->faker->randomElement(['draft', 'published', 'discontinued', 'private']),
            'active' => $this->faker->boolean(85),
            'description' => $this->faker->optional(0.8)->paragraphs(2, true),
            'short_description' => $this->faker->optional(0.7)->sentence(),
            'sku' => $this->faker->boolean(90) ? $this->faker->unique()->bothify('SKU-###??##') : null,
            'barcode' => $this->faker->optional(0.6)->ean13(),
            'brand' => $this->faker->randomElement(['ASENSAR', 'Brand A', 'Brand B', 'Brand C', 'Generic']),
            'cost' => $cost,
            'mrp' => $mrp,
            'price' => $price,
            'sale_price' => $salePrice,
            'taxable' => $this->faker->boolean(90),
            
            // GST Information
            'tax_hsn_code' => $this->faker->optional(0.8)->numerify('####.##'),
            'tax_rate' => $this->faker->randomElement([5, 12, 18, 28]),
            'tax_inclusive' => $this->faker->boolean(70),
            
            // Stock Information
            'stock_track' => $this->faker->boolean(70),
            'stock_quantity' => $this->faker->numberBetween(0, 1000),
            'stock_low_threshold' => $this->faker->numberBetween(5, 50),
            
            // Dimensions
            'length' => $this->faker->optional(0.6)->randomFloat(2, 1, 100),
            'width' => $this->faker->optional(0.6)->randomFloat(2, 1, 100),
            'height' => $this->faker->optional(0.6)->randomFloat(2, 1, 100),
            'weight' => $this->faker->optional(0.7)->randomFloat(2, 0.1, 50),
            'unit' => $this->faker->randomElement(['nos', 'kg', 'ltr', 'mtr', 'pcs', 'box']),
            
            // Shipping
            'shipping_weight' => $this->faker->optional(0.5)->randomFloat(2, 0.1, 60),
            'shipping_required' => $this->faker->boolean(85),
            'shipping_taxable' => $this->faker->boolean(80),
            'shipping_class_id' => $this->faker->numberBetween(0, 5),
            
            // Images and URLs
            'image' => $this->faker->optional(0.8)->imageUrl(640, 480, 'products', true),
            'images' => $this->faker->optional(0.6)->randomElements([
                $this->faker->imageUrl(640, 480, 'products', true),
                $this->faker->imageUrl(640, 480, 'products', true),
                $this->faker->imageUrl(640, 480, 'products', true),
            ], $this->faker->numberBetween(1, 3)),
            'external_url' => $this->faker->optional(0.2)->url(),
            
            // Relations (stored as JSON arrays)
            'categories' => $this->faker->optional(0.8)->randomElements([1, 2, 3, 4, 5, 6, 7, 8, 9, 10], $this->faker->numberBetween(1, 3)),
            'tags' => $this->faker->optional(0.6)->randomElements([1, 2, 3, 4, 5, 6, 7, 8, 9, 10], $this->faker->numberBetween(1, 4)),
            'attributes' => $this->faker->optional(0.5)->randomElements([
                ['name' => 'Color', 'value' => $this->faker->colorName()],
                ['name' => 'Size', 'value' => $this->faker->randomElement(['Small', 'Medium', 'Large', 'XL'])],
                ['name' => 'Material', 'value' => $this->faker->randomElement(['Cotton', 'Plastic', 'Metal', 'Wood'])],
            ], $this->faker->numberBetween(1, 3)),
            'variations' => $this->faker->optional(0.3)->randomElements([
                ['sku' => $this->faker->bothify('VAR-###??##'), 'price' => $this->faker->randomFloat(2, 10, 100)],
                ['sku' => $this->faker->bothify('VAR-###??##'), 'price' => $this->faker->randomFloat(2, 10, 100)],
            ], $this->faker->numberBetween(1, 2)),
            'meta_data' => $this->faker->optional(0.4)->randomElements([
                ['key' => 'featured', 'value' => $this->faker->boolean()],
                ['key' => 'bestseller', 'value' => $this->faker->boolean()],
                ['key' => 'new_arrival', 'value' => $this->faker->boolean()],
            ], $this->faker->numberBetween(1, 3)),
            'related_ids' => $this->faker->optional(0.3)->randomElements([1, 2, 3, 4, 5], $this->faker->numberBetween(1, 3)),
            'upsell_ids' => $this->faker->optional(0.2)->randomElements([1, 2, 3, 4, 5], $this->faker->numberBetween(1, 2)),
            'cross_sell_ids' => $this->faker->optional(0.2)->randomElements([1, 2, 3, 4, 5], $this->faker->numberBetween(1, 3)),
        ];
    }

    /**
     * Indicate that the product is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => true,
        ]);
    }

    /**
     * Indicate that the product is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }

    /**
     * Backward compatibility alias for active()
     */
    public function enabled(): static
    {
        return $this->active();
    }

    /**
     * Backward compatibility alias for inactive()
     */
    public function disabled(): static
    {
        return $this->inactive();
    }

    /**
     * Indicate that the product is a simple product.
     */
    public function simple(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'simple',
        ]);
    }

    /**
     * Indicate that the product is a variable product.
     */
    public function variable(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'variable',
            'variations' => [
                ['sku' => fake()->bothify('VAR-###??##'), 'price' => fake()->randomFloat(2, 10, 100)],
                ['sku' => fake()->bothify('VAR-###??##'), 'price' => fake()->randomFloat(2, 10, 100)],
                ['sku' => fake()->bothify('VAR-###??##'), 'price' => fake()->randomFloat(2, 10, 100)],
            ],
        ]);
    }

    /**
     * Indicate that the product has stock tracking enabled.
     */
    public function tracked(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_track' => true,
            'stock_quantity' => fake()->numberBetween(10, 500),
        ]);
    }

    /**
     * Indicate that the product is out of stock.
     */
    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_track' => true,
            'stock_quantity' => 0,
        ]);
    }

    /**
     * Indicate that the product is low in stock.
     */
    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock_track' => true,
            'stock_quantity' => fake()->numberBetween(1, 5),
            'stock_low_threshold' => 10,
        ]);
    }

    /**
     * Indicate that the product is on sale.
     */
    public function onSale(): static
    {
        return $this->state(fn (array $attributes) => [
            'sale_price' => $attributes['price'] * fake()->randomFloat(2, 0.6, 0.9),
        ]);
    }

    /**
     * Indicate that the product is featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'meta_data' => array_merge($attributes['meta_data'] ?? [], [
                ['key' => 'featured', 'value' => true],
            ]),
        ]);
    }

    /**
     * Indicate that the product is a bestseller.
     */
    public function bestseller(): static
    {
        return $this->state(fn (array $attributes) => [
            'meta_data' => array_merge($attributes['meta_data'] ?? [], [
                ['key' => 'bestseller', 'value' => true],
            ]),
        ]);
    }
}
