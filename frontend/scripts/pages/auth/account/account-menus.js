import { api, API_URL } from '../../../modules/api.js';
import { loadPlats, getPlats } from './account-plats.js';
import { showAlert } from '../../../modules/alerts.js';


let menus          = [];
let themes         = [];
let regimes        = [];
let pendingDeleteId    = null;
let currentEditId      = null;
let tomSelectThemeNew  = null;
let tomSelectThemeEdit = null;

function fileToBase64(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload  = () => resolve(reader.result);
        reader.onerror = () => reject(new Error('Erreur lecture fichier'));
        reader.readAsDataURL(file);
    });
}

function getPlatOptions(cat) {
    const plats = getPlats();
    return plats
        .filter(p => p.category === cat)
        .map(p => `<option value="${p.id}">${p.titre}</option>`)
        .join('');
}

export function initMenus() {
    const collapse      = document.getElementById('collapseMenuManagement');
    if (!collapse) return;

    const tbody         = document.querySelector('#gestionMenuTable tbody');
    const btnSave       = document.getElementById('btnSaveMenuChanges');
    const btnValiderNew = document.getElementById('buttonValidateMenu');

    document.getElementById('btnConfirmDelete')?.addEventListener('click', async () => {
        if (!pendingDeleteId) return;
        const data = await api.delete(`/menu/${pendingDeleteId}`);
        if (data.success) {
            await loadAll();
            bootstrap.Modal.getInstance(document.getElementById('confirmationDeleteModal'))?.hide();
        } else {
            showAlert('Erreur : ' + (data.error ?? 'Une erreur est survenue.'), 'danger');
        }
        pendingDeleteId = null;
    });

    collapse.addEventListener('show.bs.collapse', () => loadAll());

    document.getElementById('newMenuModal')?.addEventListener('show.bs.modal', async () => {
        await loadAll();
        initTomSelectTheme();
        populateRegimeSelect();
        initPlatSelects();
    });

    async function loadAll() {
        const [dataMenus, dataThemes, dataRegimes] = await Promise.all([
            api.get('/menu/readAll'),
            api.get('/theme/readAll'),
            api.get('/regime/readAll'),
        ]);
        await loadPlats();

        if (dataMenus.success)   menus   = dataMenus.data;
        if (dataThemes.success)  themes  = dataThemes.data;
        if (dataRegimes.success) regimes = dataRegimes.data;

        populateRegimeSelect();
        renderMenus(menus);
    }

    function initTomSelectTheme() {
        if (tomSelectThemeNew) { tomSelectThemeNew.destroy(); tomSelectThemeNew = null; }

        const sel = document.getElementById('themes');
        if (!sel) return;

        sel.innerHTML = '';
        themes.forEach(t => sel.innerHTML += `<option value="${t.id}">${t.libelle}</option>`);

        tomSelectThemeNew = new TomSelect('#themes', {
            create: async (input, callback) => {
                const data = await api.post('/theme/create', { libelle: input });
                if (data.success) {
                    const updated = await api.get('/theme/readAll');
                    if (updated.success) themes = updated.data;
                    callback({ value: data.data?.id, text: input });
                } else {
                    showAlert('Erreur lors de la création du thème.', 'danger');
                    callback(null);
                }
            },
            persist: false,
            createOnBlur: true,
            maxItems: 1,
            placeholder: 'Sélectionner ou créer un thème'
        });
    }

    function populateRegimeSelect() {
        const sel = document.getElementById('dietNewMenuSelect');
        if (!sel) return;
        sel.innerHTML = '<option value="">— Régime —</option>';
        regimes.forEach(r => sel.innerHTML += `<option value="${r.id}">${r.libelle}</option>`);
    }


    function initPlatSelects() {
        const config = [
            { containerId: 'entreeNewMenuSelect', cat: 'entree' },
            { containerId: 'platNewMenuSelect',   cat: 'plat'   },
            { containerId: 'dessertNewMenuSelect',cat: 'dessert' },
        ];

        config.forEach(({ containerId, cat }) => {
            const container = document.getElementById(containerId);
            if (!container) return;
            container.innerHTML = `
                <select class="form-select form-select-sm" data-cat="${cat}">
                    <option value="">— Choisir —</option>
                    ${getPlatOptions(cat)}
                </select>`;
        });
    }

    function initEditPlatSelects(menu) {
        const config = [
            { containerId: 'entreeEditSelects',  cat: 'entree'  },
            { containerId: 'platEditSelects',    cat: 'plat'    },
            { containerId: 'dessertEditSelects', cat: 'dessert' },
        ];

        config.forEach(({ containerId, cat }) => {
            const container = document.getElementById(containerId);
            if (!container) return;

            const existing = menu.plats?.find(p => p.category === cat);
            container.innerHTML = `
                <select class="form-select form-select-sm" data-cat="${cat}">
                    <option value="">— Choisir —</option>
                    ${getPlatOptions(cat)}
                </select>`;

            if (existing) container.querySelector('select').value = String(existing.id);
        });
    }


    function renderMenus(list) {
        tbody.innerHTML = '';
        list.forEach(m => {
            tbody.innerHTML += `
                <tr data-id="${m.id}">
                    <th scope="row">${m.id}</th>
                    <td><input type="text" class="form-control form-control-sm" value="${m.titre ?? ''}" data-field="titre"></td>
                    <td>${m.description ?? '—'}</td>
                    <td>${m.themeLibelle ?? '—'}</td>
                    <td>${m.nombrePersonneMinimum ?? '—'}</td>
                    <td>${m.prixParPersonne ?? '—'} €</td>
                    <td>${m.regimeLibelle ?? '—'}</td>
                    <td><input type="number" class="form-control form-control-sm" value="${m.quantiteRestante ?? 0}" data-field="quantiteRestante" min="0" style="width:80px;"></td>
                    <td>${m.image ? `<img src="${m.image?.startsWith('/') ? API_URL + m.image : m.image}" height="40" alt="${m.titre}">` : '—'}</td>
                    <td>${m.delai ?? '—'} jours</td>
                    <td>
                        <select class="form-select form-select-sm" data-field="statut">
                            <option value="actif"   ${m.statut === 'actif'   ? 'selected' : ''}>Actif</option>
                            <option value="inactif" ${m.statut === 'inactif' ? 'selected' : ''}>Inactif</option>
                        </select>
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-primary btn-detail-menu" data-id="${m.id}"
                            data-bs-toggle="modal" data-bs-target="#detailMenuModal">
                            Détail
                        </button>
                    </td>
                </tr>
            `;
        });

        document.querySelectorAll('.btn-detail-menu').forEach(btn => {
            btn.addEventListener('click', () => openEditModal(Number.parseInt(btn.dataset.id)));
        });
    }

    async function openEditModal(id) {
        currentEditId = id;

        await loadPlats();

        const response = await api.get(`/menu/${id}`);
        if (!response.success) return;
        const menu = response.data;

        document.getElementById('editMenuTitleInput').value          = menu.titre                ?? '';
        document.getElementById('editMenuDescriptionTextarea').value = menu.description          ?? '';
        document.getElementById('editMenuPriceInput').value          = menu.prixParPersonne       ?? '';
        document.getElementById('editMenuMinPersInput').value        = menu.nombrePersonneMinimum ?? '';
        document.getElementById('editMenuDelaiInput').value          = menu.delai                ?? '';

        // TomSelect thème
        if (tomSelectThemeEdit) { tomSelectThemeEdit.destroy(); tomSelectThemeEdit = null; }

        const selTheme = document.getElementById('themeEditMenuSelect');
        selTheme.innerHTML = '';
        themes.forEach(t => selTheme.innerHTML += `<option value="${t.id}">${t.libelle}</option>`);

        tomSelectThemeEdit = new TomSelect('#themeEditMenuSelect', {
            create: false,
            maxItems: 1,
            placeholder: 'Sélectionner un thème'
        });
        if (menu.themeId) tomSelectThemeEdit.setValue(String(menu.themeId));

        // Régime
        const selRegime = document.getElementById('dietEditMenuSelect');
        selRegime.innerHTML = '<option value="">— Régime —</option>';
        regimes.forEach(r => selRegime.innerHTML += `<option value="${r.id}">${r.libelle}</option>`);
        selRegime.value = menu.regimeId ?? '';

        // Plats
        initEditPlatSelects(menu);

        // Image
        const imgContainer = document.getElementById('editMenuCurrentImage');
        if (imgContainer) {
            const isValidUrl = menu.image?.startsWith('/');
            imgContainer.innerHTML = isValidUrl
                ? `<img src="${menu.image?.startsWith('/') ? API_URL + menu.image : menu.image}" height="60" class="mb-2">`
                : '<p class="text-muted small text-danger">Image invalide — veuillez en choisir une nouvelle.</p>';
        }
    }

    // Valider création menu
    btnValiderNew?.addEventListener('click', async () => {
        const themeIds = tomSelectThemeNew ? tomSelectThemeNew.getValue() : [];
        const themeId  = themeIds.length > 0 ? Number.parseInt(themeIds[0]) : null;
        const platIds  = [...document.querySelectorAll('#entreeNewMenuSelect [data-cat], #platNewMenuSelect [data-cat], #dessertNewMenuSelect [data-cat]')]
            .map(sel => Number.parseInt(sel.value))
            .filter(Boolean);

        // Upload image
        let imageUrl = null;
        const fileInputNew = document.getElementById('newMenuBackgroundformFile');
        if (fileInputNew.files[0]) {
            const photo = await fileToBase64(fileInputNew.files[0]);
            const uploadData = await api.post('/upload', { photo });
            if (uploadData.success) imageUrl = uploadData.data.url;
        }

        const data = await api.post('/menu/create', {
            titre:                 document.getElementById('newMenuTitleInput').value.trim(),
            description:           document.getElementById('newMenuDescriptionTextarea').value.trim(),
            prixParPersonne:       Number.parseFloat(document.getElementById('newMenuPriceInput').value),
            nombrePersonneMinimum: Number.parseInt(document.getElementById('newMenuMinPersInput').value),
            delai:                 Number.parseInt(document.getElementById('newMenuDelaiInput').value),
            themeId,
            regimeId:              Number.parseInt(document.getElementById('dietNewMenuSelect').value),
            quantiteRestante:      0,
            image:                 imageUrl,
            plats:                 platIds,
        });

        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('newMenuModal'))?.hide();
            await loadAll();
        } else {
            showAlert('Erreur : ' + JSON.stringify(data.error), 'danger');
        }
    });

    // Valider modifications tableau
    btnSave?.addEventListener('click', async () => {
        const rows = tbody.querySelectorAll('tr[data-id]');
        const promises = [];

        rows.forEach(row => {
            const id     = Number.parseInt(row.dataset.id);
            const titre  = row.querySelector('[data-field="titre"]').value.trim();
            const statut = row.querySelector('[data-field="statut"]').value;
            const menu   = menus.find(m => m.id === id);
            
            const payload = {};
            const quantite = Number.parseInt(row.querySelector('[data-field="quantiteRestante"]').value, 10);

            if (quantite !== menu?.quantiteRestante) payload.quantiteRestante = quantite;
            if (titre  !== menu?.titre)  payload.titre  = titre;
            if (statut !== menu?.statut) payload.statut = statut;

            if (Object.keys(payload).length > 0) promises.push(api.put(`/menu/${id}`, payload));
        });

        if (promises.length === 0) { showAlert('Aucune modification détectée.', 'info'); return; }
        await Promise.all(promises);
        showAlert('Menus mis à jour.', 'success');
        await loadAll();

        
    });

    // Valider modification modal détail
    document.getElementById('btnValiderEditMenu')?.addEventListener('click', async () => {
        if (!currentEditId) return;

        const themeIds = tomSelectThemeEdit ? tomSelectThemeEdit.getValue() : [];
        const themeId  = themeIds.length > 0 ? Number.parseInt(themeIds[0]) : null;
        const platIds  = [...document.querySelectorAll('#entreeEditSelects [data-cat], #platEditSelects [data-cat], #dessertEditSelects [data-cat]')]
            .map(sel => Number.parseInt(sel.value))
            .filter(Boolean);

        // Upload image si nouveau fichier
        let imageUrl = menus.find(m => m.id === currentEditId)?.image ?? null;
        const fileInputEdit = document.getElementById('editMenuBackgroundformFile');
        if (fileInputEdit.files[0]) {
            const photo = await fileToBase64(fileInputEdit.files[0]);
            const uploadData = await api.post('/upload', { photo });
            if (uploadData.success) imageUrl = uploadData.data.url;
        }

        const data = await api.put(`/menu/${currentEditId}`, {
            titre:                 document.getElementById('editMenuTitleInput').value.trim(),
            description:           document.getElementById('editMenuDescriptionTextarea').value.trim(),
            prixParPersonne:       Number.parseFloat(document.getElementById('editMenuPriceInput').value),
            nombrePersonneMinimum: Number.parseInt(document.getElementById('editMenuMinPersInput').value),
            delai:                 Number.parseInt(document.getElementById('editMenuDelaiInput').value) || 48,
            themeId,
            regimeId:              Number.parseInt(document.getElementById('dietEditMenuSelect').value),
            image:                 imageUrl,
            plats:                 platIds,
        });

        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('detailMenuModal'))?.hide();
            await loadAll();
        } else {
            showAlert('Erreur : ' + JSON.stringify(data.error), 'danger');
        }
    });
}