import { api } from '../../modules/api.js';
import { showAlert } from '../../modules/alerts.js';


function validateRequired(input) {
    if (input.value === "") {
        input.classList.add("is-invalid");
        input.classList.remove("is-valid");
        return false;
    } else {
        input.classList.add("is-valid");
        input.classList.remove("is-invalid");
        return true;
    }
}

function validateEmail(input) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (input.value.match(emailRegex)) {
        input.classList.add("is-valid");
        input.classList.remove("is-invalid");
        return true;
    } else {
        input.classList.add("is-invalid");
        input.classList.remove("is-valid");
        return false;
    }
}

function validatePassword(input) {
    const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_])[A-Za-z\d\W_]{10,}$/;
    if (input.value.match(passwordRegex)) {
        input.classList.add("is-valid");
        input.classList.remove("is-invalid");
        return true;
    } else {
        input.classList.add("is-invalid");
        input.classList.remove("is-valid");
        return false;
    }
}

function validatePasswordConfirm(inputPwd, inputPwdConfirm) {
    if (inputPwd.value === inputPwdConfirm.value) {
        inputPwdConfirm.classList.add("is-valid");
        inputPwdConfirm.classList.remove("is-invalid");
        return true;
    } else {
        inputPwdConfirm.classList.add("is-invalid");
        inputPwdConfirm.classList.remove("is-valid");
        return false;
    }
}

export function init() {
    const inputNom = document.getElementById("NomInput");
    const inputPrenom = document.getElementById("PrenomInput");
    const inputTelephone = document.getElementById("telephoneInput");
    const inputEmail = document.getElementById("EmailInput");
    const inputAdress = document.getElementById("AdressInput");
    const inputCountry = document.getElementById("CountryInput");
    const inputCity = document.getElementById("CityInput");
    const inputPostal = document.getElementById("PostalInput");
    const inputPassword = document.getElementById("PasswordInput");
    const inputValidationPassword = document.getElementById("ValidatePasswordInput");
    const btnValidation = document.getElementById("btn-validation-inscription");

    // ─── Autocomplétion ville ─────────────────────────────────────────────────
    const villePostalMap = new Map();
    let debounceTimer = null;

    inputCity?.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        const q = inputCity.value.trim();

        if (q.length < 2) {
            clearSuggestions();
            return;
        }

        debounceTimer = setTimeout(async () => {
            try {
                const res = await fetch(
                    `https://geo.api.gouv.fr/communes?nom=${encodeURIComponent(q)}&fields=nom,codesPostaux&boost=population&limit=6`
                );
                const communes = await res.json();

                villePostalMap.clear();
                const datalist = document.getElementById('citysuggestions');
                if (!datalist) return;

                datalist.innerHTML = '';
                communes.forEach(c => {
                    const cp = c.codesPostaux?.[0] ?? '';
                    const label = cp ? `${c.nom} (${cp})` : c.nom;
                    villePostalMap.set(label, { nom: c.nom, cp });

                    const opt = document.createElement('option');
                    opt.value = label;
                    datalist.appendChild(opt);
                });
            } catch { /* API indisponible */ }
        }, 300);
    });

    inputCity?.addEventListener('change', () => {
        const match = villePostalMap.get(inputCity.value);
        if (match) {
            inputCity.value = match.nom;
            if (inputPostal) inputPostal.value = match.cp;
        }
        validateForm();
    });

    function clearSuggestions() {
        const datalist = document.getElementById('citysuggestions');
        if (datalist) datalist.innerHTML = '';
        if (inputPostal) inputPostal.value = '';
    }

    // ─── Validation ───────────────────────────────────────────────────────────
    inputNom.addEventListener("keyup", validateForm);
    inputPrenom.addEventListener("keyup", validateForm);
    inputTelephone.addEventListener("keyup", validateForm);
    inputEmail.addEventListener("keyup", validateForm);
    inputAdress.addEventListener("keyup", validateForm);
    inputCountry.addEventListener("keyup", validateForm);
    inputCity.addEventListener("keyup", validateForm);
    inputPassword.addEventListener("keyup", validateForm);
    inputValidationPassword.addEventListener("keyup", validateForm);
    btnValidation.addEventListener('click', register);

    function validateForm() {
        const nomOk = validateRequired(inputNom);
        const prenomOk = validateRequired(inputPrenom);
        const telephoneOk = validateRequired(inputTelephone);
        const emailOk = validateEmail(inputEmail);
        const adressOk = validateRequired(inputAdress);
        const cityOk = validateRequired(inputCity);
        const countryOk = validateRequired(inputCountry);
        const passwordOk = validatePassword(inputPassword);
        const passwordConfirmOk = validatePasswordConfirm(inputPassword, inputValidationPassword);

        btnValidation.disabled = !(nomOk && prenomOk && telephoneOk && emailOk && adressOk && cityOk && countryOk && passwordOk && passwordConfirmOk);
    }


    // ─── Inscription ──────────────────────────────────────────────────────────
    async function register() {
        // Stocker ville avec code postal si disponible
        const villeValue = inputPostal?.value
            ? `${inputCity.value.trim()} (${inputPostal.value.trim()})`
            : inputCity.value.trim();

        const data = await api.post('/utilisateur/registration', {
            nom: inputNom.value.trim(),
            prenom: inputPrenom.value.trim(),
            telephone: inputTelephone.value.trim(),
            email: inputEmail.value.trim(),
            adresse: inputAdress.value.trim(),
            ville: villeValue,
            pays: inputCountry.value.trim(),
            password: inputPassword.value.trim(),
        });

        if (data.success) {
            globalThis.location.replace('/signin');
        } else {
            showAlert('Erreur : ' + (data.error ?? 'Une erreur est survenue.'), 'danger');
        }
    }
}

