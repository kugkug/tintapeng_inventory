<div class="login-page">
    <section class="login-panel">
        <img class="login-logo" src="{{ asset('logo.png') }}" alt="Tinta Peng logo">
        <p class="eyebrow">Inventory workspace</p>
        <h1>Welcome back.</h1>
        <p class="muted">Sign in to manage stock, sales, and daily operations.</p>
        <form wire:submit="login" class="stack-form">
            <label>Email<input wire:model="email" type="email" autocomplete="email" autofocus></label>
            @error('email')
                <span class="field-error">{{ $message }}</span>
            @enderror
            <label>Password<input wire:model="password" type="password" autocomplete="current-password"></label>
            @error('password')
                <span class="field-error">{{ $message }}</span>
            @enderror
            <label class="check-row"><input wire:model="remember" type="checkbox"> Keep me signed in</label>
            <button class="button button-primary" type="submit">Sign in</button>
        </form>
    </section>
</div>
