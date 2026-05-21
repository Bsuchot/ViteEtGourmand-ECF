<?php

namespace App\Controllers;

use App\Core\AbstractController;
use App\Core\Security\Validator\AvisValidator;
use App\Models\Avis;
use App\Repository\AvisRepository;
use OpenApi\Attributes as OA;

class AvisController extends AbstractController
{
    private AvisValidator $validator;
    private AvisRepository $repository;

    public function __construct()
    {
        $this->repository = new AvisRepository();
        $this->validator  = new AvisValidator();
    }
    #[OA\Post(
        path: "/api/avis/create",
        summary: "Déposer un avis",
        description: "Crée un nouvel avis lié à l'utilisateur connecté. Le statut est 'en_attente' par défaut.",
        tags: ["Avis"],
        security: [["cookieAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["titre","description","note"],
                properties: [
                    new OA\Property(property: "titre", type: "string", example: "Mon avis"),
                    new OA\Property(property: "description", type: "string", example: "Ma description"),
                    new OA\Property(property: "note", type: "integer", example: 5, description: "Note de 1 à 5") // Fix: "interger" → "integer"
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Avis créé avec succès",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Avis créé avec succès")
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
        if (!$this->requireLogin()) return;

        $this->tryCatch(function () {
            $data = json_decode(file_get_contents("php://input"), true);
            if (!$data) { $this->error('Données invalides', 400); return; }

            $errors = $this->validator->validate($data);
            if ($errors) { $this->error($errors, 422); return; }

            $avis = new Avis();
            $avis->setTitre($data['titre']);
            $avis->setDescription($data['description']);
            $avis->setNote($data['note']);
            $avis->setStatut('en_attente');
            $avis->setDate(new \DateTimeImmutable());
            $avis->setUtilisateurId($_SESSION['user']['id']);
            $this->repository->create($avis);

            $this->success(['message' => 'Avis créé avec succès'], 201);
        });
    }
    #[OA\Get(
        path: "/api/avis/{id}",
        summary: "Afficher un avis",
        description: "Retourne les informations d'un avis par son identifiant. Accessible publiquement.",
        tags: ["Avis"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Identifiant de l'avis",
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Avis trouvé",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "titre", type: "string", example: "Mon avis"),
                        new OA\Property(property: "description", type: "string", example: "Ma description"),
                        new OA\Property(property: "note", type: "integer", example: 5),              // Fix: "interger" → "integer"
                        new OA\Property(property: "statut", type: "string", example: "publié"),      // Fix: "Publiée" → "publié" (masculin)
                        new OA\Property(property: "date", type: "string", example: "2026-05-12"),
                        new OA\Property(property: "utilisateurId", type: "integer", example: 1)      // Fix: "interger" → "integer"
                    ]
                )
            ),
            new OA\Response(response: 404, description: "Avis introuvable")
        ]
    )]
    public function read(int $id): void
    {
        $this->tryCatch(function () use ($id) {
            $avis = $this->repository->findById($id);
            if (!$avis) { $this->error('Avis introuvable', 404); return; }

            $this->success($avis);
        });
    }
    #[OA\Get(
        path: "/api/avis",
        summary: "Lister tous les avis",
        description: "Retourne la liste complète des avis. Accessible publiquement.",
        tags: ["Avis"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Liste des avis",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "titre", type: "string", example: "Mon avis"),
                            new OA\Property(property: "description", type: "string", example: "Ma description"),
                            new OA\Property(property: "note", type: "integer", example: 5),          // Fix: "interger" → "integer"
                            new OA\Property(property: "statut", type: "string", example: "publié"),  // Fix: "Publiée" → "publié"
                            new OA\Property(property: "date", type: "string", example: "2026-05-12"),
                            new OA\Property(property: "utilisateurId", type: "integer", example: 1)  // Fix: "interger" → "integer"
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
        path: "/api/avis/{id}",
        summary: "Modifier un avis",
        description: "Met à jour les données d'un avis existant. Le statut est remis 'en_attente' par défaut. Réservé au propriétaire de l'avis.",
        tags: ["Avis"],
        security: [["cookieAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Identifiant de l'avis",
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["titre","description","note"],
                properties: [
                    new OA\Property(property: "titre", type: "string", example: "Mon avis"),
                    new OA\Property(property: "description", type: "string", example: "Ma description"),
                    new OA\Property(property: "note", type: "integer", example: 5),                  // Fix: "interger" → "integer"
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Avis mis à jour avec succès",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Avis mis à jour avec succès")
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Données invalides"),
            new OA\Response(response: 401, description: "Non autorisé"),
            new OA\Response(response: 403, description: "Accès interdit — l'avis n'appartient pas à l'utilisateur connecté"),
            new OA\Response(response: 404, description: "Avis introuvable"),                         // Fix: "Statut introuvable" → "Avis introuvable"
            new OA\Response(response: 422, description: "Erreur de validation")
        ]
    )]
    public function update(int $id): void
    {
        if (!$this->requireLogin()) return;

        $this->tryCatch(function () use ($id) {
            $avis = $this->repository->findById($id);
            if (!$avis) { $this->error('Avis introuvable', 404); return; }
            if (!$this->requireOwner($avis)) return;

            $data = json_decode(file_get_contents("php://input"), true);
            if (!$data) { $this->error('Données invalides', 400); return; }

            $errors = $this->validator->validateUpdate($data);
            if ($errors) { $this->error($errors, 422); return; }

            $avisModel = Avis::createAndHydrate($avis);
            $avisModel->setTitre($data['titre']);
            $avisModel->setDescription($data['description']);
            $avisModel->setNote($data['note']);
            $avisModel->setStatut("en_attente");
            $this->repository->update($avisModel);

            $this->success(['message' => 'Avis mis à jour avec succès']);
        });
    }
    #[OA\Put(
        path: "/api/employe/avis/{id}/statut",
        summary: "Modifier le statut d'un avis",
        description: "Met à jour le statut d'un avis existant. Réservé aux administrateurs et employés.",
        tags: ["Avis"],
        security: [["cookieAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Identifiant de l'avis",
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["statut"],
                properties: [
                    new OA\Property(property: "statut", type: "string", example: "publié", description: "en_attente | publié | rejeté"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Statut de l'avis mis à jour avec succès",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Statut de l'avis mis à jour avec succès")
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Données invalides"),
            new OA\Response(response: 401, description: "Non autorisé"),
            new OA\Response(response: 403, description: "Accès interdit"),
            new OA\Response(response: 404, description: "Avis introuvable"),                         // Fix: "Statut introuvable" → "Avis introuvable"
            new OA\Response(response: 422, description: "Erreur de validation")
        ]
    )]
    public function updateStatut(int $id): void
    {
        if (!$this->requireAdminOrEmploye()) return;

        $this->tryCatch(function () use ($id) {
            $avis = $this->repository->findById($id);
            if (!$avis) { $this->error('Avis introuvable', 404); return; }
            if (!$this->checkAccess(fn() => $this->requireOwner($avis))) return;

            $data = json_decode(file_get_contents("php://input"), true);
            if (!$data) { $this->error('Données invalides', 400); return; }

            $errors = $this->validator->validateStatut($data);
            if ($errors) { $this->error($errors, 422); return; }

            $avisModel = Avis::createAndHydrate($avis);
            $avisModel->setStatut($data['statut']);
            $this->repository->update($avisModel);

            $this->success(['message' => "Statut de l'avis mis à jour avec succès"]);
        });
    }
    #[OA\Delete(
        path: "/api/avis/{id}",
        summary: "Supprimer un avis",
        description: "Supprime un avis par son identifiant. Réservé aux administrateurs, employés et au propriétaire de l'avis.",
        tags: ["Avis"],
        security: [["cookieAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Identifiant de l'avis",
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Avis supprimé avec succès",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Avis supprimé avec succès")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Non autorisé"),
            new OA\Response(response: 403, description: "Accès interdit"),
            new OA\Response(response: 404, description: "Avis introuvable")
        ]
    )]
    public function delete(int $id): void
    {
        if (!$this->requireLogin()) return;

        $this->tryCatch(function () use ($id) {
            $avis = $this->repository->findById($id);
            if (!$avis) { $this->error('Avis introuvable', 404); return; }
            if (!$this->checkAccess(fn() => $this->requireOwner($avis))) return;

            $this->repository->delete($id);
            $this->success(['message' => 'Avis supprimé avec succès']);
        });
    }
}