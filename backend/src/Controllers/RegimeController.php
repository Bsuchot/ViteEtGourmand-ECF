<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Regime;
use App\Repository\RegimeRepository;

class RegimeController extends Controller
{
    public function create(): void
    {
        if (!$this->requireAdminOrEmploye()) return;

        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data || !isset($data['libelle'])) {
            $this->error('Données manquantes', 400);
            return;
        }

        $repository = new RegimeRepository();
        $existing = $repository->findByLibelle($data['libelle']);
        if ($existing) {
            $this->error('Ce régime existe déjà', 409);
            return;
        }

        $regime = new Regime();
        $regime->setLibelle($data['libelle']);
        $repository->create($regime);

        $this->success(['message' => 'Régime créé avec succès'], 201);
    }

    public function read(int $id): void
    {
        $repository = new RegimeRepository();
        $regime = $repository->findById($id);

        if (!$regime) {
            $this->error('Régime introuvable', 404);
            return;
        }

        $this->success($regime);
    }

    public function readAll(): void
    {
        $repository = new RegimeRepository();
        $regime = $repository->findAll();

        $this->success($regime);
    }

    public function update(int $id): void
    {
        if (!$this->requireAdminOrEmploye()) return;

        $repository = new RegimeRepository();
        $regime = $repository->findById($id);

        if (!$regime) {
            $this->error('Régime introuvable', 404);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data || !isset($data['libelle'])) {
            $this->error('Données manquantes', 400);
            return;
        }

        $existing = $repository->findByLibelle($data['libelle']);
        if ($existing && $existing['id'] !== $id) {
            $this->error('Ce thème existe déjà', 409);
            return;
        }

        $regimeModel = Regime::createAndHydrate($regime);
        $regimeModel->setLibelle($data['libelle']);
        $repository->update($regimeModel);

        $this->success(['message' => 'Régime mis à jour avec succès']);
    }

    public function delete(int $id): void
    {
        if (!$this->requireAdminOrEmploye()) return;

        $repository = new RegimeRepository();
        $regime = $repository->findById($id);

        if (!$regime) {
            $this->error('Régime introuvable', 404);
            return;
        }

        $repository->delete($id);
        $this->success(['message' => 'Régime supprimé avec succès']);
    }
}