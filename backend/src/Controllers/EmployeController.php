<?php

namespace App\Controllers;

use App\Core\AbstractController;
use App\Core\Security\Security;
use App\Core\Security\Validator\EmployeValidator;
use App\Models\Utilisateur;
use App\Repository\RoleRepository;
use App\Repository\UtilisateurRepository;

class EmployeController extends AbstractController
{
    private UtilisateurRepository $repository;
    private RoleRepository $roleRepository;
    private EmployeValidator $validator;

    public function __construct()
    {
        $this->repository     = new UtilisateurRepository();
        $this->roleRepository = new RoleRepository();
        $this->validator      = new EmployeValidator($this->repository);
    }

    // Route : POST /api/admin/employe/creation
    public function create(): void
    {
        if (!$this->requireAdmin()) return;

        $this->tryCatch(function () {
            $data = json_decode(file_get_contents("php://input"), true);
            if (!$data) { $this->error('Données invalides', 400); return; }

            $errors = $this->validator->validate($data);
            if ($errors) { $this->error($errors, 422); return; }

            $role = $this->roleRepository->findByLibelle('ROLE_EMPLOYE');
            if (!$role) { $this->error('Rôle introuvable', 500); return; }

            $utilisateur = new Utilisateur();
            $utilisateur->setEmail($data['email']);
            $utilisateur->setNom('nom');
            $utilisateur->setPrenom('prenom');
            $utilisateur->setTelephone('telephone');
            $utilisateur->setAdresse('adresse');
            $utilisateur->setVille('ville');
            $utilisateur->setPays('pays');
            $utilisateur->setStatut('actif');
            $utilisateur->setRoleId($role['id']);

            $plainPassword = Security::generatePassword();
            Security::hashPassword($utilisateur, $plainPassword);

            $this->repository->create($utilisateur);

            $this->success([
                'message'  => 'Employé créé avec succès',
                'password' => $plainPassword // à envoyer par email en production
            ], 201);
        });
    }

    // Route : GET /api/admin/employe/{id}
    public function read(int $id): void
    {
        if (!$this->requireAdmin()) return;

        $this->tryCatch(function () use ($id) {
            $utilisateur = $this->repository->findEmployeById($id);
            if (!$utilisateur) { $this->error('Employé introuvable', 404); return; }

            $this->success($utilisateur);
        });
    }

    // Route : GET /api/admin/employe
    public function readAll(): void
    {
        if (!$this->requireAdmin()) return;

        $this->tryCatch(fn () => $this->success($this->repository->findAllEmployes()));
    }

    // Route : PUT /api/admin/employe
    public function update(): void
    {
        if (!$this->requireAdmin()) return;

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

                $utilisateurData = $this->repository->findById($item['id']);
                if (!$utilisateurData) continue;

                $role = $this->roleRepository->findById($utilisateurData['role_id']);
                if (!$role || $role['libelle'] !== 'ROLE_EMPLOYE') continue;

                $utilisateur = Utilisateur::createAndHydrate($utilisateurData);

                if (isset($item['email'])) {
                    if (!$this->checkEmailUnique($this->repository, $item['email'], $item['id'])) {
                        $this->error('Cet email est déjà utilisé', 409);
                        return;
                    }
                    $utilisateur->setEmail($item['email']);
                }
                if (isset($item['nom']))       $utilisateur->setNom($item['nom']);
                if (isset($item['prenom']))    $utilisateur->setPrenom($item['prenom']);
                if (isset($item['telephone'])) $utilisateur->setTelephone($item['telephone']);
                if (isset($item['adresse']))   $utilisateur->setAdresse($item['adresse']);
                if (isset($item['ville']))     $utilisateur->setVille($item['ville']);
                if (isset($item['pays']))      $utilisateur->setPays($item['pays']);
                if (isset($item['statut']))    $utilisateur->setStatut($item['statut']);

                $this->repository->update($utilisateur);
            }

            $this->success(['message' => 'Employés mis à jour'], 200);
        });
    }

    // Route : PUT /api/admin/employe/{id}/password
    public function updatePassword(int $id): void
    {
        if (!$this->requireAdmin()) return;

        $this->tryCatch(function () use ($id) {
            $utilisateurData = $this->getUtilisateurOrFail($id);
            if (!$utilisateurData) return;

            $data = json_decode(file_get_contents("php://input"), true);
            if (!$data) { $this->error('Données invalides', 400); return; }

            $errors = $this->validator->validatePassword($data);
            if ($errors) { $this->error($errors, 422); return; }

            $utilisateur = Utilisateur::createAndHydrate($utilisateurData);
            Security::hashPassword($utilisateur, $data['newPassword']);
            $this->repository->updatePassword($utilisateur);

            $this->success(['message' => "Mot de passe de l'employé mis à jour"], 200);
        });
    }

    // Route : DELETE /api/admin/employe/{id}
    public function delete(int $id): void
    {
        if (!$this->requireAdmin()) return;

        $this->tryCatch(function () use ($id) {
            $utilisateurData = $this->getUtilisateurOrFail($id);
            if (!$utilisateurData) return;

            $role = $this->roleRepository->findById($utilisateurData['roleId']);

            if ($role && $role['libelle'] === 'ROLE_ADMIN') {
                $this->error('Un compte administrateur ne peut pas être supprimé', 403);
                return;
            }
            if (!$role || $role['libelle'] !== 'ROLE_EMPLOYE') {
                $this->error('Seuls les employés peuvent être supprimés', 403);
                return;
            }

            $this->repository->delete($id);
            $this->success(['message' => 'Compte supprimé avec succès']);
        });
    }
}