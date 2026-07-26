@extends('patient-auth.layout')

@section('title', 'Notifications')

@section('content')
<h1 class="text-lg font-bold text-gray-800 mb-4">Notifications</h1>

@forelse($notifications as $notification)
    <a href="{{ $notification->url ?? route('patient.dashboard') }}"
       class="block bg-white p-4 rounded-xl border border-gray-100 shadow-sm mb-3">
        <div class="flex items-start gap-3">
            <div class="text-primary mt-0.5"><i class="fas fa-bell"></i></div>
            <div class="flex-1">
                <div class="font-medium text-sm">{{ $notification->titre }}</div>
                <div class="text-sm text-gray-600 mt-0.5">{{ $notification->corps }}</div>
                <div class="text-xs text-gray-400 mt-1">
                    {{ $notification->created_at->format('d/m/Y à H:i') }}
                </div>
            </div>
        </div>
    </a>
@empty
    <div class="text-center py-16 text-gray-400">
        <div class="text-4xl mb-3"><i class="fas fa-bell"></i></div>
        <p>Aucune notification pour le moment.</p>
    </div>
@endforelse
@endsection
