<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Security\Security;
use App\Core\Security\UtilisateurValidator;
use App\Models\Utilisateur;
use App\Repository\RoleRepository;
use App\Repository\UtilisateurRepository;

class SecurityController extends Controller
{
    // Route : POST /api/utilisateur/registration
    public function registration(): void
    {
        $data = json_decode(file_get_contents("php://input"), true);

        if (!$data){
            $this->error('Données invalides', 400);
            return;
        }

        $validator = new UtilisateurValidator();
        $errors = $validator->validate($data);

        if (!empty($errors)) {
            $this->error($errors, 422);
            return;
        }

        $repository = new UtilisateurRepository();

        $existingUtilisateur = $repository->findByEmail($data['email']);

        if ($existingUtilisateur) {
            $this->error('Un compte est déja associé à cet email', 409);
            return;
        }

        $utilisateur = new Utilisateur();
        $utilisateur->setEmail($data['email']);
        $utilisateur->setNom($data['nom']);
        $utilisateur->setPrenom($data['prenom']);
        $utilisateur->setTelephone($data['telephone']);
        $utilisateur->setAdresse($data['adresse']);
        $utilisateur->setVille($data['ville']);
        $utilisateur->setPays($data['pays']);

        Security::hashPassword($utilisateur, $data['password']);

        $roleRepository = new RoleRepository();
        $role = $roleRepository->findByLibelle('ROLE_USER');

        if (!$role) {
            $this->error('Rôle introuvable', 500);
            return;
        }
        $utilisateur->setRoleId($role['id']);

        $repository->create($utilisateur);

        $this->success([
            'message' => 'Utilisateur créé'
        ], 201);

    }

    // Route : POST /api/utilisateur/login
    public function login(): void
    {
        $data = json_decode(file_get_contents("php://input"), true);

        if (!$data) {
            $this->error('Données invalides', 400);
            return;
        }

        if (empty($data['email']) || empty($data['password'])) {
            $this->error('Email et mot de passe requis', 400);
            return;
        }

        $repository = new UtilisateurRepository();
        $utilisateurData = $repository->findByEmail($data['email']);

        if (!$utilisateurData) {
            $this->error('Email ou mot de passe incorrect', 401);
            return;
        }

        $utilisateur = Utilisateur::createAndHydrate($utilisateurData);

        if (!Security::verifyPassword($data['password'], $utilisateur->getPassword())) {
            $this->error('Email ou Mot de passe incorrect', 401);
            return;
        }

        $roleRepository = new RoleRepository();
        $role = $roleRepository->findById($utilisateur->getRoleId());
        if (!$role) {
            $this->error('Rôle introuvable', 500);
            return;
        }

        $_SESSION['user'] = [
            'id'    => $utilisateur->getId(),
            'email' => $utilisateur->getEmail(),
            'role'  => $role['libelle'], // 'ROLE_ADMIN', 'ROLE_EMPLOYE', 'ROLE_USER'
        ];

        $this->success(['message' => 'Connexion réussie'], 200);
    }

    // Route : POST /api/utilisateur/logout
    public function logout(): void
    {
        if (!Security::isLogged()) {
            $this->error('Non autorisé', 401);
            return;
        }
        session_destroy();
        $this->success(['message' => 'Déconnexion réussie']);
    }

    public function read(int $id): void
    {
        if(!$this->requireSelf($id)) return;

        $utilisateur = $this->getUtilisateurOrFail($id);
        if (!$utilisateur) return;

        $this->success($utilisateur);
    }


    // Route : PUT /api/utilisateur/{id}
    public function update(int $id): void
    {
        if(!$this->requireSelf($id)) return;

        $utilisateurData = $this->getUtilisateurOrFail($id);
        if (!$utilisateurData) return;

        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data) {
            $this->error('Données invalides', 400);
            return;
        }

        $repository = new UtilisateurRepository(); // ← remonté ici

        $utilisateur = Utilisateur::createAndHydrate($utilisateurData);

        if (isset($data['email'])) {
            if (!$this->checkEmailUnique($repository, $data['email'], $id)) {
                $this->error('Cet email est déjà utilisé', 409);
                return;
            }
            $utilisateur->setEmail($data['email']);
        }

        if (isset($data['nom']))       $utilisateur->setNom($data['nom']);
        if (isset($data['prenom']))    $utilisateur->setPrenom($data['prenom']);
        if (isset($data['telephone'])) $utilisateur->setTelephone($data['telephone']);
        if (isset($data['adresse']))   $utilisateur->setAdresse($data['adresse']);
        if (isset($data['ville']))     $utilisateur->setVille($data['ville']);
        if (isset($data['pays']))      $utilisateur->setPays($data['pays']);

        $repository->update($utilisateur);

        $this->success(['message' => 'Utilisateur mis à jour'], 200);
    }

    // Route : PUT /api/utilisateur/{id}/password
    public function updatePassword(int $id): void
    {
        if (!$this->requireSelf($id)) return;

        $utilisateurData = $this->getUtilisateurOrFail($id);
        if (!$utilisateurData) return;

        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data) {
            $this->error('Données invalides', 400);
            return;
        }

        if (empty($data['currentPassword']) || empty($data['newPassword'])) {
            $this->error('Mot de passe actuel et nouveau mot de passe requis', 400);
            return;
        }

        $utilisateur = Utilisateur::createAndHydrate($utilisateurData);

        if (!Security::verifyPassword($data['currentPassword'], $utilisateur->getPassword())) {
            $this->error('Mot de passe actuel incorrect', 401);
            return;
        }

        Security::hashPassword($utilisateur, $data['newPassword']);

        $repository = new UtilisateurRepository();
        $repository->updatePassword($utilisateur);

        $this->success(['message' => 'Mot de passe mis à jour'], 200);
    }

    // Route : DELETE /api/utilisateur/{id}
    public function delete(int $id): void
    {
        if (!$this->requireUser()) return;
        if (!$this->requireSelf($id)) return;

        $utilisateurData = $this->getUtilisateurOrFail($id);
        if (!$utilisateurData) return;

        $roleRepository = new RoleRepository();
        $role = $roleRepository->findById($utilisateurData['roleId']);

        if ($role && $role['libelle'] === 'ROLE_ADMIN') {
            $this->error('Un compte administrateur ne peut pas être supprimé', 403);
            return;
        }
        if ($role && $role['libelle'] === 'ROLE_EMPLOYE') {
            $this->error('Accés interdit', 403);
            return;
        }

        $repository = new UtilisateurRepository();
        $repository->delete($id);

        session_destroy();

        $this->success(['message' => 'Compte supprimé avec succès']);
    }
}