<?php

namespace App\Controllers;

use App\Core\AbstractController;
use App\Core\Security\Validator\AvisValidator;
use App\Models\Avis;
use App\Repository\AvisRepository;

class AvisController extends AbstractController
{
    private AvisValidator $validator;
    private AvisRepository $repository;

    public function __construct()
    {
        $this->repository = new AvisRepository();
        $this->validator  = new AvisValidator();
    }

    public function create(): void
    {
        if (!$this->requireLogin()) return;

        $this->tryCatch(function () {
            $data = json_decode(file_get_contents("php://input"), true);
            if (!$data) { $this->error('Données invalides', 400); return; }

            $errors = $this->validator->validate($data);
            if ($errors) { $this->error($errors, 422); return; }

            $avis = new Avis();
            $avis->setTitre($data['titre']);
            $avis->setDescription($data['description']);
            $avis->setNote($data['note']);
            $avis->setStatut('en_attente');
            $avis->setDate(new \DateTimeImmutable());
            $avis->setUtilisateurId($_SESSION['user']['id']);
            $this->repository->create($avis);

            $this->success(['message' => 'Avis créé avec succès'], 201);
        });
    }

    public function read(int $id): void
    {
        $this->tryCatch(function () use ($id) {
            $avis = $this->repository->findById($id);
            if (!$avis) { $this->error('Avis introuvable', 404); return; }

            $this->success($avis);
        });
    }

    public function readAll(): void
    {
        $this->tryCatch(fn () => $this->success($this->repository->findAll()));
    }

    public function update(int $id): void
    {
        if (!$this->requireLogin()) return;

        $this->tryCatch(function () use ($id) {
            $avis = $this->repository->findById($id);
            if (!$avis) { $this->error('Avis introuvable', 404); return; }
            if (!$this->requireOwner($avis)) return;

            $data = json_decode(file_get_contents("php://input"), true);
            if (!$data) { $this->error('Données invalides', 400); return; }

            $errors = $this->validator->validateUpdate($data);
            if ($errors) { $this->error($errors, 422); return; }

            $avisModel = Avis::createAndHydrate($avis);
            $avisModel->setTitre($data['titre']);
            $avisModel->setDescription($data['description']);
            $avisModel->setNote($data['note']);
            $this->repository->update($avisModel);

            $this->success(['message' => 'Avis mis à jour avec succès']);
        });
    }

    public function updateStatut(int $id): void
    {
        if (!$this->requireAdminOrEmploye()) return;

        $this->tryCatch(function () use ($id) {
            $avis = $this->repository->findById($id);
            if (!$avis) { $this->error('Avis introuvable', 404); return; }

            $data = json_decode(file_get_contents("php://input"), true);
            if (!$data) { $this->error('Données invalides', 400); return; }

            $errors = $this->validator->validateStatut($data);
            if ($errors) { $this->error($errors, 422); return; }

            $avisModel = Avis::createAndHydrate($avis);
            $avisModel->setStatut($data['statut']);
            $this->repository->update($avisModel);

            $this->success(['message' => "Statut de l'avis mis à jour avec succès"]);
        });
    }

    public function delete(int $id): void
    {
        if (!$this->requireLogin()) return;

        $this->tryCatch(function () use ($id) {
            $avis = $this->repository->findById($id);
            if (!$avis) { $this->error('Avis introuvable', 404); return; }
            if (!$this->checkAccess($avis)) return;

            $this->repository->delete($id);
            $this->success(['message' => 'Avis supprimé avec succès']);
        });
    }
}