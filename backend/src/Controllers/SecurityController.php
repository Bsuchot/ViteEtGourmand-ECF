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

        $_SESSION['user'] = [
            'id' => $utilisateur->getid(),
            'email' => $utilisateur->getEmail(),
            'role' => $utilisateur->getRoleId(),
        ];

        $this->success(['message' => 'Connexion réussie'], 200);
    }

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

        $repository = new UtilisateurRepository();
        $utilisateur = $repository->findById($id);
        if (!$utilisateur) {
            $this->error('Utilisateur introuvable', 404);
            return;
        }
        $this->success($utilisateur);
    }


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

        $utilisateur = Utilisateur::createAndHydrate($utilisateurData);

        if (isset($data['email']))       $utilisateur->setEmail($data['email']);
        if (isset($data['nom']))       $utilisateur->setNom($data['nom']);
        if (isset($data['prenom']))    $utilisateur->setPrenom($data['prenom']);
        if (isset($data['telephone'])) $utilisateur->setTelephone($data['telephone']);
        if (isset($data['adresse']))   $utilisateur->setAdresse($data['adresse']);
        if (isset($data['ville']))     $utilisateur->setVille($data['ville']);
        if (isset($data['pays']))      $utilisateur->setPays($data['pays']);

        $repository = new UtilisateurRepository();
        $repository->update($utilisateur);

        $this->success(['message' => 'Utilisateur mis à jour'], 200);
    }
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

    public function delete(int $id): void
    {
        if (!$this->requireSelf($id)) return;

        $utilisateurData = $this->getUtilisateurOrFail($id);
        if (!$utilisateurData) return;

        $repository = new UtilisateurRepository();
        $repository->delete($id);

        session_destroy();

        $this->success(['message' => 'Compte supprimé avec succès']);
    }
}