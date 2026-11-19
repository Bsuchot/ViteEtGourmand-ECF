import { api } from '../../../modules/api.js';
import { showAlert } from '../../../modules/alerts.js';

let allPlats            = [];
let allergenes          = [];
let pendingDeletePlatId = null;
let currentEditPlat     = null;
let newTomSelect        = null;
let editTomSelect       = null;

const byId = (id) => document.getElementById(id);

// ─── Utilitaires ─────────────────────────────────────────────────────────────
function escHtml(str) {
    return String(str ?? '').replace(/[&<>"']/g, m =>
        ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m])
    );
}

function imgSrc(photo) {
    if (!photo) return '';
    return photo.startsWith('/') ? API_URL + photo : photo;
}

// Transforme une erreur renvoyée par l'API (texte ou objet de champs) en texte lisible
function errText(error, fallback = 'Une erreur est survenue.') {
    if (!error) return fallback;
    if (typeof error === 'string') return error;
    if (typeof error === 'object') return Object.values(error).flat().join(' ');
    return fallback;
}

// fonction de conversion image en base64
function fileToBase64(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload  = () => resolve(reader.result);
        reader.onerror = () => reject(new Error('Erreur lecture fichier'));
        reader.readAsDataURL(file);
    });
}

// Bootstrap gère mal deux modals ouverts en même temps :
// on ferme le premier, puis on ouvre le second une fois qu'il est fermé.
function switchModal(fromId, toId) {
    const fromEl = byId(fromId);
    const open   = () => bootstrap.Modal.getOrCreateInstance(byId(toId)).show();
    const fromInstance = fromEl ? bootstrap.Modal.getInstance(fromEl) : null;

    if (fromInstance && fromEl.classList.contains('show')) {
        fromEl.addEventListener('hidden.bs.modal', open, { once: true });
        fromInstance.hide();
    } else {
        open();
    }
}

// ─── Chargement des données ──────────────────────────────────────────────────
async function refreshAllergenes() {
    const res = await api.get('/allergene/readAll');
    if (res.success) allergenes = res.data ?? [];
}

export async function loadPlats() {
    const [dataPlats, dataAllergenes] = await Promise.all([
        api.get('/plat/readAll'),
        api.get('/allergene/readAll'),
    ]);
    if (dataPlats.success)      allPlats   = dataPlats.data;
    if (dataAllergenes.success) allergenes = dataAllergenes.data;
    return allPlats;
}

export function getPlats() { return allPlats; }

// Ids des allergènes d'un plat. Accepte plusieurs formats renvoyés par l'API :
// tableau d'ids, d'objets {id, libelle} ou de libellés, ou texte "soja, gluten".
function getPlatAllergeneIds(plat) {
    const idByLibelle = (txt) => {
        const wanted = String(txt ?? '').trim().toLowerCase();
        return allergenes.find(a => String(a.libelle).trim().toLowerCase() === wanted)?.id;
    };

    const toId = (item) => {
        if (item && typeof item === 'object') {
            return Number.parseInt(item.id ?? item.allergeneId) || idByLibelle(item.libelle ?? item.nom);
        }
        return /^\d+$/.test(String(item).trim()) ? Number.parseInt(item) : idByLibelle(item);
    };

    // L'API peut renvoyer `allergenes: []` (vide) alors que `allergenesLibelle` contient
    // les noms : on additionne les deux sources au lieu de s'arrêter à la première.
    const fromArray   = Array.isArray(plat.allergenes) ? plat.allergenes : [];
    const fromLibelle = Array.isArray(plat.allergenesLibelle)
        ? plat.allergenesLibelle
        : String(plat.allergenesLibelle ?? '').split(/[,;/]/);

    const ids = [...fromArray, ...fromLibelle]
        .filter(i => i !== null && i !== undefined && String(i).trim() !== '')
        .map(toId)
        .filter(Number.isFinite);

    return [...new Set(ids)];
}

