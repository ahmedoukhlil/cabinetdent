@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto">
    <h1 class="text-3xl font-bold text-gray-900 mb-8 text-center">🎯 Test de Synchronisation des Animations Harmonisées</h1>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Test des Boutons Principaux -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
            <h2 class="text-xl font-semibold text-gray-800 mb-4 text-center">🎮 Boutons Principaux</h2>
            <p class="text-gray-600 text-center mb-4">Style de référence de l'application</p>
            
            <div class="space-y-4">
                <button class="nav-button w-full px-4 py-3 border-2 border-primary bg-white text-primary rounded-xl shadow-lg hover:bg-primary hover:text-white hover:shadow-xl transition-all duration-500 ease-out">
                    Bouton Principal 1
                </button>
                
                <button class="nav-button w-full px-4 py-3 border-2 border-primary bg-white text-primary rounded-xl shadow-lg hover:bg-primary hover:text-white hover:shadow-xl transition-all duration-500 ease-out">
                    Bouton Principal 2
                </button>
                
                <button class="nav-button w-full px-4 py-3 border-2 border-primary bg-white text-primary rounded-xl shadow-lg hover:bg-primary hover:text-white hover:shadow-xl transition-all duration-500 ease-out">
                    Bouton Principal 3
                </button>
            </div>
            
            <div class="mt-4 p-3 bg-blue-50 rounded-lg">
                <h3 class="font-semibold text-blue-800 mb-2">📊 Caractéristiques du Style</h3>
                <ul class="text-sm text-blue-700 space-y-1">
                    <li>• Couleur primaire: #1e3a8a</li>
                    <li>• Bordures: rounded-xl</li>
                    <li>• Ombres: shadow-lg</li>
                    <li>• Transitions: 0.5s ease-out</li>
                </ul>
            </div>
        </div>

        <!-- Test des Modaux -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
            <h2 class="text-xl font-semibold text-gray-800 mb-4 text-center">🎭 Modaux Harmonisés</h2>
            <p class="text-gray-600 text-center mb-4">Style cohérent avec l'application</p>
            
            <div class="space-y-4">
                <button onclick="openTestModal('test1')" 
                        class="w-full bg-green-500 hover:bg-green-600 text-white font-medium py-3 px-4 rounded-lg transition-all duration-300 transform hover:scale-105">
                    Ouvrir Modal Test 1
                </button>
                
                <button onclick="openTestModal('test2')" 
                        class="w-full bg-purple-500 hover:bg-purple-600 text-white font-medium py-3 px-4 rounded-lg transition-all duration-300 transform hover:scale-105">
                    Ouvrir Modal Test 2
                </button>
                
                <button onclick="openTestModal('test3')" 
                        class="w-full bg-orange-500 hover:bg-orange-600 text-white font-medium py-3 px-4 rounded-lg transition-all duration-300 transform hover:scale-105">
                    Ouvrir Modal Test 3
                </button>
            </div>
            
            <div class="mt-4 p-3 bg-green-50 rounded-lg">
                <h3 class="font-semibold text-green-800 mb-2">📊 Caractéristiques du Style</h3>
                <ul class="text-sm text-green-700 space-y-1">
                    <li>• Couleur primaire: #1e3a8a</li>
                    <li>• Bordures: rounded-2xl</li>
                    <li>• Ombres: shadow-2xl</li>
                    <li>• Transitions: 0.3s ease-out</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Test de Synchronisation -->
    <div class="mt-8 bg-white rounded-xl shadow-lg p-6 border border-gray-200">
        <h2 class="text-xl font-semibold text-gray-800 mb-4 text-center">🔄 Test de Synchronisation</h2>
        <p class="text-gray-600 text-center mb-4">Vérifier que les styles sont parfaitement harmonisés</p>
        
        <div class="flex justify-center space-x-4">
            <button onclick="testSynchronization()" 
                    class="bg-indigo-500 hover:bg-indigo-600 text-white font-medium py-3 px-6 rounded-lg transition-all duration-300 transform hover:scale-105">
                🚀 Tester la Synchronisation
            </button>
            
            <button onclick="testPerformance()" 
                    class="bg-red-500 hover:bg-red-600 text-white font-medium py-3 px-6 rounded-lg transition-all duration-300 transform hover:scale-105">
                ⚡ Test de Performance
            </button>
        </div>
        
        <div id="sync-results" class="mt-4 p-4 bg-gray-50 rounded-lg hidden">
            <h3 class="font-semibold text-gray-800 mb-2">📊 Résultats du Test</h3>
            <div id="sync-content" class="text-sm text-gray-700"></div>
        </div>
    </div>

    <!-- Instructions -->
    <div class="mt-8 bg-yellow-50 rounded-xl p-6 border border-yellow-200">
        <h3 class="text-lg font-semibold text-yellow-800 mb-3">📋 Instructions de Test</h3>
        <ul class="text-yellow-700 space-y-2">
            <li>• <strong>Boutons Principaux</strong> : Observez le style de référence de l'application</li>
            <li>• <strong>Modaux</strong> : Vérifiez que les styles sont cohérents</li>
            <li>• <strong>Test de Synchronisation</strong> : Lancez les deux types d'animations simultanément</li>
            <li>• <strong>Test de Performance</strong> : Vérifiez la fluidité des animations</li>
            <li>• Les styles doivent être <strong>parfaitement harmonisés</strong> en couleurs et en design</li>
        </ul>
    </div>
