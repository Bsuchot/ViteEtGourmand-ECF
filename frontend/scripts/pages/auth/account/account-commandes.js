import { api } from '../../../modules/api.js';
import { showAlert } from '../../../modules/alerts.js';

function getDateStr(dateField) {
    if (!dateField) return '';
    if (typeof dateField === 'string') return dateField;
    return dateField.date ?? '';
}

function formatDate(dateStr) {
    if (!dateStr) return '—';
    const [year, month, day] = dateStr.split(/[-T ]/);
    return `${day}/${month}/${year}`;
}

function fillModalHeader(modal, c) {
    const h5Menus = modal.querySelectorAll('h5.text-center.text-primary');
    if (h5Menus[0]) h5Menus[0].textContent = c.menuTitre ?? '—';

    const statutBadge = {
        'en attente':                       'bg-dark',
        'accepté':                          'bg-success',
        'en cours de livraison':            'bg-primary',
        'en attente du retour de matériel': 'bg-warning text-dark',
        'terminee':                         'bg-secondary',
    };
    const badge = modal.querySelector('.badge');
    if (badge) {
        badge.textContent = c.statut;
        badge.className   = 'badge ' + (statutBadge[c.statut] ?? 'bg-secondary');
    }

    const rows      = modal.querySelectorAll('.row.my-2');
    const dateCmdEl = rows[1]?.querySelector('.col-6.align-items-center p');
    if (dateCmdEl) dateCmdEl.textContent = formatDate(getDateStr(c.dateCommande));
}

function fillModalPrix(modal, c, elMenu, elDel, elTtc, liPromo) {
    const prixLivraison = Number.parseFloat(c.prixLivraison ?? 0);
    if (elMenu) elMenu.textContent = Number.parseFloat(c.prixMenu ?? 0).toFixed(2);
    if (elDel)  elDel.textContent  = prixLivraison.toFixed(2);
    if (elTtc)  elTtc.textContent  = (Number.parseFloat(c.prixMenu ?? 0) + prixLivraison).toFixed(2);
    if (liPromo) liPromo.style.setProperty('display', 'none', 'important');
}

function onNbBlur(input, min) {
    if (Number.parseInt(input.value) < min) {
        input.value = min;
        input.dispatchEvent(new Event('input'));
    }
}

function onNbInput(input, min, prixUnit, prixLivraison, elMenu, elTtc, liPromo) {
    const nb        = Number.parseInt(input.value) || 0;
    const reduction = nb >= min + 5 ? 0.1 : 0;
    const newPrix   = prixUnit * nb * (1 - reduction);

    if (elMenu) elMenu.textContent = newPrix.toFixed(2);
    if (elTtc)  elTtc.textContent  = (newPrix + prixLivraison).toFixed(2);
    if (liPromo) {
        liPromo.style.setProperty('display', reduction > 0 ? '' : 'none', 'important');
        const promoEl = liPromo.querySelector('.promoPrice');
        if (promoEl) promoEl.textContent = (prixUnit * nb * reduction).toFixed(2);
    }
}

function setTextById(modal, selector, text) {
    const el = modal.querySelector(selector);
    if (el) el.textContent = text;
}

function fillModalPlats(modal, c, inputNb, elMenu, elTtc, liPromo, prixLivraison) {
    if (!c.menuId) return;

    api.get(`/menu/${c.menuId}`).then(res => {
        if (!res.success) return;

        const min      = Number.parseInt(res.data.nombrePersonneMinimum ?? 1);
        const prixUnit = Number.parseFloat(res.data.prixParPersonne ?? 0);

        if (inputNb) {
            inputNb.min = min;
            const newInputNb = inputNb.cloneNode(true);
            inputNb.parentNode.replaceChild(newInputNb, inputNb);
            newInputNb.addEventListener('blur',  () => onNbBlur(newInputNb, min));
            newInputNb.addEventListener('input', () => onNbInput(newInputNb, min, prixUnit, prixLivraison, elMenu, elTtc, liPromo));
        }

        const plats = res.data.plats ?? [];
        setTextById(modal, '#detailEntree',  plats.find(p => p.category === 'entree')?.titre  ?? '—');
        setTextById(modal, '#detailPlat',    plats.find(p => p.category === 'plat')?.titre    ?? '—');
        setTextById(modal, '#detailDessert', plats.find(p => p.category === 'dessert')?.titre ?? '—');
    });
}

