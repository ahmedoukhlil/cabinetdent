            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                <!-- Header vert dégradé -->
                <div class="p-6 rounded-t-lg bg-primary text-white shadow">
                    <h2 class="text-2xl font-bold">Nouvelle Consultation</h2>
                    <p class="text-primary-light mt-1">Patient : {{ $patient->Nom }} {{ $patient->Prenom }}</p>
                </div>

                <!-- Contenu du formulaire -->
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <form wire:submit.prevent="enregistrerConsultation">
                        <!-- ... existing form fields ... -->

                        <!-- Bouton d'enregistrement -->
                        <div class="mt-6 flex justify-end">
                            <button type="submit" class="px-4 py-2 bg-primary text-white rounded hover:bg-primary hover:text-white">
                                Enregistrer la consultation
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Footer avec bouton Fermer -->
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse rounded-b-lg">
                    <button type="button" wire:click="closeConsultationForm" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Fermer
                    </button>
                </div>
            </div> 