<?php

namespace App\Controllers;

use App\Core\AbstractController;
use App\Core\Security\Validator\LibelleValidator;
use App\Models\Regime;
use App\Repository\RegimeRepository;

class RegimeController extends AbstractController
{
    private LibelleValidator $validator;
    private RegimeRepository $repository;

    public function __construct()
    {
        $this->repository = new RegimeRepository();
        $this->validator  = new LibelleValidator($this->repository, 'Ce régime');
    }

    public function create(): void
    {
        if (!$this->requireAdminOrEmploye()) return;

        $this->tryCatch(function () {
            $data = json_decode(file_get_contents("php://input"), true);
            if (!$data) { $this->error('Données invalides', 400); return; }

            $errors = $this->validator->validate($data);
            if ($errors) { $this->error($errors, 422); return; }

            $regime = new Regime();
            $regime->setLibelle($data['libelle']);
            $this->repository->create($regime);

            $this->success(['message' => 'Régime créé avec succès'], 201);
        });
    }

    public function read(int $id): void
    {
        $this->tryCatch(function () use ($id) {
            $regime = $this->repository->findById($id);
            if (!$regime) { $this->error('Régime introuvable', 404); return; }

            $this->success($regime);
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
            $regime = $this->repository->findById($id);
            if (!$regime) { $this->error('Régime introuvable', 404); return; }

            $data = json_decode(file_get_contents("php://input"), true);
            if (!$data) { $this->error('Données invalides', 400); return; }

            $errors = $this->validator->validateUpdate($data, $id);
            if ($errors) { $this->error($errors, 422); return; }

            $regimeModel = Regime::createAndHydrate($regime);
            $regimeModel->setLibelle($data['libelle']);
            $this->repository->update($regimeModel);

            $this->success(['message' => 'Régime mis à jour avec succès']);
        });
    }

    public function delete(int $id): void
    {
        if (!$this->requireAdminOrEmploye()) return;

        $this->tryCatch(function () use ($id) {
            $regime = $this->repository->findById($id);
            if (!$regime) { $this->error('Régime introuvable', 404); return; }

            $this->repository->delete($id);
            $this->success(['message' => 'Régime supprimé avec succès']);
        });
    }
}