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

        $utilisateurs = $query->fetchAll(PDO::FETCH_ASSOC);

        $utilisateursArray = [];
        if ($utilisateurs) {
            foreach ($utilisateurs as $utilisateurArray) {
                // On hydrate le modèle puis on convertit directement en tableau
                $utilisateur = Utilisateur::createAndHydrate($utilisateurArray);
                $utilisateursArray[] = $utilisateur->toArray();
            }
        }

        return $utilisateursArray;
    }
    public function findById(int $id): ?array
    {
        $query = $this->pdo->prepare("SELECT * FROM `utilisateur` WHERE `id` = :id");
        $query->bindValue(':id', $id, PDO::PARAM_INT);
        $query->execute();

        $utilisateurArray = $query->fetch(PDO::FETCH_ASSOC);

        if (!$utilisateurArray) {
            return null;
        }

        $utilisateur = Utilisateur::createAndHydrate($utilisateurArray);

        return $utilisateur->toArray();
    }
    public function findByEmail(string $email)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM utilisateur WHERE email = :email");
        $stmt->execute(['email' => $email]);

        return $stmt->fetch();
    }
    public function create(Utilisateur $utilisateur)
    {
        $stmt = $this->pdo->prepare("
        INSERT INTO utilisateur (email, password, nom, prenom, telephone, adresse, ville, pays, role_id)
        VALUES (:email, :password, :nom, :prenom, :telephone, :adresse, :ville, :pays, :role_id)
    ");

        $stmt->execute([
            'email' => $utilisateur->getEmail(),
            'password' => $utilisateur->getPassword(),
            'nom' => $utilisateur->getNom(),
            'prenom' => $utilisateur->getPrenom(),
            'telephone' => $utilisateur->getTelephone(),
            'adresse' => $utilisateur->getAdresse(),
            'ville' => $utilisateur->getVille(),
            'pays' => $utilisateur->getPays(),
            'role_id' => $utilisateur->getRoleId()
        ]);
    }
    public function update(Utilisateur $utilisateur): void
    {
        $stmt = $this->pdo->prepare("
        UPDATE utilisateur 
        SET nom = :nom,
            prenom = :prenom,
            telephone = :telephone,
            adresse = :adresse,
            ville = :ville,
            pays = :pays
        WHERE id = :id
    ");

        $stmt->execute([
            'nom'       => $utilisateur->getNom(),
            'prenom'    => $utilisateur->getPrenom(),
            'telephone' => $utilisateur->getTelephone(),
            'adresse'   => $utilisateur->getAdresse(),
            'ville'     => $utilisateur->getVille(),
            'pays'      => $utilisateur->getPays(),
            'id'        => $utilisateur->getId(),
        ]);
    }
}