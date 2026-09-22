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
        $fastMovingSince = today()->subDays(30);
        $fastMovingProducts = Product::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->withSum(['saleItems as sold_quantity' => function ($query) use ($tenantId, $fastMovingSince): void {
                $query->whereHas('sale', function ($query) use ($tenantId, $fastMovingSince): void {
                    $query->where('tenant_id', $tenantId)
                        ->whereDate('created_at', '>=', $fastMovingSince);
                });
            }], 'quantity')
            ->having('sold_quantity', '>', 0)
            ->orderByDesc('sold_quantity')
            ->limit(5)
            ->get();

        return view('livewire.dashboard', [
            'todaySales' => Sale::where('tenant_id', $tenantId)->whereDate('created_at', today())->count(),
            'todayRevenue' => Sale::where('tenant_id', $tenantId)->whereDate('created_at', today())->sum('total_amount'),
            'productCount' => Product::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->count(),
            'lowStockCount' => Product::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->lowStock()
                ->count(),
            'fastMovingProducts' => $fastMovingProducts,
            'recentSales' => Sale::where('tenant_id', $tenantId)->with('user')->latest()->limit(6)->get(),
        ]);
    }
}