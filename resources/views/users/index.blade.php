@extends('layouts.app')

@section('content')
<div class="modal-overlay">
    <div class="modal-box max-w-5xl w-full">
        <div class="modal-header">
            <h2><i class="fas fa-users-cog mr-2"></i>Gestion des utilisateurs</h2>
            <a href="{{ route('accueil.patient') }}" class="modal-close"><i class="fas fa-times"></i></a>
        </div>
        <div class="modal-body">
            @livewire('user-manager')
        </div>
    </div>
</div>
@endsection
