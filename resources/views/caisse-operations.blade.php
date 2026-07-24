@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-6">
        {{-- Titre supprimé --}}
        {{-- <h1 class="text-2xl font-bold text-gray-900">Gestion de la caisse</h1> --}}
        {{-- <p class="mt-2 text-sm text-gray-600">Consultez et gérez les opérations de caisse</p> --}}
    </div>

    <livewire:caisse-operations-manager />
</div>
@endsection 