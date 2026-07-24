@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto">
    <h1 class="text-3xl font-bold text-gray-900 mb-8 text-center">🎭 Démonstration des Modaux Harmonisés</h1>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Modal Assurances -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
            <div class="text-center mb-4">
                <i class="fas fa-shield-alt text-4xl text-blue-500 mb-3"></i>
                <h3 class="text-xl font-semibold text-gray-800">Assurances</h3>
            </div>
            <p class="text-gray-600 text-center mb-4">Gestion des assurances avec style harmonisé</p>
            <button onclick="openDemoModal('assurances')" 
                    class="w-full bg-blue-500 hover:bg-blue-600 text-white font-medium py-3 px-4 rounded-lg transition-all duration-300 transform hover:scale-105">
                Ouvrir le Modal
            </button>
        </div>

        <!-- Modal Actes -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
            <div class="text-center mb-4">
                <i class="fas fa-list-alt text-4xl text-green-500 mb-3"></i>
                <h3 class="text-xl font-semibold text-gray-800">Liste des Actes</h3>
            </div>
            <p class="text-gray-600 text-center mb-4">Gestion des actes avec design cohérent</p>
            <button onclick="openDemoModal('actes')" 
                    class="w-full bg-green-500 hover:bg-green-600 text-white font-medium py-3 px-4 rounded-lg transition-all duration-300 transform hover:scale-105">
                Ouvrir le Modal
            </button>
        </div>

        <!-- Modal Médecins -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
            <div class="text-center mb-4">
                <i class="fas fa-user-md text-4xl text-purple-500 mb-3"></i>
                <h3 class="text-xl font-semibold text-gray-800">Médecins</h3>
            </div>
            <p class="text-gray-600 text-center mb-4">Gestion des médecins avec style unifié</p>
            <button onclick="openDemoModal('medecins')" 
                    class="w-full bg-purple-500 hover:bg-purple-600 text-white font-medium py-3 px-4 rounded-lg transition-all duration-300 transform hover:scale-105">
                Ouvrir le Modal
            </button>
        </div>

        <!-- Modal Paiements -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
            <div class="text-center mb-4">
                <i class="fas fa-credit-card text-4xl text-orange-500 mb-3"></i>
                <h3 class="text-xl font-semibold text-gray-800">Modes de Paiement</h3>
            </div>
            <p class="text-gray-600 text-center mb-4">Gestion des modes de paiement harmonisée</p>
            <button onclick="openDemoModal('paiements')" 
                    class="w-full bg-orange-500 hover:bg-orange-600 text-white font-medium py-3 px-4 rounded-lg transition-all duration-300 transform hover:scale-105">
                Ouvrir le Modal
            </button>
        </div>

        <!-- Modal Utilisateurs -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
            <div class="text-center mb-4">
                <i class="fas fa-users-cog text-4xl text-red-500 mb-3"></i>
                <h3 class="text-xl font-semibold text-gray-800">Utilisateurs</h3>
            </div>
            <p class="text-gray-600 text-center mb-4">Gestion des utilisateurs avec style cohérent</p>
            <button onclick="openDemoModal('utilisateurs')" 
                    class="w-full bg-red-500 hover:bg-red-600 text-white font-medium py-3 px-4 rounded-lg transition-all duration-300 transform hover:scale-105">
                Ouvrir le Modal
            </button>
        </div>

        <!-- Test de Performance -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
            <div class="text-center mb-4">
                <i class="fas fa-tachometer-alt text-4xl text-indigo-500 mb-3"></i>
                <h3 class="text-xl font-semibold text-gray-800">Test de Performance</h3>
            </div>
            <p class="text-gray-600 text-center mb-4">Vérifier la fluidité des animations</p>
            <button onclick="testPerformance()" 
                    class="w-full bg-indigo-500 hover:bg-indigo-600 text-white font-medium py-3 px-4 rounded-lg transition-all duration-300 transform hover:scale-105">
                Tester
            </button>
        </div>
    </div>

    <!-- Instructions -->
    <div class="mt-12 bg-blue-50 rounded-xl p-6 border border-blue-200">
        <h3 class="text-lg font-semibold text-blue-800 mb-3">📋 Instructions de Test</h3>
        <ul class="text-blue-700 space-y-2">
            <li>• Cliquez sur les boutons pour ouvrir les modaux de démonstration</li>
            <li>• Observez le style harmonisé avec le reste de l'application</li>
            <li>• Testez les effets de survol sur les boutons de fermeture</li>
            <li>• Utilisez la touche Escape pour fermer les modaux</li>
            <li>• Vérifiez la cohérence visuelle sur différents appareils</li>
        </ul>
    </div>
</div>

