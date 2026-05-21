import Route from "./Route.js";
import { allRoutes, websiteName } from "./allRoutes.js";
import { showAndHideElementsForRoles, getRole, isConnected,} from "../scripts/main.js";

const route404 = new Route("404", "Page introuvable", "/pages/404.html", []);

const getRouteByUrl = (url) => {
  return allRoutes.find(route => route.url === url) || route404;
};

const loadedModules = {};

const checkAuthorization = (route) => {
    const roles = route.authorize;
    if (!roles.length) return true;

    if (roles.includes("disconnected")) {
        if (isConnected()) {
            globalThis.location.replace("/");
            return false;
        }
        return true;
    }

    if (!roles.includes(getRole())) {
        globalThis.location.replace("/");
        return false;
    }
    return true;
};

const loadHtml = async (route) => {
    const response = await fetch(route.pathHtml);
    const html     = await response.text();
    const mainPage = document.getElementById("main-page");
    if (!mainPage) { console.error("Element #main-page introuvable"); return; }
    mainPage.innerHTML = html;
    document.querySelectorAll('a[href^="/"]').forEach(link => {
        link.addEventListener("click", routeEvent);
    });
};

const loadModule = async (route) => {
    if (!route.pathJS) return;
    if (!loadedModules[route.pathJS]) {
        loadedModules[route.pathJS] = await import(route.pathJS);
    }
    loadedModules[route.pathJS].init?.();
};

const LoadContentPage = async () => {
    const actualRoute = getRouteByUrl(globalThis.location.pathname);

    if (!checkAuthorization(actualRoute)) return;

    try {
        await loadHtml(actualRoute);
        await loadModule(actualRoute);
        document.title = `${actualRoute.title} - ${websiteName}`;
    } catch (error) {
        console.error("Erreur lors du chargement de la page :", error);
    }

    showAndHideElementsForRoles();
};

const routeEvent = (event) => {
  event.preventDefault();
  const href = event.currentTarget.getAttribute("href");
  globalThis.history.pushState({}, "", href);
  LoadContentPage();
};

globalThis.onpopstate = LoadContentPage;
globalThis.route = routeEvent;
LoadContentPage();