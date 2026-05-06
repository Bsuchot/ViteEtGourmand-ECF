<?php

namespace App\Repository;

use App\Core\Repository;
use App\Models\Role;
use PDO;

class RoleRepository extends Repository
{
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM role WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    public function findByLibelle(string $libelle): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM role WHERE libelle = :libelle");
        $stmt->execute(['libelle' => $libelle]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }
        return Role::createAndHydrate($row)->toArray();
    }
}