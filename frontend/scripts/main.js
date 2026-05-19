const tokenCookieName = "accessToken";
const RoleCookieName = "role";
const signoutBtn = document.getElementById("signout-btn");

signoutBtn.addEventListener("click", signout);

function getRole(){
    return getCookie(RoleCookieName);
}

function signout() {
    eraseCookie(tokenCookieName);
    eraseCookie(RoleCookieName);
    window.location.replace('/');
}
function setToken(token) {
    setCookie(tokenCookieName, token, 7);
}

function getToken() {
    return getCookie(tokenCookieName);
}

function setCookie(name,value,days) {
    var expires = "";
    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days*24*60*60*1000));
        expires = "; expires=" + date.toUTCString();
    }
    document.cookie = name + "=" + (value || "")  + expires + "; path=/";
}

function getCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for(var i=0;i < ca.length;i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1,c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
    }
    return null;
}

function eraseCookie(name) {   
    document.cookie = name +'=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;';
}

async function initSession() {
    const data = await fetch('/api/utilisateur/me', {
        credentials: 'include'
    }).then(r => r.json());

    if (data.success && data.user) {
        setCookie('role', data.user.role, 1);
    } else {
        eraseCookie('role');
    }

    showAndHideElementsForRoles();
}

function isConnected() {
    return getCookie('role') !== null;
}


function showAndHideElementsForRoles() {
    const userConnected = isConnected();
    const role = getRole();

    let allElementsToEdit = document.querySelectorAll("[data-show]");

    allElementsToEdit.forEach((element) => {
        const rules = element.dataset.show.split(" ");

        let showElement = false;

        rules.forEach(rule => {
            switch(rule) {
                case "disconnected":
                    if (!userConnected) showElement = true;
                    break;

                case "connected":
                    if (userConnected) showElement = true;
                    break;

                case "admin":
                case "employe":
                case "client":
                    if (userConnected && role === rule) showElement = true;
                    break;
            }
        });

        if (!showElement) {
            element.classList.add("d-none");
        }
    });
}
export { initSession, setCookie, eraseCookie, getCookie, isConnected, getRole, showAndHideElementsForRoles };
