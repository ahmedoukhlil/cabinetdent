@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800">Règlement des Factures</h1>
        
    </div>

    @if (session('message'))
        <div class="bg-primary-light border border-primary text-primary px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('message') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="bg-primary-light border border-primary text-primary px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    <livewire:reglement-facture />

    <button type="submit" class="px-4 py-2 bg-primary text-white rounded hover:bg-primary-dark">
        Enregistrer le règlement
    </button>
</div>
@endsection

 