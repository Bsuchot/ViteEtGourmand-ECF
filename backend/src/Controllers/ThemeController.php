<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DbConnection;
use PDO;
use App\Models\Theme;


class ThemeController extends Controller
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
            INSERT INTO theme (libelle)
            VALUES (:libelle)
        ');

        $theme = new Theme();
        $theme->setLibelle($data['libelle']);

        $stmt->execute([
            'libelle' => $theme->getLibelle()
        ]);

        $this->success([
            'message' => ' Thème créé avec succès',
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
        $id   = $data['theme_id'] ?? null;

        if (!$id) {
            $this->error('ID manquant', 400);
            return;
        }

        $stmt = $this->pdo->prepare('SELECT * FROM theme WHERE theme_id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            $this->error('Thème introuvable', 404);
            return;
        }

        $stmt = $this->pdo->prepare('DELETE FROM theme WHERE theme_id = :id');
        $stmt->execute(['id' => $id]);

        $this->success(['message' => 'Thème supprimé avec succès']);
    }
}