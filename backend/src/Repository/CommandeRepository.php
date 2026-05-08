<?php

namespace App\Repository;

use App\Core\Repository;
use App\Models\Commande;

class CommandeRepository extends Repository
{
    public function findAll(): array
    {
        $query = $this->pdo->prepare("SELECT * FROM `commande`");
        $query->execute();

        $commandes = $query->fetchAll(PDO::FETCH_ASSOC);

        $commandesArray = [];
        if ($commandes) {
            foreach ($commandes as $commandeArray) {
                $commande = Commande::createAndHydrate($commandeArray);
                $commandesArray[] = $commande->toArray();
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
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM utilisateur WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;

        return Utilisateur::createAndHydrate($row)->toArray();
    }
    public function create(Utilisateur $utilisateur): void
    {
        $stmt = $this->pdo->prepare("
        INSERT INTO utilisateur (email, password, nom, prenom, telephone, adresse, ville, pays, statut, role_id)
        VALUES (:email, :password, :nom, :prenom, :telephone, :adresse, :ville, :pays, :statut, :role_id)
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
            'statut' => $utilisateur->getStatut(),
            'role_id' => $utilisateur->getRoleId()
        ]);
    }
    public function update(Utilisateur $utilisateur): void
    {
        $stmt = $this->pdo->prepare("
        UPDATE utilisateur 
        SET nom = :nom,
            prenom = :prenom,
            email = :email,
            telephone = :telephone,
            adresse = :adresse,
            ville = :ville,
            pays = :pays,
            statut = :statut
        WHERE id = :id
    ");

        $stmt->execute([
            'nom'       => $utilisateur->getNom(),
            'prenom'    => $utilisateur->getPrenom(),
            'email'     => $utilisateur->getEmail(),
            'telephone' => $utilisateur->getTelephone(),
            'adresse'   => $utilisateur->getAdresse(),
            'ville'     => $utilisateur->getVille(),
            'pays'      => $utilisateur->getPays(),
            'statut'    => $utilisateur->getStatut(),
            'id'        => $utilisateur->getId(),
        ]);
    }
    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM utilisateur WHERE id = :id");
        $stmt->execute(['id' => $id]);

    }
}