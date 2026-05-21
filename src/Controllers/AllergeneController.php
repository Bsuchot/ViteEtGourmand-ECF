<?php

namespace App\Controllers;

use App\Core\AbstractController;
use App\Core\Security\Validator\LibelleValidator;
use App\Models\Allergene;
use App\Repository\AllergeneRepository;
use OpenApi\Attributes as OA;

class AllergeneController extends AbstractController
{
    private LibelleValidator $validator;
    private AllergeneRepository $repository;

    public function __construct()
    {
        $this->repository = new AllergeneRepository();
        $this->validator  = new LibelleValidator($this->repository, 'Cet allergène');
    }
    #[OA\Post(
        path: "/api/allergene/create",
        summary: "Créer un allergène",
        description: "Crée un nouvel allergène. Réservé aux administrateurs et employés.",
        tags: ["Allergène"],
        security: [["cookieAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["libelle"],
                properties: [
                    new OA\Property(property: "libelle", type: "string", example: "Gluten")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Allergène créé avec succès",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Allergène créé avec succès")
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

            $allergene = new Allergene();
            $allergene->setLibelle($data['libelle']);
            $this->repository->create($allergene);
            $id = $this->repository->getLastInsertId();

            $this->success([
                'message' => 'Allergène créé avec succès',
                'id'      => $id
            ], 201);
        });
    }
    #[OA\Get(
        path: "/api/allergene/{id}",
        summary: "Afficher un allergène",
        description: "Retourne les informations d'un allergène par son identifiant. Accessible publiquement.",
        tags: ["Allergène"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Identifiant de l'allergène",
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Allergène trouvé",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "libelle", type: "string", example: "Arachide")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Allergène introuvable")
        ]
    )]
    public function read(int $id): void
    {
        $this->tryCatch(function () use ($id) {
            $allergene = $this->repository->findById($id);
            if (!$allergene) { $this->error('Allergène introuvable', 404); return; }

            $this->success($allergene);
        });
    }
    #[OA\Get(
        path: "/api/allergene",
        summary: "Lister tous les allergènes",
        description: "Retourne la liste complète des allergènes. Accessible publiquement.",
        tags: ["Allergène"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Liste des allergènes",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "libelle", type: "string", example: "Gluten") // Fix: exemple "Noël" → "Gluten" (incohérent)
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
        path: "/api/allergene/{id}",
        summary: "Modifier un allergène",
        description: "Met à jour le libellé d'un allergène existant. Réservé aux administrateurs et employés.",
        tags: ["Allergène"],
        security: [["cookieAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Identifiant de l'allergène",                                           // Fix: "du allergène" → "de l'allergène"
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["libelle"],
                properties: [
                    new OA\Property(property: "libelle", type: "string", example: "Fruits à coque") // Fix: exemple "Hivernal" incohérent
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Allergène mis à jour avec succès",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Allergène mis à jour avec succès")
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Données invalides"),
            new OA\Response(response: 401, description: "Non autorisé"),
            new OA\Response(response: 403, description: "Accès interdit"),
            new OA\Response(response: 404, description: "Allergène introuvable"),
            new OA\Response(response: 422, description: "Erreur de validation")
        ]
    )]
    public function update(int $id): void
    {
        if (!$this->requireAdminOrEmploye()) return;

        $this->tryCatch(function () use ($id) {
            $allergene = $this->repository->findById($id);
            if (!$allergene) { $this->error('Allergène introuvable', 404); return; }

            $data = json_decode(file_get_contents("php://input"), true);
            if (!$data) { $this->error('Données invalides', 400); return; }

            $errors = $this->validator->validateUpdate($data, $id);
            if ($errors) { $this->error($errors, 422); return; }

            $allergeneModel = Allergene::createAndHydrate($allergene);
            $allergeneModel->setLibelle($data['libelle']);
            $this->repository->update($allergeneModel);

            $this->success(['message' => 'Allergène mis à jour avec succès']);
        });
    }
    #[OA\Delete(
        path: "/api/allergene/{id}",
        summary: "Supprimer un allergène",
        description: "Supprime un allergène par son identifiant. Réservé aux administrateurs et employés.",
        tags: ["Allergène"],
        security: [["cookieAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Identifiant de l'allergène",                                           // Fix: "du allergène" → "de l'allergène"
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Allergène supprimé avec succès",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Allergène supprimé avec succès")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Non autorisé"),
            new OA\Response(response: 403, description: "Accès interdit"),
            new OA\Response(response: 404, description: "Allergène introuvable")
        ]
    )]
    public function delete(int $id): void
    {
        if (!$this->requireAdminOrEmploye()) return;

        $this->tryCatch(function () use ($id) {
            $allergene = $this->repository->findById($id);
            if (!$allergene) { $this->error('Allergène introuvable', 404); return; }

            $this->repository->delete($id);
            $this->success(['message' => 'Allergène supprimé avec succès']);
        });
    }
}