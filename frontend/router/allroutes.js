import Route from "./Route.js";

//Définir ici vos routes
export const allRoutes = [
    new Route("/", "Accueil", "/pages/home.html", [], "/scripts/pages/home.js"),
    new Route("/menu", "Menu", "/pages/menu.html", [], "/scripts/pages/menu.js"),
    new Route("/contact", "Contact", "/pages/contact.html", [], "/scripts/pages/contact.js"),
    new Route("/account", "Compte", "/pages/auth/account.html", ["admin","employe","user"], "/scripts/pages/auth/account/account.js"),
    new Route("/signin", "Connexion", "/pages/auth/signin.html", ["disconnected"], "/scripts/pages/auth/signin.js"),
    new Route("/signup", "Inscription", "/pages/auth/signup.html", ["disconnected"], "/scripts/pages/auth/signup.js"),
    new Route("/forgot-password", "Mot de passe oublié", "/pages/auth/forgot-password.html", ["disconnected"], "/scripts/pages/auth/forgot-password.js"),
    new Route("/reset-password", "Réinitialisation", "/pages/auth/reset-password.html", ["disconnected"], "/scripts/pages/auth/reset-password.js"),
];

//Le titre s'affiche comme ceci : Route.titre - websitename
export const websiteName = "Vite & Gourmand";