import { api } from '../../../modules/api.js';

const jours = [
    { label: 'Lundi',    statut: 'statutLundi',    ouverture: 'ouvertureLundi',    fermeture: 'fermetureLundi' },
    { label: 'Mardi',    statut: 'statutMardi',    ouverture: 'ouvertureMardi',    fermeture: 'fermetureMardi' },
    { label: 'Mercredi', statut: 'statutMercredi', ouverture: 'ouvertureMercredi', fermeture: 'fermetureMercredi' },
    { label: 'Jeudi',    statut: 'statutJeudi',    ouverture: 'ouvertureJeudi',    fermeture: 'fermetureJeudi' },
    { label: 'Vendredi', statut: 'statutVendredi', ouverture: 'ouvertureVendredi', fermeture: 'fermetureVendredi' },
    { label: 'Samedi',   statut: 'statutSamedi',   ouverture: 'ouvertureSamedi',   fermeture: 'fermetureSamedi' },
    { label: 'Dimanche', statut: 'statutDimanche', ouverture: 'ouvertureDimanche', fermeture: 'fermetureDimanche' },
];

let horaireIds = {};

export function initHoraires() {
    const collapse = document.getElementById('collapseHourly');
    if (!collapse) return;

    const btnSave = document.getElementById('btnSaveHoraires');

    collapse.addEventListener('show.bs.collapse', () => {
        loadHoraires();
    });

    async function loadHoraires() {
        const data = await api.get('/horaire/readAll');
        if (!data.success) return;

        data.data.forEach(h => {
            const jour = jours.find(j => j.label.toLowerCase() === h.jour.toLowerCase());
            if (!jour) return;

            horaireIds[h.jour] = h.id;
            document.getElementById(jour.statut).value    = h.statut;
            document.getElementById(jour.ouverture).value = h.heureOuverture ?? '';
            document.getElementById(jour.fermeture).value = h.heureFermeture ?? '';
        });
    }

    btnSave.addEventListener('click', async () => {
        const payload = jours.map(j => ({
            id:             horaireIds[j.label.toLowerCase()],
            jour:           j.label.toLowerCase(),
            statut:         document.getElementById(j.statut).value,
            heureOuverture: document.getElementById(j.ouverture).value,
            heureFermeture: document.getElementById(j.fermeture).value,
        })).filter(h => h.id);

        const data = await api.put('/horaire/update', payload);
        alert(data.success ? 'Horaires mis à jour.' : 'Erreur : ' + (data.error ?? 'Une erreur est survenue.'));
    });
}