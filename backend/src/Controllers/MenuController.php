<?php

namespace App\Controllers;

use App\Core\AbstractController;
use App\Core\Security\Validator\MenuValidator;
use App\Models\Menu;
use App\Repository\MenuRepository;
use OpenApi\Attributes as OA;                                                                        // Fix: "Annotations" → "Attributes"

class MenuController extends AbstractController
{
    private MenuRepository $repository;
    private MenuValidator $validator;

    public function __construct()
    {
        $this->repository = new MenuRepository();
        $this->validator  = new MenuValidator();
    }
    #[OA\Post(
        path: "/api/menu/create",
        summary: "Créer un menu",
        description: "Crée un nouveau menu avec ses plats associés. Réservé aux administrateurs et employés.",
        tags: ["Menu"],
        security: [["cookieAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["titre","nombrePersonneMinimum","prixParPersonne","description","regimeId","themeId","delai","service","plats"],
                properties: [
                    new OA\Property(property: "titre",                 type: "string",  example: "Menu du Chef"),
                    new OA\Property(property: "nombrePersonneMinimum", type: "integer", example: 2),
                    new OA\Property(property: "prixParPersonne",       type: "number",  format: "float", example: 45.00),
                    new OA\Property(property: "description",           type: "string",  example: "Un menu gastronomique raffiné"),
                    new OA\Property(property: "quantiteRestante",      type: "integer", example: 10, description: "Défaut : 0"),
                    new OA\Property(property: "regimeId",              type: "integer", example: 1),
                    new OA\Property(property: "themeId",               type: "integer", example: 2),
                    new OA\Property(property: "image",                 type: "string",  example: "https://example.com/menu.jpg"),
                    new OA\Property(property: "delai",                 type: "integer", example: 48, description: "Délai en heures"),
                    new OA\Property(property: "service",               type: "string",  example: "midi"),
                    new OA\Property(
                        property: "plats",
                        type: "array",
                        items: new OA\Items(type: "integer", example: 1),
                        description: "Liste des identifiants de plats"
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Menu créé avec succès",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Menu créé avec succès")
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

            $menu = new Menu();
            $menu->setTitre($data['titre']);
            $menu->setNombrePersonneMinimum($data['nombrePersonneMinimum']);
            $menu->setPrixParPersonne($data['prixParPersonne']);
            $menu->setDescription($data['description']);
            $menu->setQuantiteRestante($data['quantiteRestante'] ?? 0);
            $menu->setRegimeId($data['regimeId']);
            $menu->setThemeId($data['themeId']);
            $menu->setImage($data['image'] ?? null);
            $menu->setStatut('actif');
            $menu->setDelai($data['delai']);
            $menu->setService($data['service']);
            foreach ($data['plats'] as $platId) {
                $menu->addPlatId((int)$platId);
            }

            $this->repository->create($menu);
            $this->success(['message' => 'Menu créé avec succès'], 201);
        });
    }
    #[OA\Get(
        path: "/api/menu/{id}",
        summary: "Afficher un menu",
        description: "Retourne les informations d'un menu par son identifiant. Accessible publiquement.",
        tags: ["Menu"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Identifiant du menu",
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Menu trouvé",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "id",                    type: "integer", example: 1),
                        new OA\Property(property: "titre",                 type: "string",  example: "Menu du Chef"),
                        new OA\Property(property: "nombrePersonneMinimum", type: "integer", example: 2),
                        new OA\Property(property: "prixParPersonne",       type: "number",  format: "float", example: 45.00),
                        new OA\Property(property: "description",           type: "string",  example: "Un menu gastronomique raffiné"),
                        new OA\Property(property: "quantiteRestante",      type: "integer", example: 10),
                        new OA\Property(property: "statut",                type: "string",  example: "actif"),
                        new OA\Property(property: "delai",                 type: "integer", example: 48),
                        new OA\Property(property: "service",               type: "string",  example: "midi"),
                        new OA\Property(property: "regimeId",              type: "integer", example: 1),
                        new OA\Property(property: "themeId",               type: "integer", example: 2),
                        new OA\Property(property: "image",                 type: "string",  example: "https://example.com/menu.jpg"),
                        new OA\Property(
                            property: "plats",
                            type: "array",
                            items: new OA\Items(type: "integer", example: 1)
                        )
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Menu introuvable")
        ]
    )]
    public function read(int $id): void
    {
        $this->tryCatch(function () use ($id) {
            $menu = $this->repository->findById($id);
            if (!$menu) { $this->error('Menu introuvable', 404); return; }

            $this->success($menu);
        });
    }
    #[OA\Get(
        path: "/api/menu",
        summary: "Lister tous les menus",
        description: "Retourne la liste complète des menus. Accessible publiquement.",
        tags: ["Menu"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Liste des menus",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: "id",     type: "integer", example: 1),
                            new OA\Property(property: "titre",  type: "string",  example: "Menu du Chef"),
                            new OA\Property(property: "statut", type: "string",  example: "actif")
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
        path: "/api/menu/{id}",
        summary: "Modifier un menu",
        description: "Met à jour les informations d'un menu existant. Tous les champs sont optionnels. Réservé aux administrateurs et employés.",
        tags: ["Menu"],
        security: [["cookieAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Identifiant du menu",
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "titre",                 type: "string",  example: "Menu Prestige"),
                    new OA\Property(property: "nombrePersonneMinimum", type: "integer", example: 4),
                    new OA\Property(property: "prixParPersonne",       type: "number",  format: "float", example: 60.00),
                    new OA\Property(property: "description",           type: "string",  example: "Version mise à jour"),
                    new OA\Property(property: "quantiteRestante",      type: "integer", example: 5),
                    new OA\Property(property: "regimeId",              type: "integer", example: 1),
                    new OA\Property(property: "themeId",               type: "integer", example: 3),
                    new OA\Property(property: "image",                 type: "string",  example: "https://example.com/new.jpg"),
                    new OA\Property(property: "statut",                type: "string",  example: "inactif"),
                    new OA\Property(property: "delai",                 type: "integer", example: 24),
                    new OA\Property(property: "service",               type: "string",  example: "soir"),
                    new OA\Property(
                        property: "plats",
                        type: "array",
                        items: new OA\Items(type: "integer", example: 3),
                        description: "Remplace la liste complète des plats"
                    )
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Menu mis à jour avec succès",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Menu mis à jour avec succès")
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Données invalides"),
            new OA\Response(response: 401, description: "Non autorisé"),
            new OA\Response(response: 403, description: "Accès interdit"),
            new OA\Response(response: 404, description: "Menu introuvable"),
            new OA\Response(response: 422, description: "Erreur de validation")
        ]
    )]
    public function update(int $id): void
    {
        if (!$this->requireAdminOrEmploye()) return;

        $this->tryCatch(function () use ($id) {
            $menu = $this->repository->findById($id);
            if (!$menu) { $this->error('Menu introuvable', 404); return; }

            $data = json_decode(file_get_contents("php://input"), true);
            if (!$data) { $this->error('Données invalides', 400); return; }

            $errors = $this->validator->validateUpdate($data);
            if ($errors) { $this->error($errors, 422); return; }

            $menuModel = Menu::createAndHydrate($menu);
            if (isset($data['titre']))                $menuModel->setTitre($data['titre']);
            if (isset($data['nombrePersonneMinimum'])) $menuModel->setNombrePersonneMinimum($data['nombrePersonneMinimum']);
            if (isset($data['prixParPersonne']))       $menuModel->setPrixParPersonne($data['prixParPersonne']);
            if (isset($data['description']))           $menuModel->setDescription($data['description']);
            if (isset($data['quantiteRestante']))      $menuModel->setQuantiteRestante($data['quantiteRestante']);
            if (isset($data['regimeId']))              $menuModel->setRegimeId($data['regimeId']);
            if (isset($data['themeId']))               $menuModel->setThemeId($data['themeId']);
            if (isset($data['image']))                 $menuModel->setImage($data['image']);
            if (isset($data['statut']))                $menuModel->setStatut($data['statut']);
            if (isset($data['delai']))                 $menuModel->setDelai($data['delai']);
            if (isset($data['service']))               $menuModel->setService($data['service']);
            if (isset($data['plats'])) {
                $menuModel->setPlats([]);
                foreach ($data['plats'] as $platId) {
                    $menuModel->addPlatId((int)$platId);
                }
            }

            $this->repository->update($menuModel);
            $this->success(['message' => 'Menu mis à jour avec succès']);
        });
    }
    #[OA\Delete(
        path: "/api/menu/{id}",
        summary: "Supprimer un menu",
        description: "Supprime un menu par son identifiant. Réservé aux administrateurs et employés.",
        tags: ["Menu"],
        security: [["cookieAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Identifiant du menu",
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Menu supprimé avec succès",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Menu supprimé avec succès")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Non autorisé"),
            new OA\Response(response: 403, description: "Accès interdit"),
            new OA\Response(response: 404, description: "Menu introuvable")
        ]
    )]
    public function delete(int $id): void
    {
        if (!$this->requireAdminOrEmploye()) return;

        $this->tryCatch(function () use ($id) {
            $menu = $this->repository->findById($id);
            if (!$menu) { $this->error('Menu introuvable', 404); return; }

            $this->repository->delete($id);
            $this->success(['message' => 'Menu supprimé avec succès']);
        });
    }
}