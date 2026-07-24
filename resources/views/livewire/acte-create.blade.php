<div class="max-w-4xl mx-auto p-6 bg-white rounded shadow">
    @if (session()->has('success'))
        <div class="mb-4 p-2 bg-green-100 text-green-800 rounded text-center">
            {{ session('success') }}
        </div>
    @endif

    <h2 class="text-2xl font-bold text-gray-800 mb-6 text-left">Créer un acte</h2>

    <form wire:submit.prevent="save" class="space-y-6">
        <div class="grid grid-cols-2 gap-6">
            <!-- Colonne de gauche -->
            <div class="space-y-6">
                <div>
                    <label class="block text-base font-bold text-gray-700 mb-1">Nom de l'acte <span class="text-red-500">*</span></label>
                    <input type="text" wire:model="Acte" class="mt-1 block w-full rounded-md border-gray-400 shadow focus:border-blue-500 focus:ring-blue-500 h-12 text-lg bg-gray-50" required>
                    @error('Acte') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-base font-bold text-gray-700 mb-1">Prix de référence (MRU) <span class="text-red-500">*</span></label>
                    <input type="number" wire:model="PrixRef" min="0" step="0.01" class="mt-1 block w-full rounded-md border-gray-400 shadow focus:border-blue-500 focus:ring-blue-500 h-12 text-lg bg-gray-50" required>
                    @error('PrixRef') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-base font-bold text-gray-700 mb-1">Type d'acte <span class="text-red-500">*</span></label>
                    <select wire:model="fkidTypeActe" class="mt-1 block w-full rounded-md border-gray-400 shadow focus:border-blue-500 focus:ring-blue-500 h-12 text-lg bg-gray-50" required>
                        <option value="">Sélectionner un type d'acte</option>
                        @foreach($types as $type)
                            <option value="{{ $type->ID }}">{{ $type->LibTypeActe }}</option>
                        @endforeach
                    </select>
                    @error('fkidTypeActe') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- Colonne de droite -->
            <div class="space-y-6">
                <div>
                    <label class="block text-base font-bold text-gray-700 mb-1">Assureur</label>
                    <select wire:model="fkidassureur" class="mt-1 block w-full rounded-md border-gray-400 shadow focus:border-blue-500 focus:ring-blue-500 h-12 text-lg bg-gray-50">
                        <option value="">Aucun assureur</option>
                        @foreach($assureurs as $assureur)
                            <option value="{{ $assureur->ID }}">{{ $assureur->LibAssurance }}</option>
                        @endforeach
                    </select>
                    @error('fkidassureur') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-base font-bold text-gray-700 mb-1">Nom arabe</label>
                    <input type="text" wire:model="ActeArab" class="mt-1 block w-full rounded-md border-gray-400 shadow focus:border-blue-500 focus:ring-blue-500 h-12 text-lg bg-gray-50">
                    @error('ActeArab') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                </div>

                <div class="flex items-center">
                    <input type="checkbox" wire:model="Masquer" id="masquer" class="form-checkbox h-5 w-5 text-blue-600 rounded border-gray-400 focus:ring-blue-500">
                    <label for="masquer" class="ml-2 block text-base text-gray-700">Masquer cet acte</label>
                </div>
            </div>
        </div>

        <div class="flex justify-end mt-6">
            <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded hover:bg-blue-700 text-lg font-bold">Enregistrer</button>
        </div>
    </form>
</div> 