export function initCommandes() {
    const collapse = document.getElementById('collapseCommandUser');
    if (!collapse) return;

    const tbodyOrders = document.querySelector('#userOrdersTable tbody');
    const yearsFilter = document.getElementById('yearsUserCommandFilter');
    const monthFilter = document.getElementById('monthUserCommandFilter');

    let allCommandes = [];

    collapse.addEventListener('show.bs.collapse', () => {
        loadCommandes();
    });

    async function loadCommandes() {
        const data = await api.get('/commande/mesCommandes');
        if (!data.success) return;
        allCommandes = data.data;
        populateFilters(allCommandes);
        renderCommandes(allCommandes);
    }

    function populateFilters(commandes) {
        yearsFilter.innerHTML  = '<option value="">Année de livraison</option>';
        monthFilter.innerHTML  = '<option value="">Mois de livraison</option>';

        const years  = [...new Set(commandes.map(c => getDateStr(c.datePrestation).split('-')[0]))];
        const months = [...new Set(commandes.map(c => Number.parseInt(getDateStr(c.datePrestation).split('-')[1])))];
        const monthNames = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];

        years.forEach(y => {
            const opt = document.createElement('option');
            opt.value = y; opt.textContent = y;
            yearsFilter.appendChild(opt);
        });
        months.forEach(m => {
            const opt = document.createElement('option');
            opt.value = m; opt.textContent = monthNames[m - 1];
            monthFilter.appendChild(opt);
        });

        yearsFilter.addEventListener('change', filterCommandes);
        monthFilter.addEventListener('change', filterCommandes);
    }

    function filterCommandes() {
        const year  = yearsFilter.value;
        const month = monthFilter.value;

        const filtered = allCommandes.filter(c => {
            const [y, m] = getDateStr(c.datePrestation).split('-');
            const matchYear  = year  ? y == year            : true;
            const matchMonth = month ? Number.parseInt(m) == month : true;
            return matchYear && matchMonth;
        });

        renderCommandes(filtered);
    }

    const statutBadge = {
        'en_attente':                       'bg-dark',
        'accepté':                          'bg-success',
        'en cours de livraison':            'bg-primary',
        'en attente du retour de matériel': 'bg-warning text-dark',
        'terminee':                         'bg-secondary',
    };

    function renderCommandes(commandes) {
        tbodyOrders.innerHTML = '';

        if (commandes.length === 0) {
            tbodyOrders.innerHTML = '<tr><td colspan="9" class="text-center">Aucune commande trouvée</td></tr>';
            return;
        }

        commandes.forEach(c => {
            const date      = formatDate(getDateStr(c.dateCommande));
            const livraison = formatDate(getDateStr(c.datePrestation)) + ' à ' + c.heureLivraison;
            const prix      = (Number.parseFloat(c.prixMenu) + Number.parseFloat(c.prixLivraison)).toFixed(2);
            const badge     = statutBadge[c.statut] ?? 'bg-secondary';

            tbodyOrders.innerHTML += `
                <tr>
                    <td>${date}</td>
                    <td>${c.menuTitre ?? '—'}</td>
                    <td>${c.nombrePersonne}</td>
                    <td class="d-none d-md-table-cell">—</td>
                    <td class="d-none d-md-table-cell">${c.adresseLivraison}</td>
                    <td>${livraison}</td>
                    <td class="text-success fw-semibold text-nowrap">${prix} €</td>
                    <td><span class="badge ${badge}">${c.statut}</span></td>
                    <td>
                        <button type="button" class="btn btn-sm btn-primary"
                            data-bs-toggle="modal" data-bs-target="#detailCommandeModal"
                            data-id="${c.id}">Détails</button>
                    </td>
                </tr>
            `;
        });
    }
}

