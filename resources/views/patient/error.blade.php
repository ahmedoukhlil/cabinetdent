<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Erreur - Interface Patient</title>
    @vite('resources/css/app.css')
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full bg-white rounded-lg shadow-md p-8">
        <div class="text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
            </div>
            
            <h3 class="text-lg font-medium text-gray-900 mb-2">
                Erreur d'accès
            </h3>
            
            <p class="text-sm text-gray-500 mb-6">
                {{ $message ?? 'Une erreur est survenue lors du chargement de vos données.' }}
            </p>
            
            <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-yellow-400"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">
                            Que faire ?
                        </h3>
                        <div class="mt-2 text-sm text-yellow-700">
                            <ul class="list-disc list-inside space-y-1">
                                <li>Vérifiez que le QR code est complet et non endommagé</li>
                                <li>Assurez-vous que le lien n'a pas expiré</li>
                                <li>Contactez le cabinet si le problème persiste</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-xs text-gray-400">
                <p>Cette interface est sécurisée et nécessite un accès valide.</p>
            </div>
        </div>
    </div>
</body>
</html> 