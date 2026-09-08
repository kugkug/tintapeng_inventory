# Livewire frontend

The browser application now lives in Laravel and uses Livewire instead of the separate React/TypeScript app.

- Components: `app/Livewire`
- Blade views: `resources/views/livewire`
- Shared layout: `resources/views/layouts/livewire.blade.php`
- Browser routes: `routes/web.php`
- Styles: `resources/css/livewire.css`

The existing `/api/v1` JWT API remains available for external clients and integrations. The Livewire browser app uses Laravel's `web` session guard and scopes queries by the authenticated user's `tenant_id`.
