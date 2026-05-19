export default class Route {
    constructor(url, title, pathHtml, authorize, pathJS = "") {
      this.url = url;
      this.title = title;
      this.pathHtml = pathHtml;
      this.authorize = authorize;
      this.pathJS = pathJS;
    }
}

/*
[] -> tout le monde
["disconnected"] -> utilisateur non connectés
["user"] -> utilisateur connecté
["employe"] -> employé connecté
["admin"] -> admin connecté
["admin","employe","user"]
*/