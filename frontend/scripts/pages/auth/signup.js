import { api } from '../../modules/api.js';
import { setCookie } from '../../main.js';

export function init() {
    const inputNom                = document.getElementById("NomInput");
    const inputPrenom             = document.getElementById("PrenomInput");
    const inputGsm                = document.getElementById("GsmInput");
    const inputEmail              = document.getElementById("EmailInput");
    const inputAdress             = document.getElementById("AdressInput");
    const inputCountry            = document.getElementById("CountryInput");
    const inputCity               = document.getElementById("CityInput");
    const inputPassword           = document.getElementById("PasswordInput");
    const inputValidationPassword = document.getElementById("ValidatePasswordInput");
    const btnValidation           = document.getElementById("btn-validation-inscription");

    inputNom.addEventListener("keyup", validateForm);
    inputPrenom.addEventListener("keyup", validateForm);
    inputGsm.addEventListener("keyup", validateForm);
    inputEmail.addEventListener("keyup", validateForm);
    inputAdress.addEventListener("keyup", validateForm);
    inputCountry.addEventListener("keyup", validateForm);
    inputCity.addEventListener("keyup", validateForm);
    inputPassword.addEventListener("keyup", validateForm);
    inputValidationPassword.addEventListener("keyup", validateForm);
    btnValidation.addEventListener('click', register);

    function validateForm(){
        const nomOk = validateRequired(inputNom);
        const prenomOk = validateRequired(inputPrenom);
        const gsmOk = validateRequired(inputGsm);
        const emailOk = validateEmail(inputEmail);
        const adressOk = validateRequired(inputAdress);
        const cityOk = validateRequired(inputCity);
        const countryOk = validateRequired(inputCountry);
        const passwordOk = validatePassword(inputPassword);
        const passwordConfirmOk = validatePasswordConfirm(inputPassword, inputValidationPassword);

        if(nomOk && prenomOk && gsmOk && emailOk && adressOk  && cityOk && countryOk && passwordOk && passwordConfirmOk){
            btnValidation.disabled = false;
        }else{
            btnValidation.disabled = true;
        }
    }
    function validateRequired(input){
        if(input.value != ""){
            input.classList.add("is-valid");
            input.classList.remove("is-invalid");
            return true;
        }else{
            input.classList.add("is-invalid");
            input.classList.remove("is-valid");
            return false;
        }
    }
    function validateEmail(input){
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const mailUser = input.value;
        if(mailUser.match(emailRegex)){
            input.classList.add("is-valid");
            input.classList.remove("is-invalid");
            return true;
        }else{
            input.classList.add("is-invalid");
            input.classList.remove("is-valid");
            return false;
        }
    }
    function validatePassword(input){
        const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_])[A-Za-z\d\W_]{10,}$/;
        const passwordUser = input.value;
        if(passwordUser.match(passwordRegex)){
            input.classList.add("is-valid");
            input.classList.remove("is-invalid");
            return true;
        }else{
            input.classList.add("is-invalid");
            input.classList.remove("is-valid");
            return false;
        }
    }
    function validatePasswordConfirm(inputPwd, inputPwdConfirm){
        if(inputPwd.value === inputPwdConfirm.value){
            inputPwdConfirm.classList.add("is-valid");
            inputPwdConfirm.classList.remove("is-invalid");
            return true;
        }else{
            inputPwdConfirm.classList.add("is-invalid");
            inputPwdConfirm.classList.remove("is-valid");
            return false;
        }
    }

    async function register() {
        const data = await api.post('/utilisateur/registration', {
            nom:       inputNom.value.trim(),
            prenom:    inputPrenom.value.trim(),
            telephone: inputGsm.value.trim(),
            email:     inputEmail.value.trim(),
            adresse:   inputAdress.value.trim(),
            ville:     inputCity.value.trim(),
            pays:      inputCountry.value.trim(),
            password:  inputPassword.value.trim(),
        });

        if (data.success) {
            window.location.replace('/signin');
        } else {
            console.error('Erreur inscription:', data);
        }
    }
}

