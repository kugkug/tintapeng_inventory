<div>
    <div class="page-heading">
        <div>
            <p class="eyebrow">Workspace</p>
            <h1>Settings</h1>
            <p class="muted">Manage your profile, product categories, and units of measurement.</p>
        </div>
    </div>
    <section class="form-panel narrow">
        <div class="section-heading">
            <h2>Profile</h2>
        </div>
        <form wire:submit="save" class="stack-form"><label>Name<input wire:model="name" type="text">
                @error('name')
                    <span class="field-error">{{ $message }}</span>
                @enderror
            </label>
            <label>Email<input wire:model="email" type="email">
                @error('email')
                    <span class="field-error">{{ $message }}</span>
                @enderror
            </label>
            <div><span class="muted">Role: {{ ucfirst(auth()->user()->role) }}</span></div><button
                class="button button-primary" type="submit">Save profile</button>
        </form>
    </section>
    @if (auth()->user()->isManager())
        <div class="settings-grid">
            <section class="form-panel">
                <div class="section-heading">
                    <h2>Categories</h2><span class="muted">{{ $categories->count() }} total</span>
                </div>
                <form wire:submit="saveCategory" class="stack-form"><label>Name<input wire:model="categoryName"
                            type="text" placeholder="e.g. Beverages">
                        @error('categoryName')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                    </label>
                    <label>Description
                        <textarea wire:model="categoryDescription" rows="3" placeholder="Optional description"></textarea>
                        @error('categoryDescription')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                    </label>
                    <div class="form-actions"><button class="button button-primary"
                            type="submit">{{ $editingCategoryId ? 'Update category' : 'Add category' }}</button>
                        @if ($editingCategoryId)
                            <button class="button button-quiet" type="button"
                                wire:click="resetCategoryForm">Cancel</button>
                        @endif
                    </div>
                </form>
                <div class="settings-list">
                    @forelse($categories as $category)
                        <div class="settings-list-item">
                            <div>
                                <strong>{{ $category->name }}</strong><small>{{ $category->description ?: 'No description' }}</small>
                            </div>
                            <div class="table-actions"><button class="button button-small" type="button"
                                    wire:click="editCategory({{ $category->id }})">Edit</button><button
                                    class="button button-small button-danger" type="button"
                                    wire:click="removeCategory({{ $category->id }})"
                                    wire:confirm="Remove {{ $category->name }}?">Remove</button></div>
                    </div>@empty<p class="muted">No categories yet.</p>
                    @endforelse
                </div>
            </section>
            <section class="form-panel">
                <div class="section-heading">
                    <h2>Units of measurement</h2><span class="muted">{{ $units->where('is_active', true)->count() }}
                        active</span>
                </div>
                <form wire:submit="saveUnit" class="stack-form"><label>Name<input wire:model="unitName" type="text"
                            placeholder="e.g. Kilogram">
                        @error('unitName')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                    </label>
                    <label>Symbol<input wire:model="unitSymbol" type="text" placeholder="e.g. kg">
                        @error('unitSymbol')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                    </label>
                    <div class="form-actions"><button class="button button-primary"
                            type="submit">{{ $editingUnitId ? 'Update unit' : 'Add unit' }}</button>
                        @if ($editingUnitId)
                            <button class="button button-quiet" type="button"
                                wire:click="resetUnitForm">Cancel</button>
                        @endif
                    </div>
                </form>
                <div class="settings-list">
                    @forelse($units->where('is_active', true) as $unit)
                        <div class="settings-list-item">
                            <div><strong>{{ $unit->name }}</strong><small>{{ $unit->symbol ?: 'No symbol' }}</small>
                            </div>
                            <div class="table-actions"><button class="button button-small" type="button"
                                    wire:click="editUnit({{ $unit->id }})">Edit</button><button
                                    class="button button-small button-danger" type="button"
                                    wire:click="removeUnit({{ $unit->id }})"
                                    wire:confirm="Remove {{ $unit->name }} from product options?">Remove</button>
                            </div>
                    </div>@empty<p class="muted">No units yet.</p>
                    @endforelse
                </div>
            </section>
        </div>
    @endif
</div>
