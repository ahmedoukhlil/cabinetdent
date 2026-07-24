<div class="bg-primary text-white p-4 rounded-t-lg">
    <h2 class="text-xl font-bold">Statistiques</h2>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    <div class="bg-white rounded-lg shadow p-4 border border-primary">
        <h3 class="text-lg font-semibold text-primary mb-2">Consultations</h3>
        <p class="text-2xl font-bold text-primary">{{ $totalConsultations }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4 border border-primary">
        <h3 class="text-lg font-semibold text-primary mb-2">Rendez-vous</h3>
        <p class="text-2xl font-bold text-primary">{{ $totalRendezVous }}</p>
    </div>
    <div class="bg-white rounded-lg shadow p-4 border border-primary">
        <h3 class="text-lg font-semibold text-primary mb-2">Patients</h3>
        <p class="text-2xl font-bold text-primary">{{ $totalPatients }}</p>
    </div>
</div>

<button type="submit" class="px-4 py-2 bg-primary text-white rounded hover:bg-primary-dark">
    Filtrer
</button> 