<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DbConnection;
use App\Models\Role;
use PDO;


class RoleController extends Controller
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
            INSERT INTO role (libelle)
            VALUES (:libelle)
        ');

        $role = new Role();
        $role->setLibelle($data['libelle']);

        $stmt->execute([
            'libelle' => $role->getLibelle()
        ]);

        $this->success([
            'message' => ' Role créé avec succès',
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
        $id   = $data['role_id'] ?? null;

        if (!$id) {
            $this->error('ID manquant', 400);
            return;
        }

        $stmt = $this->pdo->prepare('SELECT * FROM role WHERE role_id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            $this->error('Role introuvable', 404);
            return;
        }

        $stmt = $this->pdo->prepare('DELETE FROM role WHERE role_id = :id');
        $stmt->execute(['id' => $id]);

        $this->success(['message' => 'Role supprimé avec succès']);
    }
}