</div>

<!-- Modal de test harmonisé -->
<div id="test-modal-backdrop" class="modal-backdrop" style="display: none;"></div>
<div id="test-modal" class="modal-container" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title" id="test-modal-title">
                <i class="fas fa-check-circle header-icon" id="test-modal-header-icon"></i>
                Modal de Test
            </h2>
            <button class="modal-close-button" onclick="closeTestModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="text-center py-8">
                <i class="fas fa-check-circle text-6xl text-green-500 mb-4" id="test-modal-icon"></i>
                <h3 class="text-xl font-semibold text-gray-800 mb-2" id="test-modal-subtitle">Test Réussi</h3>
                <p class="text-gray-600" id="test-modal-description">Ce modal utilise le style harmonisé de l'application</p>
            </div>
        </div>
        <div class="modal-footer">
            <button class="modal-btn modal-btn-secondary" onclick="closeTestModal()">Fermer</button>
            <button class="modal-btn modal-btn-primary">Action</button>
        </div>
    </div>
</div>

<script>
// Configuration des modaux de test harmonisés
const testModalConfigs = {
    test1: {
        title: 'Test de Synchronisation 1',
        subtitle: 'Style Harmonisé',
        description: 'Ce modal utilise exactement le même style que l\'application',
        icon: 'fa-check-circle',
        headerIcon: 'fa-check-circle',
        color: 'text-green-500'
    },
    test2: {
        title: 'Test de Synchronisation 2',
        subtitle: 'Design Cohérent',
        description: 'Les couleurs et bordures sont parfaitement alignées',
        icon: 'fa-clock',
        headerIcon: 'fa-clock',
        color: 'text-blue-500'
    },
    test3: {
        title: 'Test de Synchronisation 3',
        subtitle: 'Style Unifié',
        description: 'Même palette de couleurs, même style visuel',
        icon: 'fa-star',
        headerIcon: 'fa-star',
        color: 'text-yellow-500'
    }
};

