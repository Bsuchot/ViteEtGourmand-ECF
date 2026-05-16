<?php

namespace App\Controllers;

use App\Core\AbstractController;
use App\Core\Security\Validator\LibelleValidator;
use App\Models\Regime;
use App\Repository\RegimeRepository;
use OpenApi\Attributes as OA;                                                                        // Fix: "Annotations" → "Attributes"

class RegimeController extends AbstractController
{
    private LibelleValidator $validator;
    private RegimeRepository $repository;

    public function __construct()
    {
        $this->repository = new RegimeRepository();
        $this->validator  = new LibelleValidator($this->repository, 'Ce régime');
    }
    #[OA\Post(
        path: "/api/regime/create",
        summary: "Créer un régime alimentaire",
        description: "Crée un nouveau régime alimentaire. Réservé aux administrateurs et employés.",
        tags: ["Régime"],
        security: [["cookieAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["libelle"],
                properties: [
                    new OA\Property(property: "libelle", type: "string", example: "Végétarien")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Régime créé avec succès",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Régime créé avec succès")
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

            $regime = new Regime();
            $regime->setLibelle($data['libelle']);
            $this->repository->create($regime);

            $this->success(['message' => 'Régime créé avec succès'], 201);
        });
    }
    #[OA\Get(
        path: "/api/regime/{id}",
        summary: "Afficher un régime alimentaire",
        description: "Retourne les informations d'un régime par son identifiant. Accessible publiquement.",
        tags: ["Régime"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Identifiant du régime",
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Régime trouvé",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "id",      type: "integer", example: 1),
                        new OA\Property(property: "libelle", type: "string",  example: "Végétarien")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Régime introuvable")
        ]
    )]
    public function read(int $id): void
    {
        $this->tryCatch(function () use ($id) {
            $regime = $this->repository->findById($id);
            if (!$regime) { $this->error('Régime introuvable', 404); return; }

            $this->success($regime);
        });
    }
    #[OA\Get(
        path: "/api/regime",
        summary: "Lister tous les régimes alimentaires",
        description: "Retourne la liste complète des régimes. Accessible publiquement.",
        tags: ["Régime"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Liste des régimes",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: "id",      type: "integer", example: 1),
                            new OA\Property(property: "libelle", type: "string",  example: "Végétarien")
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
        path: "/api/regime/{id}",
        summary: "Modifier un régime alimentaire",
        description: "Met à jour le libellé d'un régime existant. Réservé aux administrateurs et employés.",
        tags: ["Régime"],
        security: [["cookieAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Identifiant du régime",
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["libelle"],
                properties: [
                    new OA\Property(property: "libelle", type: "string", example: "Vegan")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Régime mis à jour avec succès",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Régime mis à jour avec succès")
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Données invalides"),
            new OA\Response(response: 401, description: "Non autorisé"),
            new OA\Response(response: 403, description: "Accès interdit"),
            new OA\Response(response: 404, description: "Régime introuvable"),
            new OA\Response(response: 422, description: "Erreur de validation")
        ]
    )]
    public function update(int $id): void
    {
        if (!$this->requireAdminOrEmploye()) return;

        $this->tryCatch(function () use ($id) {
            $regime = $this->repository->findById($id);
            if (!$regime) { $this->error('Régime introuvable', 404); return; }

            $data = json_decode(file_get_contents("php://input"), true);
            if (!$data) { $this->error('Données invalides', 400); return; }

            $errors = $this->validator->validateUpdate($data, $id);
            if ($errors) { $this->error($errors, 422); return; }

            $regimeModel = Regime::createAndHydrate($regime);
            $regimeModel->setLibelle($data['libelle']);
            $this->repository->update($regimeModel);

            $this->success(['message' => 'Régime mis à jour avec succès']);
        });
    }
    #[OA\Delete(
        path: "/api/regime/{id}",
        summary: "Supprimer un régime alimentaire",
        description: "Supprime un régime par son identifiant. Réservé aux administrateurs et employés.",
        tags: ["Régime"],
        security: [["cookieAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Identifiant du régime",
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Régime supprimé avec succès",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Régime supprimé avec succès")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Non autorisé"),
            new OA\Response(response: 403, description: "Accès interdit"),
            new OA\Response(response: 404, description: "Régime introuvable")
        ]
    )]
    public function delete(int $id): void
    {
        if (!$this->requireAdminOrEmploye()) return;

        $this->tryCatch(function () use ($id) {
            $regime = $this->repository->findById($id);
            if (!$regime) { $this->error('Régime introuvable', 404); return; }

            $this->repository->delete($id);
            $this->success(['message' => 'Régime supprimé avec succès']);
        });
    }
}