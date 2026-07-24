@extends('layouts.app')

@section('content')
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Gestion des rendez-vous</h1>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-primary to-primary-dark">
                <h2 class="text-lg font-medium text-white">Création de rendez-vous</h2>
            </div>

            <div class="p-6">
                <livewire:create-rendez-vous />
            </div>
        </div>
    </div>
@endsection 