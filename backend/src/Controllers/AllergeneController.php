<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DbConnection;
use App\Models\Allergene;
use PDO;

class AllergeneController extends Controller
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = DbConnection::getPDO();
    }

    public function create(): void
    {
        $data  = json_decode(file_get_contents("php://input"), true);
        if (!$data || !isset($data['libelle'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Données manquantes']);
            return;
        }

        $stmt = $this->pdo->prepare('
            INSERT INTO allergene (libelle)
            VALUES (:libelle)
        ');

        $allergene = new Allergene();
        $allergene->setLibelle($data['libelle']);

        $stmt->execute([
            'libelle' => $allergene->getLibelle()
        ]);

        $this->success([
            'message' => ' Allergène créé avec succès',
            'id'     => $this->pdo->lastInsertId()
        ], 201);
    }

    public function read(): void
    {
    }

    public function update(): void
    {
    }

    public function delete(): void
    {
        $data = json_decode(file_get_contents("php://input"), true);
        $id   = $data['allergene_id'] ?? null;

        if (!$id) {
            $this->error('ID manquant', 400);
            return;
        }

        $stmt = $this->pdo->prepare('SELECT * FROM allergene WHERE allergene_id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            $this->error('Allergène introuvable', 404);
            return;
        }

        $stmt = $this->pdo->prepare('DELETE FROM allergene WHERE allergene_id = :id');
        $stmt->execute(['id' => $id]);

        $this->success(['message' => 'Allergène supprimé avec succès']);
    }
}