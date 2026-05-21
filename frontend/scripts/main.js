import { api } from "./modules/api.js";
import { showAlert } from "./modules/alerts.js";


const tokenCookieName = "accessToken";
const RoleCookieName = "role";

/* --------------------------
   SIGNOUT (FULL BACKEND)
-------------------------- */
document.getElementById("logout-link")?.addEventListener("click", signout);

async function signout(e) {
    if (e) e.preventDefault();

    try {
        await fetch("/utilisateur/logout", {
            method: "POST",
            credentials: "include",
            headers: {
                "X-CSRF-Token": window.CSRF_TOKEN || ""
            }
        });
    } catch (err) {
        console.warn("Logout error:", err);
    }

    // nettoyage front (sécurité UI)
    eraseCookie(tokenCookieName);
    eraseCookie(RoleCookieName);

    window.location.replace("/");
}

/* --------------------------
   COOKIES
-------------------------- */
function setCookie(name, value, days) {
    let expires = "";

    if (days) {
        const date = new Date();
        date.setTime(date.getTime() + days * 24 * 60 * 60 * 1000);
        expires = "; expires=" + date.toUTCString();
    }

    document.cookie = `${name}=${value || ""}${expires}; path=/`;
}

function getCookie(name) {
    const nameEQ = name + "=";
    const ca = document.cookie.split(";");

    for (let i = 0; i < ca.length; i++) {
        let c = ca[i].trim();
        if (c.indexOf(nameEQ) === 0) {
            return c.substring(nameEQ.length);
        }
    }
    return null;
}

function eraseCookie(name) {
    document.cookie = `${name}=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;`;
}

/* --------------------------
   SESSION INIT
-------------------------- */
async function initSession() {
    try {
        const data = await fetch("/utilisateur/me", {
            credentials: "include"
        }).then(r => r.json());

        if (data.success && data.user) {
            setCookie("role", data.user.role, 1);
        } else {
            eraseCookie("role");
            eraseCookie(tokenCookieName);
        }
    } catch (e) {
        eraseCookie("role");
        eraseCookie(tokenCookieName);
    }

    showAndHideElementsForRoles();
}

/* --------------------------
   AUTH STATE
-------------------------- */
function isConnected() {
    return getCookie("role") !== null;
}

function getRole() {
    return getCookie(RoleCookieName);
}

/* --------------------------
   SHOW / HIDE UI
-------------------------- */
function showAndHideElementsForRoles() {
    const userConnected = isConnected();
    const role = getRole();

    const elements = document.querySelectorAll("[data-show]");

    elements.forEach((element) => {
        element.classList.remove("d-none");

        const rules = element.dataset.show.split(" ");
        let showElement = false;

        for (const rule of rules) {
            if (rule === "disconnected" && !userConnected) showElement = true;

            if (rule === "connected" && userConnected) showElement = true;

            if (
                userConnected &&
                (rule === "admin" || rule === "employe" || rule === "client") &&
                role === rule
            ) {
                showElement = true;
            }
        }

        if (!showElement) {
            element.classList.add("d-none");
        }
    });
}

/* --------------------------
   EXPORTS
-------------------------- */
export {
    initSession,
    setCookie,
    eraseCookie,
    getCookie,
    isConnected,
    getRole,
    showAndHideElementsForRoles
};

/* --------------------------
   FOOTER HORAIRES
-------------------------- */


function escHtml(str) {
    return str
        ? str.replace(/[&<>"']/g, m => ({
            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            '"': "&quot;",
            "'": "&#039;"
        }[m]))
        : "";
}

function formatHour(hour) {
    return hour?.slice(0, 5).replace(":", "h");
}

async function loadFooterHours() {
    const container = document.querySelector("#footer-hours");

    if (!container) return;

    try {
        const res = await api.get("/horaire/readAll");

        if (!res.success) {
            container.innerHTML = `<li class="text-warning">Horaires indisponibles</li>`;
            return;
        }

        const horaires = res.data ?? res;

        container.innerHTML = horaires.map(h => `
            <li class="d-flex justify-content-between border-bottom border-light pb-1 mb-1">
                <span>${escHtml(h.jour)}</span>
                <span>
                    ${h.statut === "fermé"
                        ? "Fermé"
                        : `${formatHour(h.heureOuverture)} - ${formatHour(h.heureFermeture)}`
                    }
                </span>
            </li>
        `).join("");

    } catch (e) {
        container.innerHTML = `<li class="text-warning">Horaires indisponibles</li>`;
    }
}

loadFooterHours();