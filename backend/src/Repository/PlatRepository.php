<?php

namespace App\Repository;

use App\Core\Repository;
use App\Models\Allergene;
use App\Models\Plat;
use PDO;

class PlatRepository extends Repository
{
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM plat WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;

        $plat = Plat::createAndHydrate($row);

        $stmt = $this->pdo->prepare("
            SELECT a.* FROM allergene a
            JOIN plat_allergene pa ON a.id = pa.allergene_id
            WHERE pa.plat_id = :plat_id
        ");
        $stmt->execute(['plat_id' => $id]);
        $allergenes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($allergenes as $allergeneRow) {
            /** @var Allergene $allergene */
            $plat->addAllergene(Allergene::createAndHydrate($allergeneRow));
        }

        return $plat->toArray();
    }


    public function findAll(): array
    {
        $stmt = $this->pdo->prepare("
        SELECT p.*,
               GROUP_CONCAT(a.libelle SEPARATOR ', ') AS allergenesLibelle
        FROM plat p
        LEFT JOIN plat_allergene pa ON p.id = pa.plat_id
        LEFT JOIN allergene a ON pa.allergene_id = a.id
        GROUP BY p.id
        ORDER BY p.id DESC
    ");
        $stmt->execute();

        return array_map(function($row) {
            $plat = Plat::createAndHydrate($row)->toArray();
            $plat['allergenesLibelle'] = $row['allergenesLibelle'] ?? null;
            return $plat;
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function create(Plat $plat): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO plat (titre, category, photo)
            VALUES (:titre, :category, :photo)");
        $stmt->execute([
            'titre' => $plat->getTitre(),
            'category' => $plat->getCategory(),
            'photo' => $plat->getPhoto(),
        ]);

        $platId = (int) $this->pdo->lastInsertId();
        foreach ($plat->getAllergenes() as $allergene) {
            $stmt = $this->pdo->prepare("
            INSERT INTO plat_allergene (plat_id, allergene_id)
            VALUES (:plat_id, :allergene_id)
        ");
            $stmt->execute([
                'plat_id'     => $platId,
                'allergene_id' => $allergene['id']
            ]);
        }
    }

    public function update(Plat $plat): void
    {
        $stmt = $this->pdo->prepare("
        UPDATE plat
        SET titre    = :titre,
            category = :category,
            photo    = :photo
        WHERE id = :id
    ");
        $stmt->execute([
            'titre'    => $plat->getTitre(),
            'category' => $plat->getCategory(),
            'photo'    => $plat->getPhoto(),
            'id'       => $plat->getId()
        ]);

        $stmt = $this->pdo->prepare("DELETE FROM plat_allergene WHERE plat_id = :plat_id");
        $stmt->execute(['plat_id' => $plat->getId()]);

        foreach ($plat->getAllergenes() as $allergene) {
            $stmt = $this->pdo->prepare("
            INSERT INTO plat_allergene (plat_id, allergene_id)
            VALUES (:plat_id, :allergene_id)
        ");
            $stmt->execute([
                'plat_id'      => $plat->getId(),
                'allergene_id' => $allergene['id']
            ]);
        }
    }
    public function delete(int $id): void
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("DELETE FROM plat_allergene WHERE plat_id = :id");
            $stmt->execute(['id' => $id]);

            $stmt = $this->pdo->prepare("DELETE FROM plat WHERE id = :id");
            $stmt->execute(['id' => $id]);

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}