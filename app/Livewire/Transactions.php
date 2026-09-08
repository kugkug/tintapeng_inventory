<?php

namespace App\Livewire;

use App\Models\Sale;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use Livewire\Component;

#[Layout('layouts.livewire')]
class Transactions extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $sales = Sale::where('tenant_id', auth()->user()->tenant_id)
            ->with('user', 'location')
            ->when($this->search !== '', fn ($query) => $query->where(function ($query): void {
                $query->where('id', 'like', "%{$this->search}%")
                    ->orWhere('payment_method', 'like', "%{$this->search}%");
            }))
            ->latest()
            ->paginate(15);

        return view('livewire.transactions', ['sales' => $sales]);
    }
}
