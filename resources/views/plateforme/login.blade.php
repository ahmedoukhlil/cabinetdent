<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Administration · Sysdental</title>
    @include('partials.pwa-head')
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen items-center justify-center bg-slate-50 px-4">
    <form method="POST" action="{{ route('plateforme.login') }}" class="card w-full max-w-sm space-y-4">
        @csrf
        <div class="text-center">
            <i class="ti ti-dental text-4xl text-primary" aria-hidden="true"></i>
            <h1 class="mt-1 text-xl font-semibold">Sysdental</h1>
            <p class="text-sm text-slate-500">Administration de la plateforme</p>
        </div>
        @error('email')
            <p class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700" role="alert">{{ $message }}</p>
        @enderror
        <div>
            <label for="email" class="label">Adresse e-mail</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="field">
        </div>
        <div>
            <label for="password" class="label">Mot de passe</label>
            <input id="password" name="password" type="password" required autocomplete="current-password" class="field">
        </div>
        <button class="btn-primary w-full">Se connecter</button>
    </form>
</body>
</html>
