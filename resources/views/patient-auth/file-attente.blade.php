@extends('patient-auth.layout')

@section('title', "File d'attente")

@section('content')
<h1 class="text-lg font-bold text-gray-800 mb-4">File d'attente</h1>

<div id="file-attente-contenu">
    @include('patient-auth.partials.file-attente-contenu')
</div>

<script>
    // Polling simple : rafraîchit uniquement le contenu de la file d'attente
    // toutes les 20s, sans recharger toute la page — même principe que le
    // wire:poll utilisé côté cabinet (SalleAttente.php).
    setInterval(() => {
        fetch(window.location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then((r) => r.text())
            .then((html) => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const nouveauContenu = doc.getElementById('file-attente-contenu');
                if (nouveauContenu) {
                    document.getElementById('file-attente-contenu').innerHTML = nouveauContenu.innerHTML;
                }
            })
            .catch(() => {});
    }, 20000);
</script>
@endsection
