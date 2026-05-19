import { initProfile }         from './account-profile.js';
import { initCommandes }       from './account-commandes.js';
import { initCommandesAdmin }  from './account-commandes-admin.js';
import { initHoraires }        from './account-horaires.js';
import { initEmployes }        from './account-employes.js';
import { initMenus }           from './account-menus.js';
import { initPlats }           from './account-plats.js';
import { initStatsAdmin }      from './account-stats-admin.js';

export function init() {
    initProfile();
    initCommandes();
    initCommandesAdmin();
    initHoraires();
    initEmployes();
    initMenus();
    initPlats();
    initStatsAdmin();
}