// ─── TomSelect (sélecteur d'allergènes) ──────────────────────────────────────
function rebuildTomSelect(previous, selectId, selectedIds = []) {
    previous?.destroy();

    const sel = byId(selectId);
    if (!sel) return null;

    sel.innerHTML = allergenes
        .map(a => `<option value="${a.id}">${escHtml(a.libelle)}</option>`)
        .join('');

    const instance = new TomSelect(`#${selectId}`, {
        create: async (input, callback) => {
            try {
                const data = await api.post('/allergene/create', { libelle: input });
                if (!data.success) throw data;
                await refreshAllergenes();
                callback({ value: data.data.id, text: input });
            } catch {
                showAlert('Erreur lors de la création de l\'allergène.', 'danger');
                callback(null);
            }
        },
        persist: false,
        createOnBlur: true,
        maxItems: null,
        placeholder: 'Sélectionner ou créer des allergènes'
    });

    instance.setValue(selectedIds.map(String));
    return instance;
}

// ─── Initialisation ──────────────────────────────────────────────────────────
export function initPlats() {
    const modalEl = byId('allPlatModal');
    if (!modalEl) return;

    // Ouvrir la liste → charger les plats
    modalEl.addEventListener('show.bs.modal', async () => {
        await loadPlats();
        renderPlats();
    });

    // Boutons du tableau (délégation : un seul écouteur, même après chaque rendu)
    modalEl.addEventListener('click', (e) => {
        const editBtn = e.target.closest('.btn-edit-plat');
        if (editBtn) {
            const id = Number.parseInt(editBtn.dataset.id);
            currentEditPlat = allPlats.find(p => p.id === id) ?? null;
            if (currentEditPlat) switchModal('allPlatModal', 'editPlatModal');
            return;
        }

        const deleteBtn = e.target.closest('.btn-delete-plat');
        if (deleteBtn) pendingDeletePlatId = Number.parseInt(deleteBtn.dataset.id);
    });

    // Bouton « Ajouter un plat » de la liste
    byId('btnOpenNewPlat')?.addEventListener('click', () => {
        switchModal('allPlatModal', 'newPlatModal');
    });

    // Confirmation suppression plat
    byId('btnConfirmDelete')?.addEventListener('click', async () => {
        if (!pendingDeletePlatId) return;
        try {
            const data = await api.delete(`/plat/${pendingDeletePlatId}`);
            if (data.success) {
                await loadPlats();
                renderPlats();
                bootstrap.Modal.getInstance(byId('confirmationDeleteModal'))?.hide();
            } else {
                showAlert('Erreur : ' + errText(data.error), 'danger');
            }
        } catch (err) {
            showAlert('Erreur : ' + errText(err.data?.error), 'danger');
        }
        pendingDeletePlatId = null;
    });

    initNewPlat();
    initEditPlat();
}

// ─── Création d'un plat ──────────────────────────────────────────────────────
function initNewPlat() {
    byId('newPlatModal')?.addEventListener('show.bs.modal', async () => {
        await refreshAllergenes();
        newTomSelect = rebuildTomSelect(newTomSelect, 'allergenes');
    });

    byId('btnValiderNewPlat')?.addEventListener('click', async () => {
        const titreInput = byId('newPlatTitleInput');
        const titre      = titreInput.value.trim();
        const category   = byId('newPlatCategorySelect').value;
        const fileInput  = byId('newPlatPhotoFormFile');
        const allergeneIds = newTomSelect
            ? newTomSelect.getValue().map(v => Number.parseInt(v))
            : [];

        if (!titre) {
            titreInput.classList.add('is-invalid');
            return;
        }
        titreInput.classList.remove('is-invalid');

        if (!fileInput.files[0]) {
            showAlert('La photo est obligatoire.', 'danger');
            return;
        }

        try {
            // Convertir en base64 puis uploader l'image
            const photo = await fileToBase64(fileInput.files[0]);
            const uploadData = await api.post('/upload', { photo });
            if (!uploadData.success) {
                showAlert('Erreur lors de l\'upload de l\'image.', 'danger');
                return;
            }

            // Créer le plat avec l'URL
            const data = await api.post('/plat/create', {
                titre,
                category,
                photo:      uploadData.data.url,
                allergenes: allergeneIds,
            });

            if (data.success) {
                titreInput.value = '';
                fileInput.value  = '';
                newTomSelect?.clear();
                switchModal('newPlatModal', 'allPlatModal');
            } else {
                showAlert('Erreur : ' + errText(data.error), 'danger');
            }
        } catch (err) {
            showAlert('Erreur : ' + errText(err.data?.error), 'danger');
        }
    });
}

