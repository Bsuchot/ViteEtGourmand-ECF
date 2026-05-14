<?php

namespace App\Controllers;

use App\Core\AbstractController;
use App\Core\Security\Validator\LibelleValidator;
use App\Models\Theme;
use App\Repository\ThemeRepository;

class ThemeController extends AbstractController
{
    private LibelleValidator $validator;
    private ThemeRepository $repository;

    public function __construct()
    {
        $this->repository = new ThemeRepository();
        $this->validator  = new LibelleValidator($this->repository, 'Ce thème');
    }

    public function create(): void
    {
        if (!$this->requireAdminOrEmploye()) return;

        $this->tryCatch(function () {
            $data = json_decode(file_get_contents("php://input"), true);
            if (!$data) { $this->error('Données invalides', 400); return; }

            $errors = $this->validator->validate($data);
            if ($errors) { $this->error($errors, 422); return; }

            $theme = new Theme();
            $theme->setLibelle($data['libelle']);
            $this->repository->create($theme);

            $this->success(['message' => 'Thème créé avec succès'], 201);
        });
    }

    public function read(int $id): void
    {
        $this->tryCatch(function () use ($id) {
            $theme = $this->repository->findById($id);
            if (!$theme) { $this->error('Thème introuvable', 404); return; }

            $this->success($theme);
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
            $theme = $this->repository->findById($id);
            if (!$theme) { $this->error('Thème introuvable', 404); return; }

            $data = json_decode(file_get_contents("php://input"), true);
            if (!$data) { $this->error('Données invalides', 400); return; }

            $errors = $this->validator->validateUpdate($data, $id);
            if ($errors) { $this->error($errors, 422); return; }

            $themeModel = Theme::createAndHydrate($theme);
            $themeModel->setLibelle($data['libelle']);
            $this->repository->update($themeModel);

            $this->success(['message' => 'Thème mis à jour avec succès']);
        });
    }

    public function delete(int $id): void
    {
        if (!$this->requireAdminOrEmploye()) return;

        $this->tryCatch(function () use ($id) {
            $theme = $this->repository->findById($id);
            if (!$theme) { $this->error('Thème introuvable', 404); return; }

            $this->repository->delete($id);
            $this->success(['message' => 'Thème supprimé avec succès']);
        });
    }
}