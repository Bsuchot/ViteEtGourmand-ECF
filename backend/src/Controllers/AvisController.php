<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Avis;
use App\Repository\AvisRepository;



class AvisController extends Controller
{
    public function create(): void
    {
        if (!$this->requireLogin()) return;

        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data) {
            $this->error('Données invalides', 400);
            return;
        }

        $repository = new AvisRepository();

        $avis = new Avis();
        $avis->setTitre($data['titre']);
        $avis->setDescription($data['description']);
        $avis->setNote($data['note']);
        $avis->setStatut($data['en_attente']);
        $avis->setDate(($data['Y-m-d H:i:s']));
        $avis->setUtilisateurId($_SESSION['user']['id']);
        $repository->create($avis);

        $this->success(['message' => 'Avis créé avec succès'], 201);
    }

    public function read(int $id): void
    {
        $repository = new AvisRepository();
        $avis = $repository->findById($id);

        if (!$avis) {
            $this->error('Avis introuvable', 404);
            return;
        }

        $this->success($avis);
    }

    public function readAll(): void
    {
        $repository = new AvisRepository();
        $avis = $repository->findAll();

        $this->success($avis);
    }
    public function update(int $id): void
    {
        if (!$this->requireLogin()) return;

        $repository = new AvisRepository();
        $avis = $repository->findById($id);

        if (!$avis) {
            $this->error('Avis introuvable', 404);
            return;
        }
        if (!$this->requireAvisOwner($avis)) return;

        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data) {
            $this->error('Données invalides', 400);
            return;
        }


        $avisModel = Avis::createAndHydrate($avis);
        $avisModel->setTitre($data['titre']);
        $avisModel->setDescription($data['description']);
        $avisModel->setNote($data['note']);
        $repository->update($avisModel);

        $this->success(['message' => "Avis mis à jour avec succès"]);
    }

    public function updateStatut(int $id): void
    {
        if (!$this->requireAdminOrEmploye()) return;

        $repository = new AvisRepository();
        $avis = $repository->findById($id);

        if (!$avis) {
            $this->error('Avis introuvable', 404);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data) {
            $this->error('Données invalides', 400);
            return;
        }


        $avisModel = Avis::createAndHydrate($avis);
        $avisModel->setStatut($data['statut']);
        $repository->update($avisModel);

        $this->success(['message' => "Statut de l'avis mis à jour avec succès"]);
    }

    public function delete(int $id): void
    {
        if (!$this->requireLogin()) return;

        $repository = new AvisRepository();
        $avis = $repository->findById($id);

        if (!$avis) {
            $this->error('Avis introuvable', 404);
            return;
        }
        if (!$this->requireAvisOwner($avis)) return;

        $repository->delete($id);
        $this->success(['message' => 'Avis supprimé avec succès']);
    }
}