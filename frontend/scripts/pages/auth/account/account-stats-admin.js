import { api } from '../../../modules/api.js';

export function initStatsAdmin() {
    let chartInstance = null;

    async function loadStats() {
        try {
            const res = await api.get('/commande/stats');

            const data = res.data || res;

            updateKPI(data);
            renderChart(labels, values);

            const labels = data.map(item => item._id);
            const values = data.map(item => item.total);

            renderChart(labels, values);

        } catch (err) {
            console.error('Erreur chargement stats:', err);
        }
    }

    function updateKPI(data) {
        const total = data.reduce((sum, item) => sum + item.total, 0);

        const orders = data.length;

        const caEl = document.getElementById('totalCa');
        const orderEl = document.getElementById('totalOrders');

        if (caEl) caEl.textContent = total.toFixed(2) + ' €';
        if (orderEl) orderEl.textContent = orders;
    }

    function renderChart(labels, values) {
        const ctx = document.getElementById('statsChart');

        if (!ctx) return;

        
        if (chartInstance) {
            chartInstance.destroy();
        }

        chartInstance = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Chiffre d\'affaires',
                    data: values,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: true
                    }
                }
            }
        });
    }

    document.addEventListener('DOMContentLoaded', loadStats);
}