// ─── DETAIL MODAL ─────────────────────────────────────────────────────────────
export function initDetailCommande() {
    const modal         = document.getElementById('detailCommandeModal');
    const annulModal    = document.getElementById('annulationCommandModal');
    if (!modal) return;

    let currentCommande = null;

    // Remplir les heures du select
    const selectHeure = document.getElementById('commandeDetailDeliveryTime');
    if (selectHeure) {
        selectHeure.innerHTML = '<option value="">Choisir une heure</option>';
        for (let h = 9; h <= 19; h++) {
            ['00', '30'].forEach(m => {
                const val = `${String(h).padStart(2, '0')}:${m}`;
                selectHeure.innerHTML += `<option value="${val}">${val}</option>`;
            });
        }
    }

    // Date min = aujourd'hui
    const inputDate = document.getElementById('commandeDetailDeliveryDateFormControlInput3');
    if (inputDate) inputDate.min = new Date().toISOString().split('T')[0];

    // Ouverture du modal → charger la commande
    modal.addEventListener('show.bs.modal', async (e) => {
        const btn = e.relatedTarget;
        const id  = btn?.dataset?.id;
        if (!id) return;

        const res = await api.get(`/commande/${id}`);
        if (!res.success) return;

        currentCommande = res.data;
        fillModal(currentCommande);
    });

    

    function fillModalAdresse(modal, c, inputDate, selectHeure) {
        const rows         = modal.querySelectorAll('.row.my-2');
        const inputNb      = document.getElementById('commandeDetailNumberPersonFormControlInput');
        if (inputNb) inputNb.value = c.nombrePersonne ?? '';

        if (inputDate) {
            const d = getDateStr(c.datePrestation);
            inputDate.value = d ? d.substring(0, 10) : '';
        }
        if (selectHeure) selectHeure.value = c.heureLivraison ?? '';

        const parts = (c.adresseLivraison ?? '').split(',').map(s => s.trim());
        setVal('#commandeDetailAdressFormControlInput',  parts[0] ?? '');
        setVal('#commandeDetailCityFormControlInput',    parts[1] ?? '');
        setVal('#commandeDetailCountryFormControlInput', parts[2] ?? '');

        const livraisonRow = [...rows].find(r => r.textContent.includes('A livrer à'));
        const nomEl = livraisonRow?.querySelector('.col-6.align-items-center p');
        if (nomEl) {
            nomEl.innerHTML = `<span class="text-uppercase">${escHtml(c.utilisateurNom ?? '')}</span> ${escHtml(c.utilisateurPrenom ?? '')}`;
        }

        setVal('#commandeDetailEmailFormControlInput',     c.utilisateurEmail     ?? '');
        setVal('#commandeDetailTelephoneFormControlInput', c.utilisateurTelephone ?? '');
    }

    function fillModalEditable(modal, c, inputDate, selectHeure) {
        const editable = c.statut === 'en attente';
        [
            document.getElementById('commandeDetailNumberPersonFormControlInput'),
            inputDate, selectHeure,
            document.getElementById('commandeDetailAdressFormControlInput'),
            document.getElementById('commandeDetailCityFormControlInput'),
            document.getElementById('commandeDetailCountryFormControlInput'),
            document.getElementById('commandeDetailEmailFormControlInput'),
            document.getElementById('commandeDetailTelephoneFormControlInput'),
        ].forEach(el => { if (el) el.disabled = !editable; });

        const btnValider = modal.querySelector('button[type="submit"]');
        if (btnValider) btnValider.style.display = editable ? '' : 'none';

        const btnAnnuler = modal.querySelector('.btn-danger');
        if (btnAnnuler) btnAnnuler.style.display = editable ? '' : 'none';
    }

    function fillModal(c) {
        const elMenu     = modal.querySelector('.menuPrice');
        const elDel      = modal.querySelector('.deliveryCost');
        const elTtc      = modal.querySelector('.ttcPrice');
        const liPromo    = modal.querySelector('#rowReductionDetail');
        const inputNb    = document.getElementById('commandeDetailNumberPersonFormControlInput');
        const prixLivraison = Number.parseFloat(c.prixLivraison ?? 0);

        fillModalHeader(modal, c);
        fillModalAdresse(modal, c, inputDate, selectHeure);
        fillModalPrix(modal, c, elMenu, elDel, elTtc, liPromo);
        fillModalPlats(modal, c, inputNb, elMenu, elTtc, liPromo, prixLivraison);
        fillModalEditable(modal, c, inputDate, selectHeure);
    }

    // ── Valider les changements ───────────────────────────────────────────────
    const btnValider = modal.querySelector('button[type="submit"]');
    btnValider?.addEventListener('click', async (e) => {
        e.preventDefault();
        if (!currentCommande) return;

        const adresse = [
            document.getElementById('commandeDetailAdressFormControlInput')?.value?.trim(),
            document.getElementById('commandeDetailCityFormControlInput')?.value?.trim(),
            document.getElementById('commandeDetailCountryFormControlInput')?.value?.trim(),
        ].filter(Boolean).join(', ');

        const payload = {
            datePrestation:   inputDate?.value,
            heureLivraison:   selectHeure?.value,
            adresseLivraison: adresse,
            nombrePersonne:   Number.parseInt(document.getElementById('commandeDetailNumberPersonFormControlInput')?.value),
            prixMenu:         Number.parseFloat(modal.querySelector('.menuPrice')?.textContent ?? 0),
            prixLivraison:    Number.parseFloat(modal.querySelector('.deliveryCost')?.textContent ?? 0),
        };

        const res = await api.put(`/commande/${currentCommande.id}`, payload);
        if (res.success) {
            bootstrap.Modal.getInstance(modal)?.hide();
            showAlert('Commande mise à jour.', 'success');
            // Recharger la liste
            document.getElementById('collapseCommandUser')?.dispatchEvent(new Event('show.bs.collapse'));
        } else {
            showAlert('Erreur : ' + (res.error ?? 'Une erreur est survenue.'), 'danger');
        }
    });

    // ── Annuler la commande ───────────────────────────────────────────────────
    const btnConfirmAnnul = annulModal?.querySelector('.btn-danger');
    btnConfirmAnnul?.addEventListener('click', async () => {
        if (!currentCommande) return;

        const res = await api.delete(`/commande/${currentCommande.id}`);
        if (res.success) {
            bootstrap.Modal.getInstance(annulModal)?.hide();
            bootstrap.Modal.getInstance(modal)?.hide();
            showAlert('Commande annulée.', 'success');
            document.getElementById('collapseCommandUser')?.dispatchEvent(new Event('show.bs.collapse'));
        } else {
            showAlert('Erreur : ' + (res.error ?? 'Une erreur est survenue.'), 'danger');
        }
    });
}

function setVal(selector, value) {
    const el = document.querySelector(selector);
    if (el) el.value = value ?? '';
}

function escHtml(str) {
    return str ? str.replace(/[&<>"']/g, m =>
        ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m])
    ) : '';
}
function fillDetailSelect(selector, plats, placeholder) {
    const select = document.querySelector(selector);
    if (!select) return;
    select.innerHTML = `<option value="">${placeholder}</option>`;
    plats.forEach(p => {
        const opt = document.createElement('option');
        opt.value       = p.id;
        opt.textContent = p.titre;
        select.appendChild(opt);
    });
}