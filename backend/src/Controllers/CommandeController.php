<?php

namespace App\Controllers;

use App\Core\AbstractController;
use App\Core\Security\Validator\CommandeValidator;
use App\Models\Commande;
use App\Repository\CommandeRepository;
use App\Repository\MenuRepository;
use DateTimeImmutable;

class CommandeController extends AbstractController
{
    private CommandeValidator $validator;
    private CommandeRepository $repository;

    public function __construct()
    {
        $this->repository = new CommandeRepository();
        $this->validator  = new CommandeValidator(new MenuRepository());
    }

    public function create(): void
    {
        if (!$this->requireLogin()) return;

        $this->tryCatch(function () {
            $data = json_decode(file_get_contents("php://input"), true);
            if (!$data) { $this->error('Données invalides', 400); return; }

            $errors = $this->validator->validate($data);
            if ($errors) { $this->error($errors, 422); return; }

            $commande = new Commande();
            $commande->setNumeroDeCommande($this->generateNumeroCommande());
            $commande->setDateCommande(new DateTimeImmutable());
            $commande->setDatePrestation($data['datePrestation']);
            $commande->setHeureLivraison($data['heureLivraison']);
            $commande->setAdresseLivraison($data['adresseLivraison']);
            $commande->setPrixMenu($data['prixMenu']);
            $commande->setNombrePersonne($data['nombrePersonne']);
            $commande->setPrixLivraison($data['prixLivraison']);
            $commande->setStatut('en_attente');
            $commande->setPretMateriel($data['pretMateriel'] ?? false);
            $commande->setRestitutionMateriel(false);
            $commande->setUtilisateurId($_SESSION['user']['id']);
            $commande->setMenuId($data['menuId']);
            $this->repository->create($commande);

            $this->success(['message' => 'Commande créée avec succès'], 201);
        });
    }

    public function read(int $id): void
    {
        if (!$this->requireLogin()) return;

        $this->tryCatch(function () use ($id) {
            $commande = $this->repository->findById($id);
            if (!$commande) { $this->error('Commande introuvable', 404); return; }
            if (!$this->checkAccess(fn() => $this->requireOwner($commande))) return;

            $this->success($commande);
        });
    }
    public function readMyCommandes(): void
    {
        if (!$this->requireLogin()) return;

        $this->tryCatch(function () {
            $commandes = $this->repository->findByUtilisateurId($_SESSION['user']['id']);
            $this->success($commandes);
        });
    }

    public function readAll(): void
    {
        if (!$this->requireAdminOrEmploye()) return;

        $this->tryCatch(fn () => $this->success($this->repository->findAll()));
    }

    public function update(int $id): void
    {
        if (!$this->requireLogin()) return;

        $this->tryCatch(function () use ($id) {
            $commande = $this->repository->findById($id);
            if (!$commande) { $this->error('Commande introuvable', 404); return; }
            if (!$this->checkAccess(fn() => $this->requireOwner($commande) && $this->checkOrderStatut($commande))) return;

            $data = json_decode(file_get_contents("php://input"), true);
            if (!$data) { $this->error('Données invalides', 400); return; }

            $errors = $this->validator->validateUpdate($data);
            if ($errors) { $this->error($errors, 422); return; }

            $commandeModel = Commande::createAndHydrate($commande);
            if (isset($data['datePrestation']))  $commandeModel->setDatePrestation($data['datePrestation']);
            if (isset($data['heureLivraison']))  $commandeModel->setHeureLivraison($data['heureLivraison']);
            if (isset($data['adresseLivraison']))$commandeModel->setAdresseLivraison($data['adresseLivraison']);
            if (isset($data['prixMenu']))        $commandeModel->setPrixMenu($data['prixMenu']);
            if (isset($data['nombrePersonne']))  $commandeModel->setNombrePersonne($data['nombrePersonne']);
            if (isset($data['prixLivraison']))   $commandeModel->setPrixLivraison($data['prixLivraison']);
            if (isset($data['pretMateriel']))    $commandeModel->setPretMateriel($data['pretMateriel']);
            $this->repository->update($commandeModel);

            $this->success(['message' => 'Commande mise à jour avec succès']);
        });
    }

    public function updateStatut(int $id): void
    {
        if (!$this->requireAdminOrEmploye()) return;

        $this->tryCatch(function () use ($id) {
            $commande = $this->repository->findById($id);
            if (!$commande) { $this->error('Commande introuvable', 404); return; }

            $data = json_decode(file_get_contents("php://input"), true);
            if (!$data) { $this->error('Données invalides', 400); return; }

            $errors = $this->validator->validateStatut($data);
            if ($errors) { $this->error($errors, 422); return; }

            if ($data['statut'] === 'terminee') {
                if (!$this->checkCommandeTerminable($commande)) return;
            }

            $commandeModel = Commande::createAndHydrate($commande);
            $commandeModel->setStatut($data['statut']);
            $this->repository->update($commandeModel);

            $this->success(['message' => 'Statut de la commande mis à jour']);
        });
    }

    public function delete(int $id): void
    {
        if (!$this->requireLogin()) return;

        $this->tryCatch(function () use ($id) {
            $commande = $this->repository->findById($id);
            if (!$commande) { $this->error('Commande introuvable', 404); return; }
            if (!$this->checkAccess(fn() => $this->requireOwner($commande) && $this->checkOrderStatut($commande))) return;

            $this->repository->delete($id);
            $this->success(['message' => 'Commande supprimée avec succès']);
        });
    }
}