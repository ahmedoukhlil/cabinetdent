<div class="relative">
    <input type="text" wire:model.live="search" class="w-full border rounded px-3 py-2" placeholder="Rechercher un acte...">
    @if($showDropdown && strlen($search) > 0)
        <ul class="absolute z-10 bg-white border w-full mt-1 rounded shadow max-h-48 overflow-auto">
            @forelse($actes as $acte)
                <li wire:click="selectActe({{ $acte->ID }})" class="px-3 py-2 hover:bg-blue-100 cursor-pointer">{{ $acte->Acte }}</li>
            @empty
                <li class="px-3 py-2 text-gray-400">Aucun acte trouvé</li>
            @endforelse
        </ul>
    @endif
</div>
