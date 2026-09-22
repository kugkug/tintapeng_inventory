<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Location;
use App\Models\Product;
use Tests\TestCase;

class ProductTest extends TestCase
{
    /**
     * Test manager can create product with auto-barcode
     */
    public function test_manager_can_create_product_with_auto_barcode(): void
    {
        $category = Category::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->withHeaders($this->getApiHeaders($this->managerToken))
            ->postJson('/api/v1/products', [
                'name' => 'Test Product',
                'sku' => 'SKU-TEST-001',
                'category_id' => $category->id,
                'cost_per_unit' => 10.00,
                'selling_price' => 25.00,
                'description' => 'Test product description',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'sku',
                    'barcode',
                    'barcode_format',
                    'barcode_image_path',
                ],
            ]);

        // Verify barcode was generated
        $this->assertDatabaseHas('products', [
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Product',
            'sku' => 'SKU-TEST-001',
        ]);

        $product = Product::where('sku', 'SKU-TEST-001')->first();
        $this->assertNotNull($product->barcode);
        $this->assertEquals('CODE128', $product->barcode_format);
    }

    public function test_manager_can_create_product_with_optional_expiration_date(): void
    {
        $category = Category::factory()->create(['tenant_id' => $this->tenant->id]);
        Location::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_main_warehouse' => true,
        ]);

        $response = $this->withHeaders($this->getApiHeaders($this->managerToken))
            ->postJson('/api/v1/products', [
                'name' => 'Expiring Product',
                'sku' => 'SKU-EXPIRY-001',
                'category_id' => $category->id,
                'cost_per_unit' => 10.00,
                'selling_price' => 25.00,
                'expiration_date' => '2026-12-31',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('inventory_items', [
            'product_id' => $response->json('data.id'),
            'expiration_date' => '2026-12-31',
        ]);
    }

    /**
     * Test staff cannot create product
     */
    public function test_staff_cannot_create_product(): void
    {
        $category = Category::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->withHeaders($this->getApiHeaders($this->staffToken))
            ->postJson('/api/v1/products', [
                'name' => 'Test Product',
                'sku' => 'SKU-TEST-002',
                'category_id' => $category->id,
                'cost_per_unit' => 10.00,
                'selling_price' => 25.00,
            ]);

        $response->assertStatus(403);
    }

    /**
     * Test user can list products
     */
    public function test_user_can_list_products(): void
    {
        Product::factory(5)->create(['tenant_id' => $this->tenant->id]);

        $response = $this->withHeaders($this->getApiHeaders($this->staffToken))
            ->getJson('/api/v1/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'sku',
                        'barcode',
                    ],
                ],
            ]);
    }

    /**
     * Test user can search products by name
     */
    public function test_user_can_search_products_by_name(): void
    {
        Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Unique Product Name',
        ]);

        $response = $this->withHeaders($this->getApiHeaders($this->staffToken))
            ->getJson('/api/v1/products?query=Unique');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    /**
     * Test user can retrieve product by barcode
     */
    public function test_user_can_retrieve_product_by_barcode(): void
    {
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'barcode' => 'PROD_' . $this->tenant->id . '_1',
        ]);

        $response = $this->withHeaders($this->getApiHeaders($this->staffToken))
            ->getJson('/api/v1/products/barcode/' . $product->barcode);

        $response->assertStatus(200)
            ->assertJsonPath('data.barcode', $product->barcode);
    }

    /**
     * Test manager can update product
     */
    public function test_manager_can_update_product(): void
    {
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->withHeaders($this->getApiHeaders($this->managerToken))
            ->putJson('/api/v1/products/' . $product->id, [
                'name' => 'Updated Product Name',
                'selling_price' => 35.00,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.name', 'Updated Product Name');
    }

    /**
     * Test admin can delete product
     */
    public function test_admin_can_delete_product(): void
    {
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);

        $response = $this->withHeaders($this->getApiHeaders($this->adminToken))
            ->deleteJson('/api/v1/products/' . $product->id);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }
}
