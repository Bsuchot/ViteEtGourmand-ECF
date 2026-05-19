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
        const months  = [...new Set(commandes.map(c => parseInt(getDateStr(c.datePrestation).split('-')[1])))];
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
            const matchMonth  = month  ? parseInt(m) == month : true;
            const matchClient = client ? c.utilisateurId == client : true;
            const matchStatut = statutFilter.value ? c.statut === statutFilter.value : true;
            return matchYear && matchMonth && matchClient && matchStatut;
        });

        renderCommandes(filtered);
    }

    const statutBadge = {
        'en attente':                       'bg-dark',
        'accepté':                          'bg-success',
        'en préparation':                   'bg-primary',
        'livré':                            'bg-info text-dark',
        'en attente du retour de matériel': 'bg-warning text-dark',
        'terminée':                         'bg-secondary',
    };

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
            const prix      = (parseFloat(c.prixMenu) + parseFloat(c.prixLivraison)).toFixed(2);
            const badge     = statutBadge[c.statut] ?? 'bg-secondary';

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
                if (!data.success) alert('Erreur lors du changement de statut.');
            });
        });
    }
}