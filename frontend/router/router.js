import Route from "./Route.js";
import { allRoutes, websiteName } from "./allRoutes.js";

const route404 = new Route("404", "Page introuvable", "/pages/404.html", []);

const getRouteByUrl = (url) => {
  return allRoutes.find(route => route.url === url) || route404;
};

const LoadContentPage = async () => {
  const path = window.location.pathname;
  const actualRoute = getRouteByUrl(path);

  //Vérifier les autorisations d'accès
  const allRolesArray = actualRoute.authorize;

    if(allRolesArray.length > 0){
      if(allRolesArray.includes("disconnected")){
        if(isConnected()){
          window.location.replace("/");
        }
      }
      else{
        const roleUser = getRole();
        if(!allRolesArray.includes(roleUser)){
          window.location.replace("/");
        }
      }
    }

  try {
    const response = await fetch(actualRoute.pathHtml);
    const html = await response.text();
    const mainPage = document.getElementById("main-page");

    if (mainPage) {
      mainPage.innerHTML = html;

      // Ajoute les événements sur les liens internes
      document.querySelectorAll('a[href^="/"]').forEach(link => {
        link.addEventListener("click", routeEvent);
      });

      // Ajoute le JS de la page si nécessaire
      if (actualRoute.pathJS) {
        const scriptTag = document.createElement("script");
        scriptTag.type = "text/javascript";
        scriptTag.src = actualRoute.pathJS;
        document.body.appendChild(scriptTag);
      }

      // Change le titre de la page
      document.title = `${actualRoute.title} - ${websiteName}`;
    } else {
      console.error("Element #main-page introuvable");
    }
  } catch (error) {
    console.error("Erreur lors du chargement de la page :", error);
  }

  //Afficher et masquer les éléments en fonction du rôle
  showAndHideElementsForRoles();
  
};

const routeEvent = (event) => {
  event.preventDefault();
  const href = event.currentTarget.getAttribute("href");
  window.history.pushState({}, "", href);
  LoadContentPage();
};

window.onpopstate = LoadContentPage;
window.route = routeEvent;
LoadContentPage();
