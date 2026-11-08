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

export function initCommandesAdmin() {
    const collapse = document.getElementById('collapseCommandAdmin');
    if (!collapse) return;

    const tbodyAdmin   = document.querySelector('#adminOrderTable tbody');
    const yearsFilter  = document.getElementById('yearsAdminCommandFilter');
    const monthFilter  = document.getElementById('monthAdminCommandFilter');
    const clientFilter = document.getElementById('clientAdminCommandFilter');
    const statutFilter = document.getElementById('statutAdminCommandFilter');

    let allCommandes = [];

    collapse.addEventListener('show.bs.collapse', () => {
        loadCommandes();
    });

    async function loadCommandes() {
        const data = await api.get('/employe/commande/readAll');
        if (!data.success) return;
        allCommandes = data.data;
        populateFilters(allCommandes);
        renderCommandes(allCommandes);
    }

    function populateFilters(commandes) {
        const years   = [...new Set(commandes.map(c => getDateStr(c.datePrestation).split('-')[0]))];
        const months  = [...new Set(commandes.map(c => Number.parseInt(getDateStr(c.datePrestation).split('-')[1])))];
        const clients = [...new Set(commandes.map(c => c.utilisateurId))];
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
        clients.forEach(id => {
            const commande = commandes.find(c => c.utilisateurId === id);
            const opt = document.createElement('option');
            opt.value = id;
            opt.textContent = `${commande?.clientPrenom ?? ''} ${commande?.clientNom ?? ''}`;
            clientFilter.appendChild(opt);
        });

        yearsFilter.addEventListener('change', filterCommandes);
        monthFilter.addEventListener('change', filterCommandes);
        clientFilter.addEventListener('change', filterCommandes);
        statutFilter.addEventListener('change', filterCommandes);
    }

    function filterCommandes() {
        const year   = yearsFilter.value;
        const month  = monthFilter.value;
        const client = clientFilter.value;

        const filtered = allCommandes.filter(c => {
            const [y, m] = getDateStr(c.datePrestation).split('-');
            const matchYear   = year   ? y == year            : true;
            const matchMonth  = month  ? Number.parseInt(m) == month : true;
            const matchClient = client ? c.utilisateurId == client : true;
            const matchStatut = statutFilter.value ? c.statut === statutFilter.value : true;
            return matchYear && matchMonth && matchClient && matchStatut;
        });

        renderCommandes(filtered);
    }


    const statutOptions = {
        'en attente':                       'en attente',
        'accepté':                          'accepté',
        'en préparation':                   'en préparation',
        'livré':                            'livré',
        'en attente du retour de matériel': 'en attente du retour de matériel',
        'terminée':                         'terminée',
    };

    function renderCommandes(commandes) {
        tbodyAdmin.innerHTML = '';

        if (commandes.length === 0) {
            tbodyAdmin.innerHTML = '<tr><td colspan="9" class="text-center">Aucune commande trouvée</td></tr>';
            return;
        }

        commandes.forEach(c => {
            const date      = formatDate(getDateStr(c.dateCommande));
            const livraison = formatDate(getDateStr(c.datePrestation)) + ' à ' + c.heureLivraison;
            const prix      = (Number.parseFloat(c.prixMenu) + Number.parseFloat(c.prixLivraison)).toFixed(2);

            const statutSelect = Object.keys(statutOptions).map(s =>
                `<option value="${s}" ${c.statut === s ? 'selected' : ''}>${s}</option>`
            ).join('');

            tbodyAdmin.innerHTML += `
                <tr>
                    <td>${date}</td>
                    <td>${c.menuTitre ?? '—'}</td>
                    <td>${c.nombrePersonne}</td>
                    <td class="d-none d-md-table-cell">${c.clientPrenom ?? ''} ${c.clientNom ?? ''}</td>
                    <td class="d-none d-sm-table-cell">${c.adresseLivraison}</td>
                    <td>${livraison}</td>
                    <td class="text-success fw-semibold text-nowrap">${prix} €</td>
                    <td>
                        <select class="form-select form-select-sm" data-id="${c.id}">
                            ${statutSelect}
                        </select>
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-primary"
                            data-bs-toggle="modal" data-bs-target="#detailCommandeAdminModal"
                            data-id="${c.id}">Détails</button>
                    </td>
                </tr>
            `;
        });

        document.querySelectorAll('#adminOrderTable select[data-id]').forEach(select => {
            select.addEventListener('change', async () => {
                const id = select.dataset.id;
                const data = await api.put(`/employe/commande/${id}/statut`, { statut: select.value });
                if (data.success){showAlert('Statut mis à jour avec succès !', 'success')}else {showAlert('Erreur lors du changement de statut.', 'danger')   
                }
                ;
            });
        });
        document.querySelectorAll('#adminOrderTable button[data-id]').forEach(btn => {
            btn.addEventListener('click', () => openDetailModal(Number.parseInt(btn.dataset.id)));
        });
    }

    // Détails de la commande
    async function openDetailModal(id) {
        const c = allCommandes.find(c => c.id === id);
        if (!c) return;

        const modal = document.querySelector('#detailCommandeAdminModal');

        // Charger le détail complet
        const res = await api.get(`/employe/commande/${id}`);
        const detail = res.success ? res.data : c;

        // Titre menu
        modal.querySelector('#detailCommandeAdminModalLabel').textContent = 'Détail de la commande';
        modal.querySelector('.text-center.text-primary').textContent = detail.menuTitre ?? '—';

        // Statut
        modal.querySelector('#deliveryTime').value = detail.statut ?? '';

        // Date commande
        modal.querySelector('#detailDateCommande').textContent = formatDate(getDateStr(detail.dateCommande));

        // Nombre de personnes
        modal.querySelector('#commandeDetailNumberPersonFormControlInput1').value = detail.nombrePersonne ?? '';

        // Date livraison
        modal.querySelector('#commandeDetailDeliveryDateFormControlInput1').value = getDateStr(detail.datePrestation).split(' ')[0];

        // Heure livraison
        const selHeure = modal.querySelector('#commandeDetaildeliveryTime1');
        selHeure.innerHTML = `<option value="${detail.heureLivraison}">${detail.heureLivraison}</option>`;

        // Coordonnées client
        const ul = modal.querySelector('.list-unstyled');
        ul.innerHTML = `
            <li><span class="text-uppercase">${detail.utilisateurNom ?? ''}</span> ${detail.utilisateurPrenom ?? ''}</li>
            <li>Téléphone : ${detail.utilisateurTelephone ?? '—'}</li>
            <li>Email : ${detail.utilisateurEmail ?? '—'}</li>
            <li>${detail.adresseLivraison ?? '—'}</li>
        `;

        // Plats depuis le menu
        const menuRes = await api.get(`/menu/${detail.menuId}`);
        const plats = menuRes.success ? (menuRes.data.plats ?? []) : [];

        
        const entree  = plats.find(p => p.category === 'entree')?.titre ?? '—';
        const plat    = plats.find(p => p.category === 'plat')?.titre   ?? '—';
        const dessert = plats.find(p => p.category === 'dessert')?.titre ?? '—';

        modal.querySelector('#detailEntreeAdmin').textContent  = entree;
        modal.querySelector('#detailPlatAdmin').textContent    = plat;
        modal.querySelector('#detailDessertAdmin').textContent = dessert;

        // Prix
        const personMin      = Number.parseInt(menuRes.data?.nombrePersonneMinimum ?? 0);
        const seuil          = personMin + 5;
        const reduction      = detail.nombrePersonne >= seuil ? 0.1 : 0;
        const prixAvantReduc = Number.parseFloat(detail.prixMenu ?? 0) / (1 - reduction || 1);
        const montantReduc   = prixAvantReduc * reduction;
        const prixMenu       = Number.parseFloat(detail.prixMenu ?? 0);
        const prixLiv        = Number.parseFloat(detail.prixLivraison ?? 0);
        const total          = prixMenu + prixLiv;

        modal.querySelector('.menuPrice').textContent    = prixAvantReduc.toFixed(2);
        modal.querySelector('.deliveryCost').textContent = prixLiv.toFixed(2);
        modal.querySelector('.ttcPrice').textContent     = total.toFixed(2);

        const rowReduc = modal.querySelector('#rowReduction');
        if (rowReduc) {
            rowReduc.classList.toggle('hidden', reduction === 0);
            if (reduction > 0) modal.querySelector('.promoPrice').textContent = montantReduc.toFixed(2);
        }
        // Bouton annulation
        const btnAnnul = document.getElementById('btnConfirmAnnulAdmin');
        if (btnAnnul) {
            const newBtn = btnAnnul.cloneNode(true);
            btnAnnul.parentNode.replaceChild(newBtn, btnAnnul);

            newBtn.addEventListener('click', async () => {
                const res = await api.delete(`/commande/${id}`);
                if (res.success) {
                    bootstrap.Modal.getInstance(document.getElementById('annulationCommandModal'))?.hide();
                    bootstrap.Modal.getInstance(modal)?.hide();
                    showAlert('Commande annulée avec succès.', 'success');
                    await loadCommandes();
                } else {
                    showAlert('Erreur lors de l\'annulation.', 'danger');
                }
            });
        }
        const isAdminOrEmploye = getRole() === 'admin' || getRole() === 'employe';
        document.getElementById('annulMsgUser')?.style.setProperty('display', isAdminOrEmploye ? 'none' : '');
        document.getElementById('annulMsgAdmin')?.style.setProperty('display', isAdminOrEmploye ? '' : 'none');
    }
}