<?php

namespace App\Repository;

use App\Core\Repository;
use App\Models\Avis;
use PDO;

class AvisRepository extends Repository
{
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM avis WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;

        return Avis::createAndHydrate($row)->toArray();
    }


    public function findAll(): array
    {
        $query = $this->pdo->prepare("SELECT * FROM avis");
        $query->execute();
        $rows = $query->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($row) => Avis::createAndHydrate($row)->toArray(), $rows);
    }

    public function create(Avis $avis): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO avis (titre, description, note, statut, date, utilisateurId)
            VALUES (:titre, :description, :note, :statut, :date, :utilisateurId)");
        $stmt->execute([
            'titre' => $avis->getTitre(),
            'description' => $avis->getDescription(),
            'note' => $avis->getNote(),
            'statut' => $avis->getStatut(),
            'date' => $avis->getDate(),
            'utilisateurId' => $avis->getUtilisateurId()
        ]);
    }

    public function update(Avis $avis): void
    {
        $stmt = $this->pdo->prepare(
            "UPDATE avis SET 
                titre = :titre,
                description = :description,
                note = :note,
                statut = :statut
            WHERE id = :id");
        $stmt->execute([
            'titre' => $avis->getTitre(),
            'description' => $avis->getDescription(),
            'note' => $avis->getNote(),
            'statut' => $avis->getStatut(),
            'id'      => $avis->getId()
        ]);
    }
    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM avis WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }
}