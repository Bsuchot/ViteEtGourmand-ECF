<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Security\Security;
use App\Core\Security\UtilisateurValidator;
use App\Models\Utilisateur;
use App\Repository\UtilisateurRepository;
use App\Models\Role;
use PDO;

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

        $utilisateur->setRoleId(3);

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
        if(!Security::isLogged()){
            $this->error('Non autorisé', 401);
            return;
        }
        $currentUserId = Security::getCurrentUserId();
        if ($currentUserId !== $id) {
            $this->error('Accés interdit', 403);
            return;
        }

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
        $utilisateurData = $this->getUtilisateurOrFail($id);
        if (!$utilisateurData) return;

        if ($_SESSION['user']['id'] !== $id) {
            $this->error('Accès interdit', 403);
            return;
        }

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

    public function delete(int $id): void
    {
        $utilisateur = $this->getUtilisateurOrFail($id);
        if (!$utilisateur) return;

        $this->success($utilisateur);
    }
}