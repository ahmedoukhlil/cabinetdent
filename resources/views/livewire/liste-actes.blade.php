<div>
    <div class="mb-4">
        <div class="flex gap-4">
            <div class="flex-1">
                <input type="text" wire:model.live.debounce.300ms="search"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                       placeholder="Rechercher un acte...">
            </div>
            <div class="w-64">
                <select wire:model.live="selectedTypeActe"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                    <option value="">Tous les types</option>
                    @foreach($typesActes as $type)
                        <option value="{{ $type->IDTypeActe }}">{{ $type->TypeActe }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($actes as $acte)
            <div class="bg-white rounded-lg shadow-md p-4 hover:shadow-lg transition-shadow duration-200 cursor-pointer"
                 wire:click="selectActe({{ $acte->ID }})">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">{{ $acte->Acte }}</h3>
                        <p class="text-sm text-gray-600 mt-1">{{ $acte->ActeArab }}</p>
                    </div>
                    <div class="text-right">
                        <span class="text-lg font-bold text-primary">{{ number_format($acte->PrixRef, 2) }} DH</span>
                    </div>
                </div>
                @if($acte->typeActe)
                    <div class="mt-2">
                        <span class="inline-block bg-primary-light text-primary text-xs px-2 py-1 rounded">
                            {{ $acte->typeActe->TypeActe }}
                        </span>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    @if($actes->isEmpty())
        <div class="text-center py-8">
            <p class="text-gray-500">Aucun acte trouvé</p>
        </div>
    @endif
</div> 