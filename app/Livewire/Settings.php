<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Unit;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.livewire')]
class Settings extends Component
{
    public string $name = '';
    public string $email = '';
    public string $categoryName = '';
    public string $categoryDescription = '';
    public ?int $editingCategoryId = null;
    public string $unitName = '';
    public string $unitSymbol = '';
    public ?int $editingUnitId = null;

    public function mount(): void
    {
        $this->name = auth()->user()->name;
        $this->email = auth()->user()->email;
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
        ]);

        auth()->user()->update($data);
        session()->flash('status', 'Profile updated successfully.');
    }

    public function saveCategory(): void
    {
        $this->ensureManager();
        $tenantId = auth()->user()->tenant_id;
        $data = $this->validate([
            'categoryName' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')->where(fn ($query) => $query->where('tenant_id', $tenantId))->ignore($this->editingCategoryId)],
            'categoryDescription' => ['nullable', 'string', 'max:1000'],
        ]);

        Category::updateOrCreate(
            ['id' => $this->editingCategoryId, 'tenant_id' => $tenantId],
            ['name' => trim($data['categoryName']), 'description' => $data['categoryDescription'] ?: null],
        );

        $this->resetCategoryForm();
        session()->flash('status', 'Category saved successfully.');
    }

    public function editCategory(int $categoryId): void
    {
        $this->ensureManager();
        $category = Category::where('tenant_id', auth()->user()->tenant_id)->findOrFail($categoryId);
        $this->editingCategoryId = $category->id;
        $this->categoryName = $category->name;
        $this->categoryDescription = $category->description ?? '';
    }

    public function removeCategory(int $categoryId): void
    {
        $this->ensureManager();
        Category::where('tenant_id', auth()->user()->tenant_id)->findOrFail($categoryId)->delete();
        $this->resetCategoryForm();
        session()->flash('status', 'Category removed successfully.');
    }

    public function saveUnit(): void
    {
        $this->ensureManager();
        $tenantId = auth()->user()->tenant_id;
        $data = $this->validate([
            'unitName' => ['required', 'string', 'max:80', Rule::unique('units', 'name')->where(fn ($query) => $query->where('tenant_id', $tenantId))->ignore($this->editingUnitId)],
            'unitSymbol' => ['nullable', 'string', 'max:20'],
        ]);

        Unit::updateOrCreate(
            ['id' => $this->editingUnitId, 'tenant_id' => $tenantId],
            ['name' => trim($data['unitName']), 'symbol' => trim($data['unitSymbol']) ?: null, 'is_active' => true],
        );

        $this->resetUnitForm();
        session()->flash('status', 'Unit saved successfully.');
    }

    public function editUnit(int $unitId): void
    {
        $this->ensureManager();
        $unit = Unit::where('tenant_id', auth()->user()->tenant_id)->findOrFail($unitId);
        $this->editingUnitId = $unit->id;
        $this->unitName = $unit->name;
        $this->unitSymbol = $unit->symbol ?? '';
    }

    public function removeUnit(int $unitId): void
    {
        $this->ensureManager();
        Unit::where('tenant_id', auth()->user()->tenant_id)->findOrFail($unitId)->update(['is_active' => false]);
        $this->resetUnitForm();
        session()->flash('status', 'Unit removed from product options.');
    }

    public function resetCategoryForm(): void
    {
        $this->reset(['categoryName', 'categoryDescription', 'editingCategoryId']);
    }

    public function resetUnitForm(): void
    {
        $this->reset(['unitName', 'unitSymbol', 'editingUnitId']);
    }

    private function ensureManager(): void
    {
        abort_unless(auth()->user()->isManager(), 403);
    }

    public function render()
    {
        $tenantId = auth()->user()->tenant_id;

        return view('livewire.settings', [
            'categories' => Category::where('tenant_id', $tenantId)->latest()->get(),
            'units' => Unit::where('tenant_id', $tenantId)->latest()->get(),
        ]);
    }
}