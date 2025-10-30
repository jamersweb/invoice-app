<?php

namespace Database\Factories\Modules\Invoices\Models;

use App\Modules\Invoices\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\Invoices\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_number' => $this->faker->unique()->numerify('INV-####'),
            'amount' => $this->faker->randomFloat(2, 1000, 50000),
            'currency' => 'USD',
            'due_date' => $this->faker->dateTimeBetween('now', '+30 days'),
            'status' => $this->faker->randomElement(['pending', 'approved', 'funded', 'overdue']),
            'supplier_id' => 1, // Default supplier ID for tests
            'buyer_id' => 1, // Default buyer ID for tests
            'assigned_to' => null,
            'priority' => $this->faker->randomElement(['low', 'medium', 'high']),
        ];
    }

    /**
     * Indicate that the invoice is overdue.
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'overdue',
            'due_date' => $this->faker->dateTimeBetween('-30 days', '-1 day'),
        ]);
    }

    /**
     * Indicate that the invoice has high priority.
     */
    public function highPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'high',
        ]);
    }
}
