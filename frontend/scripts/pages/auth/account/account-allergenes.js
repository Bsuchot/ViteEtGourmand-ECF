import { api } from '../../../modules/api.js';

let allergenes = [];
let editingId  = null;   // id de l'allergène en cours de modification
let deletingId = null;   // id de l'allergène en attente de confirmation de suppression
let busy       = false;  // évite les doubles clics pendant un appel API

const byId = (id) => document.getElementById(id);

// ─── Utilitaires ─────────────────────────────────────────────────────────────
function escHtml(str) {
    return String(str ?? '').replace(/[&<>"']/g, m =>
        ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m])
    );
}

function errText(error, fallback = 'Une erreur est survenue.') {
    if (!error) return fallback;
    if (typeof error === 'string') return error;
    if (typeof error === 'object') return Object.values(error).flat().join(' ');
    return fallback;
}

// Message affiché dans le modal (showAlert serait masqué par le fond du modal)
function showFeedback(message, type = 'danger') {
    const el = byId('allergeneFeedback');
    if (!el) return;
    el.className   = `alert alert-${type} py-2`;
    el.textContent = message;
}

function clearFeedback() {
    const el = byId('allergeneFeedback');
    if (!el) return;
    el.className   = 'alert d-none py-2';
    el.textContent = '';
}

function isDuplicate(libelle, ignoreId = null) {
    const wanted = libelle.trim().toLowerCase();
    return allergenes.some(a => a.id !== ignoreId && String(a.libelle).trim().toLowerCase() === wanted);
}

// ─── Données ─────────────────────────────────────────────────────────────────
export async function loadAllergenes() {
    const res = await api.get('/allergene/readAll');
    if (res.success) allergenes = res.data ?? [];
    return allergenes;
}

export function getAllergenes() { return allergenes; }

async function reload() {
    await loadAllergenes();
    renderAllergenes();
}

// ─── Affichage ───────────────────────────────────────────────────────────────
function renderAllergenes() {
    const tbody = document.querySelector('#tableAllergenes tbody');
    if (!tbody) return;

    if (!allergenes.length) {
        tbody.innerHTML = '<tr><td colspan="2" class="text-center text-muted">Aucun allergène pour le moment.</td></tr>';
        return;
    }

    tbody.innerHTML = allergenes.map(a => {
        // Ligne en cours de modification
        if (a.id === editingId) {
            return `
                <tr data-id="${a.id}">
                    <td>
                        <input type="text" class="form-control form-control-sm" data-role="edit-input"
                            value="${escHtml(a.libelle)}" aria-label="Nom de l'allergène">
                        <div class="invalid-feedback">Le nom est requis.</div>
                    </td>
                    <td class="text-end text-nowrap">
                        <button type="button" class="btn btn-sm btn-primary" data-action="save">Enregistrer</button>
                        <button type="button" class="btn btn-sm btn-secondary" data-action="cancel">Annuler</button>
                    </td>
                </tr>`;
        }

        // Ligne en attente de confirmation de suppression
        if (a.id === deletingId) {
            return `
                <tr data-id="${a.id}" class="table-danger">
                    <td class="align-middle">Supprimer « ${escHtml(a.libelle)} » ?</td>
                    <td class="text-end text-nowrap">
                        <button type="button" class="btn btn-sm btn-danger" data-action="confirm-delete">Oui</button>
                        <button type="button" class="btn btn-sm btn-secondary" data-action="cancel">Non</button>
                    </td>
                </tr>`;
        }

        // Ligne normale
        return `
            <tr data-id="${a.id}">
                <td class="align-middle">${escHtml(a.libelle)}</td>
                <td class="text-end text-nowrap">
                    <button type="button" class="btn btn-sm btn-primary" data-action="edit">Modifier</button>
                    <button type="button" class="btn btn-sm btn-danger" data-action="delete">Supprimer</button>
                </td>
            </tr>`;
    }).join('');

    tbody.querySelector('[data-role="edit-input"]')?.focus();
}

