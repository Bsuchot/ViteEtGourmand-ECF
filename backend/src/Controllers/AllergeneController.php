<?php

namespace App\Controllers;

use App\Core\AbstractController;
use App\Core\Security\Validator\LibelleValidator;
use App\Models\Allergene;
use App\Repository\AllergeneRepository;

class AllergeneController extends AbstractController
{
    private LibelleValidator $validator;
    private AllergeneRepository $repository;

    public function __construct()
    {
        $this->repository = new AllergeneRepository();
        $this->validator  = new LibelleValidator($this->repository, 'Cet allergène');
    }

    public function create(): void
    {
        if (!$this->requireAdminOrEmploye()) return;

        $this->tryCatch(function () {
            $data = json_decode(file_get_contents("php://input"), true);
            if (!$data) { $this->error('Données invalides', 400); return; }

            $errors = $this->validator->validate($data);
            if ($errors) { $this->error($errors, 422); return; }

            $allergene = new Allergene();
            $allergene->setLibelle($data['libelle']);
            $this->repository->create($allergene);

            $this->success(['message' => 'Allergène créé avec succès'], 201);
        });
    }

    public function read(int $id): void
    {
        $this->tryCatch(function () use ($id) {
            $allergene = $this->repository->findById($id);
            if (!$allergene) { $this->error('Allergène introuvable', 404); return; }

            $this->success($allergene);
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
            $allergene = $this->repository->findById($id);
            if (!$allergene) { $this->error('Allergène introuvable', 404); return; }

            $data = json_decode(file_get_contents("php://input"), true);
            if (!$data) { $this->error('Données invalides', 400); return; }

            $errors = $this->validator->validateUpdate($data, $id);
            if ($errors) { $this->error($errors, 422); return; }

            $allergeneModel = Allergene::createAndHydrate($allergene);
            $allergeneModel->setLibelle($data['libelle']);
            $this->repository->update($allergeneModel);

            $this->success(['message' => 'Allergène mis à jour avec succès']);
        });
    }

    public function delete(int $id): void
    {
        if (!$this->requireAdminOrEmploye()) return;

        $this->tryCatch(function () use ($id) {
            $allergene = $this->repository->findById($id);
            if (!$allergene) { $this->error('Allergène introuvable', 404); return; }

            $this->repository->delete($id);
            $this->success(['message' => 'Allergène supprimé avec succès']);
        });
    }
}