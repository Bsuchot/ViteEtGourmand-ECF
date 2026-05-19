import { api } from '../../../modules/api.js';
import { getCookie } from '../../../main.js';

export function initProfile() {
    const userId = getCookie('id');

    const inputNom     = document.getElementById('NomInput');
    const inputPrenom  = document.getElementById('PrenomInput');
    const inputTelephone     = document.getElementById('TelephoneInput');
    const inputEmail   = document.getElementById('EmailInput');
    const inputAdresse = document.getElementById('changeAdressFormInput');
    const inputVille   = document.getElementById('changeCityFormInput');
    const inputPays    = document.getElementById('changecountryFormInput');

    const btnCoordonnees = document.getElementById('NewCoordonesBtn');
    const inputPassword    = document.getElementById('PasswordInput');
    const inputNewPassword = document.getElementById('NewPasswordInput');
    const btnPassword      = document.getElementById('NewPasswordBtn');

    let originalData = {};

    async function loadUser() {
        const data = await api.get(`/utilisateur/${userId}`);
        if (!data.success) return;
        const u = data.data;
        inputNom.value     = u.nom       ?? '';
        inputPrenom.value  = u.prenom    ?? '';
        inputTelephone.value     = u.telephone ?? '';
        inputEmail.value   = u.email     ?? '';
        inputAdresse.value = u.adresse   ?? '';
        inputVille.value   = u.ville     ?? '';
        inputPays.value    = u.pays      ?? '';
        originalData = u;
    }

    document.getElementById('collapsePersonalData')?.addEventListener('show.bs.collapse', () => {
        loadUser();
    });

    document.getElementById('collapseAdresse')?.addEventListener('show.bs.collapse', () => {
        loadUser();
    });

    btnCoordonnees?.addEventListener('click', async () => {
        const payload = {};
        if (inputNom.value.trim()     !== originalData.nom)       payload.nom       = inputNom.value.trim();
        if (inputPrenom.value.trim()  !== originalData.prenom)    payload.prenom    = inputPrenom.value.trim();
        if (inputTelephone.value.trim()     !== originalData.telephone) payload.telephone = inputTelephone.value.trim();
        if (inputEmail.value.trim()   !== originalData.email)     payload.email     = inputEmail.value.trim();
        if (inputAdresse.value.trim() !== originalData.adresse)   payload.adresse   = inputAdresse.value.trim();
        if (inputVille.value.trim()   !== originalData.ville)     payload.ville     = inputVille.value.trim();
        if (inputPays.value.trim()    !== originalData.pays)      payload.pays      = inputPays.value.trim();

        if (Object.keys(payload).length === 0) {
            alert('Aucune modification détectée.');
            return;
        }

        const data = await api.put(`/utilisateur/${userId}`, payload);
        if (data.success) {
            originalData = { ...originalData, ...payload };
            alert('Données mises à jour.');
        } else {
            alert('Erreur : ' + (data.error ?? 'Une erreur est survenue.'));
        }
    });

    btnPassword?.addEventListener('click', async () => {
        const data = await api.put(`/utilisateur/${userId}/password`, {
            currentPassword: inputPassword.value,
            newPassword:     inputNewPassword.value,
        });
        if (data.success) {
            inputPassword.value    = '';
            inputNewPassword.value = '';
            alert('Mot de passe mis à jour.');
        } else {
            alert('Erreur : ' + (data.error ?? 'Une erreur est survenue.'));
        }
    });
}