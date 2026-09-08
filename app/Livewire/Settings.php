<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Location;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
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

    public string $locationName = '';

    public string $locationAddress = '';

    public bool $isMainWarehouse = false;

    public ?int $editingLocationId = null;

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

    public function saveLocation(): void
    {
        $this->ensureManager();
        $tenantId = auth()->user()->tenant_id;
        $data = $this->validate([
            'locationName' => ['required', 'string', 'max:255', Rule::unique('locations', 'name')->where(fn ($query) => $query->where('tenant_id', $tenantId))->ignore($this->editingLocationId)],
            'locationAddress' => ['nullable', 'string', 'max:1000'],
            'isMainWarehouse' => ['boolean'],
        ]);

        DB::transaction(function () use ($data, $tenantId): void {
            if ($data['isMainWarehouse']) {
                Location::where('tenant_id', $tenantId)->update(['is_main_warehouse' => false]);
            }

            Location::updateOrCreate(
                ['id' => $this->editingLocationId, 'tenant_id' => $tenantId],
                [
                    'name' => trim($data['locationName']),
                    'address' => trim($data['locationAddress'] ?? '') ?: null,
                    'is_main_warehouse' => $data['isMainWarehouse'],
                    'is_active' => true,
                ],
            );
        });

        $this->resetLocationForm();
        session()->flash('status', 'Location saved successfully.');
    }

    public function editLocation(int $locationId): void
    {
        $this->ensureManager();
        $location = Location::where('tenant_id', auth()->user()->tenant_id)
            ->where('is_active', true)
            ->findOrFail($locationId);

        $this->editingLocationId = $location->id;
        $this->locationName = $location->name;
        $this->locationAddress = $location->address ?? '';
        $this->isMainWarehouse = $location->is_main_warehouse;
    }

    public function removeLocation(int $locationId): void
    {
        $this->ensureManager();
        $tenantId = auth()->user()->tenant_id;
        $location = Location::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->findOrFail($locationId);

        if (Location::where('tenant_id', $tenantId)->where('is_active', true)->count() <= 1) {
            $this->addError('locationName', 'At least one active location is required.');

            return;
        }

        $wasMainWarehouse = $location->is_main_warehouse;

        DB::transaction(function () use ($location, $tenantId, $wasMainWarehouse): void {
            $location->update(['is_active' => false, 'is_main_warehouse' => false]);

            if ($wasMainWarehouse) {
                Location::where('tenant_id', $tenantId)
                    ->where('is_active', true)
                    ->orderBy('id')
                    ->limit(1)
                    ->update(['is_main_warehouse' => true]);
            }
        });

        $this->resetLocationForm();
        session()->flash('status', 'Location removed from the workspace.');
    }

    public function resetCategoryForm(): void
    {
        $this->reset(['categoryName', 'categoryDescription', 'editingCategoryId']);
    }

    public function resetUnitForm(): void
    {
        $this->reset(['unitName', 'unitSymbol', 'editingUnitId']);
    }

    public function resetLocationForm(): void
    {
        $this->reset(['locationName', 'locationAddress', 'isMainWarehouse', 'editingLocationId']);
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
            'locations' => Location::where('tenant_id', $tenantId)->latest()->get(),
        ]);
    }
}
