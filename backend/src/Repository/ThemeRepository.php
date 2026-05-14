<?php

namespace App\Repository;

use App\Core\Repository;
use App\Models\Theme;
use PDO;

class ThemeRepository extends Repository implements LibelleRepository
{
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM theme WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;

        return Theme::createAndHydrate($row)->toArray();
    }

    public function findByLibelle(string $libelle): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM theme WHERE libelle = :libelle");
        $stmt->execute(['libelle' => $libelle]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;

        return Theme::createAndHydrate($row)->toArray();
    }

    public function findAll(): array
    {
        $query = $this->pdo->prepare("SELECT * FROM theme");
        $query->execute();
        $rows = $query->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($row) => Theme::createAndHydrate($row)->toArray(), $rows);
    }

    public function create(Theme $theme): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO theme (libelle) VALUES (:libelle)");
        $stmt->execute(['libelle' => $theme->getLibelle()]);
    }

    public function update(Theme $theme): void
    {
        $stmt = $this->pdo->prepare("UPDATE theme SET libelle = :libelle WHERE id = :id");
        $stmt->execute([
            'libelle' => $theme->getLibelle(),
            'id'      => $theme->getId()
        ]);
    }
    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM theme WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }
}