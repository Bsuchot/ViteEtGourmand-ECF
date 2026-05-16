<?php

namespace App\Controllers;

use App\Core\AbstractController;
use App\Core\Security\Security;
use App\Core\Security\Validator\EmployeValidator;
use App\Models\Utilisateur;
use App\Repository\RoleRepository;
use App\Repository\UtilisateurRepository;
use OpenApi\Attributes as OA;

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
    #[OA\Post(
        path: "/api/admin/employe/create",
        summary: "Créer un compte employé",
        description: "Crée un nouveau compte employé et génère un mot de passe temporaire. Réservé aux administrateurs.",
        tags: ["Employé"],
        security: [["cookieAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email"],
                properties: [
                    new OA\Property(property: "email", type: "string", example: "employe@example.com")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Employé créé avec succès. Le mot de passe temporaire est retourné (à envoyer par email en production).",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message",  type: "string", example: "Employé créé avec succès"),
                        new OA\Property(property: "password", type: "string", example: "xK9#mP2!qL", description: "Mot de passe temporaire généré automatiquement")
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Données invalides"),
            new OA\Response(response: 401, description: "Non autorisé"),
            new OA\Response(response: 403, description: "Accès interdit"),
            new OA\Response(response: 422, description: "Erreur de validation"),
            new OA\Response(response: 500, description: "Rôle employé introuvable en base")
        ]
    )]
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
    #[OA\Get(
        path: "/api/admin/employe/{id}",
        summary: "Afficher un employé",
        description: "Retourne les informations d'un employé par son identifiant. Réservé aux administrateurs.",
        tags: ["Employé"],
        security: [["cookieAuth" => []]],                                                            // Fix: security manquant
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Identifiant de l'employé",
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Employé trouvé",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "id",      type: "integer", example: 1),
                        new OA\Property(property: "nom",     type: "string",  example: "Dupont"),
                        new OA\Property(property: "prenom",  type: "string",  example: "Marie"),
                        new OA\Property(property: "email",   type: "string",  example: "employe@example.com"), // Fix: example était 5 (integer)
                        new OA\Property(property: "statut",  type: "string",  example: "actif"),
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Non autorisé"),
            new OA\Response(response: 403, description: "Accès interdit"),
            new OA\Response(response: 404, description: "Employé introuvable")                       // Fix: "Avis introuvable" → "Employé introuvable"
        ]
    )]
    // Fix: suppression du doublon @OA\Get en annotation PHPDoc qui coexistait avec l'attribut ci-dessus
    public function read(int $id): void
    {
        if (!$this->requireAdmin()) return;

        $this->tryCatch(function () use ($id) {
            $utilisateur = $this->repository->findEmployeById($id);
            if (!$utilisateur) { $this->error('Employé introuvable', 404); return; }

            $this->success($utilisateur);
        });
    }
    #[OA\Get(
        path: "/api/admin/employe",
        summary: "Lister tous les employés",
        description: "Retourne la liste de tous les comptes employés. Réservé aux administrateurs.",
        tags: ["Employé"],
        security: [["cookieAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Liste des employés",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: "id",      type: "integer", example: 1),
                            new OA\Property(property: "nom",     type: "string",  example: "Dupont"),
                            new OA\Property(property: "prenom",  type: "string",  example: "Marie"),
                            new OA\Property(property: "email",   type: "string",  example: "employe@example.com"),
                            new OA\Property(property: "statut",  type: "string",  example: "actif")
                        ]
                    )
                )
            ),
            new OA\Response(response: 401, description: "Non autorisé"),
            new OA\Response(response: 403, description: "Accès interdit")
        ]
    )]
    public function readAll(): void
    {
        if (!$this->requireAdmin()) return;

        $this->tryCatch(fn () => $this->success($this->repository->findAllEmployes()));
    }
    #[OA\Put(
        path: "/api/admin/employe",
        summary: "Modifier plusieurs employés",
        description: "Met à jour les informations d'un ou plusieurs employés en une seule requête. Chaque élément doit contenir un 'id'. Réservé aux administrateurs.",
        tags: ["Employé"],
        security: [["cookieAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "array",
                items: new OA\Items(
                    required: ["id"],
                    properties: [
                        new OA\Property(property: "id",        type: "integer", example: 1),
                        new OA\Property(property: "email",     type: "string",  example: "employe@example.com"),
                        new OA\Property(property: "nom",       type: "string",  example: "Dupont"),
                        new OA\Property(property: "prenom",    type: "string",  example: "Marie"),
                        new OA\Property(property: "telephone", type: "string",  example: "0612345678"),
                        new OA\Property(property: "adresse",   type: "string",  example: "5 rue de la Paix"),
                        new OA\Property(property: "ville",     type: "string",  example: "Lyon"),
                        new OA\Property(property: "pays",      type: "string",  example: "France")
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Employés mis à jour",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Employés mis à jour")
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Données invalides"),
            new OA\Response(response: 401, description: "Non autorisé"),
            new OA\Response(response: 403, description: "Accès interdit"),
            new OA\Response(response: 409, description: "Email déjà utilisé"),
            new OA\Response(response: 422, description: "Erreurs de validation par index")
        ]
    )]
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

                $this->repository->update($utilisateur);
            }

            $this->success(['message' => 'Employés mis à jour'], 200);
        });
    }
    #[OA\Put(
        path: "/api/admin/employe/{id}/password",
        summary: "Modifier le mot de passe d'un employé",
        description: "Réinitialise le mot de passe d'un employé sans vérification de l'ancien mot de passe. Réservé aux administrateurs.",
        tags: ["Employé"],
        security: [["cookieAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Identifiant de l'employé",
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["newPassword"],
                properties: [
                    new OA\Property(property: "newPassword", type: "string", example: "NewPassword123")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Mot de passe de l'employé mis à jour",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Mot de passe de l'employé mis à jour")
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Données invalides"),
            new OA\Response(response: 401, description: "Non autorisé"),
            new OA\Response(response: 403, description: "Accès interdit"),
            new OA\Response(response: 404, description: "Employé introuvable"),
            new OA\Response(response: 422, description: "Erreur de validation")
        ]
    )]
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
    #[OA\Delete(
        path: "/api/admin/employe/{id}",
        summary: "Supprimer un compte employé",
        description: "Supprime un compte employé. Les comptes administrateurs ne peuvent pas être supprimés via cette route. Réservé aux administrateurs.",
        tags: ["Employé"],
        security: [["cookieAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Identifiant de l'employé",
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Compte supprimé avec succès",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Compte supprimé avec succès")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Non autorisé"),
            new OA\Response(response: 403, description: "Accès interdit — tentative de suppression d'un administrateur ou d'un non-employé"),
            new OA\Response(response: 404, description: "Employé introuvable")
        ]
    )]
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