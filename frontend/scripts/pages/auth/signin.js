const mailInput = document.getElementById('EmailInput');
const passwordInput = document.getElementById('PasswordInput');
const btnSingnin = document.getElementById('btn-signin');

btnSingnin.addEventListener('click', checkCredentials);

function checkCredentials() {
    // ici il faudra appelé l'API pour vérifier les credentials

    if (mailInput.value === "test@mail.com" && passwordInput.value === "123") {
        
        // Il faudra récupérer le vrai token depuis l'API
        const token = "sdmfkjzslkjvcxbcvbsfhsfjgchkjcbncghdhfisddjhflsdujfh"
        setToken(token);

        // placer ce token en cookie

        setCookie(RoleCookieName, "employe", 7);
        window.location.replace("/");
    }else{
        mailInput.classList.add("is-invalid");
        passwordInput.classList.add("is-invalid");
    }
}