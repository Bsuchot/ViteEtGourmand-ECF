

const inputNom = document.getElementById("NomInput");
const inputPrenom = document.getElementById("PrenomInput");
const inputGsm = document.getElementById("GsmInput");
const inputEmail = document.getElementById("EmailInput");
const inputAdress = document.getElementById("AdressInput");
const inputPostalCode = document.getElementById("PostalCodeInput");
const inputCity = document.getElementById("CityInput");
const inputPassword = document.getElementById("PasswordInput");
const inputValidationPassword = document.getElementById("ValidatePasswordInput");
const btnValidation = document.getElementById("btn-validation-inscription");

inputNom.addEventListener("keyup", validateForm);
inputPrenom.addEventListener("keyup", validateForm);
inputGsm.addEventListener("keyup", validateForm);
inputEmail.addEventListener("keyup", validateForm);
inputAdress.addEventListener("keyup", validateForm);
inputPostalCode.addEventListener("keyup", validateForm);
inputCity.addEventListener("keyup", validateForm);
inputPassword.addEventListener("keyup", validateForm);
inputValidationPassword.addEventListener("keyup", validateForm);

function validateForm(){
    const nomOk = validateRequired(inputNom);
    const prenomOk = validateRequired(inputPrenom);
    const gsmOk = validateRequired(inputGsm);
    const emailOk = validateEmail(inputEmail);
    const adressOk = validateRequired(inputAdress);
    const postalCodeOk = validateRequired(inputPostalCode);
    const cityOk = validateRequired(inputCity);
    const passwordOk = validatePassword(inputPassword);
    const passwordConfirmOk = validatePasswordConfirm(inputPassword, inputValidationPassword);

    if(nomOk && prenomOk && gsmOk && emailOk && adressOk && postalCodeOk && cityOk && passwordOk && passwordConfirmOk){
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
    const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_])[A-Za-z\d\W_]{8,}$/;
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
