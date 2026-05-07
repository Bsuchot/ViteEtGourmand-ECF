<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Allergene;
use App\Repository\AllergeneRepository;

class AllergeneController extends Controller
{
    public function create(): void
    {
        if (!$this->requireAdminOrEmploye()) return;

        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data || !isset($data['libelle'])) {
            $this->error('Données manquantes', 400);
            return;
        }

        $repository = new AllergeneRepository();
        $existing = $repository->findByLibelle($data['libelle']);
        if ($existing) {
            $this->error('Cet allergène existe déjà', 409);
            return;
        }

        $allergene = new Allergene();
        $allergene->setLibelle($data['libelle']);
        $repository->create($allergene);

        $this->success(['message' => 'Allergène créé avec succès'], 201);
    }

    public function read(int $id): void
    {
        $repository = new AllergeneRepository();
        $allergene = $repository->findById($id);

        if (!$allergene) {
            $this->error('Allergène introuvable', 404);
            return;
        }

        $this->success($allergene);
    }

    public function readAll(): void
    {
        $repository = new AllergeneRepository();
        $allergenes = $repository->findAll();

        $this->success($allergenes);
    }

    public function update(int $id): void
    {
        if (!$this->requireAdminOrEmploye()) return;

        $repository = new AllergeneRepository();
        $allergene = $repository->findById($id);

        if (!$allergene) {
            $this->error('Allergène introuvable', 404);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data || !isset($data['libelle'])) {
            $this->error('Données manquantes', 400);
            return;
        }

        $existing = $repository->findByLibelle($data['libelle']);
        if ($existing && $existing['id'] !== $id) {
            $this->error('Cet allergène existe déjà', 409);
            return;
        }

        $allergeneModel = Allergene::createAndHydrate($allergene);
        $allergeneModel->setLibelle($data['libelle']);
        $repository->update($allergeneModel);

        $this->success(['message' => 'Allergène mis à jour avec succès']);
    }

    public function delete(int $id): void
    {
        if (!$this->requireAdminOrEmploye()) return;

        $repository = new AllergeneRepository();
        $allergene = $repository->findById($id);

        if (!$allergene) {
            $this->error('Allergène introuvable', 404);
            return;
        }

        $repository->delete($id);
        $this->success(['message' => 'Allergène supprimé avec succès']);
    }
}