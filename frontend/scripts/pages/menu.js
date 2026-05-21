import { showAlert } from '../modules/alerts.js';
import { isConnected } from '../main.js';
import { api, API_URL } from '../modules/api.js';

// ─── État global ─────────────────────────────────────────────────────────────
let allMenus = [];
let selectedMenu = null;
const menuCache = new Map();


function displayPlat(container, plat, label) {
    if (!container) return;
    if (!plat) {
        container.innerHTML = `<p class="text-muted">Aucun(e) ${label}</p>`;
        return;
    }
    const allergenes = plat.allergenes?.length
        ? plat.allergenes.map(a => a.libelle ?? a).join(', ')
        : 'Aucun';

    container.innerHTML = `
        <div>
            <strong>${plat.titre}</strong>
            <p class="mb-1 text-muted small">${plat.description ?? ''}</p>
            <p class="mb-0 small"><span class="fw-semibold">Allergènes :</span> ${allergenes}</p>
        </div>`;
}
// ─── Point d'entrée (appelé par le Router) ───────────────────────────────────
export async function init() {
    const [data, dataThemes, dataRegimes] = await Promise.all([
        api.get('/menu/readAll'),
        api.get('/theme/readAll'),
        api.get('/regime/readAll'),
    ]);

    if (data.success) {
        allMenus = data.data ?? data;

        if (dataThemes.success)  renderThemeCheckboxes(dataThemes.data ?? []);
        if (dataRegimes.success) renderRegimeCheckboxes(dataRegimes.data ?? []);

        renderMenus(allMenus);
        populateMenuSelect(allMenus);
        populateDeliveryHours();
        initFilters();
        initOrderModal();
        initCommandModal();
        initConfirmationModal();
    }
}

// ─── THÈMES DYNAMIQUES ───────────────────────────────────────────────────────
function renderThemeCheckboxes(themes) {
    const container = document.querySelector('#themeCollapseOne .accordion-body');
    if (!container) return;
    container.innerHTML = themes.map(t => `
        <div class="form-check check-theme">
            <input class="form-check-input" type="checkbox" value="${t.id}" id="checkTheme${t.id}">
            <label class="form-check-label" for="checkTheme${t.id}">${escHtml(t.libelle)}</label>
        </div>
    `).join('');
}

// ─── RÉGIMES DYNAMIQUES ──────────────────────────────────────────────────────
function renderRegimeCheckboxes(regimes) {
    const container = document.querySelector('#dietCollapseOne .accordion-body');
    if (!container) return;
    container.innerHTML = regimes.map(r => `
        <div class="form-check check-Diet">
            <input class="form-check-input" type="checkbox" value="${r.id}" id="checkRegime${r.id}">
            <label class="form-check-label" for="checkRegime${r.id}">${escHtml(r.libelle)}</label>
        </div>
    `).join('');
}

