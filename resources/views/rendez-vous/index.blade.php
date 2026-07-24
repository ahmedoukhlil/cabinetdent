@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">
            <i class="fas fa-calendar-alt text-primary mr-2"></i>
            Liste des rendez-vous
        </h1>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <livewire:rendez-vous-manager />
    </div>
</div>
@endsection
