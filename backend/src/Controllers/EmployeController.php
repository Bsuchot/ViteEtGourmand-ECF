<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Security\Security;
use App\Core\Security\EmployeValidator;
use App\Repository\RoleRepository;
use App\Repository\UtilisateurRepository;
use App\Models\Utilisateur;

class EmployeController extends Controller
{
    // Route : PUT /api/admin/employe/create
    public function create(): void
    {
        if (!Security::isAdmin()) {
            $this->error('Accès interdit', 403);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);

        if (!$data) {
            $this->error('Données invalides', 400);
            return;
        }

        $validator = new EmployeValidator();
        $errors = $validator->validate($data);

        if (!empty($errors)) {
            $this->error($errors, 422);
            return;
        }

        $repository = new UtilisateurRepository();

        $existingUtilisateur = $repository->findByEmail($data['email']);
        if ($existingUtilisateur) {
            $this->error('Un compte est déjà associé à cet email', 409);
            return;
        }

        $utilisateur = new Utilisateur();
        $utilisateur->setEmail($data['email']);
        $utilisateur->setNom('nom');
        $utilisateur->setPrenom('prenom');
        $utilisateur->setTelephone('telephone');
        $utilisateur->setAdresse('adresse');
        $utilisateur->setVille('ville');
        $utilisateur->setPays('pays');
        $utilisateur->setStatut('actif');

        $plainPassword = Security::generatePassword();
        Security::hashPassword($utilisateur, $plainPassword);

        $roleRepository = new RoleRepository();
        $role = $roleRepository->findByLibelle('ROLE_EMPLOYE');

        if (!$role) {
            $this->error('Rôle introuvable', 500);
            return;
        }

        $utilisateur->setRoleId($role['id']);

        $repository->create($utilisateur);

        $this->success([
            'message'  => 'Employé créé avec succès',
            'password' => $plainPassword // à envoyer par email en production
        ], 201);
    }
    // Route : PUT /api/admin/employe/read
    public function read(int $id): void
    {
        if (!Security::isAdmin()) {
            $this->error('Accès interdit', 403);
            return;
        }

        $repository = new UtilisateurRepository();
        $utilisateur = $repository->findEmployeById($id);

        if (!$utilisateur) {
            $this->error('Employé introuvable', 404);
            return;
        }

        $this->success($utilisateur);
    }
    // Route : PUT /api/admin/employe/readAll
    public function readAll(): void
    {
        if (!Security::isAdmin()) {
            $this->error('Accès interdit', 403);
            return;
        }

        $repository = new UtilisateurRepository();
        $employes = $repository->findAllEmployes();

        $this->success($employes);
    }
    // Route : PUT /api/admin/employe/update
    public function update(): void
    {
        if (!Security::isAdmin()) {
            $this->error('Accès interdit', 403);
            return;
        }

        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data || !is_array($data)) {
            $this->error('Données invalides', 400);
            return;
        }

        $repository = new UtilisateurRepository();

        foreach ($data as $item) {
            if (empty($item['id'])) continue;
            $utilisateurData = $repository->findById($item['id']);
            if (!$utilisateurData) continue;

            $utilisateur = Utilisateur::createAndHydrate($utilisateurData);

            if (isset($item['email']))     $utilisateur->setEmail($item['email']);
            if (isset($item['nom']))       $utilisateur->setNom($item['nom']);
            if (isset($item['prenom']))    $utilisateur->setPrenom($item['prenom']);
            if (isset($item['telephone'])) $utilisateur->setTelephone($item['telephone']);
            if (isset($item['adresse']))   $utilisateur->setAdresse($item['adresse']);
            if (isset($item['ville']))     $utilisateur->setVille($item['ville']);
            if (isset($item['pays']))      $utilisateur->setPays($item['pays']);
            if (isset($item['statut']))    $utilisateur->setStatut($item['statut']);

            $repository->update($utilisateur);
        }

        $this->success(['message' => 'Employés mis à jour'], 200);
    }
    // Route : PUT /api/admin/employe/update/{id}/password
    public function updatePassword(int $id): void
    {
        if (!Security::isAdmin()) {
            $this->error('Accès interdit', 403);
            return;
        }

        $utilisateurData = $this->getUtilisateurOrFail($id);
        if (!$utilisateurData) return;

        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data) {
            $this->error('Données invalides', 400);
            return;
        }

        if ( empty($data['newPassword'])) {
            $this->error('Nouveau mot de passe requis', 400);
            return;
        }

        $utilisateur = Utilisateur::createAndHydrate($utilisateurData);


        Security::hashPassword($utilisateur, $data['newPassword']);

        $repository = new UtilisateurRepository();
        $repository->updatePassword($utilisateur);

        $this->success(['message' => "Mot de passe de l'employée mis à jour"], 200);
    }
    // Route : DELETE /api/admin/employe/{id}/
    public function delete(int $id): void
    {
        if (!Security::isAdmin()) {
            $this->error('Accès interdit', 403);
            return;
        }

        $utilisateurData = $this->getUtilisateurOrFail($id);
        if (!$utilisateurData) return;

        $roleRepository = new RoleRepository();
        $role = $roleRepository->findById($utilisateurData['roleId']);

        if ($role && $role['libelle'] === 'ROLE_ADMIN') {
            $this->error('Un compte administrateur ne peut pas être supprimé', 403);
            return;
        }
        if (!$role || $role['libelle'] !== 'ROLE_EMPLOYE') {
            $this->error('Seuls les employés peuvent être supprimés', 403);
            return;
        }

        $repository = new UtilisateurRepository();
        $repository->delete($id);


        $this->success(['message' => 'Compte supprimé avec succès']);
    }
}