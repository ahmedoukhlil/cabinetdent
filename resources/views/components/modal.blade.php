<div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-lg p-6 relative">
        <button {{ $attributes->merge(['class' => 'absolute top-2 right-2 text-gray-500 hover:text-red-600 text-2xl']) }} wire:click="$emit('closeModal')">&times;</button>
        <div class="mb-4">
            {{ $header }}
        </div>
        <div>
            {{ $slot }}
        </div>
    </div>
</div> 