@extends('patient-auth.layout')

@section('title', 'Accueil')

@section('content')
<h1 class="text-lg font-bold text-gray-800 mb-1">
    Bonjour {{ $patient->NomContact ?? trim(($patient->Prenom ?? '').' '.($patient->Nom ?? '')) }}
</h1>
<p class="text-sm text-gray-500 mb-6">Bienvenue dans votre espace patient</p>

<div class="grid grid-cols-2 gap-3">
    <a href="{{ route('patient.plan-traitement') }}" class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
        <div class="text-2xl mb-1">🦷</div>
        <div class="font-medium text-sm">Plan de traitement</div>
        <div class="text-xs text-gray-500">{{ $planEnCours }} en cours</div>
    </a>
    <a href="{{ route('patient.factures') }}" class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
        <div class="text-2xl mb-1">🧾</div>
        <div class="font-medium text-sm">Factures</div>
        <div class="text-xs {{ $facturesImpayees > 0 ? 'text-orange-600' : 'text-gray-500' }}">
            {{ $facturesImpayees > 0 ? $facturesImpayees . ' impayée(s)' : 'À jour' }}
        </div>
    </a>
    <a href="{{ route('patient.paiements') }}" class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
        <div class="text-2xl mb-1">💳</div>
        <div class="font-medium text-sm">Paiements</div>
        <div class="text-xs text-gray-500">Historique</div>
    </a>
    <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm opacity-50">
        <div class="text-2xl mb-1">⏱️</div>
        <div class="font-medium text-sm">File d'attente</div>
        <div class="text-xs text-gray-400">Bientôt disponible</div>
    </div>
</div>
@endsection
