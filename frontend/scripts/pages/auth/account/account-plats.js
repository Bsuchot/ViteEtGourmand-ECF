import { api } from '../../../modules/api.js';
import TomSelect from 'tom-select';
import 'tom-select/dist/css/tom-select.bootstrap5.css';

let allPlats            = [];
let allergenes          = [];
let pendingDeletePlatId = null;
let tomSelectInstance   = null;

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

export function initPlats() {
    const modalEl = document.getElementById('allPlatModal');
    if (!modalEl) return;

    // Ouvrir le modal → charger les plats
    modalEl.addEventListener('show.bs.modal', async () => {
        await loadPlats();
        renderPlats();
    });

    // Confirmation suppression plat
    document.getElementById('btnConfirmDelete')?.addEventListener('click', async () => {
        if (!pendingDeletePlatId) return;
        const data = await api.delete(`/plat/${pendingDeletePlatId}`);
        if (data.success) {
            await loadPlats();
            renderPlats();
            bootstrap.Modal.getInstance(document.getElementById('confirmationDeleteModal'))?.hide();
        } else {
            alert('Erreur : ' + (data.error ?? 'Une erreur est survenue.'));
        }
        pendingDeletePlatId = null;
    });

    // Ouvrir modal création plat
    document.getElementById('newPlatModal')?.addEventListener('show.bs.modal', () => {
        initTomSelect();
    });
    // fonction de conversion image en base64
    function fileToBase64(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload  = () => resolve(reader.result);
            reader.onerror = () => reject(new Error('Erreur lecture fichier'));
            reader.readAsDataURL(file);
        });
    }

    // Valider création plat
    document.getElementById('btnValiderNewPlat')?.addEventListener('click', async () => {
        const titre     = document.getElementById('newPlatTitleInput').value.trim();
        const category  = document.getElementById('newPlatCategorySelect').value;
        const fileInput = document.getElementById('newPlatPhotoFormFile');
        const allergeneIds = tomSelectInstance
            ? tomSelectInstance.getValue().map(v => parseInt(v))
            : [];

        if (!titre) {
            document.getElementById('newPlatTitleInput').classList.add('is-invalid');
            return;
        }

        if (!fileInput.files[0]) {
            alert('La photo est obligatoire.');
            return;
        }

        // Convertir en base64
        const photo = await fileToBase64(fileInput.files[0]);

        // Upload de l'image
        const uploadData = await api.post('/upload', { photo });
        if (!uploadData.success) {
            alert('Erreur lors de l\'upload de l\'image.');
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
            bootstrap.Modal.getInstance(document.getElementById('newPlatModal'))?.hide();
            document.getElementById('newPlatTitleInput').value    = '';
            document.getElementById('newPlatPhotoFormFile').value = '';
            tomSelectInstance?.clear();
            await loadPlats();
            renderPlats();
        } else {
            console.log('erreur:', data);
            alert('Erreur : ' + JSON.stringify(data.error));
        }
    });
}

function initTomSelect() {
    if (tomSelectInstance) {
        tomSelectInstance.destroy();
        tomSelectInstance = null;
    }

    const sel = document.getElementById('allergenes');
    if (!sel) return;

    sel.innerHTML = '';
    allergenes.forEach(a => {
        sel.innerHTML += `<option value="${a.id}">${a.libelle}</option>`;
    });

    tomSelectInstance = new TomSelect('#allergenes', {
        create: async (input, callback) => {
            const data = await api.post('/allergene/create', { libelle: input });
            if (data.success) {
                const updated = await api.get('/allergene/readAll');
                if (updated.success) allergenes = updated.data;
                callback({ value: data.data.id, text: input });
            } else {
                alert('Erreur lors de la création de l\'allergène.');
                callback(null);
            }
        },
        persist: false,
        createOnBlur: true,
        maxItems: null,
        placeholder: 'Sélectionner ou créer des allergènes'
    });
}

function renderPlats() {
    const tbodies = {
        entree:  document.querySelector('#tableEntrees tbody'),
        plat:    document.querySelector('#tablePlats tbody'),
        dessert: document.querySelector('#tableDesserts tbody'),
    };

    ['entree', 'plat', 'dessert'].forEach(cat => {
        if (!tbodies[cat]) return;
        tbodies[cat].innerHTML = '';
        const filtered = allPlats.filter(p => p.category === cat);

        if (filtered.length === 0) {
            tbodies[cat].innerHTML = `<tr><td colspan="5" class="text-center text-muted">Aucun plat</td></tr>`;
            return;
        }

        filtered.forEach(p => {
            const allergenesList = p.allergenes?.map(a => a.libelle ?? a).join(', ') ?? '—';

            tbodies[cat].innerHTML += `
                <tr data-id="${p.id}">
                    <td>${p.titre}</td>
                    <td>${p.photo ? `<img src="${p.photo}" height="40" alt="${p.titre}">` : '—'}</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-info btn-allergenes" 
                            data-allergenes="${p.allergenesLibelle ?? ''}">
                            Voir
                        </button>
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-danger btn-delete-plat" data-id="${p.id}"
                            data-bs-toggle="modal" data-bs-target="#confirmationDeleteModal">
                            Supprimer
                        </button>
                    </td>
                </tr>
            `;
        });
    });

    document.querySelectorAll('.btn-allergenes').forEach(btn => {
        btn.setAttribute('data-bs-toggle', 'tooltip');
        btn.setAttribute('data-bs-placement', 'top');
        btn.setAttribute('title', btn.dataset.allergenes || 'Aucun allergène');
        new bootstrap.Tooltip(btn);
    });

    document.querySelectorAll('.btn-delete-plat').forEach(btn => {
        btn.addEventListener('click', () => {
            pendingDeletePlatId = parseInt(btn.dataset.id);
        });
    });
    document.getElementById('btnOpenNewPlat')?.addEventListener('click', () => {
        bootstrap.Modal.getInstance(document.getElementById('allPlatModal'))?.hide();
        bootstrap.Modal.getOrCreateInstance(document.getElementById('newPlatModal')).show();
    });
}