// ─── RENDER MENUS ────────────────────────────────────────────────────────────
function renderMenus(menus) {
    const container = document.querySelector('#accordionMenu');
    container.innerHTML = '';

    if (!menus.length) {
        container.innerHTML = '<p class="text-center text-muted my-4">Aucun menu disponible.</p>';
        return;
    }

    menus.forEach((menu, index) => {
        const isSoldOut = menu.quantiteRestante <= 0;
        const collapseId = `collapseMenu${index}`;

        const item = document.createElement('div');
        item.className = `accordion-item border-secondary${isSoldOut ? ' soldout' : ''}`;
        item.dataset.menuId = menu.id;
        const imageUrl = menu.image?.startsWith('/') ? API_URL + menu.image : (menu.image ?? '');
        item.style.setProperty('--menu-image', `url(${imageUrl})`);

        item.innerHTML = `
            <h2 class="accordion-header">
                <button class="accordion-button collapsed"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#${collapseId}">
                    <div class="container-fluid">
                        <div class="row g-2 align-items-start">
                            <div class="col-12 col-md-4">
                                <h5 class="mb-1">${escHtml(menu.titre)}</h5>
                                <p class="text-info mb-1">Thème : ${escHtml(menu.themeLibelle ?? '—')}</p>
                                <p class="text-info mb-1">Régime : ${escHtml(menu.regimeLibelle ?? '—')}</p>
                                <p class="text-info mb-1">Pers. min : ${menu.nombrePersonneMinimum}</p>
                            </div>
                            <div class="col-12 col-md-4">
                                <p class="mb-1">${escHtml(menu.description)}</p>
                            </div>
                            <div class="col-12 col-md-4 text-end">
                                <strong class="text-primary fs-3 d-block">
                                    ${formatPrice(menu.prixParPersonne)} / pers.
                                </strong>
                                ${isSoldOut
                                    ? '<br><strong class="text-danger">Victime de son succès</strong>'
                                    : `<p class="mb-0">Encore ${menu.quantiteRestante} disponibilités</p>`
                                }
                            </div>
                        </div>
                    </div>
                </button>
            </h2>
            <div id="${collapseId}" class="accordion-collapse collapse" data-bs-parent="#accordionMenu">
                <div class="accordion-body text-center">
                    <div class="container plats-container">
                        <p class="text-muted">Chargement des plats...</p>
                    </div>
                </div>
            </div>
        `;

        container.appendChild(item);
    });

    // Lazy loading plats
    container.querySelectorAll('.accordion-collapse').forEach(collapse => {
        collapse.addEventListener('show.bs.collapse', async () => {
            const menuId = collapse.closest('.accordion-item').dataset.menuId;

            if (menuCache.has(menuId)) {
                renderPlats(collapse, menuCache.get(menuId));
                return;
            }

            const res = await api.get(`/menu/${menuId}`);
            if (!res.success) return;

            menuCache.set(menuId, res.data.plats ?? []);
            renderPlats(collapse, res.data.plats ?? []);
        });
    });

    // Mémoriser le menu sélectionné au clic
    container.querySelectorAll('.accordion-item').forEach(item => {
        item.querySelector('.accordion-button')?.addEventListener('click', () => {
            selectedMenu = allMenus.find(m => m.id == item.dataset.menuId);
        });
    });
}

// ─── RENDER PLATS ─────────────────────────────────────────────────────────────
function renderPlats(collapse, plats) {
    const container = collapse.querySelector('.plats-container');
    const categories = groupPlatsByCategorie(plats);

    container.innerHTML = `
        ${renderPlatSection('Entrées',  categories.entree  ?? [])}
        ${renderPlatSection('Plats',    categories.plat    ?? [])}
        ${renderPlatSection('Desserts', categories.dessert ?? [])}
        <div class="text-center mt-5">
            <button type="button" class="btn btn-primary mb-3" id="btnOpenOrderModal">Commander</button>
            <br>
            <strong class="text-secondary">À commander ${selectedMenu?.delai ?? '—'} jours avant</strong>
        </div>
    `;
}

function groupPlatsByCategorie(plats) {
    const groups = {};
    plats.forEach(plat => {
        const cat = (plat.category ?? 'autre').toLowerCase();
        if (!groups[cat]) groups[cat] = [];
        groups[cat].push(plat);
    });
    return groups;
}

function renderPlatSection(label, plats) {
    if (!plats.length) return '';

    const plat = plats[0];
    const allergenes = plat.allergenes?.length
        ? plat.allergenes.map(a => `<span class="badge bg-warning text-dark me-1">${escHtml(a.libelle ?? a)}</span>`).join('')
        : '<span class="text-muted fst-italic small">Aucun allergène</span>';

    return `
    <div class="row align-items-center mb-4">
        <div class="col-4 d-flex justify-content-center">
            ${plat.photo ? `
            <div class="plat-image shadow">
                <img src="${escHtml(plat.photo?.startsWith('/') ? API_URL + plat.photo : plat.photo)}"
                     alt="${escHtml(plat.titre)}"
                     style="width:100%; max-width:280px; height:200px; object-fit:cover; border-radius:8px;">
            </div>` : ''}
        </div>
        <div class="col-4 text-center">
            <p class="text-uppercase text-primary fw-semibold mb-1 small">${label}</p>
            <h4 class="fw-bold mb-0">${escHtml(plat.titre ?? '')}</h4>
        </div>
        <div class="col-4 d-flex justify-content-end align-items-center gap-2 flex-wrap">
            <span class="small fw-semibold text-secondary">Allergènes :</span>
            ${allergenes}
        </div>
    </div>
    <hr class="my-2 opacity-25">
`;
}

