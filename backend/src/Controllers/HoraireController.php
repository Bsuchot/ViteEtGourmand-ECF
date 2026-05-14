<?php

namespace App\Controllers;

use App\Core\AbstractController;
use App\Core\Security\Validator\HoraireValidator;
use App\Models\Horaire;
use App\Repository\HoraireRepository;

class HoraireController extends AbstractController
{
    private HoraireValidator $validator;
    private HoraireRepository $repository;

    public function __construct()
    {
        $this->repository = new HoraireRepository();
        $this->validator  = new HoraireValidator($this->repository);
    }

    public function create(): void
    {
        if (!$this->requireAdminOrEmploye()) return;

        $this->tryCatch(function () {
            $data = json_decode(file_get_contents("php://input"), true);
            if (!$data) { $this->error('Données invalides', 400); return; }

            $errors = $this->validator->validate($data);
            if ($errors) { $this->error($errors, 422); return; }

            $horaire = new Horaire();
            $horaire->setJour($data['jour']);
            $horaire->setHeureOuverture($data['heure_ouverture']);
            $horaire->setHeureFermeture($data['heure_fermeture']);
            $horaire->setStatut($data['statut']);
            $this->repository->create($horaire);

            $this->success(['message' => 'Horaire créé avec succès'], 201);
        });
    }

    public function readAll(): void
    {
        $this->tryCatch(fn () => $this->success($this->repository->findAll()));
    }

    public function update(): void
    {
        if (!$this->requireAdminOrEmploye()) return;

        $this->tryCatch(function () {
            $data = json_decode(file_get_contents("php://input"), true);
            if (!$data || !is_array($data)) { $this->error('Données invalides', 400); return; }

            $allErrors = [];
            foreach ($data as $index => $item) {
                if (empty($item['id'])) continue;
                $errors = $this->validator->validateUpdate($item);
                if ($errors) $allErrors[$index] = $errors;
            }

            if ($allErrors) { $this->error($allErrors, 422); return; }

            foreach ($data as $item) {
                if (empty($item['id'])) continue;

                $horaireData = $this->repository->findById($item['id']);
                if (!$horaireData) continue;

                $horaire = Horaire::createAndHydrate($horaireData);
                if (isset($item['jour']))           $horaire->setJour($item['jour']);
                if (isset($item['heure_ouverture'])) $horaire->setHeureOuverture($item['heure_ouverture']);
                if (isset($item['heure_fermeture'])) $horaire->setHeureFermeture($item['heure_fermeture']);
                if (isset($item['statut']))         $horaire->setStatut($item['statut']);
                $this->repository->update($horaire);
            }

            $this->success(['message' => 'Horaires mis à jour'], 200);
        });
    }

    public function delete(int $id): void
    {
        if (!$this->requireAdminOrEmploye()) return;

        $this->tryCatch(function () use ($id) {
            $horaire = $this->repository->findById($id);
            if (!$horaire) { $this->error('Horaire introuvable', 404); return; }

            $this->repository->delete($id);
            $this->success(['message' => 'Horaire supprimé avec succès']);
        });
    }
}