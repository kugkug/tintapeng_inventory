<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.livewire')]
class Pos extends Component
{
    public string $search = '';
    public string $paymentMethod = 'cash';
    public array $cart = [];
    public bool $showCheckoutModal = false;

    public function updatedSearch(): void
    {
        $barcode = trim($this->search);
        if ($barcode === '') {
            return;
        }

        $product = Product::where('tenant_id', Auth::user()->tenant_id)
            ->where('is_active', true)
            ->where('barcode', $barcode)
            ->first();

        if ($product) {
            $this->addToCart($product->id);
        }
    }

    public function addToCart(int $productId): void
    {
        $product = Product::where('tenant_id', Auth::user()->tenant_id)->findOrFail($productId);
        $key = (string) $product->id;

        if (isset($this->cart[$key])) {
            $this->cart[$key]['quantity']++;
        } else {
            $this->cart[$key] = [
                'id' => $product->id,
                'name' => $product->name,
                'price' => (float) $product->selling_price,
                'quantity' => 1,
            ];
        }

        $this->search = '';
    }

    public function scanBarcode(): void
    {
        $barcode = trim($this->search);
        if ($barcode === '') {
            return;
        }

        $product = Product::where('tenant_id', Auth::user()->tenant_id)
            ->where('is_active', true)
            ->where('barcode', $barcode)
            ->first();

        if ($product) {
            $this->addToCart($product->id);
        }
    }

    public function removeFromCart(int $productId): void
    {
        unset($this->cart[(string) $productId]);
    }

    public function openCheckoutModal(): void
    {
        abort_if($this->cart === [], 422, 'Add at least one product before checkout.');

        $this->showCheckoutModal = true;
    }

    public function closeCheckoutModal(): void
    {
        $this->showCheckoutModal = false;
    }

    public function checkout(): mixed
    {
        $user = Auth::user();
        abort_if($this->cart === [], 422, 'Add at least one product before checkout.');

        $location = $user->tenant->locations()
            ->where('is_active', true)
            ->orderByDesc('is_main_warehouse')
            ->orderBy('id')
            ->first();
        abort_if(! $location, 422, 'No active inventory location is configured.');

        $sale = DB::transaction(function () use ($user, $location): Sale {
            $products = Product::where('tenant_id', $user->tenant_id)
                ->where('is_active', true)
                ->whereIn('id', array_keys($this->cart))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $subtotal = 0;
            foreach ($this->cart as $item) {
                $product = $products->get((int) $item['id']);
                abort_if(! $product, 422, 'A product in the basket is no longer available.');
                $inventory = $product->inventoryItems()->where('location_id', $location->id)->lockForUpdate()->first();
                abort_if(! $inventory || $inventory->quantity < (int) $item['quantity'], 422, "Insufficient stock for {$product->name}.");
                $subtotal += (float) $product->selling_price * (int) $item['quantity'];
            }

            $sale = Sale::create([
                'tenant_id' => $user->tenant_id,
                'location_id' => $location->id,
                'user_id' => $user->id,
                'subtotal' => $subtotal,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'total_amount' => $subtotal,
                'payment_method' => $this->paymentMethod,
            ]);

            foreach ($this->cart as $item) {
                $product = $products->get((int) $item['id']);
                $quantity = (int) $item['quantity'];
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $product->selling_price,
                    'total_price' => (float) $product->selling_price * $quantity,
                ]);
                $product->inventoryItems()->where('location_id', $location->id)->decrement('quantity', $quantity);
            }

            return $sale;
        });

        $this->cart = [];
        $this->search = '';
        $this->showCheckoutModal = false;

        return redirect()->route('sales.receipt', $sale);
    }

    public function getCartTotalProperty(): float
    {
        return collect($this->cart)->sum(fn (array $item) => $item['price'] * $item['quantity']);
    }

    public function render()
    {
        return view('livewire.pos', [
            'products' => Product::where('tenant_id', Auth::user()->tenant_id)
                ->where('is_active', true)
                ->where(fn ($query) => $query->where('name', 'like', "%{$this->search}%")
                    ->orWhere('sku', 'like', "%{$this->search}%")
                    ->orWhere('barcode', 'like', "%{$this->search}%"))
                ->with('inventoryItems')
                ->limit(24)
                ->get(),
        ]);
    }
}