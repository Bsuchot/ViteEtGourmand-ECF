import { api } from '../../modules/api.js';
import { showAlert } from '../../modules/alerts.js';

const token   = new URLSearchParams(window.location.search).get('token');
const input   = document.getElementById('resetPasswordInput');
const confirm = document.getElementById('resetPasswordConfirmInput');
const btn     = document.getElementById('btnResetPassword');

if (!token) showAlert('Token manquant ou invalide.', 'danger');

btn?.addEventListener('click', async () => {
    const password        = input?.value;
    const passwordConfirm = confirm?.value;

    if (!password || password !== passwordConfirm) {
        showAlert('Les mots de passe ne correspondent pas.', 'warning');
        return;
    }

    const res = await api.post('/utilisateur/reset-password', { token, newPassword: password });
    if (res.success) {
        showAlert('Mot de passe réinitialisé ! Vous pouvez vous connecter.', 'success');
        setTimeout(() => window.location.href = '/signin', 2000);
    } else {
        showAlert(res.data?.error ?? 'Une erreur est survenue.', 'danger');
    }
});