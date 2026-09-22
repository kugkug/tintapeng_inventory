<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Pos;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PosTest extends TestCase
{
    use RefreshDatabase;

    public function test_quantity_is_applied_and_repeated_products_are_aggregated(): void
    {
        $this->actingAs($this->staffUser);
        $product = Product::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        $component = Livewire::test(Pos::class)
            ->set('quantityInput', '10')
            ->call('confirmQuantity')
            ->call('addToCart', $product->id);

        $this->assertSame(10, $component->get('cart')[(string) $product->id]['quantity']);
        $this->assertSame(1, $component->get('pendingQuantity'));

        $component->call('addToCart', $product->id);

        $this->assertSame(11, $component->get('cart')[(string) $product->id]['quantity']);
    }

    public function test_quantity_must_be_a_positive_integer(): void
    {
        $this->actingAs($this->staffUser);

        Livewire::test(Pos::class)
            ->set('quantityInput', '0')
            ->call('confirmQuantity')
            ->assertHasErrors(['quantityInput' => ['min']]);
    }
}