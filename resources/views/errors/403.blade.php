@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0">Accès Refusé</h4>
                </div>
                <div class="card-body text-center">
                    <div class="mb-4">
                        <i class="fas fa-lock fa-4x text-danger"></i>
                    </div>
                    <h5 class="card-title">Vous n'avez pas les permissions nécessaires</h5>
                    <p class="card-text">
                        Désolé, vous n'avez pas les autorisations requises pour accéder à cette page.
                    </p>
                    <div class="mt-4">
                        <a href="{{ url()->previous() }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Retour
                        </a>
                        <a href="{{ route('accueil.patient') }}" class="btn btn-primary">
                            <i class="fas fa-home"></i> Accueil
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 