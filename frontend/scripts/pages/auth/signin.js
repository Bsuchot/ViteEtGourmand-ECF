import { api } from '../../modules/api.js';
import { setCookie } from '../../main.js';
import { showAlert } from '../../modules/alerts.js';

const mailInput     = document.getElementById('EmailInput');
const passwordInput = document.getElementById('PasswordInput');
const btnSignin     = document.getElementById('btn-signin');

btnSignin.addEventListener('click', checkCredentials);



async function checkCredentials() {
    const email    = mailInput.value.trim();
    const password = passwordInput.value.trim();

    if (!email || !password) {
        mailInput.classList.add('is-invalid');
        passwordInput.classList.add('is-invalid');
        showAlert('Veuillez remplir tous les champs.', 'danger');
        return;
    }

    const data = await api.post('/utilisateur/login', { email, password });

    if (data.success) {
        const role = data.data.user.role.replace('ROLE_', '').toLowerCase();
        setCookie('role', role, 1);
        setCookie('id', data.data.user.id, 1);
        globalThis.location.replace('/');
    } else {
        mailInput.classList.add('is-invalid');
        passwordInput.classList.add('is-invalid');
        showAlert('Identifiants incorrects.', 'danger');
    }
}