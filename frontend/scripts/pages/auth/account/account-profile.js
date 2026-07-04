import { api } from '../../../modules/api.js';
import { getCookie } from '../../../main.js';
import { showAlert } from '../../../modules/alerts.js';

export function initProfile() {
    const userId = getCookie('id');

    const inputNom       = document.getElementById('NomInput');
    const inputPrenom    = document.getElementById('PrenomInput');
    const inputTelephone = document.getElementById('TelephoneInput');
    const inputEmail     = document.getElementById('EmailInput');
    const inputAdresse   = document.getElementById('changeAdressFormInput');
    const inputVille     = document.getElementById('changeCityFormInput');
    const inputPostal    = document.getElementById('changePostalFormInput');
    const inputPays      = document.getElementById('changecountryFormInput');

    const btnCoordonnees   = document.getElementById('NewCoordonesBtn');
    const inputPassword    = document.getElementById('PasswordInput');
    const inputNewPassword = document.getElementById('NewPasswordInput');
    const btnPassword      = document.getElementById('NewPasswordBtn');

    let originalData = {};
    // Map ville → code postal pour les suggestions
    const villePostalMap = new Map();

    // ─── Autocomplétion ville via geo.api.gouv.fr ────────────────────────────
    let debounceTimer = null;

    inputVille?.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        const q = inputVille.value.trim();

        if (q.length < 2) {
            clearSuggestions();
            return;
        }

        debounceTimer = setTimeout(async () => {
            try {
                const res = await fetch(
                    `https://geo.api.gouv.fr/communes?nom=${encodeURIComponent(q)}&fields=nom,codesPostaux&boost=population&limit=6`
                );
                const communes = await res.json();

                villePostalMap.clear();
                const datalist = document.getElementById('citysuggestions');
                if (!datalist) return;

                datalist.innerHTML = '';
                communes.forEach(c => {
                    const cp = c.codesPostaux?.[0] ?? '';
                    const label = cp ? `${c.nom} (${cp})` : c.nom;
                    villePostalMap.set(label, { nom: c.nom, cp });

                    const opt = document.createElement('option');
                    opt.value = label;
                    datalist.appendChild(opt);
                });
            } catch {
                // API indisponible, on ignore
            }
        }, 300);
    });

    // Quand l'utilisateur choisit une suggestion → remplir code postal
    inputVille?.addEventListener('change', () => {
        const match = villePostalMap.get(inputVille.value);
        if (match) {
            inputVille.value = match.nom;
            if (inputPostal) inputPostal.value = match.cp;
        }
    });

    function clearSuggestions() {
        const datalist = document.getElementById('citysuggestions');
        if (datalist) datalist.innerHTML = '';
    }

    // ─── Chargement utilisateur ───────────────────────────────────────────────
    async function loadUser() {
        const data = await api.get(`/utilisateur/${userId}`);
        if (!data.success) return;
        const u = data.data;

        inputNom.value       = u.nom       ?? '';
        inputPrenom.value    = u.prenom    ?? '';
        inputTelephone.value = u.telephone ?? '';
        inputEmail.value     = u.email     ?? '';
        inputAdresse.value   = u.adresse   ?? '';
        inputPays.value      = u.pays      ?? '';

        // Ville : peut contenir "Bordeaux (33000)" ou juste "Bordeaux"
        if (u.ville) {
            const match = u.ville.match(/^(.+?)\s*\((\d{5})\)$/);
            if (match) {
                inputVille.value  = match[1];
                if (inputPostal) inputPostal.value = match[2];
            } else {
                inputVille.value = u.ville;
                // Tenter de récupérer le code postal depuis l'API
                if (inputPostal) {
                    try {
                        const res = await fetch(
                            `https://geo.api.gouv.fr/communes?nom=${encodeURIComponent(u.ville)}&fields=codesPostaux&limit=1`
                        );
                        const communes = await res.json();
                        inputPostal.value = communes?.[0]?.codesPostaux?.[0] ?? '';
                    } catch { /* silencieux */ }
                }
            }
        }

        originalData = u;
    }

    document.getElementById('collapsePersonalData')?.addEventListener('show.bs.collapse', () => loadUser());
    document.getElementById('collapseAdresse')?.addEventListener('show.bs.collapse', () => loadUser());

    // ─── Sauvegarde ───────────────────────────────────────────────────────────
    btnCoordonnees?.addEventListener('click', async () => {
        // Construire la valeur ville stockée : "Bordeaux (33000)" si code postal dispo
        const villeValue = inputPostal?.value
            ? `${inputVille.value.trim()} (${inputPostal.value.trim()})`
            : inputVille.value.trim();

        const payload = {};
        if (inputNom.value.trim()       !== originalData.nom)       payload.nom       = inputNom.value.trim();
        if (inputPrenom.value.trim()    !== originalData.prenom)    payload.prenom    = inputPrenom.value.trim();
        if (inputTelephone.value.trim() !== originalData.telephone) payload.telephone = inputTelephone.value.trim();
        if (inputEmail.value.trim()     !== originalData.email)     payload.email     = inputEmail.value.trim();
        if (inputAdresse.value.trim()   !== originalData.adresse)   payload.adresse   = inputAdresse.value.trim();
        if (villeValue                  !== originalData.ville)     payload.ville     = villeValue;
        if (inputPays.value.trim()      !== originalData.pays)      payload.pays      = inputPays.value.trim();

        if (Object.keys(payload).length === 0) {
            showAlert('Aucune modification détectée.', 'info');
            return;
        }

        const data = await api.put(`/utilisateur/${userId}`, payload);
        if (data.success) {
            originalData = { ...originalData, ...payload };
            showAlert('Données mises à jour.', 'success');
        } else {
            showAlert('Erreur : ' + (data.error ?? 'Une erreur est survenue.'), 'danger');
        }
    });

    // ─── Mot de passe ─────────────────────────────────────────────────────────
    btnPassword?.addEventListener('click', async () => {
        const data = await api.put(`/utilisateur/${userId}/password`, {
            currentPassword: inputPassword.value,
            newPassword:     inputNewPassword.value,
        });
        if (data.success) {
            inputPassword.value    = '';
            inputNewPassword.value = '';
            showAlert('Mot de passe mis à jour.', 'success');
        } else {
            showAlert('Erreur : ' + (data.error ?? 'Une erreur est survenue.'), 'danger');
        }
    });
}