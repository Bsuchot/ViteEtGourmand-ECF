<?php

namespace App\Repository;

use App\Core\Repository;
use App\Models\Allergene;
use PDO;

class AllergeneRepository extends Repository
{
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM allergene WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;

        return Allergene::createAndHydrate($row)->toArray();
    }

    public function findByLibelle(string $libelle): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM allergene WHERE libelle = :libelle");
        $stmt->execute(['libelle' => $libelle]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;

        return Allergene::createAndHydrate($row)->toArray();
    }

    public function findAll(): array
    {
        $query = $this->pdo->prepare("SELECT * FROM allergene");
        $query->execute();
        $rows = $query->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($row) => Allergene::createAndHydrate($row)->toArray(), $rows);
    }

    public function create(Allergene $allergene): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO allergene (libelle) VALUES (:libelle)");
        $stmt->execute(['libelle' => $allergene->getLibelle()]);
    }

    public function update(Allergene $allergene): void
    {
        $stmt = $this->pdo->prepare("UPDATE allergene SET libelle = :libelle WHERE id = :id");
        $stmt->execute([
            'libelle' => $allergene->getLibelle(),
            'id'      => $allergene->getId()
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM allergene WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }
}