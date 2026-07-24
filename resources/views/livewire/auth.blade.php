<div class="container" style="max-width: 400px; margin: 40px auto;">
    <h2>Connexion</h2>
    @if($errorMessage)
        <div class="alert alert-danger">{{ $errorMessage }}</div>
    @endif
    <form wire:submit.prevent="login">
        <div class="mb-3">
            <label for="login" class="form-label">Login</label>
            <input wire:model="login" type="text" class="form-control" id="login" required>
            @error('login') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Mot de passe</label>
            <input wire:model="password" type="password" class="form-control" id="password" required>
            @error('password') <span class="text-danger">{{ $message }}</span> @enderror
        </div>
        <div class="mb-3 form-check">
            <input wire:model="remember" type="checkbox" class="form-check-input" id="remember">
            <label class="form-check-label" for="remember">Se souvenir de moi</label>
        </div>
        <button type="submit" class="btn btn-primary">Se connecter</button>
    </form>
</div>