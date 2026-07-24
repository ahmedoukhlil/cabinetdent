<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Consultations - {{ $patient->Nom }} {{ $patient->Prenom }}</title>
    @vite('resources/css/app.css')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- En-tête -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">
                        <i class="fas fa-stethoscope text-green-600 mr-3"></i>
                        Mes Consultations
                    </h1>
                    <p class="text-gray-600 mt-2">
                        Patient: <span class="font-semibold">{{ $patient->Nom }} {{ $patient->Prenom }}</span>
                    </p>
                    <p class="text-gray-500 text-sm">
                        NNI: {{ $patient->NNI ?? 'Non renseigné' }}
                    </p>
                </div>
                <div class="text-right">
                    <div class="text-sm text-gray-500">Dernière mise à jour</div>
                    <div class="text-sm font-medium">{{ now()->format('d/m/Y H:i') }}</div>
                </div>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow-md p-4">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 rounded-lg">
                        <i class="fas fa-stethoscope text-green-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Consultations</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $consultations->count() }}</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-4">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <i class="fas fa-calendar text-blue-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Cette année</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $consultations->where('DateConsultation', '>=', now()->startOfYear())->count() }}</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-4">
                <div class="flex items-center">
                    <div class="p-2 bg-purple-100 rounded-lg">
                        <i class="fas fa-user-md text-purple-600"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Médecins consultés</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $consultations->unique('fkidMedecin')->count() }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Liste des consultations -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800">Historique des consultations</h2>
            </div>
            
            @if($consultations->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <i class="fas fa-calendar mr-2"></i>Date
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <i class="fas fa-user-md mr-2"></i>Médecin
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <i class="fas fa-stethoscope mr-2"></i>Diagnostic
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <i class="fas fa-pills mr-2"></i>Traitement
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <i class="fas fa-money-bill mr-2"></i>Montant
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($consultations as $consultation)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ \Carbon\Carbon::parse($consultation->DateConsultation)->format('d/m/Y') }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ \Carbon\Carbon::parse($consultation->DateConsultation)->format('H:i') }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $consultation->medecin->Nom ?? 'Non assigné' }} {{ $consultation->medecin->Prenom ?? '' }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ $consultation->medecin->Specialite ?? 'Spécialité non renseignée' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900">
                                            {{ Str::limit($consultation->Diagnostic ?? 'Non renseigné', 50) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900">
                                            {{ Str::limit($consultation->Traitement ?? 'Non renseigné', 50) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ number_format($consultation->Montant ?? 0, 2) }} UM
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-12">
                    <i class="fas fa-stethoscope text-4xl text-gray-400 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Aucune consultation</h3>
                    <p class="text-gray-500">Vous n'avez pas encore de consultations enregistrées.</p>
                </div>
            @endif
        </div>

        <!-- Pied de page -->
        <div class="mt-8 text-center text-sm text-gray-500">
            <p>Cette page se met à jour automatiquement. Scannez le QR code sur votre reçu pour y accéder.</p>
            <p class="mt-2">
                <i class="fas fa-shield-alt mr-1"></i>
                Vos données sont sécurisées et confidentielles
            </p>
        </div>
    </div>

    <!-- Auto-refresh script -->
    <script>
        // Actualiser la page toutes les 30 secondes
        setTimeout(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html> 