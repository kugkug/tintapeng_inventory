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
    public ?int $previewSaleId = null;

    public function openPreview(int $saleId): void
    {
        Sale::where('tenant_id', auth()->user()->tenant_id)->findOrFail($saleId);

        $this->previewSaleId = $saleId;
    }

    public function closePreview(): void
    {
        $this->previewSaleId = null;
    }

    public function getPreviewSaleProperty(): ?Sale
    {
        if ($this->previewSaleId === null) {
            return null;
        }

        return Sale::where('tenant_id', auth()->user()->tenant_id)
            ->with('items.product', 'user', 'location')
            ->findOrFail($this->previewSaleId);
    }

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
