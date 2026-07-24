@extends('layouts.app')

@section('content')
    <div class="space-y-6">
        <!-- En-tête de la page -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center space-y-4 md:space-y-0">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Gestion des Patients</h1>
                <p class="mt-1 text-sm text-gray-500">
                    Gérez vos patients et leurs informations personnelles
                </p>
            </div>
        </div>

        <!-- Composant Livewire de gestion des patients -->
        @livewire('patient-manager')
    </div>
@endsection 