import { api } from '../../../modules/api.js';
import { showAlert } from '../../../modules/alerts.js';

export function initEmployes() {
    const collapse = document.getElementById('collapseEmployeeManagement');
    if (!collapse) return;

    const tbody         = document.querySelector('#employeeTable tbody');
    const btnSave = document.getElementById('btnSaveChanges');
    const btnValiderNew = document.getElementById('btnCreateEmployee');

    let employes = [];
    let pendingDeleteId = null;

    // Confirmation suppression — une seule fois
    document.getElementById('btnConfirmDelete')?.addEventListener('click', async () => {
        if (!pendingDeleteId) return;
        const data = await api.delete(`/admin/employe/${pendingDeleteId}`);
        if (data.success) {
            loadEmployes();
            bootstrap.Modal.getInstance(document.getElementById('confirmationDeleteModal'))?.hide();
        } else {
            showAlert('Erreur : ' + (data.error ?? 'Une erreur est survenue.'), 'danger');
        }
        pendingDeleteId = null;
    });

    collapse.addEventListener('show.bs.collapse', () => {
        loadEmployes();
    });

    async function loadEmployes() {
        const data = await api.get('/admin/employe/readAll');
        if (!data.success) return;
        employes = data.data;
        renderEmployes(employes);
    }

    function renderEmployes(list) {
        tbody.innerHTML = '';
        list.forEach(e => {
            tbody.innerHTML += `
                <tr data-id="${e.id}">
                    <td>${e.id}</td>
                    <td><input type="text" class="form-control" value="${e.nom ?? ''}" placeholder="Nom" data-field="nom"></td>
                    <td><input type="text" class="form-control" value="${e.prenom ?? ''}" placeholder="Prénom" data-field="prenom"></td>
                    <td><input type="text" class="form-control" value="${e.telephone ?? ''}" placeholder="Téléphone" data-field="telephone"></td>
                    <td><input type="email" class="form-control" value="${e.email ?? ''}" placeholder="Email" data-field="email"></td>
                    <td>
                        <select class="form-select" data-field="statut">
                            <option value="actif"   ${e.statut === 'actif'   ? 'selected' : ''}>Actif</option>
                            <option value="inactif" ${e.statut === 'inactif' ? 'selected' : ''}>Inactif</option>
                        </select>
                    </td>
                    <td>
                        <button type="button" class="btn btn-danger btn-delete" data-id="${e.id}"
                            data-bs-toggle="modal" data-bs-target="#confirmationDeleteModal">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        });

        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', () => {
                pendingDeleteId = btn.dataset.id;
            });
        });
    }

    btnSave?.addEventListener('click', async () => {
    const rows = tbody.querySelectorAll('tr[data-id]');
    const payload = [];

    rows.forEach(row => {
        const id      = parseInt(row.dataset.id);
        const original = employes.find(e => e.id === id);
        const item    = { id };

        const nom    = row.querySelector('[data-field="nom"]').value.trim();
        const prenom = row.querySelector('[data-field="prenom"]').value.trim();
        const telephone = row.querySelector('[data-field="telephone"]').value.trim();
        const email  = row.querySelector('[data-field="email"]').value.trim();
        const statut = row.querySelector('[data-field="statut"]').value;

        if (nom    !== original?.nom)    item.nom    = nom;
        if (prenom !== original?.prenom) item.prenom = prenom;
        if (telephone !== original?.telephone) item.telephone = telephone;
        if (email  !== original?.email)  item.email  = email;
        if (statut !== original?.statut) item.statut = statut;

        if (Object.keys(item).length > 1) payload.push(item);
    });

    if (payload.length === 0) {
        showAlert('Aucune modification détectée.', 'warning');
        return;
    }

    const data = await api.put('/admin/employe/update', payload);
    if (data.success) {
        showAlert('Employés mis à jour.', 'success');
        loadEmployes();
    } else {
        showAlert('Erreur : ' + (data.error ?? 'Une erreur est survenue.'), 'danger');
    }
});

    btnValiderNew?.addEventListener('click', async () => {
        const emailInput           = document.getElementById('newEmployeeMailInput');
        const nomInput             = document.getElementById('newEmployeeNameInput');
        const prenomInput          = document.getElementById('newEmployeeFirstnameInput');
        const passwordInput        = document.getElementById('newEmployeePasswordInput');
        const passwordConfirmInput = document.getElementById('ValidateNewEmployeePasswordInput');

        function validateRequired(input) {
            const ok = input.value.trim() !== '';
            input.classList.toggle('is-valid', ok);
            input.classList.toggle('is-invalid', !ok);
            return ok;
        }

        function validateEmail(input) {
            const ok = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(input.value.trim());
            input.classList.toggle('is-valid', ok);
            input.classList.toggle('is-invalid', !ok);
            return ok;
        }

        function validatePassword(input) {
            const ok = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_])[A-Za-z\d\W_]{10,}$/.test(input.value);
            input.classList.toggle('is-valid', ok);
            input.classList.toggle('is-invalid', !ok);
            return ok;
        }

        function validatePasswordConfirm(inputPwd, inputConfirm) {
            const ok = inputPwd.value === inputConfirm.value && inputConfirm.value !== '';
            inputConfirm.classList.toggle('is-valid', ok);
            inputConfirm.classList.toggle('is-invalid', !ok);
            return ok;
        }

        const ok = validateRequired(nomInput)
            && validateRequired(prenomInput)
            && validateEmail(emailInput)
            && validatePassword(passwordInput)
            && validatePasswordConfirm(passwordInput, passwordConfirmInput);

        if (!ok) return;

        const data = await api.post('/admin/employe/create', {
            email:    emailInput.value.trim(),
            nom:      nomInput.value.trim(),
            prenom:   prenomInput.value.trim(),
            password: passwordInput.value,
        });

        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('newEmployeeModal'))?.hide();
            [emailInput, nomInput, prenomInput, passwordInput, passwordConfirmInput].forEach(el => {
                el.value = '';
                el.classList.remove('is-valid', 'is-invalid');
            });
            loadEmployes();
        } else {
            showAlert('Erreur : ' + (data.error ?? 'Une erreur est survenue.'), 'danger');
        }
    });
}