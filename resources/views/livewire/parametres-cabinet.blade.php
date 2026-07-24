<div class="p-4 max-w-5xl mx-auto">

    {{-- Notifications flash --}}
    @if(session()->has('success'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
         class="mb-4 flex items-center gap-2 p-3 rounded-lg bg-green-50 border border-green-200 text-green-700 text-sm">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
    </div>
    @endif
    @if(session()->has('error'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
         class="mb-4 flex items-center gap-2 p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm">
        <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
    </div>
    @endif

    <form wire:submit.prevent="save" enctype="multipart/form-data">

        {{-- LOGO --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
                <i class="fas fa-image text-primary"></i> Logo du cabinet (en-tête des imprimés)
            </h3>
            <div class="flex flex-col items-center gap-4">
                @if($logoActuel && file_exists(public_path($logoActuel)))
                <div class="relative group">
                    <img src="{{ asset($logoActuel) }}" alt="Logo" class="max-h-28 max-w-xs object-contain rounded-lg border border-gray-200 shadow-sm">
                    <button type="button" wire:click="supprimerLogo"
                        class="absolute -top-2 -right-2 w-6 h-6 rounded-full bg-red-500 text-white text-xs flex items-center justify-center shadow hover:bg-red-600 opacity-0 group-hover:opacity-100 transition-opacity">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                @endif

                @if($logoFile)
                <div>
                    <img src="{{ $logoFile->temporaryUrl() }}" alt="Aperçu" class="max-h-24 max-w-xs object-contain rounded-lg border border-dashed border-primary/40">
                    <p class="text-xs text-center text-gray-400 mt-1">Aperçu — non encore enregistré</p>
                </div>
                @endif

                <label class="cursor-pointer flex flex-col items-center gap-2 px-6 py-4 rounded-xl border-2 border-dashed border-gray-300 hover:border-primary/50 hover:bg-primary/5 transition-colors text-sm text-gray-500 w-full max-w-sm">
                    <i class="fas fa-cloud-upload-alt text-2xl text-gray-400"></i>
                    <span>Cliquer pour choisir un logo</span>
                    <span class="text-xs text-gray-400">PNG, JPG, SVG — max 2 Mo</span>
                    <input type="file" wire:model="logoFile" accept="image/*" class="hidden">
                </label>
            </div>
        </div>

        {{-- APERÇU EN-TÊTE --}}
        <div class="bg-gray-50 rounded-xl border border-gray-200 shadow-sm p-5 mb-5">
            <h3 class="text-sm font-semibold text-gray-500 mb-3 flex items-center gap-2">
                <i class="fas fa-eye text-gray-400"></i> Aperçu de l'en-tête
            </h3>
            <div class="bg-white rounded-lg border border-gray-200 p-4">
                <div class="grid grid-cols-3 gap-2 items-start">
                    <div class="text-left text-xs text-gray-700 leading-relaxed">
                        @if($NomCabFr)<div class="font-bold text-sm">{{ $NomCabFr }}</div>@endif
                        @if($DrFr)<div>{{ $DrFr }}</div>@endif
                        @if($Specialite1Fr)<div class="text-gray-500">{{ $Specialite1Fr }}</div>@endif
                        @if($Specialite2fr)<div class="text-gray-500">{{ $Specialite2fr }}</div>@endif
                        @if($Specialite3Fr)<div class="text-gray-500">{{ $Specialite3Fr }}</div>@endif
                        @if($AdresseFr1)<div>{{ $AdresseFr1 }}</div>@endif
                        @if($AdresseFr2)<div>{{ $AdresseFr2 }}</div>@endif
                        @if($ContactFR)<div>{{ $ContactFR }}</div>@endif
                    </div>
                    <div class="flex justify-center">
                        @if($logoActuel && file_exists(public_path($logoActuel)))
                        <img src="{{ asset($logoActuel) }}" alt="Logo" class="max-h-20 object-contain">
                        @elseif($logoFile)
                        <img src="{{ $logoFile->temporaryUrl() }}" alt="Logo" class="max-h-20 object-contain">
                        @else
                        <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center text-gray-300">
                            <i class="fas fa-image text-2xl"></i>
                        </div>
                        @endif
                    </div>
                    <div class="text-right text-xs text-gray-700 leading-relaxed" dir="rtl">
                        @if($NomCabAr)<div class="font-bold text-sm">{{ $NomCabAr }}</div>@endif
                        @if($DrAr)<div>{{ $DrAr }}</div>@endif
                        @if($Specialite1Ar)<div class="text-gray-500">{{ $Specialite1Ar }}</div>@endif
                        @if($Specialite2Ar)<div class="text-gray-500">{{ $Specialite2Ar }}</div>@endif
                        @if($Specialite3Ar)<div class="text-gray-500">{{ $Specialite3Ar }}</div>@endif
                        @if($AdresseL1AR)<div>{{ $AdresseL1AR }}</div>@endif
                        @if($AdresseL2AR)<div>{{ $AdresseL2AR }}</div>@endif
                        @if($ContactAR)<div dir="ltr">{{ $ContactAR }}</div>@endif
                    </div>
                </div>
                @if($AdresseMail || $TelephonePublic)
                <div class="mt-2 pt-2 border-t border-gray-100 text-center text-xs text-gray-500">
                    @if($AdresseMail)<span class="mr-3"><i class="fas fa-envelope mr-1"></i>{{ $AdresseMail }}</span>@endif
                    @if($TelephonePublic)<span><i class="fas fa-phone mr-1"></i>{{ $TelephonePublic }}</span>@endif
                </div>
                @endif
            </div>
        </div>

        {{-- INFOS BILINGUES --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">

            <div class="bg-white rounded-xl border border-blue-100 shadow-sm p-5">
                <h3 class="text-sm font-semibold text-blue-700 mb-4 flex items-center gap-2">
                    <span class="text-base">🇫🇷</span> Informations en français
                </h3>
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Nom du cabinet</label>
                        <input type="text" wire:model="NomCabFr" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Médecin / Titre</label>
                        <input type="text" wire:model="DrFr" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Spécialité 1</label>
                        <input type="text" wire:model="Specialite1Fr" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Spécialité 2</label>
                        <input type="text" wire:model="Specialite2fr" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Spécialité 3</label>
                        <input type="text" wire:model="Specialite3Fr" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Adresse ligne 1</label>
                        <input type="text" wire:model="AdresseFr1" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Adresse ligne 2</label>
                        <input type="text" wire:model="AdresseFr2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Contact / Téléphone</label>
                        <input type="text" wire:model="ContactFR" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-green-100 shadow-sm p-5">
                <h3 class="text-sm font-semibold text-green-700 mb-4 flex items-center gap-2">
                    <span class="text-base">🇲🇷</span> المعلومات بالعربية
                </h3>
                <div class="space-y-3" dir="rtl">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">اسم العيادة</label>
                        <input type="text" wire:model="NomCabAr" dir="rtl" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary text-right">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">الطبيب / اللقب</label>
                        <input type="text" wire:model="DrAr" dir="rtl" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary text-right">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">التخصص 1</label>
                        <input type="text" wire:model="Specialite1Ar" dir="rtl" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary text-right">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">التخصص 2</label>
                        <input type="text" wire:model="Specialite2Ar" dir="rtl" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary text-right">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">التخصص 3</label>
                        <input type="text" wire:model="Specialite3Ar" dir="rtl" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary text-right">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">العنوان 1</label>
                        <input type="text" wire:model="AdresseL1AR" dir="rtl" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary text-right">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">العنوان 2</label>
                        <input type="text" wire:model="AdresseL2AR" dir="rtl" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary text-right">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">الاتصال / الهاتف</label>
                        <input type="text" wire:model="ContactAR" dir="ltr" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary text-right">
                    </div>
                </div>
            </div>
        </div>

        {{-- COMMUN --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-5">
            <h3 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
                <i class="fas fa-globe text-primary"></i> Coordonnées communes
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Email</label>
                    <input type="email" wire:model="AdresseMail" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Téléphone public</label>
                    <input type="text" wire:model="TelephonePublic" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary">
                </div>
            </div>
        </div>

        {{-- PIED DE PAGE --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
            <h3 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
                <i class="fas fa-align-center text-primary"></i> Pied de page (bas des imprimés)
            </h3>
            <textarea wire:model="piedPage" rows="3"
                placeholder="Ex: Consultations sur rendez-vous — Tél: XX XX XX XX — Ouvert du lundi au vendredi"
                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-primary focus:border-primary resize-none"></textarea>
            @if($piedPage)
            <div class="mt-2 p-2 bg-gray-50 rounded border border-gray-200 text-center text-xs text-gray-500 italic">
                {{ $piedPage }}
            </div>
            @endif
        </div>

        {{-- Bouton Enregistrer --}}
        <div class="flex justify-end">
            <button type="submit"
                class="flex items-center gap-2 px-6 py-2.5 rounded-xl bg-primary text-white text-sm font-semibold hover:bg-primary/90 shadow-sm transition-colors">
                <i class="fas fa-save"></i> Enregistrer les paramètres
            </button>
        </div>
    </form>
</div>
