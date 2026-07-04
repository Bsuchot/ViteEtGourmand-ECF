import { api } from '../modules/api.js';
import { showAlert } from '../modules/alerts.js';

const emailInput     = document.getElementById('contactEmailFormControlInput');
const nameInput      = document.getElementById('contactNameFormControlInput');
const firstnameInput = document.getElementById('contactFirstNameFormControlInput');
const titreInput     = document.getElementById('contactTitleFormControlInput');
const msgInput       = document.getElementById('contactDescriptionFormControlTextarea');
const emailRegex     = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

document.querySelector('#contactForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();

    const email   = emailInput?.value.trim();
    const nom     = nameInput?.value.trim();
    const prenom  = firstnameInput?.value.trim();
    const titre   = titreInput?.value.trim();
    const message = msgInput?.value.trim();

    if (!email || !emailRegex.test(email)) {
        emailInput.classList.add('is-invalid');
        showAlert('Veuillez saisir un email valide.', 'warning');
        return;
    }
    emailInput.classList.remove('is-invalid');

    if (!titre || !message) {
        showAlert('Veuillez remplir tous les champs.', 'warning');
        return;
    }

    const res = await api.post('/contact', { email, nom, prenom, titre, message });
    if (res.success) {
        showAlert('Votre message a bien été envoyé !', 'success');
        emailInput.value     = '';
        nameInput.value      = '';
        firstnameInput.value = '';
        titreInput.value     = '';
        msgInput.value       = '';
    } else {
        showAlert('Une erreur est survenue.', 'danger');
    }
});