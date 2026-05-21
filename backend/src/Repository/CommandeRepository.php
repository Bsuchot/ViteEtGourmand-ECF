<?php

namespace App\Repository;

use App\Core\Repository;
use App\Models\Commande;
use PDO;

class CommandeRepository extends Repository
{
    public function findAll(): array
    {
        $query = $this->pdo->prepare("
        SELECT c.*, m.titre AS menuTitre,
               u.nom AS clientNom, u.prenom AS clientPrenom
        FROM commande c
        LEFT JOIN menu m ON c.menu_id = m.id
        LEFT JOIN utilisateur u ON c.utilisateur_id = u.id
        ORDER BY c.date_commande DESC
    ");
        $query->execute();
        $rows = $query->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function($row) {
            $commande = Commande::createAndHydrate($row)->toArray();
            $commande['menuTitre']    = $row['menuTitre'];
            $commande['clientNom']    = $row['clientNom'];
            $commande['clientPrenom'] = $row['clientPrenom'];
            return $commande;
        }, $rows);
    }

    public function findById(int $id): ?array
    {
        $query = $this->pdo->prepare("
        SELECT c.*,
               m.titre        AS menuTitre,
               u.nom          AS utilisateurNom,
               u.prenom       AS utilisateurPrenom,
               u.email        AS utilisateurEmail,
               u.telephone    AS utilisateurTelephone
        FROM commande c
        LEFT JOIN menu        m ON c.menu_id        = m.id
        LEFT JOIN utilisateur u ON c.utilisateur_id = u.id
        WHERE c.id = :id
    ");
        $query->bindValue(':id', $id, PDO::PARAM_INT);
        $query->execute();
        $row = $query->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;

        $data = Commande::createAndHydrate($row)->toArray();

        // Ajouter les champs joints
        $data['menuTitre']            = $row['menuTitre'];
        $data['utilisateurNom']       = $row['utilisateurNom'];
        $data['utilisateurPrenom']    = $row['utilisateurPrenom'];
        $data['utilisateurEmail']     = $row['utilisateurEmail'];
        $data['utilisateurTelephone'] = $row['utilisateurTelephone'];

        return $data;
    }

    public function findByUtilisateurId(int $utilisateurId): array
    {
        $stmt = $this->pdo->prepare("
        SELECT c.*, m.titre AS menuTitre
        FROM commande c
        LEFT JOIN menu m ON c.menu_id = m.id
        WHERE c.utilisateur_id = :utilisateur_id
        ORDER BY c.date_commande DESC
    ");
        $stmt->execute(['utilisateur_id' => $utilisateurId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(function($row) {
            $commande = Commande::createAndHydrate($row)->toArray();
            $commande['menuTitre'] = $row['menuTitre'];
            return $commande;
        }, $rows);
    }

    public function create(Commande $commande): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO commande (numero_de_commande, date_commande, date_prestation, heure_livraison, adresse_livraison, prix_menu, nombre_personne, prix_livraison, statut, pret_materiel, restitution_materiel, utilisateur_id, menu_id)
            VALUES (:numeroDeCommande, :dateCommande, :datePrestation, :heureLivraison, :adresseLivraison, :prixMenu, :nombrePersonne, :prixLivraison, :statut, :pretMateriel, :restitutionMateriel, :utilisateur_id, :menu_id)
        ");
        $stmt->execute([
            'numeroDeCommande'   => $commande->getNumeroDeCommande(),
            'dateCommande'       => $commande->getDateCommande()?->format('Y-m-d H:i:s'),
            'datePrestation'     => $commande->getDatePrestation()?->format('Y-m-d'),
            'heureLivraison'     => $commande->getHeureLivraison(),
            'adresseLivraison'   => $commande->getAdresseLivraison(),
            'prixMenu'           => $commande->getPrixMenu(),
            'nombrePersonne'         => $commande->getNombrePersonne(),
            'prixLivraison'      => $commande->getPrixLivraison(),
            'statut'             => $commande->getStatut(),
            'pretMateriel'       => $commande->getPretMateriel(),
            'restitutionMateriel'=> $commande->getRestitutionMateriel(),
            'utilisateur_id'     => $commande->getUtilisateurId(),
            'menu_id'            => $commande->getMenuId(),
        ]);
    }

    public function update(Commande $commande): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE commande 
            SET numero_de_commande  = :numeroDeCommande,
                date_commande      = :dateCommande,
                date_prestation    = :datePrestation,
                heure_livraison    = :heureLivraison,
                adresse_livraison   = :adresseLivraison,
                prix_menu          = :prixMenu,
                nombre_personne        = :nombrePersonne,
                prix_livraison     = :prixLivraison,
                statut            = :statut,
                pret_materiel      = :pretMateriel,
                restitution_materiel = :restitutionMateriel,
                menu_id           = :menu_id
            WHERE id = :id
        ");
        $stmt->execute([
            'numeroDeCommande'   => $commande->getNumeroDeCommande(),
            'dateCommande'       => $commande->getDateCommande()?->format('Y-m-d H:i:s'),
            'datePrestation'     => $commande->getDatePrestation()?->format('Y-m-d'),
            'heureLivraison'     => $commande->getHeureLivraison(),
            'adresseLivraison'   => $commande->getAdresseLivraison(),
            'prixMenu'           => $commande->getPrixMenu(),
            'nombrePersonne'         => $commande->getNombrePersonne(),
            'prixLivraison'      => $commande->getPrixLivraison(),
            'statut'             => $commande->getStatut(),
            'pretMateriel'       => $commande->getPretMateriel(),
            'restitutionMateriel'=> $commande->getRestitutionMateriel(),
            'menu_id'            => $commande->getMenuId(),
            'id'                 => $commande->getId(),
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM commande WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }
}