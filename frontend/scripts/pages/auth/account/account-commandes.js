import { api } from '../../../modules/api.js';

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
        const years  = [...new Set(commandes.map(c => getDateStr(c.datePrestation).split('-')[0]))];
        const months = [...new Set(commandes.map(c => parseInt(getDateStr(c.datePrestation).split('-')[1])))];
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
            const matchMonth = month ? parseInt(m) == month : true;
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
            const prix      = (parseFloat(c.prixMenu) + parseFloat(c.prixLivraison)).toFixed(2);
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