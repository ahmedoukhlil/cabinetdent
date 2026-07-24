<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $__cab = \App\Models\Infocabinet::find(Auth::user()->fkidcabinet ?? 0) ?? \App\Models\Infocabinet::first();
        $__appName = ($__cab && !empty($__cab->nom_application)) ? $__cab->nom_application : 'SysMedical';
        $__logo = ($__cab && !empty($__cab->logo) && file_exists(public_path($__cab->logo))) ? asset($__cab->logo) : asset('SysMedical.png');
    @endphp
    <title>{{ $__appName }}</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Tailwind CSS + Alpine.js + app bundle -->
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/odontogram.jsx'])
    @livewireStyles

</head>
<body class="bg-gray-50 min-h-screen">

    <!-- Navbar -->
    <nav class="bg-white shadow-lg border-b border-gray-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center space-x-8">
                    <div class="flex items-center">
                        <a href="{{ route('accueil.patient') }}" class="flex items-center">
                            <img src="{{ $__logo }}" alt="{{ $__appName }}" class="h-8 w-auto">
                        </a>
                    </div>
                    <!-- Menu desktop -->
                    <div class="hidden md:flex space-x-4">
                        
                    </div>
                </div>

                <!-- Bouton menu mobile -->
                <div class="flex items-center md:hidden">
                    <button type="button" class="mobile-menu-button inline-flex items-center justify-center p-2 rounded-md text-gray-500 hover:text-blue-600 hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>

                <!-- Menu utilisateur -->
                <div class="hidden md:flex items-center space-x-4">
                    @if(auth()->check())
                        <div class="relative group">
                            <button class="flex items-center space-x-3 px-4 py-2 rounded-lg text-gray-700 hover:text-blue-600 hover:bg-blue-50 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 border border-gray-200">
                                <span class="font-medium">{{ auth()->user()->NomComplet ?? auth()->user()->name ?? 'Utilisateur' }}</span>
                                <i class="fas fa-chevron-down text-sm group-hover:text-blue-600 transition-colors duration-200"></i>
                            </button>
                            <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-100 opacity-0 group-hover:opacity-100 transition-all duration-200 transform origin-top-right scale-95 group-hover:scale-100 z-50">
                                <div class="py-1">
                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="block">
                                        @csrf
                                        <button type="submit" class="w-full text-left px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-all duration-200">
                                            Déconnexion
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-sm hover:shadow">
                            Connexion
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <!-- Menu mobile -->
        <div class="mobile-menu hidden md:hidden">
            <div class="px-2 pt-2 pb-3 space-y-2">
               
                
                @if(auth()->check())
                    <form id="mobile-logout-form" action="{{ route('logout') }}" method="POST" class="block">
                        @csrf
                        <button type="submit" class="w-full text-left px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-blue-50 hover:text-blue-600 transition-all duration-200">
                            Déconnexion
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="py-4">
        @yield('content')
    </main>

    <!-- Conteneur de toasts -->
    <div id="toast-container" aria-live="polite" aria-atomic="true"></div>

    @livewireScripts


    <script>
        /* ─── Toast system ─── */
        window.showToast = function(message, type = 'info', duration = 4000) {
            const container = document.getElementById('toast-container');
            if (!container) return;
            const icons = { success:'fa-check-circle', error:'fa-exclamation-circle', warning:'fa-exclamation-triangle', info:'fa-info-circle' };
            const colors = { success:'#16a34a', error:'#dc2626', warning:'#d97706', info:'var(--color-primary)' };
            const t = document.createElement('div');
            t.className = `toast toast-${type}`;
            t.innerHTML = `<i class="fas ${icons[type]||icons.info}" style="color:${colors[type]||colors.info};flex-shrink:0"></i><span>${message}</span>`;
            container.appendChild(t);
            setTimeout(() => { t.classList.add('toast-hide'); setTimeout(() => t.remove(), 220); }, duration);
        };

        /* ─── Livewire toast bridge ─── */
        document.addEventListener('livewire:load', function() {
            Livewire.on('toast', ({ message, type }) => window.showToast(message, type || 'info'));
        });

        /* ─── WhatsApp opener ─── */
        window.whatsappWindow = window.whatsappWindow || null;
        window.openWhatsApp = function(url, cb) {
            try {
                if (window.whatsappWindow && !window.whatsappWindow.closed) {
                    window.whatsappWindow.location.href = url;
                    window.whatsappWindow.focus();
                } else {
                    window.whatsappWindow = window.open(url, '_blank', 'noopener,noreferrer')
                                        || window.open(url, '_blank');
                }
                if (cb) cb();
            } catch(e) {
                navigator.clipboard?.writeText(url).then(() =>
                    window.showToast('Lien WhatsApp copié dans le presse-papiers.', 'info')
                );
                if (cb) cb();
            }
        };
    </script>

    <!-- Styles for nav-link -->
    <style>
        .nav-link {
            @apply text-gray-600 hover:text-blue-600 px-4 py-2 rounded-lg transition-all duration-200 font-medium hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 border border-gray-200;
        }
        .nav-link.active {
            @apply text-blue-600 bg-blue-50 ring-2 ring-blue-500 ring-offset-2 border-blue-200;
        }
        .mobile-nav-link {
            @apply text-gray-600 hover:text-blue-600 hover:bg-blue-50 transition-all duration-200 border border-gray-200;
        }
        .mobile-nav-link.active {
            @apply text-blue-600 bg-blue-50 font-medium border-blue-200;
        }
    </style>

    <!-- Script pour le menu mobile -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const btn = document.querySelector('.mobile-menu-button');
            const menu = document.querySelector('.mobile-menu');

            btn.addEventListener('click', () => {
                menu.classList.toggle('hidden');
            });
        });
    </script>

    <script>
        /* ─── Confirm dialog stylisé (remplace window.confirm) ─── */
        window.confirmAction = function(title, message, onConfirm) {
            // Supprimer un éventuel dialog existant
            document.getElementById('_confirm_dialog')?.remove();

            const overlay = document.createElement('div');
            overlay.id = '_confirm_dialog';
            overlay.className = 'confirm-dialog-overlay';
            overlay.innerHTML = `
                <div class="confirm-dialog-box" role="dialog" aria-modal="true" aria-labelledby="_cd_title">
                    <h3 id="_cd_title">${title}</h3>
                    ${message ? `<p>${message}</p>` : ''}
                    <div class="confirm-dialog-actions">
                        <button id="_cd_cancel" class="btn-secondary text-sm">Annuler</button>
                        <button id="_cd_ok"     class="btn-danger text-sm">Confirmer</button>
                    </div>
                </div>`;
            document.body.appendChild(overlay);

            const close = () => overlay.remove();
            overlay.querySelector('#_cd_cancel').addEventListener('click', close);
            overlay.addEventListener('click', (e) => { if (e.target === overlay) close(); });
            overlay.querySelector('#_cd_ok').addEventListener('click', () => {
                close();
                if (typeof onConfirm === 'function') onConfirm();
            });
            // Focus sur le bouton Annuler par défaut (accessibilité)
            setTimeout(() => overlay.querySelector('#_cd_cancel').focus(), 50);
        };
    </script>

    <script>
        /* ─── Impression modale historique paiements ─── */
        document.addEventListener('imprimer-modal', function () {
            const printModal = document.getElementById('historique-paiement-modal');
            if (!printModal) return;
            const w = window.open('', 'PRINT', 'height=800,width=1200');
            let styles = [...document.querySelectorAll('link[rel="stylesheet"]')].map(n => n.outerHTML).join('');
            styles += '<style>body{background:#fff;font-family:Arial,sans-serif;font-size:12px;padding:20px}table{width:100%;border-collapse:collapse;margin-bottom:20px}th,td{border:1px solid #000;padding:4px 8px}th{background:#f3f4f6;-webkit-print-color-adjust:exact}@page{size:A4;margin:10mm}</style>';
            w.document.write(`<html><head><title>Historique des paiements</title>${styles}</head><body>${printModal.innerHTML}</body></html>`);
            w.document.close();
            w.focus();
            setTimeout(() => { w.print(); w.close(); }, 400);
        });

        /* ─── Feedback visuel boutons sous-menu patient ─── */
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.patient-nav-button');
            if (!btn) return;
            // Retirer active sur tous les boutons du sous-menu
            document.querySelectorAll('.patient-nav-button').forEach(b => b.classList.remove('active'));
            // Appliquer active sur le bouton cliqué
            btn.classList.add('active');
        });

        /* ─── Ouverture reçu (open-receipt) ─── */
        if (!window.openReceiptListenerAdded) {
            window.addEventListener('open-receipt', function(e) {
                if (e.detail?.url) window.open(e.detail.url, '_blank');
            });
            window.openReceiptListenerAdded = true;
        }
    </script>
</body>
</html>