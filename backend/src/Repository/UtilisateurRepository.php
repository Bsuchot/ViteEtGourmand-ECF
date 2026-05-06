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
    public function updatePassword(Utilisateur $utilisateur): void
    {
        $stmt = $this->pdo->prepare("
        UPDATE utilisateur SET password = :password WHERE id = :id
    ");

        $stmt->execute([
            'password' => $utilisateur->getPassword(),
            'id'       => $utilisateur->getId(),
        ]);
    }
    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM utilisateur WHERE id = :id");
        $stmt->execute(['id' => $id]);

    }

    public function findEmployeById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
        SELECT u.* FROM utilisateur u
        JOIN role r ON u.role_id = r.id
        WHERE u.id = :id AND r.libelle = 'ROLE_EMPLOYE'
    ");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;

        return Utilisateur::createAndHydrate($row)->toArray();
    }
    public function findAllEmployes(): array
    {
        $stmt = $this->pdo->prepare("
        SELECT u.* FROM utilisateur u
        JOIN role r ON u.role_id = r.id
        WHERE r.libelle = 'ROLE_EMPLOYE'
    ");
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($row) => Utilisateur::createAndHydrate($row)->toArray(), $rows);
    }
}