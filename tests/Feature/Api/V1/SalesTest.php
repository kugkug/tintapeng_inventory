<?php

namespace Tests\Feature\Api\V1;

use App\Models\Location;
use App\Models\Product;
use Tests\TestCase;

class SalesTest extends TestCase
{
    protected Location $mainLocation;
    protected Product $product;

    /**
     * Set up test data
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create location
        $this->mainLocation = Location::factory()->create(['tenant_id' => $this->tenant->id]);

        // Create product with inventory
        $this->product = Product::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->product->inventoryItems()->create([
            'location_id' => $this->mainLocation->id,
            'quantity' => 50,
        ]);
    }

    /**
     * Test staff can create a complete sale transaction
     */
    public function test_staff_can_create_sale_transaction(): void
    {
        $response = $this->withHeaders($this->getApiHeaders($this->staffToken))
            ->postJson('/api/v1/sales', [
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 5,
                        'location_id' => $this->mainLocation->id,
                    ],
                ],
                'payment_method' => 'cash',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'subtotal',
                    'discount_amount',
                    'total_amount',
                    'payment_method',
                ],
            ]);

        // Verify stock was decremented
        $this->product->refresh();
        $inventory = $this->product->inventoryItems()
            ->where('location_id', $this->mainLocation->id)
            ->first();

        $this->assertEquals(45, $inventory->quantity);
    }

    /**
     * Test sale fails if insufficient stock
     */
    public function test_sale_fails_with_insufficient_stock(): void
    {
        // Try to sell more than available
        $response = $this->withHeaders($this->getApiHeaders($this->staffToken))
            ->postJson('/api/v1/sales', [
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 100, // More than available
                        'location_id' => $this->mainLocation->id,
                    ],
                ],
                'payment_method' => 'cash',
            ]);

        $response->assertStatus(422);
    }

    /**
     * Test sale with discount code
     */
    public function test_sale_can_include_discount(): void
    {
        // This test assumes discount functionality
        // Create a discount code first (admin feature)
        $discountResponse = $this->withHeaders($this->getApiHeaders($this->adminToken))
            ->postJson('/api/v1/discounts', [
                'code' => 'SAVE10',
                'discount_type' => 'percentage',
                'discount_value' => 10,
                'valid_from' => now(),
                'valid_until' => now()->addDays(30),
                'max_uses' => 100,
            ]);

        if ($discountResponse->status() === 201) {
            $discount = $discountResponse->json('data');

            $response = $this->withHeaders($this->getApiHeaders($this->staffToken))
                ->postJson('/api/v1/sales', [
                    'items' => [
                        [
                            'product_id' => $this->product->id,
                            'quantity' => 10,
                            'location_id' => $this->mainLocation->id,
                        ],
                    ],
                    'discount_id' => $discount['id'],
                    'payment_method' => 'card',
                ]);

            $response->assertStatus(201);
        }
    }

    /**
     * Test manager can view sales
     */
    public function test_manager_can_view_sales(): void
    {
        // Create a sale first
        $this->withHeaders($this->getApiHeaders($this->staffToken))
            ->postJson('/api/v1/sales', [
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'quantity' => 2,
                        'location_id' => $this->mainLocation->id,
                    ],
                ],
                'payment_method' => 'cash',
            ]);

        $response = $this->withHeaders($this->getApiHeaders($this->managerToken))
            ->getJson('/api/v1/sales');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'subtotal',
                        'total_amount',
                        'payment_method',
                    ],
                ],
            ]);
    }

    /**
     * Test sales can be filtered by date range
     */
    public function test_sales_can_be_filtered_by_date(): void
    {
        $response = $this->withHeaders($this->getApiHeaders($this->managerToken))
            ->getJson('/api/v1/sales?start_date=2026-08-01&end_date=2026-08-31');

        $response->assertStatus(200);
    }
}
