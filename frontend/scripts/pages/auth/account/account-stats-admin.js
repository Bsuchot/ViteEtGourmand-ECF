import { api } from '../../../modules/api.js';
import { showAlert } from '../../../modules/alerts.js';
import Chart from 'chart.js/auto';

export function initStatsAdmin() {
    let chartInstance = null;

    const collapse = document.getElementById('collapseBudget');
    collapse?.addEventListener('show.bs.collapse', () => loadStats());

    document.getElementById('btnFilterStats')?.addEventListener('click', () => loadStats());

    async function loadStats() {
        const menuTitre    = document.getElementById('filterMenu')?.value;
        const dateDebut = document.getElementById('filterDateDebut')?.value;
        const dateFin   = document.getElementById('filterDateFin')?.value;

        const params = new URLSearchParams();
        if (menuTitre)    params.append('menuTitre',    menuTitre);
        if (dateDebut) params.append('dateDebut', dateDebut);
        if (dateFin)   params.append('dateFin',   dateFin);

        const res = await api.get(`/commande/stats?${params.toString()}`);
        if (!res.success || !res.data?.length) {
            showAlert('Aucune stat disponible.', 'warning');
            return;
        }

        const data = res.data;
        updateKPI(data);
        renderChart(data);
        populateMenuFilter(data);
    }

    function updateKPI(data) {
        const total   = data.reduce((sum, d) => sum + d.total, 0);
        const orders  = data.reduce((sum, d) => sum + d.commandes, 0);

        const caEl    = document.getElementById('totalCa');
        const orderEl = document.getElementById('totalOrders');
        if (caEl)    caEl.textContent    = total.toFixed(2) + ' €';
        if (orderEl) orderEl.textContent = orders;
    }

    function populateMenuFilter(data) {
        const select = document.getElementById('filterMenu');
        if (!select || select.options.length > 1) return;
        data.forEach(d => {
            const opt = document.createElement('option');
            opt.value       = d.menu;
            opt.textContent = d.menu;
            select.appendChild(opt);
        });
    }

    function renderChart(data) {
        const ctx = document.getElementById('statsChart');
        if (!ctx) return;

        if (chartInstance) chartInstance.destroy();

        chartInstance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.map(d => d.menu),
                datasets: [
                    {
                        label: "Chiffre d'affaires (€)",
                        data:  data.map(d => d.total),
                        backgroundColor: 'rgba(54, 162, 235, 0.6)',
                        yAxisID: 'y',
                    },
                    {
                        label: 'Nombre de commandes',
                        data:  data.map(d => d.commandes),
                        backgroundColor: 'rgba(255, 99, 132, 0.6)',
                        yAxisID: 'y1',
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y:  { position: 'left',  title: { display: true, text: '€' } },
                    y1: { position: 'right', title: { display: true, text: 'Commandes' }, grid: { drawOnChartArea: false } }
                }
            }
        });
    }
}