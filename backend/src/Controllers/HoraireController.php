<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Horaire;
use App\Repository\HoraireRepository;


class HoraireController extends Controller
{

    public function create(Horaire $horaire): void
    {
        if (!$this->requireAdminOrEmploye()) return;

        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data) {
            $this->error('Données invalides', 400);
            return;
        }

        $repository = new HoraireRepository();
        $existing = $repository->findByJour($data['jour']);
        if ($existing) {
            $this->error('Cet horaire existe déjà', 409);
            return;
        }

        $horaire = new Horaire();
        $horaire->setJour($data['jour']);
        $horaire->setHeureOuverture($data['heureOuverture']);
        $horaire->setHeureFermeture($data['heureFermeture']);
        $horaire->setStatut($data['statut']);
        $repository->create($horaire);

        $this->success(['message' => 'Horaire créé avec succès'], 201);
    }

    public function readAll(): void
    {
        $horaireRepository = new HoraireRepository();
        $horaires = $horaireRepository->findAll();

        $this->success($horaires);

    }

    public function update(): void
    {
        if (!$this->requireAdminOrEmploye()) return;
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data || !is_array($data)) {
            $this->error('Données invalides', 400);
            return;
        }

        $repository = new HoraireRepository();

        foreach ($data as $item) {
            if (empty($item['id'])) continue;

            $horaireData = $repository->findById($item['id']);
            if (!$horaireData) continue;


            $horaire = Horaire::createAndHydrate($horaireData);

            if (isset($item['jour']))       $horaire->setJour($item['jour']);
            if (isset($item['heureOuverture']))    $horaire->setHeureOuverture($item['heureOuverture']);
            if (isset($item['heureFermeture'])) $horaire->setHeureFermeture($item['heureFermeture']);
            if (isset($item['statut']))    $horaire->setStatut($item['statut']);

            $repository->update($horaire);
        }

        $this->success(['message' => 'Horaires mis à jour'], 200);
    }

    public function delete(int $id): void
    {
        if (!$this->requireAdminOrEmploye()) return;

        $repository = new HoraireRepository();
        $horaire = $repository->findById($id);

        if (!$horaire) {
            $this->error('Horaire introuvable', 404);
            return;
        }

        $repository->delete($id);
        $this->success(['message' => 'Horaire supprimé avec succès']);

    }
}