// Pages d'impression : odontogramme anatomique en lecture seule (sans Livewire ni Alpine).
document.querySelectorAll('[data-odontogramme-impression]').forEach((bloc) => {
    const { conditions = [] } = JSON.parse(bloc.dataset.odontogrammeImpression || '{}');
    import('./odontogramme').then((m) => m.monter(bloc.querySelector('[data-scene]'), { selection: [], conditions, lectureSeule: true, numeros: true }));
});