// ─── Édition d'un plat ───────────────────────────────────────────────────────
function initEditPlat() {
    const editModalEl = byId('editPlatModal');
    if (!editModalEl) return;

    // Pré-remplir le formulaire avec le plat sélectionné
    editModalEl.addEventListener('show.bs.modal', async () => {
        if (!currentEditPlat) return;
        const p = currentEditPlat;

        const titreInput = byId('editPlatTitleInput');
        titreInput.value = p.titre ?? '';
        titreInput.classList.remove('is-invalid');

        byId('editPlatCategorySelect').value = p.category ?? 'plat';
        byId('editPlatPhotoFormFile').value  = '';

        const preview = byId('editPlatPhotoPreview');
        if (preview) {
            if (p.photo) {
                preview.src = imgSrc(p.photo);
                preview.alt = p.titre ?? '';
                preview.classList.remove('d-none');
            } else {
                preview.classList.add('d-none');
            }
        }

        await refreshAllergenes();
        editTomSelect = rebuildTomSelect(editTomSelect, 'editAllergenes', getPlatAllergeneIds(p));
    });

    // Valider la modification
    byId('btnValiderEditPlat')?.addEventListener('click', async (e) => {
        if (!currentEditPlat) return;

        const btn        = e.currentTarget;
        const titreInput = byId('editPlatTitleInput');
        const titre      = titreInput.value.trim();
        const category   = byId('editPlatCategorySelect').value;
        const file       = byId('editPlatPhotoFormFile').files[0];
        const allergeneIds = editTomSelect
            ? editTomSelect.getValue().map(v => Number.parseInt(v))
            : [];

        if (!titre) {
            titreInput.classList.add('is-invalid');
            return;
        }
        titreInput.classList.remove('is-invalid');

        btn.disabled = true;
        try {
            // Nouvelle photo uniquement si un fichier est choisi, sinon on garde l'actuelle
            let photo = currentEditPlat.photo;
            if (file) {
                const base64 = await fileToBase64(file);
                const upload = await api.post('/upload', { photo: base64 });
                if (!upload.success) {
                    showAlert('Erreur lors de l\'upload de l\'image.', 'danger');
                    return;
                }
                photo = upload.data.url;
            }

            const data = await api.put(`/plat/${currentEditPlat.id}`, {
                titre,
                category,
                photo,
                allergenes: allergeneIds,
            });

            if (data.success) {
                currentEditPlat = null;
                switchModal('editPlatModal', 'allPlatModal');
            } else {
                showAlert('Erreur : ' + errText(data.error), 'danger');
            }
        } catch (err) {
            showAlert('Erreur : ' + errText(err.data?.error), 'danger');
        } finally {
            btn.disabled = false;
        }
    });
}

// ─── Affichage de la liste ───────────────────────────────────────────────────
function renderPlats() {
    const tbodies = {
        entree:  document.querySelector('#tableEntrees tbody'),
        plat:    document.querySelector('#tablePlats tbody'),
        dessert: document.querySelector('#tableDesserts tbody'),
    };

    Object.entries(tbodies).forEach(([cat, tbody]) => {
        if (!tbody) return;
        const filtered = allPlats.filter(p => p.category === cat);

        if (!filtered.length) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">Aucun plat</td></tr>';
            return;
        }

        tbody.innerHTML = filtered.map(p => `
            <tr data-id="${p.id}">
                <td>${escHtml(p.titre)}</td>
                <td>${p.photo ? `<img src="${escHtml(imgSrc(p.photo))}" height="40" alt="${escHtml(p.titre)}">` : '—'}</td>
                <td>
                    <button type="button" class="btn btn-sm btn-info btn-allergenes"
                        data-allergenes="${escHtml(p.allergenesLibelle)}">
                        Voir
                    </button>
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-primary btn-edit-plat" data-id="${p.id}">
                        Modifier
                    </button>
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-danger btn-delete-plat" data-id="${p.id}"
                        data-bs-toggle="modal" data-bs-target="#confirmationDeleteModal">
                        Supprimer
                    </button>
                </td>
            </tr>
        `).join('');
    });

    document.querySelectorAll('#allPlatModal .btn-allergenes').forEach(btn => {
        btn.setAttribute('title', btn.dataset.allergenes || 'Aucun allergène');
        bootstrap.Tooltip.getOrCreateInstance(btn);
    });
}