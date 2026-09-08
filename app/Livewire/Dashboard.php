<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\Sale;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.livewire')]
class Dashboard extends Component
{
    public function render()
    {
        $tenantId = Auth::user()->tenant_id;

        return view('livewire.dashboard', [
            'todaySales' => Sale::where('tenant_id', $tenantId)->whereDate('created_at', today())->count(),
            'todayRevenue' => Sale::where('tenant_id', $tenantId)->whereDate('created_at', today())->sum('total_amount'),
            'productCount' => Product::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->count(),
            'lowStockCount' => Product::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->whereHas('inventoryItems', fn ($query) => $query->whereColumn('quantity', '<=', 'products.min_stock'))
                ->count(),
            'recentSales' => Sale::where('tenant_id', $tenantId)->with('user')->latest()->limit(6)->get(),
        ]);
    }
}