// ─── FILTRES ──────────────────────────────────────────────────────────────────
function initFilters() {
    const filterAndRender = () => renderMenus(applyFilters());

    document.querySelector('#accordionFilter')?.addEventListener('change', filterAndRender);
    document.querySelector('#minPriceFormControlInput1')?.addEventListener('input', filterAndRender);
    document.querySelector('#maxPriceFormControlInput1')?.addEventListener('input', filterAndRender);
    document.querySelector('#minPersonFormControlInput1')?.addEventListener('input', filterAndRender);

    document.querySelector('#accordionFilter .btn-primary')?.addEventListener('click', () => {
        document.querySelectorAll('#accordionFilter input[type="checkbox"]')
            .forEach(cb => cb.checked = false);
        document.querySelector('#minPriceFormControlInput1').value  = '';
        document.querySelector('#maxPriceFormControlInput1').value  = '';
        document.querySelector('#minPersonFormControlInput1').value = '';
        renderMenus(allMenus);
    });
}

function applyFilters() {
    const minPrice  = Number.parseFloat(document.querySelector('#minPriceFormControlInput1')?.value) || null;
    const maxPrice  = Number.parseFloat(document.querySelector('#maxPriceFormControlInput1')?.value) || null;
    // "Personne min" → afficher les menus dont nombrePersonneMinimum <= valeur saisie
    const maxPerson = Number.parseInt(document.querySelector('#minPersonFormControlInput1')?.value)  || null;

    const checkedThemes = [...document.querySelectorAll('.check-theme input:checked')]
        .map(cb => cb.closest('.form-check').querySelector('label').textContent.trim().toLowerCase());

    const checkedDiets = [...document.querySelectorAll('.check-Diet input:checked')]
        .map(cb => cb.closest('.form-check').querySelector('label').textContent.trim().toLowerCase());

    return allMenus.filter(menu => {
        if (minPrice  !== null && menu.prixParPersonne       < minPrice)   return false;
        if (maxPrice  !== null && menu.prixParPersonne       > maxPrice)   return false;
        // On garde uniquement les menus accessibles au nombre de personnes saisi
        if (maxPerson !== null && menu.nombrePersonneMinimum > maxPerson)  return false;

        if (checkedThemes.length > 0) {
            const theme = (menu.themeLibelle ?? '').toLowerCase();
            if (!checkedThemes.some(t => theme.includes(t))) return false;
        }

        if (checkedDiets.length > 0) {
            const regime = (menu.regimeLibelle ?? '').toLowerCase();
            if (!checkedDiets.some(d => regime.includes(d))) return false;
        }

        return true;
    });
}

// ─── ORDER MODAL ──────────────────────────────────────────────────────────────
function initOrderModal() {
    populateDeliveryHours();
    initCityAutocomplete();

    const dateInput = document.querySelector('#deliveryDateOrderInput');
    if (dateInput) dateInput.min = new Date().toISOString().split('T')[0];

    // Bouton Commander — vérification connexion
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('#btnOpenOrderModal');
        if (!btn) return;

        if (!isConnected()) {
            showAlert('Veuillez <a href="/signin">vous connecter</a> ou <a href="/signup">créer un compte</a> pour commander.', 'warning');
            return;
        }

        bootstrap.Modal.getOrCreateInstance(document.querySelector('#coordoneesModal'))?.show();
    });

    // Préremplir au premier affichage du modal
    const modal = document.querySelector('#coordoneesModal');
    let prefilled = false;

    modal?.addEventListener('show.bs.modal', async () => {
        if (prefilled) return;
        if (!isConnected()) return;
        try {
            // 1. Récupérer l'id de l'utilisateur connecté
            const me = await api.get('/utilisateur/me');
            if (!me.success) return;

            const userId = me.data.user?.id ?? me.data.id;
            if (!userId) return;

            // 2. Récupérer le profil complet
            const res = await api.get(`/utilisateur/${userId}`);
            if (!res.success) return;

            const u = res.data;

            // Informations client
            setVal('#nameOrderInput',      u.nom);
            setVal('#firstnameOrderInput', u.prenom);
            setVal('#mailOrderInput',      u.email);
            setVal('#telephoneOrderInput', u.telephone);

            // Adresse de livraison
            setVal('#adressOrderInput',  u.adresse);
            setVal('#countryOrderInput', u.pays ?? 'France');

            // Ville : parser "Bordeaux (33000)" → ville + code postal séparés
            if (u.ville) {
                const matchVille = u.ville.match(/^(.+?)\s*\((\d{5})\)$/);
                if (matchVille) {
                    setVal('#cityOrderInput',   matchVille[1].trim());
                    setVal('#postalOrderInput', matchVille[2]);
                } else {
                    setVal('#cityOrderInput', u.ville);
                }
            }

            prefilled = true;
        } catch {
            // Silencieux : l'utilisateur remplit manuellement
        }
    });
}

