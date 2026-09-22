<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Products;
use App\Models\InventoryItem;
use App\Models\Location;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductsTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_populates_an_unsaved_form_with_a_blank_sku(): void
    {
        $this->actingAs($this->managerUser);
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'sku' => 'COFFEE-001',
            'name' => 'Original coffee',
            'unit' => 'pc',
            'cost_per_unit' => 12.50,
            'selling_price' => 25.00,
            'is_active' => true,
        ]);

        $component = Livewire::test(Products::class)
            ->call('duplicate', $product->id);

        $this->assertDatabaseCount('products', 1);
        $component
            ->assertSet('showForm', true)
            ->assertSet('isDuplicating', true)
            ->assertSet('editingProductId', null)
            ->assertSet('sku', '')
            ->assertSet('name', 'Original coffee')
            ->assertSet('originalDuplicateSku', 'COFFEE-001');
    }

    public function test_duplicate_requires_a_new_sku_before_saving(): void
    {
        $this->actingAs($this->managerUser);
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'sku' => 'COFFEE-001',
        ]);

        Livewire::test(Products::class)
            ->call('duplicate', $product->id)
            ->call('save')
            ->assertHasErrors(['sku' => ['required']]);

        Livewire::test(Products::class)
            ->call('duplicate', $product->id)
            ->set('sku', 'COFFEE-001')
            ->call('save')
            ->assertHasErrors(['sku']);
    }

    public function test_duplicate_copies_optional_expiration_date_into_the_draft(): void
    {
        $this->actingAs($this->managerUser);
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);
        $location = Location::factory()->create(['tenant_id' => $this->tenant->id]);
        InventoryItem::create([
            'product_id' => $product->id,
            'location_id' => $location->id,
            'quantity' => 12,
            'expiration_date' => '2026-12-31',
        ]);

        Livewire::test(Products::class)
            ->call('duplicate', $product->id)
            ->assertSet('expirationDate', '2026-12-31');
    }
}