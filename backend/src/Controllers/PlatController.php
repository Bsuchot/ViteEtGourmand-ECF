<?php

namespace App\Controllers;

use App\Core\AbstractController;
use App\Core\Security\Validator\PlatValidator;
use App\Models\Plat;
use App\Repository\PlatRepository;

class PlatController extends AbstractController
{
    private PlatValidator $validator;
    private PlatRepository $repository;

    public function __construct()
    {
        $this->repository = new PlatRepository();
        $this->validator  = new PlatValidator();
    }

    public function create(): void
    {
        if (!$this->requireAdminOrEmploye()) return;

        $this->tryCatch(function () {
            $data = json_decode(file_get_contents("php://input"), true);
            if (!$data) { $this->error('Données invalides', 400); return; }

            $errors = $this->validator->validate($data);
            if ($errors) { $this->error($errors, 422); return; }

            $plat = new Plat();
            $plat->setTitre($data['titre']);
            $plat->setCategory($data['category']);
            $plat->setPhoto($data['photo']);
            foreach ($data['allergenes'] ?? [] as $allergeneId) {
                $plat->addAllergeneId((int) $allergeneId);
            }
            $this->repository->create($plat);

            $this->success(['message' => 'Plat créé avec succès'], 201);
        });
    }

    public function read(int $id): void
    {
        $this->tryCatch(function () use ($id) {
            $plat = $this->repository->findById($id);
            if (!$plat) { $this->error('Plat introuvable', 404); return; }

            $this->success($plat);
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
            $plat = $this->repository->findById($id);
            if (!$plat) { $this->error('Plat introuvable', 404); return; }

            $data = json_decode(file_get_contents("php://input"), true);
            if (!$data) { $this->error('Données invalides', 400); return; }

            $errors = $this->validator->validateUpdate($data);
            if ($errors) { $this->error($errors, 422); return; }

            $platModel = Plat::createAndHydrate($plat);
            if(isset($data['titre']))  $platModel->setTitre($data['titre']);
            if(isset($data['category'])) $platModel->setCategory($data['category']);
            if(isset($data['photo'])) $platModel->setPhoto($data['photo']);
            if(isset($data['allergenes'])) {
                $platModel->setAllergenes([]);
                foreach ($data['allergenes'] as $allergeneId) {
                    $platModel->addAllergeneId((int) $allergeneId);
                }
            }
            $this->repository->update($platModel);

            $this->success(['message' => 'Plat mis à jour avec succès']);
        });
    }

    public function delete(int $id): void
    {
        if (!$this->requireAdminOrEmploye()) return;

        $this->tryCatch(function () use ($id) {
            $plat = $this->repository->findById($id);
            if (!$plat) { $this->error('Plat introuvable', 404); return; }

            $this->repository->delete($id);
            $this->success(['message' => 'Plat supprimé avec succès']);
        });
    }
}