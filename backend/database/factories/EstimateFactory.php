<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class EstimateFactory extends Factory
{
    public function definition(): array
    {
        $subtotal = $this->faker->randomFloat(2, 5000, 50000);
        $totalCost = $subtotal * $this->faker->randomFloat(2, 0.3, 0.7);
        $taxRate = $this->faker->randomElement([5, 12, 18, 28]);
        $totalTax = $subtotal * ($taxRate / 100);
        $shippingCharges = $this->faker->randomFloat(2, 0, 500);
        $otherCharges = $this->faker->randomFloat(2, 0, 200);
        $adjustment = $this->faker->randomFloat(2, -100, 100);
        $roundOff = $this->faker->randomFloat(2, -1, 1);
        $grandTotal = $subtotal + $totalTax + $shippingCharges + $otherCharges + $adjustment + $roundOff;

        return [
            'type' => 'ESTIMATE',
            'number' => 'E-' . date('Y') . '-' . $this->faker->unique()->numberBetween(1000, 9999),
            'date' => $this->faker->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'validity' => $this->faker->numberBetween(5, 30),
            'status' => $this->faker->randomElement(['DRAFT', 'SENT', 'ACCEPTED', 'REJECTED', 'EXPIRED', 'INVOICED']),
            'active' => $this->faker->boolean(85),
            'branch_id' => $this->faker->bothify('CHN###'),
            'channel' => $this->faker->randomElement(['Online', 'Offline', 'Phone', 'Email', 'Walk-in']),
            'customer_id' => $this->faker->bothify('CUST###'),
            'salesperson' => $this->faker->name(),
            // Flattened options
            'tax_inclusive' => $this->faker->boolean(),
            'show_bank_details' => $this->faker->boolean(80),
            'bank_id' => $this->faker->bothify('BANK###'),
            'show_signature' => $this->faker->boolean(70),
            'show_upi_qr' => $this->faker->boolean(60),
            'customer_billing' => [
                'name' => $this->faker->company(),
                'address' => $this->faker->streetAddress(),
                'city' => $this->faker->city(),
                'state' => $this->faker->state(),
                'pincode' => $this->faker->postcode(),
                'state_code' => $this->faker->numberBetween(10, 35),
                'gstin' => $this->faker->bothify('##?????####?#?#'),
                'phone' => $this->faker->phoneNumber(),
            ],
            'customer_shipping' => [
                'name' => $this->faker->company(),
                'address' => $this->faker->streetAddress(),
                'city' => $this->faker->city(),
                'state' => $this->faker->state(),
                'pincode' => $this->faker->postcode(),
                'state_code' => $this->faker->numberBetween(10, 35),
                'gstin' => $this->faker->bothify('##?????####?#?#'),
                'phone' => $this->faker->phoneNumber(),
            ],
            'items' => $this->generateItems(),
            // Flattened totals
            'subtotal' => $subtotal,
            'total_cost' => $totalCost,
            'taxable_amount' => $subtotal,
            'total_tax' => $totalTax,
            'shipping_charges' => $shippingCharges,
            'other_charges' => $otherCharges,
            'adjustment' => $adjustment,
            'round_off' => $roundOff,
            'grand_total' => $grandTotal,
            'terms' => $this->faker->optional(0.8)->paragraph(),
            'notes' => $this->faker->optional(0.6)->sentence(),
            'created_by' => $this->faker->bothify('USER###'),
            'updated_by' => $this->faker->bothify('USER###'),
        ];
    }

    private function generateItems(): array
    {
        $items = [];
        $itemCount = $this->faker->numberBetween(1, 5);

        for ($i = 0; $i < $itemCount; $i++) {
            $quantity = $this->faker->numberBetween(1, 20);
            $rate = $this->faker->randomFloat(2, 100, 5000);
            $cost = $rate * $this->faker->randomFloat(2, 0.3, 0.8);
            $taxRate = $this->faker->randomElement([5, 12, 18, 28]);
            $subtotal = $quantity * $rate;
            $tax = $subtotal * ($taxRate / 100);
            $totalAmount = $subtotal + $tax;

            $items[] = [
                'name' => $this->faker->words(3, true),
                'description' => $this->faker->sentence(),
                'hsn_sac' => $this->faker->numerify('######'),
                'quantity' => $quantity,
                'unit' => $this->faker->randomElement(['Pieces', 'Hours', 'Pages', 'Service', 'Design', 'Nos']),
                'cost' => $cost,
                'rate' => $rate,
                'tax_rate' => $taxRate,
                'total_amount' => $totalAmount,
            ];
        }

        return $items;
    }

    public function draft(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'DRAFT',
        ]);
    }

    public function sent(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'SENT',
        ]);
    }

    public function accepted(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'ACCEPTED',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'active' => false,
        ]);
    }
}
