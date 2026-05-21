<?php

namespace App\Controllers;

use App\Core\AbstractController;
use App\Core\Security\Validator\LibelleValidator;
use App\Models\Role;
use App\Repository\RoleRepository;
use OpenApi\Attributes as OA;                                                                        // Fix: "Annotations" → "Attributes"

class RoleController extends AbstractController
{
    private LibelleValidator $validator;
    private RoleRepository $repository;

    public function __construct()
    {
        $this->repository = new RoleRepository();
        $this->validator  = new LibelleValidator($this->repository, 'Ce rôle');
    }
    #[OA\Post(
        path: "/api/role/create",
        summary: "Créer un rôle",
        description: "Crée un nouveau rôle. Réservé aux administrateurs.",
        tags: ["Rôle"],
        security: [["cookieAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["libelle"],
                properties: [
                    new OA\Property(property: "libelle", type: "string", example: "ROLE_MANAGER")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Rôle créé avec succès",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Rôle créé avec succès")
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Données invalides"),
            new OA\Response(response: 401, description: "Non autorisé"),
            new OA\Response(response: 403, description: "Accès interdit"),
            new OA\Response(response: 422, description: "Erreur de validation")
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

            $role = new Role();
            $role->setLibelle($data['libelle']);
            $this->repository->create($role);

            $this->success(['message' => 'Rôle créé avec succès'], 201);
        });
    }
    #[OA\Delete(
        path: "/api/role/{id}",
        summary: "Supprimer un rôle",
        description: "Supprime un rôle par son identifiant. Réservé aux administrateurs.",
        tags: ["Rôle"],
        security: [["cookieAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Identifiant du rôle",
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Rôle supprimé avec succès",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Rôle supprimé avec succès")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Non autorisé"),
            new OA\Response(response: 403, description: "Accès interdit"),
            new OA\Response(response: 404, description: "Rôle introuvable")
        ]
    )]
    public function delete(int $id): void
    {
        if (!$this->requireAdmin()) return;

        $this->tryCatch(function () use ($id) {
            $role = $this->repository->findById($id);
            if (!$role) { $this->error('Rôle introuvable', 404); return; }

            $this->repository->delete($id);
            $this->success(['message' => 'Rôle supprimé avec succès']);
        });
    }
}