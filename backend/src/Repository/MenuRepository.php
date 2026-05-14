<?php

namespace App\Repository;

use App\Core\Repository;
use App\Models\Allergene;
use App\Models\Menu;
use App\Models\Plat;
use PDO;

class MenuRepository extends Repository
{
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM menu WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;

        $menu = Menu::createAndHydrate($row);

        $stmt = $this->pdo->prepare("
            SELECT p.* FROM plat p
            JOIN menu_plat mp ON p.id = mp.plat_id
            WHERE mp.menu_id = :menu_id
        ");
        $stmt->execute(['menu_id' => $id]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $platRow) {

            $plat = Plat::createAndHydrate($platRow);

            $allStmt = $this->pdo->prepare("
        SELECT a.*
        FROM allergene a
        JOIN plat_allergene pa
            ON a.id = pa.allergene_id
        WHERE pa.plat_id = :plat_id
    ");

            $allStmt->execute([
                'plat_id' => $plat->getId()
            ]);

            foreach ($allStmt->fetchAll(PDO::FETCH_ASSOC) as $allergeneRow) {
                $plat->addAllergene(
                    Allergene::createAndHydrate($allergeneRow)
                );
            }

            $menu->addPlat($plat);
        }
        return $menu->toArray();
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM menu");
        $stmt->execute();

        return array_map(
            fn($row) => Menu::createAndHydrate($row)->toArray(),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    public function create(Menu $menu): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO menu
                (titre, nombre_personne_minimum, prix_par_personne, description,
                 quantite_restante, regime_id, theme_id, image)
            VALUES
                (:titre, :nombrePersonneMinimum, :prixParPersonne, :description,
                 :quantiteRestante, :regimeId, :themeId, :image)
        ");
        $stmt->execute([
            'titre'             => $menu->getTitre(),
            'nombrePersonneMinimum'         => $menu->getNombrePersonneMinimum(),
            'prixParPersonne'       => $menu->getPrixParPersonne(),
            'description'       => $menu->getDescription(),
            'quantiteRestante'  => $menu->getQuantiteRestante(),
            'regimeId'          => $menu->getRegimeId(),
            'themeId'           => $menu->getThemeId(),
            'image'             => $menu->getImage(),
        ]);

        $menuId = (int) $this->pdo->lastInsertId();
        $this->syncPlats($menuId, $menu->getPlats());
    }

    public function update(Menu $menu): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE menu SET
                titre                   = :titre,
                nombre_personne_minimum = :nombrePersonneMinimum,
                prix_par_personne       = :prixParPersonne,
                description             = :description,
                quantite_restante       = :quantiteRestante,
                regime_id               = :regimeId,
                theme_id                = :themeId,
                image                   = :image
            WHERE id = :id
        ");
        $stmt->execute([
            'titre'                 => $menu->getTitre(),
            'nombrePersonneMinimum' => $menu->getNombrePersonneMinimum(),
            'prixParPersonne'       => $menu->getPrixParPersonne(),
            'description'           => $menu->getDescription(),
            'quantiteRestante'      => $menu->getQuantiteRestante(),
            'regimeId'              => $menu->getRegimeId(),
            'themeId'               => $menu->getThemeId(),
            'image'                 => $menu->getImage(),
            'id'                    => $menu->getId(),
        ]);

        $this->syncPlats($menu->getId(), $menu->getPlats());
    }

    public function delete(int $id): void
    {
        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("DELETE FROM menu_plat WHERE menu_id = :id");
            $stmt->execute(['id' => $id]);

            $stmt = $this->pdo->prepare("DELETE FROM menu WHERE id = :id");
            $stmt->execute(['id' => $id]);

            $this->pdo->commit();
        } catch (\Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    // --- Helpers ---

    private function syncPlats(int $menuId, array $plats): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM menu_plat WHERE menu_id = :menu_id");
        $stmt->execute(['menu_id' => $menuId]);

        $stmt = $this->pdo->prepare("
            INSERT INTO menu_plat (menu_id, plat_id) VALUES (:menu_id, :plat_id)
        ");
        foreach ($plats as $plat) {
            $stmt->execute(['menu_id' => $menuId, 'plat_id' => $plat['id']]);
        }
    }
}