<!-- Modaux de démonstration harmonisés -->
<div id="demo-modal-backdrop" class="modal-backdrop" style="display: none;"></div>
<div id="demo-modal" class="modal-container modal-large" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title" id="demo-modal-title">
                <i class="fas fa-star header-icon" id="demo-modal-header-icon"></i>
                Titre du Modal
            </h2>
            <button class="modal-close-button" onclick="closeDemoModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="text-center py-8">
                <i class="fas fa-star text-6xl text-yellow-500 mb-4" id="demo-modal-icon"></i>
                <h3 class="text-xl font-semibold text-gray-800 mb-2" id="demo-modal-subtitle">Sous-titre</h3>
                <p class="text-gray-600" id="demo-modal-description">Description du modal</p>
            </div>
        </div>
        <div class="modal-footer">
            <button class="modal-btn modal-btn-secondary" onclick="closeDemoModal()">Fermer</button>
            <button class="modal-btn modal-btn-primary">Action</button>
        </div>
    </div>
</div>

<script>
// Configuration des modaux de démonstration harmonisés
const modalConfigs = {
    assurances: {
        title: 'Gestion des Assurances',
        subtitle: 'Assurances',
        description: 'Gérer les assurances avec un style harmonisé et cohérent',
        icon: 'fa-shield-alt',
        headerIcon: 'fa-shield-alt',
        color: 'text-blue-500'
    },
    actes: {
        title: 'Liste des Actes',
        subtitle: 'Actes',
        description: 'Gérer la liste des actes avec un design unifié',
        icon: 'fa-list-alt',
        headerIcon: 'fa-list-alt',
        color: 'text-green-500'
    },
    medecins: {
        title: 'Gestion des Médecins',
        subtitle: 'Médecins',
        description: 'Gérer les médecins avec un style cohérent',
        icon: 'fa-user-md',
        headerIcon: 'fa-user-md',
        color: 'text-purple-500'
    },
    paiements: {
        title: 'Modes de Paiement',
        subtitle: 'Paiements',
        description: 'Gérer les modes de paiement de manière harmonisée',
        icon: 'fa-credit-card',
        headerIcon: 'fa-credit-card',
        color: 'text-orange-500'
    },
    utilisateurs: {
        title: 'Gestion des Utilisateurs',
        subtitle: 'Utilisateurs',
        description: 'Gérer les utilisateurs avec un style unifié',
        icon: 'fa-users-cog',
        headerIcon: 'fa-users-cog',
        color: 'text-red-500'
    }
};

// Ouvrir un modal de démonstration harmonisé
function openDemoModal(type) {
    const config = modalConfigs[type];
    if (!config) return;

    // Mettre à jour le contenu du modal
    document.getElementById('demo-modal-title').innerHTML = `
        <i class="fas ${config.headerIcon} header-icon"></i>
        ${config.title}
    `;
    document.getElementById('demo-modal-subtitle').textContent = config.subtitle;
    document.getElementById('demo-modal-description').textContent = config.description;
    
    const icon = document.getElementById('demo-modal-icon');
    icon.className = `fas ${config.icon} text-6xl ${config.color} mb-4`;

    // Afficher le modal avec animation harmonisée
    const backdrop = document.getElementById('demo-modal-backdrop');
    const modal = document.getElementById('demo-modal');
    
    backdrop.style.display = 'block';
    modal.style.display = 'block';

    // Forcer un reflow
    backdrop.offsetHeight;
    modal.offsetHeight;

    // Ajouter les classes d'animation harmonisées
    backdrop.classList.add('animate-backdrop-fade-in');
    modal.classList.add('animate-modal-fade-in');

    // Empêcher le scroll du body
    document.body.style.overflow = 'hidden';
}

// Fermer le modal de démonstration harmonisé
function closeDemoModal() {
    const backdrop = document.getElementById('demo-modal-backdrop');
    const modal = document.getElementById('demo-modal');
    
    // Ajouter les classes de fermeture
    backdrop.classList.add('closing');
    modal.classList.add('closing');

    // Masquer le modal après l'animation harmonisée
    setTimeout(() => {
        backdrop.classList.remove('animate-backdrop-fade-in', 'closing');
        modal.classList.remove('animate-modal-fade-in', 'closing');
        backdrop.style.display = 'none';
        modal.style.display = 'none';
        
        // Restaurer le scroll du body
        document.body.style.overflow = '';
    }, 200); // Timing harmonisé : 200ms
}

// Test de performance
function testPerformance() {
    const startTime = performance.now();
    
    // Ouvrir et fermer rapidement plusieurs modaux
    let count = 0;
    const maxCount = 5;
    
    const testCycle = () => {
        if (count >= maxCount) {
            const endTime = performance.now();
            const duration = endTime - startTime;
            alert(`Test de performance terminé !\n${maxCount} cycles en ${duration.toFixed(2)}ms\nMoyenne: ${(duration / maxCount).toFixed(2)}ms par cycle`);
            return;
        }
        
        openDemoModal('assurances');
        setTimeout(() => {
            closeDemoModal();
            count++;
            setTimeout(testCycle, 100);
        }, 200);
    };
    
    testCycle();
}

// Fermer avec Escape
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeDemoModal();
    }
});

// Fermer en cliquant sur le backdrop
document.getElementById('demo-modal-backdrop').addEventListener('click', () => {
    closeDemoModal();
});
</script>
@endsection
