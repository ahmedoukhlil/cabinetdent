@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto">
    <h1 class="text-3xl font-bold text-gray-900 mb-8">Test de l'API de recherche de patients</h1>
    
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Test 1: Vérification de la route</h2>
        <button onclick="testRoute()" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors">
            Tester la route /patients/search
        </button>
        <div id="route-result" class="mt-4 p-4 bg-gray-50 rounded-lg border"></div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Test 2: Recherche de patients</h2>
        <div class="flex gap-4 mb-4">
            <input type="text" id="search-input" placeholder="Entrez un numéro de téléphone" value="22327272" 
                   class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            <select id="search-type" class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="telephone">Téléphone</option>
                <option value="nom">Nom</option>
                <option value="identifiant">Identifiant</option>
            </select>
            <button onclick="testSearch()" class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-6 rounded-lg transition-colors">
                Rechercher
            </button>
        </div>
        <div id="search-result" class="p-4 bg-gray-50 rounded-lg border"></div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Test 3: Vérification de l'authentification</h2>
        <button onclick="testAuth()" class="bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 px-4 rounded-lg transition-colors">
            Tester l'authentification
        </button>
        <div id="auth-result" class="mt-4 p-4 bg-gray-50 rounded-lg border"></div>
    </div>
</div>

<script>
    function testRoute() {
        const resultDiv = document.getElementById('route-result');
        resultDiv.innerHTML = '<div class="text-blue-600">Test en cours...</div>';
        resultDiv.className = 'mt-4 p-4 bg-gray-50 rounded-lg border';
        
        fetch('/patients/search?search=test&searchBy=telephone')
            .then(response => {
                resultDiv.innerHTML = `
                    <div class="space-y-2">
                        <div><strong>Statut:</strong> <span class="font-mono">${response.status} ${response.statusText}</span></div>
                        <div><strong>Headers:</strong></div>
                        <pre class="text-xs bg-gray-100 p-2 rounded overflow-x-auto">${JSON.stringify(Object.fromEntries(response.headers.entries()), null, 2)}</pre>
                    </div>
                `;
                resultDiv.className = response.ok ? 'mt-4 p-4 bg-green-50 border border-green-200 rounded-lg' : 'mt-4 p-4 bg-red-50 border border-red-200 rounded-lg';
            })
            .catch(error => {
                resultDiv.innerHTML = `<div class="text-red-600"><strong>Erreur:</strong> ${error.message}</div>`;
                resultDiv.className = 'mt-4 p-4 bg-red-50 border border-red-200 rounded-lg';
            });
    }
    
    function testSearch() {
        const searchTerm = document.getElementById('search-input').value;
        const searchType = document.getElementById('search-type').value;
        const resultDiv = document.getElementById('search-result');
        
        if (!searchTerm) {
            resultDiv.innerHTML = '<div class="text-red-600"><strong>Erreur:</strong> Veuillez entrer un terme de recherche</div>';
            resultDiv.className = 'p-4 bg-red-50 border border-red-200 rounded-lg';
            return;
        }
        
        resultDiv.innerHTML = '<div class="text-blue-600">Recherche en cours...</div>';
        resultDiv.className = 'p-4 bg-gray-50 rounded-lg border';
        
        fetch(`/patients/search?search=${encodeURIComponent(searchTerm)}&searchBy=${searchType}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    resultDiv.innerHTML = `<div class="text-red-600"><strong>Erreur API:</strong> ${data.error}</div>`;
                    resultDiv.className = 'p-4 bg-red-50 border border-red-200 rounded-lg';
                    return;
                }
                
                resultDiv.innerHTML = `
                    <div class="space-y-3">
                        <div><strong>Résultats trouvés:</strong> <span class="font-mono">${data.data.length}</span></div>
                        <div><strong>Total:</strong> <span class="font-mono">${data.pagination.total}</span></div>
                        <div><strong>Données:</strong></div>
                        <pre class="text-xs bg-gray-100 p-3 rounded overflow-x-auto">${JSON.stringify(data.data, null, 2)}</pre>
                    </div>
                `;
                resultDiv.className = 'p-4 bg-green-50 border border-green-200 rounded-lg';
            })
            .catch(error => {
                resultDiv.innerHTML = `<div class="text-red-600"><strong>Erreur:</strong> ${error.message}</div>`;
                resultDiv.className = 'p-4 bg-red-50 border border-red-200 rounded-lg';
            });
    }
    
    function testAuth() {
        const resultDiv = document.getElementById('auth-result');
        resultDiv.innerHTML = '<div class="text-blue-600">Test en cours...</div>';
        resultDiv.className = 'mt-4 p-4 bg-gray-50 rounded-lg border';
        
        // Test sans authentification
        fetch('/patients/search?search=test&searchBy=telephone')
            .then(response => {
                if (response.status === 401) {
                    resultDiv.innerHTML = `
                        <div class="text-green-600">
                            <strong>Résultat:</strong> Authentification requise (401)<br>
                            <strong>Statut:</strong> <span class="font-mono">${response.status} ${response.statusText}</span>
                        </div>
                    `;
                    resultDiv.className = 'mt-4 p-4 bg-green-50 border border-green-200 rounded-lg';
                } else {
                    resultDiv.innerHTML = `
                        <div class="text-red-600">
                            <strong>Résultat:</strong> Statut inattendu: ${response.status}<br>
                            <strong>Statut:</strong> <span class="font-mono">${response.status} ${response.statusText}</span>
                        </div>
                    `;
                    resultDiv.className = 'mt-4 p-4 bg-red-50 border border-red-200 rounded-lg';
                }
            })
            .catch(error => {
                resultDiv.innerHTML = `<div class="text-red-600"><strong>Erreur:</strong> ${error.message}</div>`;
                resultDiv.className = 'mt-4 p-4 bg-red-50 border border-red-200 rounded-lg';
            });
    }
    
    // Test automatique au chargement
    window.onload = function() {
        console.log('Page de test chargée. Assurez-vous d\'être connecté à l\'application Laravel.');
    };
</script>
@endsection
