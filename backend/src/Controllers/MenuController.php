<?php

namespace App\Controllers;

use App\Core\AbstractController;
use App\Core\Security\Validator\MenuValidator;
use App\Models\Menu;
use App\Repository\MenuRepository;

class MenuController extends AbstractController
{
    private MenuRepository $repository;
    private MenuValidator $validator;

    public function __construct()
    {
        $this->repository = new MenuRepository();
        $this->validator  = new MenuValidator();
    }

    public function create(): void
    {
        if (!$this->requireAdminOrEmploye()) return;

        $this->tryCatch(function () {
            $data = json_decode(file_get_contents("php://input"), true);
            if (!$data) { $this->error('Données invalides', 400); return; }

            $errors = $this->validator->validate($data);
            if ($errors) { $this->error($errors, 422); return; }

            $menu = new Menu();
            $menu->setTitre($data['titre']);
            $menu->setNombrePersonneMinimum($data['nombrePersonneMinimum']);
            $menu->setPrixParPersonne($data['prixParPersonne']);
            $menu->setDescription($data['description']);
            $menu->setQuantiteRestante($data['quantiteRestante'] ?? 0);
            $menu->setRegimeId($data['regimeId']);
            $menu->setThemeId($data['themeId']);
            $menu->setImage($data['image'] ?? null);

            foreach ($data['plats'] as $platId) {
                $menu->addPlatId((int)$platId);
            }

            $this->repository->create($menu);
            $this->success(['message' => 'Menu créé avec succès'], 201);
        });
    }

    public function read(int $id): void
    {
        $this->tryCatch(function () use ($id) {
            $menu = $this->repository->findById($id);
            if (!$menu) { $this->error('Menu introuvable', 404); return; }

            $this->success($menu);
        });
    }

    public function readAll(): void
    {
        $this->tryCatch(fn () => $this->success($this->repository->findAll()));
    }

    public function update(int $id): void
    {
        if (!$this->requireAdminOrEmploye()) return;

        $this->tryCatch(function () use ($id) {
            $menu = $this->repository->findById($id);
            if (!$menu) { $this->error('Menu introuvable', 404); return; }

            $data = json_decode(file_get_contents("php://input"), true);
            if (!$data) { $this->error('Données invalides', 400); return; }

            $errors = $this->validator->validateUpdate($data);
            if ($errors) { $this->error($errors, 422); return; }

            $menuModel = Menu::createAndHydrate($menu);
            if (isset($data['titre']))            $menuModel->setTitre($data['titre']);
            if (isset($data['nombrePersonneMinimum']))        $menuModel->setNombrePersonneMinimum($data['nombrePersonneMinimum']);
            if (isset($data['prixParPersonne']))      $menuModel->setPrixParPersonne($data['prixParPersonne']);
            if (isset($data['description']))      $menuModel->setDescription($data['description']);
            if (isset($data['quantiteRestante'])) $menuModel->setQuantiteRestante($data['quantiteRestante']);
            if (isset($data['regimeId']))         $menuModel->setRegimeId($data['regimeId']);
            if (isset($data['themeId']))          $menuModel->setThemeId($data['themeId']);
            if (isset($data['image']))            $menuModel->setImage($data['image']);
            if (isset($data['plats'])) {
                $menuModel->setPlats([]);
                foreach ($data['plats'] as $platId) {
                    $menuModel->addPlatId((int)$platId);
                }
            }

            $this->repository->update($menuModel);
            $this->success(['message' => 'Menu mis à jour avec succès']);
        });
    }

    public function delete(int $id): void
    {
        if (!$this->requireAdminOrEmploye()) return;

        $this->tryCatch(function () use ($id) {
            $menu = $this->repository->findById($id);
            if (!$menu) { $this->error('Menu introuvable', 404); return; }

            $this->repository->delete($id);
            $this->success(['message' => 'Menu supprimé avec succès']);
        });
    }
}