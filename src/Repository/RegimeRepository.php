<?php

namespace App\Repository;

use App\Core\Repository;
use App\Models\Regime;
use PDO;

class RegimeRepository extends Repository implements LibelleRepository
{
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM regime WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;

        return Regime::createAndHydrate($row)->toArray();
    }

    public function findByLibelle(string $libelle): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM regime WHERE libelle = :libelle");
        $stmt->execute(['libelle' => $libelle]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;

        return Regime::createAndHydrate($row)->toArray();
    }

    public function findAll(): array
    {
        $query = $this->pdo->prepare("SELECT * FROM regime");
        $query->execute();
        $rows = $query->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($row) => Regime::createAndHydrate($row)->toArray(), $rows);
    }

    public function create(Regime $regime): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO regime (libelle) VALUES (:libelle)");
        $stmt->execute(['libelle' => $regime->getLibelle()]);
    }

    public function update(Regime $regime): void
    {
        $stmt = $this->pdo->prepare("UPDATE regime SET libelle = :libelle WHERE id = :id");
        $stmt->execute([
            'libelle' => $regime->getLibelle(),
            'id'      => $regime->getId()
        ]);
    }
    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM regime WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }
}