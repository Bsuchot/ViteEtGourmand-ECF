<?php

namespace App\Repository;

use App\Core\Repository;
use App\Models\Commande;
use PDO;

class CommandeRepository extends Repository
{
    public function findAll(): array
    {
        $query = $this->pdo->prepare("SELECT * FROM commande");
        $query->execute();
        $rows = $query->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($row) => Commande::createAndHydrate($row)->toArray(), $rows);
    }

    public function findById(int $id): ?array
    {
        $query = $this->pdo->prepare("SELECT * FROM commande WHERE id = :id");
        $query->bindValue(':id', $id, PDO::PARAM_INT);
        $query->execute();
        $row = $query->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;

        return Commande::createAndHydrate($row)->toArray();
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