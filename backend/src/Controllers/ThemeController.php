<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Repository\ThemeRepository;
use App\Models\Theme;


class ThemeController extends Controller
{
    public function create(): void
    {
        if (!$this->requireAdminOrEmploye()) return;

        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data) {
            $this->error('Données invalides', 400);
            return;
        }

        $repository = new ThemeRepository();
        $existing = $repository->findByLibelle($data['libelle']);
        if ($existing) {
            $this->error('Ce thème existe déjà', 409);
            return;
        }

        $theme = new Theme();
        $theme->setLibelle($data['libelle']);
        $repository->create($theme);

        $this->success(['message' => 'Thème créé avec succès'], 201);
    }

    public function read(): void
    {
    }

    public function update(): void
    {
        if (!$this->requireAdminOrEmploye()) return;
    }

    public function delete(int $id): void
    {
        if (!$this->requireAdminOrEmploye()) return;

        $repository = new ThemeRepository();
        $theme = $repository->findById($id);

        if (!$theme) {
            $this->error('Thème introuvable', 404);
            return;
        }

        $repository->delete($id);
        $this->success(['message' => 'Thème supprimé avec succès']);

    }
}