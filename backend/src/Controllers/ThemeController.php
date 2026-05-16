<?php

namespace App\Controllers;

use App\Core\AbstractController;
use App\Core\Security\Validator\LibelleValidator;
use App\Models\Theme;
use App\Repository\ThemeRepository;
use OpenApi\Attributes as OA;

class ThemeController extends AbstractController
{
    private LibelleValidator $validator;
    private ThemeRepository $repository;

    public function __construct()
    {
        $this->repository = new ThemeRepository();
        $this->validator  = new LibelleValidator($this->repository, 'Ce thème');
    }

    #[OA\Post(
        path: "/api/theme/create",
        summary: "Créer un thème",
        description: "Crée un nouveau thème. Réservé aux administrateurs et employés.",
        tags: ["Thème"],
        security: [["cookieAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["libelle"],
                properties: [
                    new OA\Property(property: "libelle", type: "string", example: "Noël")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Thème créé avec succès",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Thème créé avec succès")
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

            $theme = new Theme();
            $theme->setLibelle($data['libelle']);
            $this->repository->create($theme);

            $this->success(['message' => 'Thème créé avec succès'], 201);
        });
    }

    #[OA\Get(
        path: "/api/theme/{id}",
        summary: "Afficher un thème",
        description: "Retourne les informations d'un thème par son identifiant. Accessible publiquement.",
        tags: ["Thème"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Identifiant du thème",
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Thème trouvé",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "libelle", type: "string", example: "Noël")
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Thème introuvable")
        ]
    )]
    public function read(int $id): void
    {
        $this->tryCatch(function () use ($id) {
            $theme = $this->repository->findById($id);
            if (!$theme) { $this->error('Thème introuvable', 404); return; }

            $this->success($theme);
        });
    }

    #[OA\Get(
        path: "/api/theme",
        summary: "Lister tous les thèmes",
        description: "Retourne la liste complète des thèmes. Accessible publiquement.",
        tags: ["Thème"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Liste des thèmes",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "libelle", type: "string", example: "Noël")
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
        path: "/api/theme/{id}",
        summary: "Modifier un thème",
        description: "Met à jour le libellé d'un thème existant. Réservé aux administrateurs et employés.",
        tags: ["Thème"],
        security: [["cookieAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Identifiant du thème",
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["libelle"],
                properties: [
                    new OA\Property(property: "libelle", type: "string", example: "Hivernal")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Thème mis à jour avec succès",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Thème mis à jour avec succès")
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Données invalides"),
            new OA\Response(response: 401, description: "Non autorisé"),
            new OA\Response(response: 403, description: "Accès interdit"),
            new OA\Response(response: 404, description: "Thème introuvable"),
            new OA\Response(response: 422, description: "Erreur de validation")
        ]
    )]
    public function update(int $id): void
    {
        if (!$this->requireAdminOrEmploye()) return;

        $this->tryCatch(function () use ($id) {
            $theme = $this->repository->findById($id);
            if (!$theme) { $this->error('Thème introuvable', 404); return; }

            $data = json_decode(file_get_contents("php://input"), true);
            if (!$data) { $this->error('Données invalides', 400); return; }

            $errors = $this->validator->validateUpdate($data, $id);
            if ($errors) { $this->error($errors, 422); return; }

            $themeModel = Theme::createAndHydrate($theme);
            $themeModel->setLibelle($data['libelle']);
            $this->repository->update($themeModel);

            $this->success(['message' => 'Thème mis à jour avec succès']);
        });
    }


    #[OA\Delete(
        path: "/api/theme/{id}",
        summary: "Supprimer un thème",
        description: "Supprime un thème par son identifiant. Réservé aux administrateurs et employés.",
        tags: ["Thème"],
        security: [["cookieAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Identifiant du thème",
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Thème supprimé avec succès",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Thème supprimé avec succès")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Non autorisé"),
            new OA\Response(response: 403, description: "Accès interdit"),
            new OA\Response(response: 404, description: "Thème introuvable")
        ]
    )]
    public function delete(int $id): void
    {
        if (!$this->requireAdminOrEmploye()) return;

        $this->tryCatch(function () use ($id) {
            $theme = $this->repository->findById($id);
            if (!$theme) { $this->error('Thème introuvable', 404); return; }

            $this->repository->delete($id);
            $this->success(['message' => 'Thème supprimé avec succès']);
        });
    }
}