<?php

namespace App\Repository;

use App\Core\Repository;
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
            JOIN menu_plat pa ON p.id = mp.plat_id
            WHERE mp.menu_id = :menu_id
        ");
        $stmt->execute(['menu_id' => $id]);
        $plats = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($plats as $platRow) {
            /** @var Plat $plat */
            $menu->addPlat(Plat::createAndHydrate($platRow));
        }

        return $menu->toArray();
    }


    public function findAll(): array
    {
        $query = $this->pdo->prepare("SELECT * FROM menu");
        $query->execute();
        $rows = $query->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn($row) => Menu::createAndHydrate($row)->toArray(), $rows);
    }

    public function create(Menu $menu): void
    {

        $stmt = $this->pdo->prepare("
            INSERT INTO menu (titre, nbPersMin, prixParPers, description, quantiteRestante, regimeId, themeId, image)
            VALUES (:titre, :nbPersMin, :prixParPers, :description, :quantiteRestante, :regimeId, :themeId, :image)");
        $stmt->execute([
            'titre' => $menu->getTitre(),
            'nbPersMin' => $menu->getNbPersMin(),
            'prixParPers' => $menu->getPrixParPers(),
            'description' => $menu->getDescription(),
            'quantiteRestante' => $menu->getQuantiteRestante(),
            'regimeId' => $menu->getRegimeId(),
            'themeId' => $menu->getThemeId(),
            'image' => $menu->getImage()
        ]);

        $menuId = (int) $this->pdo->lastInsertId();
        foreach ($menu->getPlats() as $plat) {
            $stmt = $this->pdo->prepare("
            INSERT INTO menu_plat (menu_id, plat_id)
            VALUES (:menu_id, :plat_id)
        ");
            $stmt->execute([
                'menu_id'     => $menuId,
                'plat_id' => $plat['id']
            ]);
        }
    }

    public function update(Menu $menu): void
    {
        $stmt = $this->pdo->prepare("
        UPDATE menu
        SET titre    = :titre,
            nbPersMin = :nbPersMin,
            prixParPers    = :prixParPers,
            description = :description,
            quantiteRestante = :quantiteRestante,
            regimeId    = :regimeId,
            themeId     = :themeId,
            image       = :image
        WHERE id = :id
    ");
        $stmt->execute([
            'titre'    => $menu->getTitre(),
            'nbPersMin' => $menu->getNbPersMin(),
            'prixParPers' => $menu->getPrixParPers(),
            'description' => $menu->getDescription(),
            'quantiteRestante' => $menu->getQuantiteRestante(),
            'regimeId' => $menu->getRegimeId(),
            'themeId' => $menu->getThemeId(),
            'image' => $menu->getImage(),
            'id' => $menu->getId()
        ]);

        $stmt = $this->pdo->prepare("DELETE FROM menu_plat WHERE menu_id = :menu_id");
        $stmt->execute(['menu_id' => $menu->getId()]);

        foreach ($menu->getPlats() as $plat) {
            $stmt = $this->pdo->prepare("
            INSERT INTO menu_plat (menu_id, plat_id)
            VALUES (:menu_id, :plat_id)
        ");
            $stmt->execute([
                'menu_id'      => $menu->getId(),
                'plat_id' => $plat['id']
            ]);
        }
    }
    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM menu WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }
}