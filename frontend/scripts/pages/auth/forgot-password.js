import { api } from '../../modules/api.js';
import { showAlert } from '../../modules/alerts.js';

const btn   = document.getElementById('btnForgotPassword');
const input = document.getElementById('forgotEmailInput');

btn?.addEventListener('click', async () => {
    const email = input?.value.trim();
    if (!email) {
        showAlert('Veuillez saisir votre email.', 'warning');
        return;
    }

    const res = await api.post('/utilisateur/forgot-password', { email });
    if (res.success) {
        showAlert(res.data?.message ?? 'Lien envoyé.', 'success');
    } else {
        showAlert('Une erreur est survenue.', 'danger');
    }
});