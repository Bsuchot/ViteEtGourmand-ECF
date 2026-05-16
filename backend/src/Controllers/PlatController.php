<?php

namespace App\Controllers;

use App\Core\AbstractController;
use App\Core\Security\Validator\PlatValidator;
use App\Models\Plat;
use App\Repository\PlatRepository;
use OpenApi\Attributes as OA;                                                                        // Fix: "Annotations" → "Attributes"

class PlatController extends AbstractController
{
    private PlatValidator $validator;
    private PlatRepository $repository;

    public function __construct()
    {
        $this->repository = new PlatRepository();
        $this->validator  = new PlatValidator();
    }
    #[OA\Post(
        path: "/api/plat/create",
        summary: "Créer un plat",
        description: "Crée un nouveau plat avec ses allergènes associés. Réservé aux administrateurs et employés.",
        tags: ["Plat"],
        security: [["cookieAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["titre","category","photo"],
                properties: [
                    new OA\Property(property: "titre",    type: "string", example: "Bœuf bourguignon"),
                    new OA\Property(property: "category", type: "string", example: "plat"),
                    new OA\Property(property: "photo",    type: "string", example: "https://example.com/photo.jpg"),
                    new OA\Property(
                        property: "allergenes",
                        type: "array",
                        items: new OA\Items(type: "integer", example: 1),
                        description: "Liste des identifiants d'allergènes"
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Plat créé avec succès",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Plat créé avec succès")
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
        if (!$this->requireAdminOrEmploye()) return;

        $this->tryCatch(function () {
            $data = json_decode(file_get_contents("php://input"), true);
            if (!$data) { $this->error('Données invalides', 400); return; }

            $errors = $this->validator->validate($data);
            if ($errors) { $this->error($errors, 422); return; }

            $plat = new Plat();
            $plat->setTitre($data['titre']);
            $plat->setCategory($data['category']);
            $plat->setPhoto($data['photo']);
            foreach ($data['allergenes'] ?? [] as $allergeneId) {
                $plat->addAllergeneId((int) $allergeneId);
            }
            $this->repository->create($plat);

            $this->success(['message' => 'Plat créé avec succès'], 201);
        });
    }
    #[OA\Get(
        path: "/api/plat/{id}",
        summary: "Afficher un plat",
        description: "Retourne les informations d'un plat par son identifiant. Accessible publiquement.",
        tags: ["Plat"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Identifiant du plat",
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Plat trouvé",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "id",       type: "integer", example: 1),
                        new OA\Property(property: "titre",    type: "string",  example: "Bœuf bourguignon"),
                        new OA\Property(property: "category", type: "string",  example: "plat"),
                        new OA\Property(property: "photo",    type: "string",  example: "https://example.com/photo.jpg"),
                        new OA\Property(
                            property: "allergenes",
                            type: "array",
                            items: new OA\Items(type: "integer", example: 1)
                        )
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Plat introuvable")
        ]
    )]
    public function read(int $id): void
    {
        $this->tryCatch(function () use ($id) {
            $plat = $this->repository->findById($id);
            if (!$plat) { $this->error('Plat introuvable', 404); return; }

            $this->success($plat);
        });
    }
    #[OA\Get(
        path: "/api/plat",
        summary: "Lister tous les plats",
        description: "Retourne la liste complète des plats. Accessible publiquement.",
        tags: ["Plat"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Liste des plats",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: "id",       type: "integer", example: 1),
                            new OA\Property(property: "titre",    type: "string",  example: "Bœuf bourguignon"),
                            new OA\Property(property: "category", type: "string",  example: "plat"),
                            new OA\Property(property: "photo",    type: "string",  example: "https://example.com/photo.jpg")
                        ]
                    )
                )
            )
        ]
    )]
    public function readAll(): void
    {
        $this->tryCatch(fn () => $this->success($this->repository->findAll()));
    }
    #[OA\Put(
        path: "/api/plat/{id}",
        summary: "Modifier un plat",
        description: "Met à jour les informations d'un plat existant. Tous les champs sont optionnels. Réservé aux administrateurs et employés.",
        tags: ["Plat"],
        security: [["cookieAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Identifiant du plat",
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "titre",    type: "string", example: "Poulet rôti"),
                    new OA\Property(property: "category", type: "string", example: "plat"),
                    new OA\Property(property: "photo",    type: "string", example: "https://example.com/photo.jpg"),
                    new OA\Property(
                        property: "allergenes",
                        type: "array",
                        items: new OA\Items(type: "integer", example: 2),
                        description: "Remplace la liste complète des allergènes"
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Plat mis à jour avec succès",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Plat mis à jour avec succès")
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Données invalides"),
            new OA\Response(response: 401, description: "Non autorisé"),
            new OA\Response(response: 403, description: "Accès interdit"),
            new OA\Response(response: 404, description: "Plat introuvable"),
            new OA\Response(response: 422, description: "Erreur de validation")
        ]
    )]
    public function update(int $id): void
    {
        if (!$this->requireAdminOrEmploye()) return;

        $this->tryCatch(function () use ($id) {
            $plat = $this->repository->findById($id);
            if (!$plat) { $this->error('Plat introuvable', 404); return; }

            $data = json_decode(file_get_contents("php://input"), true);
            if (!$data) { $this->error('Données invalides', 400); return; }

            $errors = $this->validator->validateUpdate($data);
            if ($errors) { $this->error($errors, 422); return; }

            $platModel = Plat::createAndHydrate($plat);
            if(isset($data['titre']))    $platModel->setTitre($data['titre']);
            if(isset($data['category'])) $platModel->setCategory($data['category']);
            if(isset($data['photo']))    $platModel->setPhoto($data['photo']);
            if(isset($data['allergenes'])) {
                $platModel->setAllergenes([]);
                foreach ($data['allergenes'] as $allergeneId) {
                    $platModel->addAllergeneId((int) $allergeneId);
                }
            }
            $this->repository->update($platModel);

            $this->success(['message' => 'Plat mis à jour avec succès']);
        });
    }
    #[OA\Delete(
        path: "/api/plat/{id}",
        summary: "Supprimer un plat",
        description: "Supprime un plat par son identifiant. Réservé aux administrateurs et employés.",
        tags: ["Plat"],
        security: [["cookieAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Identifiant du plat",
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Plat supprimé avec succès",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Plat supprimé avec succès")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Non autorisé"),
            new OA\Response(response: 403, description: "Accès interdit"),
            new OA\Response(response: 404, description: "Plat introuvable")
        ]
    )]
    public function delete(int $id): void
    {
        if (!$this->requireAdminOrEmploye()) return;

        $this->tryCatch(function () use ($id) {
            $plat = $this->repository->findById($id);
            if (!$plat) { $this->error('Plat introuvable', 404); return; }

            $this->repository->delete($id);
            $this->success(['message' => 'Plat supprimé avec succès']);
        });
    }
}