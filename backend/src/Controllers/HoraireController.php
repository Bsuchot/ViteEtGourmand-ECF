<?php

namespace App\Controllers;

use App\Core\AbstractController;
use App\Core\Security\Validator\HoraireValidator;
use App\Models\Horaire;
use App\Repository\HoraireRepository;
use OpenApi\Attributes as OA;                                                                        // Fix: "Annotations" → "Attributes"

class HoraireController extends AbstractController
{
    private HoraireValidator $validator;
    private HoraireRepository $repository;

    public function __construct()
    {
        $this->repository = new HoraireRepository();
        $this->validator  = new HoraireValidator($this->repository);
    }
    #[OA\Post(
        path: "/api/horaire/create",
        summary: "Créer un horaire",
        description: "Crée un nouvel horaire d'ouverture. Réservé aux administrateurs et employés.",
        tags: ["Horaire"],
        security: [["cookieAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["jour","heureOuverture","heureFermeture","statut"],
                properties: [
                    new OA\Property(property: "jour",            type: "string", example: "Lundi"),
                    new OA\Property(property: "heureOuverture",  type: "string", example: "09:00"),
                    new OA\Property(property: "heureFermeture",  type: "string", example: "18:00"),
                    new OA\Property(property: "statut",          type: "string", example: "ouvert", description: "ouvert | fermé")
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Horaire créé avec succès",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Horaire créé avec succès")
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

            $horaire = new Horaire();
            $horaire->setJour($data['jour']);
            $horaire->setHeureOuverture($data['heureOuverture']);
            $horaire->setHeureFermeture($data['heureFermeture']);
            $horaire->setStatut($data['statut']);
            $this->repository->create($horaire);

            $this->success(['message' => 'Horaire créé avec succès'], 201);
        });
    }
    #[OA\Get(
        path: "/api/horaire",
        summary: "Lister tous les horaires",
        description: "Retourne la liste complète des horaires d'ouverture. Accessible publiquement.",
        tags: ["Horaire"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Liste des horaires",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: "id",             type: "integer", example: 1),
                            new OA\Property(property: "jour",           type: "string",  example: "Lundi"),
                            new OA\Property(property: "heureOuverture", type: "string",  example: "09:00"),
                            new OA\Property(property: "heureFermeture", type: "string",  example: "18:00"),
                            new OA\Property(property: "statut",         type: "string",  example: "ouvert")
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
        path: "/api/horaire",
        summary: "Modifier plusieurs horaires",
        description: "Met à jour un ou plusieurs horaires en une seule requête. Chaque élément doit contenir un 'id'. Réservé aux administrateurs et employés.",
        tags: ["Horaire"],
        security: [["cookieAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: "array",
                items: new OA\Items(
                    required: ["id"],
                    properties: [
                        new OA\Property(property: "id",             type: "integer", example: 1),
                        new OA\Property(property: "jour",           type: "string",  example: "Mardi"),
                        new OA\Property(property: "heureOuverture", type: "string",  example: "10:00"),
                        new OA\Property(property: "heureFermeture", type: "string",  example: "19:00"),
                        new OA\Property(property: "statut",         type: "string",  example: "fermé")
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Horaires mis à jour",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Horaires mis à jour")
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Données invalides"),
            new OA\Response(response: 401, description: "Non autorisé"),
            new OA\Response(response: 403, description: "Accès interdit"),
            new OA\Response(response: 422, description: "Erreurs de validation par index")
        ]
    )]
    public function update(): void
    {
        if (!$this->requireAdminOrEmploye()) return;

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

                $horaireData = $this->repository->findById($item['id']);
                if (!$horaireData) continue;

                $horaire = Horaire::createAndHydrate($horaireData);
                if (isset($item['jour']))           $horaire->setJour($item['jour']);
                if (isset($item['heureOuverture'])) $horaire->setHeureOuverture($item['heureOuverture']);
                if (isset($item['heureFermeture'])) $horaire->setHeureFermeture($item['heureFermeture']);
                if (isset($item['statut']))         $horaire->setStatut($item['statut']);
                $this->repository->update($horaire);
            }

            $this->success(['message' => 'Horaires mis à jour'], 200);
        });
    }
    #[OA\Delete(
        path: "/api/horaire/{id}",
        summary: "Supprimer un horaire",
        description: "Supprime un horaire par son identifiant. Réservé aux administrateurs et employés.",
        tags: ["Horaire"],
        security: [["cookieAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Identifiant de l'horaire",
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Horaire supprimé avec succès",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Horaire supprimé avec succès")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Non autorisé"),
            new OA\Response(response: 403, description: "Accès interdit"),
            new OA\Response(response: 404, description: "Horaire introuvable")
        ]
    )]
    public function delete(int $id): void
    {
        if (!$this->requireAdminOrEmploye()) return;

        $this->tryCatch(function () use ($id) {
            $horaire = $this->repository->findById($id);
            if (!$horaire) { $this->error('Horaire introuvable', 404); return; }

            $this->repository->delete($id);
            $this->success(['message' => 'Horaire supprimé avec succès']);
        });
    }
}