function setVal(selector, value) {
    const el = document.querySelector(selector);
    if (el && value) el.value = value;
}

// ─── AUTOCOMPLETE VILLE (modal coordonnées) ───────────────────────────────────
function initCityAutocomplete() {
    const inputCity  = document.querySelector('#cityOrderInput');
    const inputPostal = document.querySelector('#postalOrderInput');
    if (!inputCity) return;

    const villePostalMap = new Map();
    let debounceTimer = null;

    inputCity.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        const q = inputCity.value.trim();
        if (q.length < 2) {
            const dl = document.getElementById('cityOrderSuggestions');
            if (dl) dl.innerHTML = '';
            return;
        }
        debounceTimer = setTimeout(async () => {
            try {
                const res = await fetch(
                    `https://geo.api.gouv.fr/communes?nom=${encodeURIComponent(q)}&fields=nom,codesPostaux&boost=population&limit=6`
                );
                const communes = await res.json();
                villePostalMap.clear();
                const datalist = document.getElementById('cityOrderSuggestions');
                if (!datalist) return;
                datalist.innerHTML = '';
                communes.forEach(c => {
                    const cp    = c.codesPostaux?.[0] ?? '';
                    const label = cp ? `${c.nom} (${cp})` : c.nom;
                    villePostalMap.set(label, { nom: c.nom, cp });
                    const opt = document.createElement('option');
                    opt.value = label;
                    datalist.appendChild(opt);
                });
            } catch { /* silencieux */ }
        }, 300);
    });

    inputCity.addEventListener('change', () => {
        const match = villePostalMap.get(inputCity.value);
        if (match) {
            inputCity.value = match.nom;
            if (inputPostal) inputPostal.value = match.cp;
        }
    });
}

function populateDeliveryHours() {
    const select = document.querySelector('#deliveryTimeOrderSelect');
    if (!select) return;
    select.innerHTML = '<option value="">Choisir une heure</option>';
    for (let h = 9; h <= 19; h++) {
        ['00', '30'].forEach(m => {
            select.innerHTML += `<option>${String(h).padStart(2, '0')}:${m}</option>`;
        });
    }
}

function populateMenuSelect(menus) {
    const select = document.querySelector('#orderChoiceMenu');
    if (!select) return;
    select.innerHTML = '<option value="">Choisir un menu</option>';
    menus.filter(m => m.quantiteRestante > 0).forEach(m => {
        const option = document.createElement('option');
        option.value = m.id;
        option.textContent = m.titre;
        select.appendChild(option);
    });
}