// ─── Actions ─────────────────────────────────────────────────────────────────
async function addAllergene() {
    const input   = byId('newAllergeneInput');
    const libelle = input.value.trim();

    if (!libelle) {
        input.classList.add('is-invalid');
        return;
    }
    input.classList.remove('is-invalid');

    if (isDuplicate(libelle)) {
        showFeedback('Cet allergène existe déjà.');
        return;
    }

    try {
        const res = await api.post('/allergene/create', { libelle });
        if (!res.success) {
            showFeedback(errText(res.error));
            return;
        }
        input.value = '';
        await reload();
        showFeedback('Allergène ajouté.', 'success');
    } catch (err) {
        showFeedback(errText(err.data?.error, 'Impossible d\'ajouter l\'allergène.'));
    }
}

async function saveAllergene(id, row) {
    const input   = row.querySelector('[data-role="edit-input"]');
    const libelle = input.value.trim();

    if (!libelle) {
        input.classList.add('is-invalid');
        return;
    }
    input.classList.remove('is-invalid');

    if (isDuplicate(libelle, id)) {
        showFeedback('Un allergène porte déjà ce nom.');
        return;
    }

    try {
        const res = await api.put(`/allergene/${id}`, { libelle });
        if (!res.success) {
            showFeedback(errText(res.error));
            return;
        }
        editingId = null;
        await reload();
        showFeedback('Allergène modifié.', 'success');
    } catch (err) {
        showFeedback(errText(err.data?.error, 'Impossible de modifier l\'allergène.'));
    }
}

async function deleteAllergene(id) {
    try {
        const res = await api.delete(`/allergene/${id}`);
        if (!res.success) {
            showFeedback(errText(res.error));
            return;
        }
        deletingId = null;
        await reload();
        showFeedback('Allergène supprimé.', 'success');
    } catch (err) {
        deletingId = null;
        renderAllergenes();
        showFeedback(errText(
            err.data?.error,
            'Suppression impossible : cet allergène est peut-être utilisé par un plat.'
        ));
    }
}

// Exécute une action asynchrone en bloquant les doubles clics
async function runOnce(fn) {
    if (busy) return;
    busy = true;
    try { await fn(); } finally { busy = false; }
}

// ─── Initialisation ──────────────────────────────────────────────────────────
export function initAllergenes() {
    console.log('[allergenes] init', !!byId('allAllergeneModal'), !!document.querySelector('#tableAllergenes tbody'));
    const modalEl = byId('allAllergeneModal');
    if (!modalEl) return;

    modalEl.addEventListener('show.bs.modal', async () => {
        console.log('[allergenes] ouverture');
        editingId  = null;
        deletingId = null;
        clearFeedback();
        byId('newAllergeneInput')?.classList.remove('is-invalid');
        await reload();
        console.log('[allergenes] chargés :', allergenes.length);
    });

    // Ajout
    byId('formNewAllergene')?.addEventListener('submit', (e) => {
        e.preventDefault();
        runOnce(addAllergene);
    });

    // Boutons de la liste (délégation : un seul écouteur, même après chaque rendu)
    const tbody = document.querySelector('#tableAllergenes tbody');
    if (!tbody) return;

    tbody.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-action]');
        if (!btn) return;

        const row = btn.closest('tr');
        const id  = Number.parseInt(row?.dataset.id);

        switch (btn.dataset.action) {
            case 'edit':
                editingId  = id;
                deletingId = null;
                clearFeedback();
                renderAllergenes();
                break;
            case 'delete':
                deletingId = id;
                editingId  = null;
                clearFeedback();
                renderAllergenes();
                break;
            case 'cancel':
                editingId  = null;
                deletingId = null;
                renderAllergenes();
                break;
            case 'save':
                runOnce(() => saveAllergene(id, row));
                break;
            case 'confirm-delete':
                runOnce(() => deleteAllergene(id));
                break;
        }
    });

    // Clavier dans le champ de modification : Entrée = enregistrer, Échap = annuler
    tbody.addEventListener('keydown', (e) => {
        if (!e.target.matches('[data-role="edit-input"]')) return;

        if (e.key === 'Enter') {
            e.preventDefault();
            const row = e.target.closest('tr');
            runOnce(() => saveAllergene(Number.parseInt(row.dataset.id), row));
        } else if (e.key === 'Escape') {
            e.preventDefault();
            e.stopPropagation(); // évite de fermer tout le modal
            editingId = null;
            renderAllergenes();
        }
    });
}