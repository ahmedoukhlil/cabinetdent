@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto">
    <h1 class="text-3xl font-bold text-gray-900 mb-8 text-center">🧪 Test des Modaux Harmonisés</h1>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Test Modal Édition Patient -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
            <div class="text-center mb-4">
                <i class="fas fa-user-edit text-4xl text-blue-500 mb-3"></i>
                <h3 class="text-xl font-semibold text-gray-800">Modal Édition Patient</h3>
            </div>
            <p class="text-gray-600 text-center mb-4">Test du modal de modification de patient</p>
            <button onclick="testEditModal()" 
                    class="w-full bg-blue-500 hover:bg-blue-600 text-white font-medium py-3 px-4 rounded-lg transition-all duration-300 transform hover:scale-105">
                Tester le Modal
            </button>
        </div>

        <!-- Test Modal Historique Paiements -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-200">
            <div class="text-center mb-4">
                <i class="fas fa-money-bill-wave text-4xl text-green-500 mb-3"></i>
                <h3 class="text-xl font-semibold text-gray-800">Modal Historique Paiements</h3>
            </div>
            <p class="text-gray-600 text-center mb-4">Test du modal d'historique des paiements</p>
            <button onclick="testPaymentHistoryModal()" 
                    class="w-full bg-green-500 hover:bg-green-600 text-white font-medium py-3 px-4 rounded-lg transition-all duration-300 transform hover:scale-105">
                Tester le Modal
            </button>
        </div>
    </div>

    <!-- Instructions -->
    <div class="mt-12 bg-blue-50 rounded-xl p-6 border border-blue-200">
        <h3 class="text-lg font-semibold text-blue-800 mb-3">📋 Instructions de Test</h3>
        <ul class="text-blue-700 space-y-2">
            <li>• Cliquez sur les boutons pour tester les différents modaux</li>
            <li>• Vérifiez que les modaux s'ouvrent avec le style harmonisé</li>
            <li>• Testez les boutons de fermeture</li>
            <li>• Utilisez la touche Escape pour fermer les modaux</li>
            <li>• Vérifiez la cohérence visuelle avec l'application</li>
        </ul>
    </div>
</div>

<!-- Modal de test - Édition Patient -->
<div id="test-edit-modal-backdrop" class="modal-backdrop" style="display: none;"></div>
<div id="test-edit-modal" class="modal-container modal-large" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">
                <i class="fas fa-user-edit header-icon"></i>
                Modifier le Patient
            </h2>
            <button class="modal-close-button" onclick="closeTestEditModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="space-y-4">
                <div class="modal-form-group">
                    <label class="modal-form-label">Nom</label>
                    <input type="text" class="modal-form-input" value="Test Patient" readonly>
                </div>
                <div class="modal-form-group">
                    <label class="modal-form-label">Prénom</label>
                    <input type="text" class="modal-form-input" value="Jean" readonly>
                </div>
                <div class="modal-form-group">
                    <label class="modal-form-label">Téléphone</label>
                    <input type="tel" class="modal-form-input" value="+222 12345678" readonly>
                </div>
                <div class="modal-form-group">
                    <label class="modal-form-label">Genre</label>
                    <select class="modal-form-input">
                        <option value="H">Homme (H)</option>
                        <option value="F">Femme (F)</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="modal-btn modal-btn-secondary" onclick="closeTestEditModal()">Annuler</button>
            <button class="modal-btn modal-btn-primary">Enregistrer</button>
        </div>
    </div>
</div>

<!-- Modal de test - Historique Paiements -->
<div id="test-payment-modal-backdrop" class="modal-backdrop" style="display: none;"></div>
<div id="test-payment-modal" class="modal-container modal-large" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2 class="modal-title">
                <i class="fas fa-money-bill-wave header-icon"></i>
                Historique des Paiements
            </h2>
            <button class="modal-close-button" onclick="closeTestPaymentModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="modal-list">
                <div class="modal-list-item">
                    <div class="flex justify-between items-center">
                        <div>
                            <strong>Consultation</strong>
                            <p class="text-sm text-gray-600">15/01/2024</p>
                        </div>
                        <span class="text-green-600 font-semibold">+5 000 MRU</span>
                    </div>
                </div>
                <div class="modal-list-item">
                    <div class="flex justify-between items-center">
                        <div>
                            <strong>Radiographie</strong>
                            <p class="text-sm text-gray-600">10/01/2024</p>
                        </div>
                        <span class="text-green-600 font-semibold">+3 000 MRU</span>
                    </div>
                </div>
                <div class="modal-list-item">
                    <div class="flex justify-between items-center">
                        <div>
                            <strong>Détartrage</strong>
                            <p class="text-sm text-gray-600">05/01/2024</p>
                        </div>
                        <span class="text-green-600 font-semibold">+8 000 MRU</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="modal-btn modal-btn-secondary" onclick="closeTestPaymentModal()">Fermer</button>
        </div>
    </div>
</div>

<script>
// Test du modal d'édition
function testEditModal() {
    const backdrop = document.getElementById('test-edit-modal-backdrop');
    const modal = document.getElementById('test-edit-modal');
    
    backdrop.style.display = 'block';
    modal.style.display = 'block';

    // Forcer un reflow
    backdrop.offsetHeight;
    modal.offsetHeight;

    // Ajouter les classes d'animation
    backdrop.classList.add('animate-backdrop-fade-in');
    modal.classList.add('animate-modal-fade-in');

    // Empêcher le scroll du body
    document.body.style.overflow = 'hidden';
}

function closeTestEditModal() {
    const backdrop = document.getElementById('test-edit-modal-backdrop');
    const modal = document.getElementById('test-edit-modal');
    
    backdrop.classList.add('closing');
    modal.classList.add('closing');

    setTimeout(() => {
        backdrop.classList.remove('animate-backdrop-fade-in', 'closing');
        modal.classList.remove('animate-modal-fade-in', 'closing');
        backdrop.style.display = 'none';
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }, 200);
}

// Test du modal d'historique des paiements
function testPaymentHistoryModal() {
    const backdrop = document.getElementById('test-payment-modal-backdrop');
    const modal = document.getElementById('test-payment-modal');
    
    backdrop.style.display = 'block';
    modal.style.display = 'block';

    // Forcer un reflow
    backdrop.offsetHeight;
    modal.offsetHeight;

    // Ajouter les classes d'animation
    backdrop.classList.add('animate-backdrop-fade-in');
    modal.classList.add('animate-modal-fade-in');

    // Empêcher le scroll du body
    document.body.style.overflow = 'hidden';
}

function closeTestPaymentModal() {
    const backdrop = document.getElementById('test-payment-modal-backdrop');
    const modal = document.getElementById('test-payment-modal');
    
    backdrop.classList.add('closing');
    modal.classList.add('closing');

    setTimeout(() => {
        backdrop.classList.remove('animate-backdrop-fade-in', 'closing');
        modal.classList.remove('animate-modal-fade-in', 'closing');
        backdrop.style.display = 'none';
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }, 200);
}

// Fermer avec Escape
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeTestEditModal();
        closeTestPaymentModal();
    }
});

// Fermer en cliquant sur le backdrop
document.getElementById('test-edit-modal-backdrop').addEventListener('click', () => {
    closeTestEditModal();
});

document.getElementById('test-payment-modal-backdrop').addEventListener('click', () => {
    closeTestPaymentModal();
});
</script>
@endsection
