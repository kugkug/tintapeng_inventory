<?php

namespace App\Livewire;

use App\Models\Sale;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.livewire')]
class Reports extends Component
{
    public string $date;

    public function mount(): void
    {
        $this->date = today()->format('Y-m-d');
    }

    public function render()
    {
        $sales = Sale::where('tenant_id', auth()->user()->tenant_id)
            ->whereDate('created_at', $this->date)
            ->with('user')
            ->latest()
            ->get();

        return view('livewire.reports', [
            'sales' => $sales,
            'transactions' => $sales->count(),
            'revenue' => $sales->sum('total_amount'),
            'average' => $sales->avg('total_amount') ?: 0,
        ]);
    }
}