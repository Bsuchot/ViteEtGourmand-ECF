<?php

namespace App\Repository;

use App\Core\Repository;
use App\Models\Utilisateur;
use PDO;

class UtilisateurRepository extends Repository
{
    public function findAll(): array
    {
        $query = $this->pdo->prepare("SELECT * FROM `utilisateur`");
        $query->execute();

        $utilisateurs = $query->fetchAll($this->pdo::FETCH_ASSOC);

        $utilisateursArray = [];
        if ($utilisateurs) {
            foreach ($utilisateurs as $utilisateurArray) {
                $utilisateursArray[] = Utilisateur::createAndHydrate($utilisateurArray);
            }
        }

        return $utilisateursArray;
    }
    public function findById(int $id): Utilisateur
    {
        $query = $this->pdo->prepare("SELECT * FROM `utilisateur` WHERE `id` = :id");
        $query->bindValue(':id', $id, PDO::PARAM_INT);
        $query->execute();
        $utilisateurArray = $query->fetch($this->pdo::FETCH_ASSOC);

        $utilisateur = Utilisateur::createAndHydrate($utilisateurArray);


        return $utilisateur;

    }
}