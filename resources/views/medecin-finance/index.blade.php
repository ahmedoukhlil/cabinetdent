@extends('layouts.app')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-semibold text-gray-900 mb-6">Tableau de bord financier par médecin</h1>

        {{-- Composant Livewire pour la recherche et l’affichage des cartes --}}
        @livewire('medecin-paiement-stats')
       

        {{-- Vous pouvez ajouter ici d'autres sections du tableau de bord si besoin --}}
    </div>
</div>
@endsection