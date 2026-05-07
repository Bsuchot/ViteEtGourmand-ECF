<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Allergene;
use App\Models\Plat;
use App\Repository\PlatRepository;

class PlatController extends Controller
{
    public function create(): void
    {
        if (!$this->requireAdminOrEmploye()) return;

        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data) {
            $this->error('Données invalides', 400);
            return;
        }

        $repository = new PlatRepository();

        $plat = new Plat();
        $plat->setTitre($data['titre']);
        $plat->setCategory($data['category']);
        $plat->setPhoto($data['photo']);
        foreach ($data['allergenes'] as $allergene) {
            $plat->addAllergene(Allergene::createAndHydrate($allergene));
        }
        $repository->create($plat);

        $this->success(['message' => 'Plat créé avec succès'], 201);
    }

    public function read(int $id): void
    {
        $repository = new PlatRepository();
        $plat = $repository->findById($id);

        if (!$plat) {
            $this->error('Plat introuvable', 404);
            return;
        }

        $this->success($plat);
    }

    public function readAll(): void
    {
        $repository = new PlatRepository();
        $plat = $repository->findAll();

        $this->success($plat);
    }
    public function update(int $id): void
    {
        if (!$this->requireAdminOrEmploye()) return;

        $repository = new PlatRepository();
        $plat = $repository->findById($id);

        if (!$plat) {
            $this->error('Plat introuvable', 404);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data) {
            $this->error('Données invalides', 400);
            return;
        }


        $platModel = Plat::createAndHydrate($plat);
        $platModel->setTitre($data['titre']);
        $platModel->setCategory($data['category']);
        $platModel->setPhoto($data['photo']);
        foreach ($data['allergenes'] as $allergene) {
            $platModel->addAllergene(Allergene::createAndHydrate($allergene));
        }
        $repository->update($platModel);

        $this->success(['message' => "Plat mis à jour avec succès"]);
    }


    public function delete(int $id): void
    {
        if (!$this->requireAdminOrEmploye()) return;

        $repository = new PlatRepository();
        $plat = $repository->findById($id);

        if (!$plat) {
            $this->error('Plat introuvable', 404);
            return;
        }

        $repository->delete($id);
        $this->success(['message' => 'Plat supprimé avec succès']);
    }
}