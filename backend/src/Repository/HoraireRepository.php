<?php

namespace App\Repository;

use App\Core\Repository;
use App\Models\Horaire;
use PDO;

class HoraireRepository extends Repository
{
    public function create(Horaire $horaire): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO horaire (jour, heure_ouverture, heure_fermeture, statut)
            VALUES (:jour, :heureOuverture, :heureFermeture, :statut)
        ");
        $stmt->execute([
            'jour'           => $horaire->getJour(),
            'heureOuverture' => $horaire->getHeureOuverture(),
            'heureFermeture' => $horaire->getHeureFermeture(),
            'statut'         => $horaire->getStatut()
        ]);
    }

    public function findAll(): array
    {
        $query = $this->pdo->prepare("SELECT * FROM horaire");
        $query->execute();
        $rows = $query->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($row) => Horaire::createAndHydrate($row)->toArray(), $rows);
    }

    public function findById(int $id): ?array
    {
        $query = $this->pdo->prepare("SELECT * FROM horaire WHERE id = :id");
        $query->bindValue(':id', $id, PDO::PARAM_INT);
        $query->execute();
        $row = $query->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;

        return Horaire::createAndHydrate($row)->toArray();
    }

    public function findByJour(string $jour): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM horaire WHERE jour = :jour");
        $stmt->execute(['jour' => $jour]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;

        return Horaire::createAndHydrate($row)->toArray();
    }

    public function update(Horaire $horaire): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE horaire 
            SET jour = :jour,
                heure_ouverture = :heureOuverture,
                heure_fermeture = :heureFermeture,
                statut = :statut
            WHERE id = :id
        ");
        $stmt->execute([
            'jour'           => $horaire->getJour(),
            'heureOuverture' => $horaire->getHeureOuverture(),
            'heureFermeture' => $horaire->getHeureFermeture(),
            'statut'         => $horaire->getStatut(),
            'id'             => $horaire->getId(),
        ]);
        error_log('rows affected: ' . $stmt->rowCount());
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM horaire WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }
}