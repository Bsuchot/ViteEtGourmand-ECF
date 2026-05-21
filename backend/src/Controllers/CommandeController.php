<?php

namespace App\Controllers;

use App\Core\AbstractController;
use App\Core\MailService;
use App\Core\Security\Security;
use App\Core\Security\Validator\CommandeValidator;
use App\Models\Commande;
use App\Repository\CommandeRepository;
use App\Repository\MenuRepository;
use App\Repository\UtilisateurRepository;
use DateTimeImmutable;
use App\Core\MongoService;
use OpenApi\Attributes as OA;

class CommandeController extends AbstractController
{
    private CommandeValidator $validator;
    private CommandeRepository $repository;
    private MongoService $mongo;
    private MailService $mailer;

    public function __construct()
    {
        $this->repository = new CommandeRepository();
        $this->validator  = new CommandeValidator(new MenuRepository());
        $this->mongo = new MongoService();
        $this->mailer = new MailService(new UtilisateurRepository());
    }
    #[OA\Post(
        path: "/api/commande/create",
        summary: "Créer une commande",
        description: "Crée une nouvelle commande liée à l'utilisateur connecté. Le statut est 'en_attente' par défaut.",
        tags: ["Commande"],
        security: [["cookieAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["datePrestation","heureLivraison","adresseLivraison","prixMenu","nombrePersonne","prixLivraison","menuId"],
                properties: [
                    new OA\Property(property: "datePrestation", type: "string", example: "2024-03-15"),
                    new OA\Property(property: "heureLivraison", type: "string", example: "12:30"),
                    new OA\Property(property: "adresseLivraison", type: "string", example: "5 avenue Foch, 39000 Bordeaux"),
                    new OA\Property(property: "prixMenu", type: "float", example: 50.50),
                    new OA\Property(property: "nombrePersonne", type: "integer", example: 6),         // Fix: "interger" → "integer"
                    new OA\Property(property: "prixLivraison", type: "float", example: 50.50),
                    new OA\Property(property: "menuId", type: "integer", example: 1)                  // Fix: "interger" → "integer"
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Commande créée avec succès",                                            // Fix: accord féminin
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Commande créée avec succès") // Fix: accord féminin
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

            $commande = new Commande();
            $commande->setNumeroDeCommande($this->generateNumeroCommande());
            $commande->setDateCommande(new DateTimeImmutable());
            $commande->setDatePrestation($data['datePrestation']);
            $commande->setHeureLivraison($data['heureLivraison']);
            $commande->setAdresseLivraison($data['adresseLivraison']);
            $commande->setPrixMenu($data['prixMenu']);
            $commande->setNombrePersonne($data['nombrePersonne']);
            $commande->setPrixLivraison($data['prixLivraison']);
            $commande->setStatut('en attente');
            $commande->setPretMateriel(filter_var($data['pretMateriel'] ?? false, FILTER_VALIDATE_BOOLEAN));
            $commande->setRestitutionMateriel(false);
            $commande->setUtilisateurId($_SESSION['user']['id']);
            $commande->setMenuId($data['menuId']);
            $this->repository->create($commande);

            $menu = (new MenuRepository())->findById($commande->getMenuId());
            $menuTitre = $menu['titre'] ?? 'Menu inconnu';

            $this->mongo->getCollection("commandes_stats")->insertOne([
                "commandeId" => $commande->getId(),
                "menuId"     => $commande->getMenuId(),
                "menuTitre"  => $menuTitre,
                "total"      => $commande->getPrixMenu() + $commande->getPrixLivraison(),
                "date"       => new \MongoDB\BSON\UTCDateTime(),
                "statut"     => "en_attente"
            ]);

            $email = $data['email'] ?? $_SESSION['user']['email'] ?? null;
            error_log('Email destinataire : ' . $email);

            try {
                $this->mailer->sendConfirmationCommande($email, [
                    'numeroDeCommande' => $commande->getNumeroDeCommande(),
                    'datePrestation'   => $data['datePrestation'],
                    'heureLivraison'   => $data['heureLivraison'],
                    'prixMenu'         => $commande->getPrixMenu(),
                    'prixLivraison'    => $commande->getPrixLivraison(),
                ]);
                error_log('Mail envoyé avec succès');
            } catch (\Exception $e) {
                error_log('Erreur mail : ' . $e->getMessage());
            }

            $this->success(['message' => 'Commande créée avec succès'], 201);
        });
    }
    public function calculerFrais(): void
    {
        $data    = json_decode(file_get_contents("php://input"), true);
        $adresse = $data['adresse'] ?? '';

        // 1. Géocoder via api-adresse.data.gouv.fr
        $geoRes = file_get_contents(
            "https://api-adresse.data.gouv.fr/search/?q=" . urlencode($adresse) . "&limit=1"
        );
        $geo    = json_decode($geoRes, true);
        $coords = $geo['features'][0]['geometry']['coordinates'] ?? null; // [lng, lat]

        if (!$coords) { $this->success(['frais' => 5.00]); return; }

        // 2. Appel ORS via curl
        $bordeaux = [-0.5792, 44.8378];
        $payload  = json_encode(['coordinates' => [$bordeaux, $coords]]);

        $ch = curl_init('https://api.openrouteservice.org/v2/directions/driving-car');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $_ENV['ORS_API_KEY'],

            ],
        ]);
        error_log('ORS KEY: ' . $_ENV['ORS_API_KEY']);
        $orsRes = curl_exec($ch);
        curl_close($ch);

        $ors = json_decode($orsRes, true);
        error_log('ORS response: ' . print_r($ors, true));
        error_log('Distance km: ' . ($ors['routes'][0]['summary']['distance'] ?? 'NULL'));
        $distanceKm = ($ors['routes'][0]['summary']['distance'] ?? 0) / 1000;

        // 3. Calcul frais (0 si ≤ 5km de Bordeaux)
        $frais = $distanceKm <= 5 ? 0.0 : 5.00 + (0.59 * $distanceKm);

        $this->success(['frais' => round($frais, 2)]);
    }
    #[OA\Get(
        path: "/api/commande/{id}",
        summary: "Afficher une commande",
        description: "Retourne les informations d'une commande par son identifiant.",
        tags: ["Commande"],
        security: [["cookieAuth" => []]],                                                             // Fix: sécurité manquante
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Identifiant de la commande",
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Commande trouvée",                                                      // Fix: accord féminin
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "id", type: "integer", example: 1),
                        new OA\Property(property: "numeroDeCommande", type: "string", example: "CMD-20240401-001"), // Fix: exemple plus représentatif
                        new OA\Property(property: "dateCommande", type: "string", example: "2020-04-01"),
                        new OA\Property(property: "datePrestation", type: "string", example: "2024-04-01"),
                        new OA\Property(property: "heureLivraison", type: "string", example: "10:00"),
                        new OA\Property(property: "adresseLivraison", type: "string", example: "5 avenue Foch, 39000 Bordeaux"), // Fix: "interger" → "string"
                        new OA\Property(property: "prixMenu", type: "float", example: 60.50),         // Fix: example string → float
                        new OA\Property(property: "nombrePersonne", type: "integer", example: 10),    // Fix: "interger" → "integer"
                        new OA\Property(property: "prixLivraison", type: "float", example: 90.00),    // Fix: example string → float
                        new OA\Property(property: "pretMateriel", type: "boolean", example: true),    // Fix: example string → bool
                        new OA\Property(property: "restitutionMateriel", type: "boolean", example: true), // Fix: example string → bool
                        new OA\Property(property: "utilisateurId", type: "integer", example: 1),      // Fix: example string → int
                        new OA\Property(property: "menuId", type: "integer", example: 1),             // Fix: example string → int
                        new OA\Property(property: "statut", type: "string", example: "en_attente")
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Non autorisé"),
            new OA\Response(response: 403, description: "Accès interdit — la commande n'appartient pas à l'utilisateur connecté"),
            new OA\Response(response: 404, description: "Commande introuvable")
        ]
    )]
    public function read(int $id): void
    {
        if (!$this->requireLogin()) return;

        $this->tryCatch(function () use ($id) {
            $commande = $this->repository->findById($id);
            if (!$commande) { $this->error('Commande introuvable', 404); return; }
            if (!$this->checkAccess(fn() => $this->requireOwner($commande))) return;

            $this->success($commande);
        });
    }
    public function readById(int $id): void
    {
        if (!$this->requireAdminOrEmploye()) return;
        $this->tryCatch(function () use ($id) {
            $commande = $this->repository->findById($id);
            if (!$commande) { $this->error('Commande introuvable', 404); return; }
            $this->success($commande);
        });
    }
    #[OA\Get(
        path: "/api/commande/mesCommandes",
        summary: "Lister mes commandes",
        description: "Retourne toutes les commandes de l'utilisateur connecté.",
        tags: ["Commande"],
        security: [["cookieAuth" => []]],                                                             // Fix: sécurité manquante
        // Fix: paramètre "id" supprimé — non pertinent pour cette route (utilisateur identifié via session)
        responses: [
            new OA\Response(
                response: 200,
                description: "Liste des commandes de l'utilisateur connecté",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "numeroDeCommande", type: "string", example: "CMD-20240401-001"), // Fix: exemple plus représentatif
                            new OA\Property(property: "dateCommande", type: "string", example: "2020-04-01"),
                            new OA\Property(property: "datePrestation", type: "string", example: "2024-04-01"),
                            new OA\Property(property: "heureLivraison", type: "string", example: "10:00"),
                            new OA\Property(property: "adresseLivraison", type: "string", example: "5 avenue Foch, 39000 Bordeaux"), // Fix: "interger" → "string"
                            new OA\Property(property: "prixMenu", type: "float", example: 60.50),     // Fix: example string → float
                            new OA\Property(property: "nombrePersonne", type: "integer", example: 10), // Fix: "interger" → "integer"
                            new OA\Property(property: "prixLivraison", type: "float", example: 90.00), // Fix: example string → float
                            new OA\Property(property: "pretMateriel", type: "boolean", example: true), // Fix: example string → bool
                            new OA\Property(property: "restitutionMateriel", type: "boolean", example: true), // Fix: example string → bool
                            new OA\Property(property: "utilisateurId", type: "integer", example: 1),  // Fix: example string → int
                            new OA\Property(property: "menuId", type: "integer", example: 1),         // Fix: example string → int
                            new OA\Property(property: "statut", type: "string", example: "en_attente")
                        ]
                    )
                )
            ),
            new OA\Response(response: 401, description: "Non autorisé")
        ]
    )]
    public function readMyCommandes(): void
    {
        if (!$this->requireLogin()) return;

        $this->tryCatch(function () {
            $commandes = $this->repository->findByUtilisateurId($_SESSION['user']['id']);
            $this->success($commandes);
        });
    }
    #[OA\Get(
        path: "/api/commande",
        summary: "Lister toutes les commandes",
        description: "Retourne la liste complète des commandes. Réservé aux administrateurs et employés.",
        tags: ["Commande"],
        security: [["cookieAuth" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Liste des commandes",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "numeroDeCommande", type: "string", example: "CMD-20240401-001"), // Fix: exemple plus représentatif
                            new OA\Property(property: "dateCommande", type: "string", example: "2020-04-01"),
                            new OA\Property(property: "datePrestation", type: "string", example: "2024-04-01"),
                            new OA\Property(property: "heureLivraison", type: "string", example: "10:00"),
                            new OA\Property(property: "adresseLivraison", type: "string", example: "5 avenue Foch, 39000 Bordeaux"), // Fix: "interger" → "string"
                            new OA\Property(property: "prixMenu", type: "float", example: 60.50),     // Fix: example string → float
                            new OA\Property(property: "nombrePersonne", type: "integer", example: 10), // Fix: "interger" → "integer"
                            new OA\Property(property: "prixLivraison", type: "float", example: 90.00), // Fix: example string → float
                            new OA\Property(property: "pretMateriel", type: "boolean", example: true), // Fix: example string → bool
                            new OA\Property(property: "restitutionMateriel", type: "boolean", example: true), // Fix: example string → bool
                            new OA\Property(property: "utilisateurId", type: "integer", example: 1),  // Fix: example string → int
                            new OA\Property(property: "menuId", type: "integer", example: 1),         // Fix: example string → int
                            new OA\Property(property: "statut", type: "string", example: "en_attente")
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
        if (!$this->requireAdminOrEmploye()) return;

        $this->tryCatch(fn () => $this->success($this->repository->findAll()));
    }
    #[OA\Get(
        path: "/api/commande/stats",
        summary: "Statistiques commandes",
        tags: ["Commande"]
    )]
    public function stats(): void
    {
        if (!$this->requireAdminOrEmploye()) return;

        $this->tryCatch(function () {
            $menuTitre = $_GET['menuTitre'] ?? null;
            $dateDebut = $_GET['dateDebut'] ?? null;
            $dateFin   = $_GET['dateFin']   ?? null;

            $match = [];
            if ($menuTitre) $match['menuTitre']    = $menuTitre;
            if ($dateDebut) $match['date']['$gte'] = new \MongoDB\BSON\UTCDateTime(strtotime($dateDebut) * 1000);
            if ($dateFin)   $match['date']['$lte'] = new \MongoDB\BSON\UTCDateTime(strtotime($dateFin)   * 1000);

            $pipeline = [];
            if ($match) $pipeline[] = ['$match' => $match];

            $pipeline[] = ['$group' => [
                '_id'        => '$menuTitre',
                'total'      => ['$sum' => '$total'],
                'commandes'  => ['$sum' => 1],
            ]];
            $pipeline[] = ['$sort' => ['total' => -1]];

            $results = $this->mongo->getCollection('commandes_stats')
                ->aggregate($pipeline)
                ->toArray();

            $this->success(array_map(fn($r) => [
                'menu'      => $r['_id'],
                'total'     => round($r['total'], 2),
                'commandes' => $r['commandes'],
            ], $results));
        });
    }
    #[OA\Put(
        path: "/api/commande/{id}",
        summary: "Modifier une commande",
        description: "Met à jour les données d'une commande existante. Uniquement si la commande est encore modifiable (statut 'en_attente'). Réservé au propriétaire de la commande.",
        tags: ["Commande"],
        security: [["cookieAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Identifiant de la commande",
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["datePrestation","heureLivraison","adresseLivraison","prixMenu","nombrePersonne","prixLivraison","pretMateriel"],
                properties: [
                    new OA\Property(property: "datePrestation", type: "string", example: "2024-04-01"),
                    new OA\Property(property: "heureLivraison", type: "string", example: "10:00"),
                    new OA\Property(property: "adresseLivraison", type: "string", example: "5 avenue Foch, 39000 Bordeaux"),
                    new OA\Property(property: "prixMenu", type: "float", example: 10.50),
                    new OA\Property(property: "nombrePersonne", type: "integer", example: 5),
                    new OA\Property(property: "prixLivraison", type: "float", example: 100.50),        // Fix: type "integer" → "float"
                    new OA\Property(property: "pretMateriel", type: "boolean", example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Commande mise à jour avec succès",                                      // Fix: accord féminin
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Commande mise à jour avec succès") // Fix: accord féminin
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Données invalides"),
            new OA\Response(response: 401, description: "Non autorisé"),
            new OA\Response(response: 403, description: "Accès interdit — commande non modifiable ou n'appartenant pas à l'utilisateur"),
            new OA\Response(response: 404, description: "Commande introuvable"),
            new OA\Response(response: 422, description: "Erreur de validation")
        ]
    )]
    public function update(int $id): void
    {
        if (!$this->requireLogin()) return;

        $this->tryCatch(function () use ($id) {
            $commande = $this->repository->findById($id);
            if (!$commande) { $this->error('Commande introuvable', 404); return; }
            if (!$this->checkAccess(fn() => $this->requireOwner($commande) && $this->checkOrderStatut($commande))) return;

            $data = json_decode(file_get_contents("php://input"), true);
            if (!$data) { $this->error('Données invalides', 400); return; }

            $errors = $this->validator->validateUpdate($data);
            if ($errors) { $this->error($errors, 422); return; }

            $commandeModel = Commande::createAndHydrate($commande);
            if (isset($data['datePrestation']))   $commandeModel->setDatePrestation($data['datePrestation']);
            if (isset($data['heureLivraison']))   $commandeModel->setHeureLivraison($data['heureLivraison']);
            if (isset($data['adresseLivraison'])) $commandeModel->setAdresseLivraison($data['adresseLivraison']);
            if (isset($data['prixMenu']))         $commandeModel->setPrixMenu($data['prixMenu']);
            if (isset($data['nombrePersonne']))   $commandeModel->setNombrePersonne($data['nombrePersonne']);
            if (isset($data['prixLivraison']))    $commandeModel->setPrixLivraison($data['prixLivraison']);
            if (isset($data['pretMateriel']))    $commandeModel->setPretMateriel(filter_var($data['pretMateriel'], FILTER_VALIDATE_BOOLEAN));
            $this->repository->update($commandeModel);

            $this->success(['message' => 'Commande mise à jour avec succès']);
        });
    }
    #[OA\Put(
        path: "/api/employe/commande/{id}/statut",
        summary: "Modifier le statut d'une commande",
        description: "Met à jour le statut d'une commande existante. Réservé aux administrateurs et employés.",
        tags: ["Commande"],                                                                           // Fix: "Avis" → "Commande"
        security: [["cookieAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Identifiant de la commande",                                           // Fix: "de l'avis" → "de la commande"
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["statut"],
                properties: [
                    new OA\Property(property: "statut", type: "string", example: "accepté", description: "en attente | accepté | en cours de livraison | en attente du retour de matériel | terminee"), // Fix: "livraidon" → "livraison"
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Statut de la commande mis à jour avec succès",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Statut de la commande mis à jour avec succès") // Fix: espace manquant "laCommande"
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Données invalides"),
            new OA\Response(response: 401, description: "Non autorisé"),
            new OA\Response(response: 403, description: "Accès interdit — conditions non remplies pour passer au statut 'terminee'"),
            new OA\Response(response: 404, description: "Commande introuvable"),
            new OA\Response(response: 422, description: "Erreur de validation")
        ]
    )]
    public function updateStatut(int $id): void
    {
        if (!$this->requireAdminOrEmploye()) return;

        $this->tryCatch(function () use ($id) {
            $commande = $this->repository->findById($id);
            if (!$commande) { $this->error('Commande introuvable', 404); return; }

            $data = json_decode(file_get_contents("php://input"), true);
            if (!$data) { $this->error('Données invalides', 400); return; }

            $errors = $this->validator->validateStatut($data);
            if ($errors) { $this->error($errors, 422); return; }

            if ($data['statut'] === 'terminee') {
                if (!$this->checkCommandeTerminable($commande)) return;
            }

            $commandeModel = Commande::createAndHydrate($commande);
            $commandeModel->setStatut($data['statut']);
            $this->repository->update($commandeModel);

            $this->mongo->getCollection("commandes_stats")->updateOne(
                ["commandeId" => $id],
                [
                    '$set' => [
                        "statut" => $data['statut']
                    ]
                ]
            );


            $this->mailer->sendChangementStatutCommande(
                $commande['utilisateurEmail'],
                $commande['utilisateurPrenom'],
                $commande['numeroDeCommande'],
                $data['statut']
            );

            $this->success(['message' => 'Statut de la commande mis à jour']);
        });
    }
    #[OA\Delete(
        path: "/api/commande/{id}",
        summary: "Supprimer une commande",
        description: "Supprime une commande. Réservé au propriétaire, uniquement si la commande est encore annulable (statut 'en_attente') ou administrateur et employés.",
        tags: ["Commande"],
        security: [["cookieAuth" => []]],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "Identifiant de la commande",
                schema: new OA\Schema(type: "integer", example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Commande supprimée avec succès",                                        // Fix: accord féminin
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Commande supprimée avec succès") // Fix: accord féminin
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Non autorisé"),
            new OA\Response(response: 403, description: "Accès interdit"),
            new OA\Response(response: 404, description: "Commande introuvable")
        ]
    )]
    public function delete(int $id): void
    {
        if (!$this->requireLogin()) return;

        $this->tryCatch(function () use ($id) {
            $commande = $this->repository->findById($id);
            if (!$commande) { $this->error('Commande introuvable', 404); return; }

            if (!$this->checkAccess(fn() => $this->requireOwner($commande) && $this->checkOrderStatut($commande))) return;

            $this->repository->delete($id);
            $this->success(['message' => 'Commande supprimée avec succès']);
        });
    }
}