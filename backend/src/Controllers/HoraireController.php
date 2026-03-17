<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DbConnection;
use App\Models\Horaire;
use App\Repository\HoraireRepository;
use PDO;

class HoraireController extends Controller
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = DbConnection::getPDO();
    }


    public function create(): void
    {
        $data  = json_decode(file_get_contents("php://input"), true);
        $items = isset($data[0]) ? $data : [$data];

        $stmt = $this->pdo->prepare('
            INSERT INTO horaire (jour, heure_ouverture, heure_fermeture, statut)
            VALUES (:jour, :heureOuverture, :heureFermeture, :statut)
        ');

        $created = [];

        foreach ($items as $item) {
            if (empty($item['jour']) || empty($item['heureOuverture']) || empty($item['heureFermeture']) || empty($item['statut'])) {
                $this->error('Données manquantes pour un des horaires', 400);
                return;
            }

            $horaire = new Horaire();
            $horaire->setJour($item['jour']);
            $horaire->setHeureOuverture($item['heureOuverture']);
            $horaire->setHeureFermeture($item['heureFermeture']);
            $horaire->setStatut($item['statut']);

            $stmt->execute([
                'jour'           => $horaire->getJour(),
                'heureOuverture' => $horaire->getHeureOuverture(),
                'heureFermeture' => $horaire->getHeureFermeture(),
                'statut'         => $horaire->getStatut()
            ]);

            $created[] = $this->pdo->lastInsertId();
        }

        $this->success([
            'message' => count($created) . ' Horaire(s) créé(s) avec succès',
            'ids'     => $created
        ], 201);
    }

    public function read(): void
    {
        $horaireRepository = new HoraireRepository();
        $horaires = $horaireRepository->findAll();
        var_dump($horaires);

        $horaire = $horaireRepository->findById(1);



    }

    public function update(): void
    {
    }

    public function delete(): void
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $id   = $data['horaire_id'] ?? null;

        if (!$id) {
            $this->error('ID manquant', 400);
            return;
        }

        $stmt = $this->pdo->prepare('SELECT * FROM horaire WHERE horaire_id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            $this->error('Horaire introuvable', 404);
            return;
        }

        $stmt = $this->pdo->prepare('DELETE FROM horaire WHERE horaire_id = :id');
        $stmt->execute(['id' => $id]);

        $this->success(['message' => 'Horaire supprimé avec succès']);
    }
}