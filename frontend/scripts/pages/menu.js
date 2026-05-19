import { api } from '../modules/api.js';

// ─── État global ────────────────────────────────────────────────────────────
let allMenus = [];
let selectedMenu = null;
const menuCache = new Map(); // cache plats par menuId

// ─── Chargement initial ──────────────────────────────────────────────────────
const data = await api.get('/menu/readAll');

if (data.success) {
    allMenus = data.data ?? data;

    renderMenus(allMenus);
    populateMenuSelect(allMenus);
    populateDeliveryHours();
    initFilters();
    initOrderModal();
    initCommandModal();
}

// ─── RENDER MENUS (SANS PLATS) ──────────────────────────────────────────────
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

        item.style.setProperty('--menu-image', `url(${menu.image ?? ''})`);

        item.innerHTML = `
            <h2 class="accordion-header">
                <button class="accordion-button collapsed"
                        type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#${collapseId}">
                    <div class="container-fluid">
                        <div class="row">

                            <div class="col-12 col-md-4">
                                <h5>${escHtml(menu.titre)}</h5>
                                <p class="text-info">Thème : ${escHtml(menu.themeLibelle ?? '—')}</p>
                                <p>Régime : ${escHtml(menu.regimeLibelle ?? '—')}</p>
                                <p>Min : ${menu.nombrePersonneMinimum}</p>
                            </div>

                            <div class="col-12 col-md-5">
                                <p>${escHtml(menu.description)}</p>
                            </div>

                            <div class="col-12 col-md-3 text-end">
                                <strong class="text-primary fs-3">
                                    ${formatPrice(menu.prixParPersonne)} / pers.
                                </strong>

                                ${isSoldOut
                                    ? '<br><strong class="text-danger">Victime de son succès</strong>'
                                    : `<p>Encore ${menu.quantiteRestante}</p>`
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

    // ─── Lazy loading plats ─────────────────────────────
    container.querySelectorAll('.accordion-collapse').forEach(collapse => {

        collapse.addEventListener('show.bs.collapse', async () => {

            const menuId = collapse.closest('.accordion-item').dataset.menuId;

            // cache
            if (menuCache.has(menuId)) {
                renderPlats(collapse, menuCache.get(menuId));
                return;
            }

            const res = await api.get(`/menu/${menuId}`);
            if (!res.success) return;

            const menu = res.data;

            menuCache.set(menuId, menu.plats ?? []);
            renderPlats(collapse, menu.plats ?? []);
        });
    });

    // bouton commander
    container.querySelectorAll('.accordion-item').forEach(item => {
        item.querySelector('.accordion-button')
            ?.addEventListener('click', () => {
                selectedMenu = allMenus.find(m => m.id == item.dataset.menuId);
            });
    });
}

function populateMenuSelect(menus) {
    const select = document.querySelector('#orderChoiceMenu');
    if (!select) return;

    select.innerHTML = '<option value="">Choisir un menu</option>';

    menus
        .filter(m => m.quantiteRestante > 0)
        .forEach(m => {
            const option = document.createElement('option');
            option.value = m.id;
            option.textContent = m.titre;
            select.appendChild(option);
        });
}

// ─── RENDER PLATS ───────────────────────────────────────────────────────────
function renderPlats(collapse, plats) {
    const container = collapse.querySelector('.plats-container');

    const categories = groupPlatsByCategorie(plats);

    container.innerHTML = `
        ${renderPlatSection('Entrées', categories.entree ?? [])}
        ${renderPlatSection('Plats', categories.plat ?? [])}
        ${renderPlatSection('Desserts', categories.dessert ?? [])}

        <div class="text-center mt-5">
            <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#coordoneesModal">Commander</button>
            <br>
            <strong class="text-secondary">À commander ${selectedMenu?.delai ?? '—'} jours avant</strong>
        </div>
    `;
}

// ─── GROUP PLATS ────────────────────────────────────────────────────────────
function groupPlatsByCategorie(plats) {
    const groups = {};

    plats.forEach(plat => {
        const cat = (plat.category ?? 'autre').toLowerCase();

        if (!groups[cat]) groups[cat] = [];
        groups[cat].push(plat);
    });

    return groups;
}

// ─── SECTION PLATS ──────────────────────────────────────────────────────────
function renderPlatSection(label, plats) {
    if (!plats.length) return '';

    const listeHtml = plats.map((p, i) => `
        <li>${escHtml(p.titre ?? `Plat ${i + 1}`)}</li>
        <li class="text-info">${escHtml(p.description ?? '')}</li>
        ${i < plats.length - 1 ? '<hr class="my-4"/>' : ''}
    `).join('');

    const imagesHtml = plats
        .filter(p => p.photo)
        .slice(0, 2)
        .map(p => `
            <div class="image-card">
                <img src="${escHtml(p.photo)}" alt="${escHtml(p.titre)}" />
                <p class="titre-image">${escHtml(p.titre)}</p>
            </div>
        `).join('');

    return `
        <div class="row my-5 align-items-center">

            <div class="col d-flex justify-content-end">
                ${imagesHtml.split('</div>')[0] ? imagesHtml.split('</div>')[0] + '</div>' : ''}
            </div>

            <div class="col justify-content-center align-self-center mb-5">
                <h6 class="text-uppercase text-primary my-3">${label}</h6>
                <ul class="list-unstyled">${listeHtml}</ul>
            </div>

            <div class="col d-flex justify-content-start">
                ${imagesHtml.split('</div>')[1] ? imagesHtml.split('</div>')[1] + '</div>' : ''}
            </div>
        </div>
    `;
}

// ─── FILTERS (inchangé mais safe) ───────────────────────────────────────────
function initFilters() {
    const btnReset = document.querySelector('#accordionFilter .btn-primary');

    btnReset?.addEventListener('click', () => {
        renderMenus(allMenus);
    });
}

// ─── ORDER MODAL ────────────────────────────────────────────────────────────
function initOrderModal() {
    populateDeliveryHours();

    const dateInput = document.querySelector('#deliveryDateOrderInput');
    if (dateInput) {
        dateInput.min = new Date().toISOString().split('T')[0];
    }
}

// ─── HOURS ──────────────────────────────────────────────────────────────────
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

// ─── COMMAND MODAL ──────────────────────────────────────────────────────────
function initCommandModal() {
    const modal = document.querySelector('#commandModal');
    const selectMenu = document.querySelector('#orderChoiceMenu');

    modal?.addEventListener('show.bs.modal', () => {
        if (selectedMenu) selectMenu.value = selectedMenu.id;
    });
}

// ─── UTILS ───────────────────────────────────────────────────────────────────
function escHtml(str) {
    return str ? str.replace(/[&<>"']/g, m =>
        ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;' }[m])
    ) : '';
}

function formatPrice(val) {
    return Number(val).toLocaleString('fr-FR', {
        style: 'currency',
        currency: 'EUR'
    });
}