<?php

namespace App\Controllers;

use App\Core\AbstractController;
use App\Core\Security\CsrfService;
use App\Core\Security\Security;
use App\Core\Security\Validator\UtilisateurValidator;
use App\Models\Utilisateur;
use App\Repository\RoleRepository;
use App\Repository\UtilisateurRepository;
use OpenApi\Attributes as OA;                                                                        // Fix: "Annotations" → "Attributes"


class SecurityController extends AbstractController
{
    private UtilisateurRepository $repository;
    private RoleRepository $roleRepository;
    private UtilisateurValidator $validator;

    public function __construct()
    {
        $this->repository     = new UtilisateurRepository();
        $this->roleRepository = new RoleRepository();
        $this->validator      = new UtilisateurValidator();
    }
    #[OA\Post(
        path: "/api/utilisateur/registration",
        summary: "Créer un compte utilisateur",
        description: "Crée un nouveau compte utilisateur avec le rôle ROLE_USER.",
        tags: ["Authentification"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email","nom","prenom","password"],
                properties: [
                    new OA\Property(property: "email",     type: "string", example: "john@example.com"),
                    new OA\Property(property: "nom",       type: "string", example: "Doe"),
                    new OA\Property(property: "prenom",    type: "string", example: "John"),
                    new OA\Property(property: "telephone", type: "string", example: "0600000000"),
                    new OA\Property(property: "adresse",   type: "string", example: "10 rue de Paris"),
                    new OA\Property(property: "ville",     type: "string", example: "Paris"),
                    new OA\Property(property: "pays",      type: "string", example: "France"),
                    new OA\Property(property: "password",  type: "string", example: "Password123")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Utilisateur créé avec succès",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Utilisateur créé")
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Données invalides"),
            new OA\Response(response: 409, description: "Email déjà utilisé"),
            new OA\Response(response: 422, description: "Erreur de validation")
        ]
    )]
    public function registration(): void
    {
        $this->tryCatch(function () {
            $data = json_decode(file_get_contents("php://input"), true);
            if (!$data) { $this->error('Données invalides', 400); return; }

            $errors = $this->validator->validate($data);
            if ($errors) { $this->error($errors, 422); return; }

            $existing = $this->repository->findByEmail($data['email']);
            if ($existing) { $this->error('Un compte est déjà associé à cet email', 409); return; }

            $role = $this->roleRepository->findByLibelle('ROLE_USER');
            if (!$role) { $this->error('Rôle introuvable', 500); return; }

            $utilisateur = new Utilisateur();
            $utilisateur->setEmail($data['email']);
            $utilisateur->setNom($data['nom']);
            $utilisateur->setPrenom($data['prenom']);
            $utilisateur->setTelephone($data['telephone']);
            $utilisateur->setAdresse($data['adresse']);
            $utilisateur->setVille($data['ville']);
            $utilisateur->setPays($data['pays']);
            $utilisateur->setRoleId($role['id']);
            Security::hashPassword($utilisateur, $data['password']);

            $this->repository->create($utilisateur);

            $this->success(['message' => 'Utilisateur créé'], 201);
        });
    }
    #[OA\Post(
        path: "/api/utilisateur/login",
        summary: "Connexion utilisateur",
        description: "Authentifie l'utilisateur et ouvre une session. Retourne le token CSRF à inclure dans les requêtes suivantes.",
        tags: ["Authentification"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["email","password"],
                properties: [
                    new OA\Property(property: "email",    type: "string", example: "john@example.com"),
                    new OA\Property(property: "password", type: "string", example: "Password123")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Connexion réussie",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message",    type: "string", example: "Connexion réussie"),
                        new OA\Property(
                            property: "user",
                            type: "object",
                            properties: [
                                new OA\Property(property: "id",    type: "integer", example: 1),
                                new OA\Property(property: "email", type: "string",  example: "john@example.com"),
                                new OA\Property(property: "role",  type: "string",  example: "ROLE_USER")
                            ]
                        ),
                        new OA\Property(property: "csrf_token", type: "string", example: "abc123xyz")
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Données invalides"),
            new OA\Response(response: 401, description: "Email ou mot de passe incorrect"),
            new OA\Response(response: 422, description: "Erreur de validation")
        ]
    )]
    public function login(): void
    {
        $this->tryCatch(function () {
            $data = json_decode(file_get_contents("php://input"), true);
            if (!$data) { $this->error('Données invalides', 400); return; }

            $errors = $this->validator->validateLogin($data);
            if ($errors) { $this->error($errors, 422); return; }

            $utilisateurData = $this->repository->findByEmail($data['email']);
            if (!$utilisateurData) { $this->error('Email ou mot de passe incorrect', 401); return; }

            $utilisateur = Utilisateur::createAndHydrate($utilisateurData);
            if (!Security::verifyPassword($data['password'], $utilisateur->getPassword())) {
                $this->error('Email ou mot de passe incorrect', 401);
                return;
            }

            $role = $this->roleRepository->findById($utilisateur->getRoleId());
            if (!$role) { $this->error('Rôle introuvable', 500); return; }

            session_regenerate_id(true);
            $_SESSION['csrf_token'] = CsrfService::generate();
            $_SESSION['user'] = [
                'id'    => $utilisateur->getId(),
                'email' => $utilisateur->getEmail(),
                'role'  => $role['libelle'],
            ];

            $this->success([
                'message'    => 'Connexion réussie',
                'user'       => $_SESSION['user'],
                'csrf_token' => $_SESSION['csrf_token']
            ], 200);

        });
    }
    public function csrf(): void
    {
        $this->tryCatch(function () {
            $this->success([
                'token' => CsrfService::generate()
            ]);
        });
    }
    #[OA\Get(
        path: "/api/utilisateur/me",
        summary: "Récupérer l'utilisateur connecté",
        description: "Retourne les informations de l'utilisateur actuellement connecté via sa session.",
        tags: ["Authentification"],
        security: [["cookieAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Utilisateur connecté",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "user",
                            type: "object",
                            properties: [
                                new OA\Property(property: "id",    type: "integer", example: 1),
                                new OA\Property(property: "email", type: "string",  example: "john@example.com"),
                                new OA\Property(property: "role",  type: "string",  example: "ROLE_USER")
                            ]
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Non authentifié")
        ]
    )]
    public function me(): void
    {
        $this->tryCatch(function () {
            if (!isset($_SESSION['user'])) {
                $this->error('Non authentifié', 401);
                return;
            }

            $this->success([
                'user' => $_SESSION['user']
            ]);
        });
    }
    #[OA\Post(
        path: "/api/utilisateur/logout",
        summary: "Déconnexion utilisateur",
        description: "Détruit la session de l'utilisateur connecté.",
        tags: ["Authentification"],
        security: [["cookieAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Déconnexion réussie",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Déconnexion réussie")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Non autorisé — utilisateur non connecté")
        ]
    )]
    public function logout(): void
    {
        if (!Security::isLogged()) { $this->error('Non autorisé', 401); return; }

        session_destroy();
        setcookie(session_name(), '', time() - 3600, '/');
        $this->success(['message' => 'Déconnexion réussie']);
    }
    #[OA\Get(
        path: "/api/utilisateur/{id}",
        summary: "Afficher un utilisateur",
        description: "Retourne les informations de l'utilisateur connecté. Accessible uniquement par le propriétaire du compte.",
        tags: ["Utilisateur"],
        security: [["cookieAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Identifiant de l'utilisateur",
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Utilisateur trouvé",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "id",        type: "integer", example: 1),
                        new OA\Property(property: "email",     type: "string",  example: "john@example.com"),
                        new OA\Property(property: "nom",       type: "string",  example: "Doe"),
                        new OA\Property(property: "prenom",    type: "string",  example: "John"),
                        new OA\Property(property: "telephone", type: "string",  example: "0600000000"),
                        new OA\Property(property: "adresse",   type: "string",  example: "10 rue de Paris"),
                        new OA\Property(property: "ville",     type: "string",  example: "Paris"),
                        new OA\Property(property: "pays",      type: "string",  example: "France")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Non autorisé"),
            new OA\Response(response: 403, description: "Accès interdit — ressource appartenant à un autre utilisateur"),
            new OA\Response(response: 404, description: "Utilisateur introuvable")
        ]
    )]
    public function read(int $id): void
    {
        if (!$this->requireSelf($id)) return;

        $this->tryCatch(function () use ($id) {
            $utilisateur = $this->getUtilisateurOrFail($id);
            if (!$utilisateur) return;

            $this->success($utilisateur);
        });
    }
    #[OA\Put(
        path: "/api/utilisateur/{id}",
        summary: "Modifier un utilisateur",
        description: "Met à jour les informations de l'utilisateur connecté. Tous les champs sont optionnels.",
        tags: ["Utilisateur"],
        security: [["cookieAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Identifiant de l'utilisateur",
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "email",     type: "string", example: "john@example.com"),
                    new OA\Property(property: "nom",       type: "string", example: "Doe"),
                    new OA\Property(property: "prenom",    type: "string", example: "John"),
                    new OA\Property(property: "telephone", type: "string", example: "0600000000"),
                    new OA\Property(property: "adresse",   type: "string", example: "10 rue de Paris"),
                    new OA\Property(property: "ville",     type: "string", example: "Paris"),
                    new OA\Property(property: "pays",      type: "string", example: "France")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Utilisateur mis à jour",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Utilisateur mis à jour")
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Données invalides"),
            new OA\Response(response: 401, description: "Non autorisé"),
            new OA\Response(response: 403, description: "Accès interdit"),
            new OA\Response(response: 409, description: "Email déjà utilisé"),
            new OA\Response(response: 422, description: "Erreur de validation")
        ]
    )]
    public function update(int $id): void
    {
        if (!$this->requireSelf($id)) return;

        $this->tryCatch(function () use ($id) {
            $utilisateurData = $this->getUtilisateurOrFail($id);
            if (!$utilisateurData) return;

            $data = json_decode(file_get_contents("php://input"), true);
            if (!$data) { $this->error('Données invalides', 400); return; }

            $errors = $this->validator->validateUpdate($data);
            if ($errors) { $this->error($errors, 422); return; }

            $utilisateur = Utilisateur::createAndHydrate($utilisateurData);

            if (isset($data['email'])) {
                if (!$this->checkEmailUnique($this->repository, $data['email'], $id)) {
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

            $this->repository->update($utilisateur);

            $this->success(['message' => 'Utilisateur mis à jour'], 200);
        });
    }
    #[OA\Put(
        path: "/api/utilisateur/{id}/password",
        summary: "Modifier le mot de passe d'un utilisateur",
        description: "Modifie le mot de passe de l'utilisateur connecté après vérification de l'ancien mot de passe.",
        tags: ["Utilisateur"],
        security: [["cookieAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Identifiant de l'utilisateur",
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["currentPassword","newPassword"],
                properties: [
                    new OA\Property(property: "currentPassword", type: "string", example: "OldPassword123"),
                    new OA\Property(property: "newPassword",     type: "string", example: "NewPassword123")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Mot de passe mis à jour",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Mot de passe mis à jour")
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Données invalides"),
            new OA\Response(response: 401, description: "Mot de passe actuel incorrect"),
            new OA\Response(response: 403, description: "Accès interdit"),
            new OA\Response(response: 422, description: "Erreur de validation")
        ]
    )]
    public function updatePassword(int $id): void
    {
        if (!$this->requireSelf($id)) return;

        $this->tryCatch(function () use ($id) {
            $utilisateurData = $this->getUtilisateurOrFail($id);
            if (!$utilisateurData) return;

            $data = json_decode(file_get_contents("php://input"), true);
            if (!$data) { $this->error('Données invalides', 400); return; }

            $errors = $this->validator->validatePasswordChange($data);
            if ($errors) { $this->error($errors, 422); return; }

            $utilisateur = Utilisateur::createAndHydrate($utilisateurData);
            if (!Security::verifyPassword($data['currentPassword'], $utilisateur->getPassword())) {
                $this->error('Mot de passe actuel incorrect', 401);
                return;
            }

            Security::hashPassword($utilisateur, $data['newPassword']);
            $this->repository->updatePassword($utilisateur);

            $this->success(['message' => 'Mot de passe mis à jour'], 200);
        });
    }
    #[OA\Delete(
        path: "/api/utilisateur/{id}",
        summary: "Supprimer un utilisateur",
        description: "Supprime le compte de l'utilisateur connecté. Les comptes administrateurs et employés ne peuvent pas se supprimer via cette route.",
        tags: ["Utilisateur"],
        security: [["cookieAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Identifiant de l'utilisateur",
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
            new OA\Response(response: 403, description: "Accès interdit — les administrateurs et employés ne peuvent pas supprimer leur compte via cette route"),
            new OA\Response(response: 404, description: "Utilisateur introuvable")
        ]
    )]
    public function delete(int $id): void
    {
        if (!$this->requireSelf($id)) return;

        $this->tryCatch(function () use ($id) {
            $utilisateurData = $this->getUtilisateurOrFail($id);
            if (!$utilisateurData) return;

            $role = $this->roleRepository->findById($utilisateurData['roleId']);

            if ($role && $role['libelle'] === 'ROLE_ADMIN') {
                $this->error('Un compte administrateur ne peut pas être supprimé', 403);
                return;
            }
            if ($role && $role['libelle'] === 'ROLE_EMPLOYE') {
                $this->error('Accès interdit', 403);
                return;
            }

            $this->repository->delete($id);
            session_destroy();

            $this->success(['message' => 'Compte supprimé avec succès']);
        });
    }
}