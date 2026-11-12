{{-- Fenêtre de confirmation (remplace la boîte native de wire:confirm, voir resources/js/confirmation.js).
     À inclure dans toute mise en page qui utilise wire:confirm, sinon les boutons concernés ne font rien. --}}
<div x-data="{ ouvert: false, message: '', libelle: '', danger: false, valider: null,
               ouvrir(d) { Object.assign(this, d, { ouvert: true }); this.$nextTick(() => this.$refs.ok.focus()) },
               confirmer() { this.ouvert = false; this.valider?.() } }"
     x-on:confirmer.window="ouvrir($event.detail)" x-on:keydown.escape.window="ouvert = false">
    <div x-show="ouvert" x-cloak x-transition.opacity class="fixed inset-0 z-[60] flex items-end justify-center bg-slate-900/40 p-4 sm:items-center"
         x-on:click.self="ouvert = false" role="alertdialog" aria-modal="true" aria-labelledby="conf-titre" aria-describedby="conf-message">
        <div x-show="ouvert" x-transition class="w-full max-w-sm rounded-xl bg-white p-5 shadow-xl">
            <div class="flex gap-3">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full" :class="danger ? 'bg-red-100 text-red-600' : 'bg-primary/10 text-primary'">
                    <i class="ti text-xl" :class="danger ? 'ti-alert-triangle' : 'ti-help-circle'" aria-hidden="true"></i>
                </span>
                <div class="min-w-0">
                    <h2 id="conf-titre" class="font-semibold">Confirmation</h2>
                    <p id="conf-message" class="mt-1 text-sm text-slate-600" x-text="message"></p>
                </div>
            </div>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" x-on:click="ouvert = false" class="btn-secondary">Non, revenir</button>
                <button type="button" x-ref="ok" x-on:click="confirmer()" :class="danger ? 'btn bg-red-600 text-white hover:bg-red-700' : 'btn-primary'" x-text="libelle"></button>
            </div>
        </div>
    </div>
</div>