// ─── COMMAND MODAL ────────────────────────────────────────────────────────────
function initCommandModal() {
    const modal          = document.querySelector('#commandModal');
    const selectMenu     = document.querySelector('#orderChoiceMenu');
    const inputNbPersons = document.querySelector('#numberPersonFormControlInput2');
    const containerEntree   = document.querySelector('#commandeMenuEntree');
    const containerPlat     = document.querySelector('#commandeMenuPlat');
    const containerDessert  = document.querySelector('#commandeMenuDessert');


    // Ouverture du modal → pré-sélectionner le menu et charger les plats
    modal?.addEventListener('show.bs.modal', async () => {
        if (selectedMenu) {
            selectMenu.value = selectedMenu.id;
            await loadPlatsIntoSelects(selectedMenu.id);
            // Valeur min = nombrePersonneMinimum du menu
            inputNbPersons.min   = selectedMenu.nombrePersonneMinimum;
            inputNbPersons.value = selectedMenu.nombrePersonneMinimum;
            updatePrice();
        }
    });

    // Changement de menu dans le select
    selectMenu?.addEventListener('change', async () => {
        const id = Number.parseInt(selectMenu.value);
        selectedMenu = allMenus.find(m => m.id === id) ?? null;
        if (!selectedMenu) return;
        await loadPlatsIntoSelects(id);
        inputNbPersons.min   = selectedMenu.nombrePersonneMinimum;
        inputNbPersons.value = selectedMenu.nombrePersonneMinimum;
        updatePrice();
    });

    // Changement du nombre de personnes → recalcul prix + empêcher valeur trop basse
    inputNbPersons?.addEventListener('input', () => {
        if (!selectedMenu) return;
        const min = Number.parseInt(selectedMenu.nombrePersonneMinimum);
        if (Number.parseInt(inputNbPersons.value) < min) inputNbPersons.value = min;
        updatePrice();
    });

    // Charger les plats d'un menu 
    async function loadPlatsIntoSelects(menuId) {
        let plats = menuCache.get(String(menuId));

        if (!plats) {
            const res = await api.get(`/menu/${menuId}`);
            if (!res.success) return;
            plats = res.data.plats ?? [];
            menuCache.set(String(menuId), plats);
        }

        const groups = groupPlatsByCategorie(plats);
        displayPlat(containerEntree,  groups.entree?.[0],  'Entrée');
        displayPlat(containerPlat,    groups.plat?.[0],    'Plat');
        displayPlat(containerDessert, groups.dessert?.[0], 'Dessert');
    }

    


    function updatePrice() {
        if (!selectedMenu) return;

        const prix      = Number.parseFloat(selectedMenu.prixParPersonne);
        const personMin = Number.parseInt(selectedMenu.nombrePersonneMinimum);
        const nb        = Number.parseInt(inputNbPersons?.value) || personMin;
        const seuil     = personMin + 5;

        const prixBase  = prix * nb;
        const reduction = nb >= seuil ? 0.1 : 0;
        const prixFinal = prixBase * (1 - reduction);

        // Prix de base
        const elBase = document.querySelector('.list-unstyled li:first-child strong');
        if (elBase) elBase.textContent = formatPrice(prixBase);

        // Prix après réduction
        const elReduc = document.querySelector('.text-success strong');
        if (elReduc) elReduc.textContent = formatPrice(prixFinal);

        // Masquer la ligne réduction si pas applicable
        const ligneReduc = document.querySelector('.text-success');
        if (ligneReduc) ligneReduc.style.display = nb >= seuil ? '' : 'none';
    }
}

