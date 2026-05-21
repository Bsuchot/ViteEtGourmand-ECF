import { api } from '../modules/api.js';
import { showAlert } from '../modules/alerts.js';

// ─── Point d'entrée ───────────────────────────────────────────────────────────
export async function init() {
    await loadAvis();
    initModal();
}

// ─── Chargement et affichage des avis ────────────────────────────────────────
async function loadAvis() {
    const container = document.querySelector('.container.my-4');
    if (!container) return;

    const res = await api.get('/avis/readAll');
    if (!res.success) return;

    const avis = (res.data ?? []).filter(a => a.statut === 'Publié');

    // Supprimer les review-card statiques
    container.querySelectorAll('.review-card').forEach(el => el.remove());

    // Insérer avant le bouton
    const btnWrapper = container.querySelector('.d-flex.justify-content-center');

    if (!avis.length) {
        const empty = document.createElement('p');
        empty.className = 'text-muted text-center';
        empty.textContent = 'Aucun avis pour le moment.';
        btnWrapper.before(empty);
        return;
    }

    avis.forEach(a => {
        const card = document.createElement('div');
        card.className = 'review-card py-3 border-bottom';
        card.innerHTML = `
            <div class="d-flex">
                <div class="review-author pe-3">
                    <strong class="d-block">${escHtml(a.utilisateurPrenom ?? '')} ${escHtml(a.utilisateurNom ?? '')}</strong>
                    <small class="text-muted">Publié le ${formatDate(a.date)}</small>
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <div class="text-warning d-flex gap-1">
                            ${renderStars(a.note)}
                        </div>
                        <strong class="review-title">${escHtml(a.titre)}</strong>
                    </div>
                    <p class="review-text mt-2 mb-0">${escHtml(a.description)}</p>
                </div>
            </div>
        `;
        btnWrapper.before(card);
    });
}

// ─── Étoiles ──────────────────────────────────────────────────────────────────
function renderStars(note) {
    const n = Number.parseInt(note) || 0;
    return Array.from({ length: 5 }, (_, i) =>
        `<i class="bi ${i < n ? 'bi-star-fill' : 'bi-star'}"></i>`
    ).join('');
}

// ─── Modal dépôt d'avis ───────────────────────────────────────────────────────
function initModal() {
    const modal       = document.getElementById('reviewModal');
    const btnValider  = document.getElementById('btn-validate-review');
    if (!modal || !btnValider) return;

    // Préremplir prénom/nom si connecté
    modal.addEventListener('show.bs.modal', async () => {
        try {
            const me = await api.get('/utilisateur/me');
            if (!me.success) return;

            const userId = me.data.user?.id ?? me.data.id;
            if (!userId) return;

            const res = await api.get(`/utilisateur/${userId}`);
            if (!res.success) return;

            const u = res.data;
            const inputPrenom = document.getElementById('firstnameReviwInput');
            const inputNom    = document.getElementById('nameReviewInput');
            if (inputPrenom && u.prenom) inputPrenom.value = u.prenom;
            if (inputNom    && u.nom)    inputNom.value    = u.nom;
        } catch { /* non connecté, champs vides */ }
    });

    // Soumission
    btnValider.addEventListener('click', async (e) => {
        e.preventDefault();

        const titre       = document.getElementById('titleReviewInput')?.value.trim();
        const description = document.getElementById('reviewTextarea')?.value.trim();
        const noteInput   = document.querySelector('input[name="rating"]:checked');
        const note        = noteInput ? Number.parseInt(noteInput.value) : null;

        // Validation
        let valid = true;

        if (titre) {
            document.getElementById('titleReviewInput')?.classList.remove('is-invalid');
        } else {
            document.getElementById('titleReviewInput')?.classList.add('is-invalid');
            valid = false;
        }

        if (description) {
            document.getElementById('reviewTextarea')?.classList.remove('is-invalid');
        } else {
            document.getElementById('reviewTextarea')?.classList.add('is-invalid');
            valid = false;
        }

        if (note) {
            document.querySelector('.star-rating')?.classList.remove('is-invalid');
        } else {
            document.querySelector('.star-rating')?.classList.add('is-invalid');
            valid = false;
        }

        if (!valid) return;

        try {
            const res = await api.post('/avis/create', { titre, description, note });

            if (res.success) {
                bootstrap.Modal.getInstance(modal)?.hide();
                resetModal();
                
                showAlert('Votre avis a bien été envoyé. Il sera visible après modération.', 'success');
            } else {
                showAlert('Erreur : ' + (res.error ?? 'Une erreur est survenue.'), 'danger');
            }
        } catch (err) {
            if (err.status === 401) {
                showAlert('Vous devez être connecté pour laisser un avis.', 'danger');
            } else {
                showAlert('Erreur lors de l\'envoi de l\'avis.', 'danger');
            }
        }
    });
}

function resetModal() {
    document.getElementById('titleReviewInput').value    = '';
    document.getElementById('reviewTextarea').value      = '';
    document.querySelectorAll('input[name="rating"]').forEach(r => r.checked = false);
    document.getElementById('titleReviewInput')?.classList.remove('is-invalid', 'is-valid');
    document.getElementById('reviewTextarea')?.classList.remove('is-invalid', 'is-valid');
}

// ─── Utils ────────────────────────────────────────────────────────────────────
function formatDate(dateField) {
    const str = typeof dateField === 'string' ? dateField : (dateField?.date ?? '');
    if (!str) return '—';
    const [year, month, day] = str.split(/[-T ]/);
    return `${day}/${month}/${year}`;
}

function escHtml(str) {
    return str ? str.replace(/[&<>"']/g, m =>
        ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m])
    ) : '';
}
