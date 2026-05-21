// modules/alerts.js
export function showAlert(message, type = 'success', containerId = 'alertContainer') {
    const icons = {
        success: '#check-circle-fill',
        danger:  '#exclamation-triangle-fill',
        warning: '#exclamation-triangle-fill',
        primary: '#info-fill',
    };

    const container = document.getElementById(containerId);
    if (!container) return;

    const alertEl = document.createElement('div');
    alertEl.className = `alert alert-${type} d-flex align-items-center alert-dismissible fade show`;
    alertEl.role = 'alert';
    alertEl.innerHTML = `
        <svg class="bi flex-shrink-0 me-2" role="img">
            <use xlink:href="${icons[type] ?? '#info-fill'}"/>
        </svg>
        <div>${message}</div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;

    container.appendChild(alertEl);

    setTimeout(() => {
        if (alertEl.parentNode) alertEl.remove();
    }, 4000);
}