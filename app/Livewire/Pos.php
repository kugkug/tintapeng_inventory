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
    public string $quantityInput = '';
    public string $paymentMethod = 'cash';
    public string $amountPaid = '';
    public string $discountAmount = '';
    public array $cart = [];
    public int $pendingQuantity = 1;
    public bool $quantityConfigured = false;
    public bool $quantityEntryActive = false;
    public bool $showCheckoutModal = false;

    public function activateQuantityEntry(): void
    {
        $this->quantityInput = '';
        $this->quantityEntryActive = true;
    }

    public function confirmQuantity(): void
    {
        $this->validate([
            'quantityInput' => ['required', 'integer', 'min:1'],
        ], [
            'quantityInput.required' => 'Enter a quantity before scanning.',
            'quantityInput.integer' => 'Quantity must be a whole number.',
            'quantityInput.min' => 'Quantity must be at least 1.',
        ]);

        $this->pendingQuantity = (int) $this->quantityInput;
        $this->quantityConfigured = true;
        $this->quantityInput = '';
        $this->quantityEntryActive = false;
        $this->dispatch('pos-focus-search');
    }

    public function addToCart(int $productId): void
    {
        $product = Product::where('tenant_id', Auth::user()->tenant_id)->findOrFail($productId);
        $key = (string) $product->id;
        $quantity = $this->pendingQuantity;

        if (isset($this->cart[$key])) {
            $this->cart[$key]['quantity'] += $quantity;
        } else {
            $this->cart[$key] = [
                'id' => $product->id,
                'name' => $product->name,
                'price' => (float) $product->selling_price,
                'quantity' => $quantity,
            ];
        }

        $this->pendingQuantity = 1;
        $this->quantityConfigured = false;
        $this->search = '';
        $this->dispatch('pos-focus-search');
    }

    public function scanBarcode(): void
    {
        $barcode = trim($this->search);
        if ($barcode === '') {
            $this->dispatch('pos-focus-search');

            return;
        }

        $product = Product::where('tenant_id', Auth::user()->tenant_id)
            ->where('is_active', true)
            ->where('barcode', $barcode)
            ->first();

        if ($product) {
            $this->addToCart($product->id);
        } else {
            $this->dispatch('pos-focus-search');
        }
    }

    public function removeFromCart(int $productId): void
    {
        unset($this->cart[(string) $productId]);
        $this->dispatch('pos-focus-search');
    }

    public function openCheckoutModal(): void
    {
        abort_if($this->cart === [], 422, 'Add at least one product before checkout.');

        $this->amountPaid = '';
        $this->discountAmount = '';
        $this->showCheckoutModal = true;
    }

    public function closeCheckoutModal(): void
    {
        $this->showCheckoutModal = false;
        $this->dispatch('pos-focus-search');
    }

    public function checkout(bool $downloadReceipt = false): mixed
    {
        $user = Auth::user();
        abort_if($this->cart === [], 422, 'Add at least one product before checkout.');

        if ($this->paymentMethod !== 'cash') {
            $this->amountPaid = (string) $this->discountedTotal;
        }

        
        $this->validate([
            'amountPaid' => ['required', 'numeric', 'min:0'],
            'discountAmount' => ['nullable', 'numeric', 'min:0'],
        ], [
            'amountPaid.required' => 'Enter the amount received.',
            'amountPaid.numeric' => 'Enter a valid amount.',
            'amountPaid.min' => 'The amount received cannot be negative.',
            'discountAmount.numeric' => 'Enter a valid discount amount.',
            'discountAmount.min' => 'The discount cannot be negative.',
        ]);

        $discountAmount = round((float) ($this->discountAmount ?: 0), 2);
        if ($discountAmount > $this->cartTotal) {
            $this->addError('discountAmount', 'The discount cannot exceed the subtotal.');

            return null;
        }

        $total = round($this->discountedTotal, 2);
        $amountPaid = round((float) $this->amountPaid, 2);
        if ($amountPaid < $total) {
            $this->addError('amountPaid', 'The amount received is less than the total.');

            return null;
        }
        $changeAmount = round($amountPaid - $total, 2);

        $location = $user->tenant->locations()
            ->where('is_active', true)
            ->orderByDesc('is_main_warehouse')
            ->orderBy('id')
            ->first();
        abort_if(! $location, 422, 'No active inventory location is configured.');

        $sale = DB::transaction(function () use ($user, $location, $amountPaid, $changeAmount, $discountAmount): Sale {
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

            $appliedDiscount = min($discountAmount, $subtotal);

            $sale = Sale::create([
                'tenant_id' => $user->tenant_id,
                'location_id' => $location->id,
                'user_id' => $user->id,
                'subtotal' => $subtotal,
                'discount_amount' => $appliedDiscount,
                'tax_amount' => 0,
                'total_amount' => $subtotal - $appliedDiscount,
                'payment_method' => $this->paymentMethod,
                'amount_paid' => $amountPaid,
                'change_amount' => $changeAmount,
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
        $this->pendingQuantity = 1;
        $this->quantityConfigured = false;
        $this->quantityInput = '';
        $this->quantityEntryActive = false;
        $this->discountAmount = '';
        $this->amountPaid = '';
        $this->closeCheckoutModal();

        if ($downloadReceipt) {
            return redirect()->route('sales.receipt', $sale);
        }

        return redirect()->route('pengpos');
    }

    public function getCartTotalProperty(): float
    {
        return collect($this->cart)->sum(fn (array $item) => $item['price'] * $item['quantity']);
    }

    public function getChangeAmountProperty(): float
    {
        if ($this->amountPaid === '' || ! is_numeric($this->amountPaid)) {
            return 0;
        }

        return max(0, round((float) $this->amountPaid - $this->discountedTotal, 2));
    }

    public function getDiscountedTotalProperty(): float
    {
        $discount = is_numeric($this->discountAmount) ? (float) $this->discountAmount : 0;

        return max(0, round($this->cartTotal - $discount, 2));
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
                ->orderBy('name')
                ->limit(24)
                ->get(),
        ]);
    }
}