// ─── CONFIRMATION MODAL ──────────────────────────────────────────────────────
function initConfirmationModal() {
    const modal = document.querySelector('#comfirmationcommandModal');
    if (!modal) return;


    modal.addEventListener('show.bs.modal', async () => {
        // ── Récupérer les données du modal commande ──────────────────────────
        const menuId = Number.parseInt(document.querySelector('#orderChoiceMenu')?.value);
        const menu   = allMenus.find(m => m.id === menuId) ?? selectedMenu;
        const nbPersonnes = Number.parseInt(document.querySelector('#numberPersonFormControlInput2')?.value) || 0;
        const plats   = menuCache.get(String(menuId)) ?? [];
        const entree  = plats.find(p => p.category === 'entree');
        const plat    = plats.find(p => p.category === 'plat');
        const dessert = plats.find(p => p.category === 'dessert');
        const pretMat     = document.querySelector('#pretMateriel')?.checked ?? false;

        const entreeTxt  = entree?.titre || '—';
        const platTxt    = plat?.titre || '—';
        const dessertTxt = dessert?.titre || '—';

        // ── Récupérer les données du modal coordonnées ───────────────────────
        const prenom     = document.querySelector('#firstnameOrderInput')?.value?.trim() || '';
        const nom        = document.querySelector('#nameOrderInput')?.value?.trim()      || '';
        const adresse    = document.querySelector('#adressOrderInput')?.value?.trim()    || '';
        const ville      = document.querySelector('#cityOrderInput')?.value?.trim()      || '';
        const postal     = document.querySelector('#postalOrderInput')?.value?.trim()    || '';
        const pays       = document.querySelector('#countryOrderInput')?.value?.trim()   || '';
        const email      = document.querySelector('#mailOrderInput')?.value?.trim()      || '';
        const telephone  = document.querySelector('#telephoneOrderInput')?.value?.trim() || '';
        const date       = document.querySelector('#deliveryDateOrderInput')?.value      || '';
        const heure      = document.querySelector('#deliveryTimeOrderSelect')?.value     || '';

        const adresseLivraison = [adresse, postal ? `${postal} ${ville}` : ville, pays]
            .filter(Boolean).join(', ');

        // ── Calcul du prix ───────────────────────────────────────────────────
        const prixUnit   = Number.parseFloat(menu?.prixParPersonne ?? 0);
        const personMin  = Number.parseInt(menu?.nombrePersonneMinimum ?? 0);
        const seuil      = personMin + 5;
        const reduction  = nbPersonnes >= seuil ? 0.1 : 0;
        const prixMenu   = prixUnit * nbPersonnes * (1 - reduction);

        // Frais de livraison : calcul via géolocalisation
        const adresseComplete = [adresse, postal ? `${postal} ${ville}` : ville, pays]
            .filter(Boolean).join(', ');
        const villeNormalisee = ville.toLowerCase().trim();
        const prixLivraison = villeNormalisee === 'bordeaux'
            ? 0
            : await calculerFraisLivraison(adresseComplete);
        const total = prixMenu + prixLivraison;

        // ── Remplir le HTML du modal ─────────────────────────────────────────
        // Nom du menu
        setConfirm('#confirmMenuNom',     menu?.titre ?? '—');

        // Composition
        setConfirm('#confirmEntree',      entreeTxt);
        setConfirm('#confirmPlat',        platTxt);
        setConfirm('#confirmDessert',     dessertTxt);

        // Coordonnées
        setConfirm('#confirmNomPrenom',   `${prenom} ${nom}`);
        setConfirm('#confirmAdresse',     adresse);
        setConfirm('#confirmVille',       postal ? `${postal} ${ville}` : ville);
        setConfirm('#confirmPays',        pays);
        setConfirm('#confirmEmail',       email);
        setConfirm('#confirmTelephone',   telephone);
        setConfirm('#confirmDate',        date ? formatDate(date) : '—');
        setConfirm('#confirmHeure',       heure || '—');

        // Prix
        setConfirm('#confirmPrixMenu',      formatPrice(prixMenu));
        setConfirm('#confirmReduction',     reduction > 0 ? `-${formatPrice(prixUnit * nbPersonnes * reduction)}` : null);
        setConfirm('#confirmFraisLivraison', prixLivraison === 0 ? 'Inclus' : formatPrice(prixLivraison));
        setConfirm('#confirmTotal',         formatPrice(total));

        // Afficher/masquer la ligne réduction
        const rowReduc = modal.querySelector('#confirmRowReduction');
        if (rowReduc) rowReduc.style.display = reduction > 0 ? '' : 'none';

        // ── Bouton Commander ─────────────────────────────────────────────────
        const btnCommander = modal.querySelector('button[type="submit"]');
        btnCommander?.addEventListener('click', async (e) => {
            e.preventDefault();
            try {
                const res = await api.post('/commande/create', {
                    datePrestation:   date,
                    heureLivraison:   heure,
                    adresseLivraison: adresseLivraison,
                    prixMenu:         Number.parseFloat(prixMenu.toFixed(2)),
                    nombrePersonne:   nbPersonnes,
                    prixLivraison:    Number.parseFloat(prixLivraison.toFixed(2)),
                    menuId:           menuId,
                    pretMateriel:     pretMat,
                    email: document.querySelector('#mailOrderInput')?.value?.trim(),
                });

                if (res.success) {
                    bootstrap.Modal.getInstance(modal)?.hide();
                    showAlert('Commande passée avec succès !');
                } else {
                    showAlert('Erreur : ' + (res.error ?? 'Une erreur est survenue.'));
                }
            } catch (err) {
                showAlert('Erreur lors de la commande.');
                console.error(err);
            }
        }, { once: true }); // once:true pour éviter les doublons à chaque ouverture
    });
}

// ─── CALCUL FRAIS LIVRAISON ──────────────────────────────────────────────────
async function calculerFraisLivraison(adresseDestination) {
    try {
        const res = await api.post('/commande/fraisLivraison', { adresse: adresseDestination });
        return res.success ? (res.data.frais ?? 5) : 5;
    } catch {
        return 5; // fallback si API indisponible
    }
}

function setConfirm(selector, value) {
    const el = document.querySelector(selector);
    if (el && value !== null && value !== undefined) el.textContent = value;
}

function toggleConfirmRow(selector, visible) {
    const el = document.querySelector(selector);
    if (el) el.style.display = visible ? '' : 'none';
}

function formatDate(dateStr) {
    const [y, m, d] = dateStr.split('-');
    return `${d}/${m}/${y}`;
}

// ─── UTILS ───────────────────────────────────────────────────────────────────
function escHtml(str) {
    return str ? str.replace(/[&<>"']/g, m =>
        ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m])
    ) : '';
}

function formatPrice(val) {
    return Number(val).toLocaleString('fr-FR', { style: 'currency', currency: 'EUR' });
}