// Ouvrir un modal de test harmonisé
function openTestModal(type) {
    const config = testModalConfigs[type];
    if (!config) return;

    // Mettre à jour le contenu du modal
    document.getElementById('test-modal-title').innerHTML = `
        <i class="fas ${config.headerIcon} header-icon"></i>
        ${config.title}
    `;
    document.getElementById('test-modal-subtitle').textContent = config.subtitle;
    document.getElementById('test-modal-description').textContent = config.description;
    
    const icon = document.getElementById('test-modal-icon');
    icon.className = `fas ${config.icon} text-6xl ${config.color} mb-4`;

    // Afficher le modal avec animation harmonisée
    const backdrop = document.getElementById('test-modal-backdrop');
    const modal = document.getElementById('test-modal');
    
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

// Fermer le modal de test harmonisé
function closeTestModal() {
    const backdrop = document.getElementById('test-modal-backdrop');
    const modal = document.getElementById('test-modal');
    
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

// Test de synchronisation
function testSynchronization() {
    const resultsDiv = document.getElementById('sync-results');
    const contentDiv = document.getElementById('sync-content');
    
    resultsDiv.classList.remove('hidden');
    contentDiv.innerHTML = '<p>🔄 Test en cours...</p>';
    
    // Simuler l'ouverture et fermeture simultanée
    let buttonCount = 0;
    let modalCount = 0;
    const maxTests = 3;
    
    const testButton = () => {
        if (buttonCount >= maxTests) return;
        
        const button = document.querySelector('.nav-button');
        button.classList.add('navigating');
        
        setTimeout(() => {
            button.classList.remove('navigating');
            buttonCount++;
        }, 600);
        
        setTimeout(testButton, 800);
    };
    
    const testModal = () => {
        if (modalCount >= maxTests) return;
        
        openTestModal('test1');
        
        setTimeout(() => {
            closeTestModal();
            modalCount++;
        }, 600);
        
        setTimeout(testModal, 800);
    };
    
    // Démarrer les tests simultanément
    testButton();
    testModal();
    
    // Afficher les résultats
    setTimeout(() => {
        const success = Math.abs(buttonCount - modalCount) <= 1; // Tolérance de 1
        contentDiv.innerHTML = `
            <div class="space-y-2">
                <p><strong>Boutons testés:</strong> ${buttonCount}/${maxTests}</p>
                <p><strong>Modaux testés:</strong> ${modalCount}/${maxTests}</p>
                <p class="font-semibold ${success ? 'text-green-600' : 'text-red-600'}">
                    ${success ? '✅ Synchronisation réussie !' : '❌ Synchronisation échouée'}
                </p>
                <p class="text-xs text-gray-500">
                    Les styles sont ${success ? 'parfaitement harmonisés' : 'légèrement différents'}
                </p>
            </div>
        `;
    }, 3000);
}

// Test de performance
function testPerformance() {
    const resultsDiv = document.getElementById('sync-results');
    const contentDiv = document.getElementById('sync-content');
    
    resultsDiv.classList.remove('hidden');
    contentDiv.innerHTML = '<p>⚡ Test de performance en cours...</p>';
    
    const startTime = performance.now();
    let animationCount = 0;
    const maxAnimations = 10;
    
    const runAnimation = () => {
        if (animationCount >= maxAnimations) {
            const endTime = performance.now();
            const duration = endTime - startTime;
            const avgTime = duration / maxAnimations;
            
            contentDiv.innerHTML = `
                <div class="space-y-2">
                    <p><strong>Animations exécutées:</strong> ${maxAnimations}</p>
                    <p><strong>Temps total:</strong> ${duration.toFixed(2)}ms</p>
                    <p><strong>Temps moyen par animation:</strong> ${avgTime.toFixed(2)}ms</p>
                    <p class="font-semibold ${avgTime < 100 ? 'text-green-600' : 'text-yellow-600'}">
                        ${avgTime < 100 ? '🚀 Performance excellente !' : '⚠️ Performance acceptable'}
                    </p>
                </div>
            `;
            return;
        }
        
        // Alterner entre bouton et modal
        if (animationCount % 2 === 0) {
            const button = document.querySelector('.nav-button');
            button.classList.add('navigating');
            setTimeout(() => button.classList.remove('navigating'), 600);
        } else {
            openTestModal('test1');
            setTimeout(() => closeTestModal(), 600);
        }
        
        animationCount++;
        setTimeout(runAnimation, 700);
    };
    
    runAnimation();
}

// Fermer avec Escape
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeTestModal();
    }
});

// Fermer en cliquant sur le backdrop
document.getElementById('test-modal-backdrop').addEventListener('click', () => {
    closeTestModal();
});
</script>
@endsection
