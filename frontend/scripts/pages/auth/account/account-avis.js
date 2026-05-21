import { api } from '../../../modules/api.js';
import { showAlert } from '../../../modules/alerts.js';

export function initAvis() {
    const collapse = document.getElementById('collapseReviewManagement');
    if (!collapse) return;

    let pendingDeleteId = null;
    let allAvis = [];

    collapse.addEventListener('show.bs.collapse', () => loadAvis());

    // ─── Chargement ───────────────────────────────────────────────────────────
    async function loadAvis() {
        const res = await api.get('/avis/readAll');
        if (!res.success) {
            showAlert('Erreur lors du chargement des avis.', 'danger');
            return;
        }
        allAvis = res.data ?? [];
        renderAvis(allAvis);
    }

    // ─── Rendu ────────────────────────────────────────────────────────────────
    function renderAvis(avis) {
        const tbody = document.querySelector('#reviewTable tbody');
        if (!tbody) return;
        tbody.innerHTML = '';

        if (!avis.length) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">Aucun avis</td></tr>';
            return;
        }

        avis.forEach(a => {
            const tr = document.createElement('tr');
            tr.dataset.id = a.id;

            tr.innerHTML = `
                <td>${escHtml(a.utilisateurPrenom ?? '')} ${escHtml(a.utilisateurNom ?? '')}</td>
                <td>${escHtml(a.titre)}</td>
                <td>
                    <div class="d-flex gap-1">
                        ${renderStars(a.note)}
                    </div>
                </td>
                <td>${escHtml(a.description)}</td>
                <td>
                    <select class="form-select form-select-sm" data-id="${a.id}">
                        <option value="En attente" ${a.statut === 'En attente' ? 'selected' : ''}>En attente</option>
                        <option value="Publié"     ${a.statut === 'Publié'     ? 'selected' : ''}>Publié</option>
                        <option value="Rejeté"     ${a.statut === 'Rejeté'     ? 'selected' : ''}>Rejeté</option>
                    </select>
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger btn-delete-avis"
                            data-id="${a.id}"
                            data-bs-toggle="modal"
                            data-bs-target="#confirmationDeleteModal">
                        Supprimer
                    </button>
                </td>
            `;

            tbody.appendChild(tr);
        });

        // Boutons supprimer → mémoriser l'id
        tbody.querySelectorAll('.btn-delete-avis').forEach(btn => {
            btn.addEventListener('click', () => {
                pendingDeleteId = parseInt(btn.dataset.id);
            });
        });
    }

    // ─── Valider les changements de statut ────────────────────────────────────
    document.getElementById('btnSaveReviewChanges')?.addEventListener('click', async () => {
        const selects = document.querySelectorAll('#reviewTable select[data-id]');
        const promises = [];

        selects.forEach(select => {
            const id      = parseInt(select.dataset.id);
            const statut  = select.value;
            const current = allAvis.find(a => a.id === id);

            if (current && current.statut !== statut) {
                promises.push(
                    api.put(`/employe/avis/${id}/statut`, { statut })
                );
            }
        });

        if (!promises.length) {
            showAlert('Aucune modification détectée.', 'warning');
            return;
        }

        const results = await Promise.all(promises);
        const errors  = results.filter(r => !r.success);

        if (errors.length) {
            showAlert('Certaines modifications ont échoué.', 'danger');
        } else {
            showAlert('Modifications enregistrées.', 'success');
            await loadAvis();
        }
    });

    // ─── Confirmation suppression ─────────────────────────────────────────────
    document.getElementById('btnConfirmDelete')?.addEventListener('click', async () => {
        if (!pendingDeleteId) return;

        const res = await api.delete(`/avis/${pendingDeleteId}`);
        if (res.success) {
            bootstrap.Modal.getInstance(document.getElementById('confirmationDeleteModal'))?.hide();
            await loadAvis();
        } else {
            showAlert('Erreur : ' + (res.error ?? 'Une erreur est survenue.'), 'danger');
        }
        pendingDeleteId = null;
    });

    // ─── Helpers ─────────────────────────────────────────────────────────────
    function renderStars(note) {
        const n = parseInt(note) || 0;
        return Array.from({ length: 5 }, (_, i) =>
            `<i class="bi ${i < n ? 'bi-star-fill' : 'bi-star'} text-warning"></i>`
        ).join('');
    }

    function escHtml(str) {
        return str ? str.replace(/[&<>"']/g, m =>
            ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m])
        ) : '';
    }
}