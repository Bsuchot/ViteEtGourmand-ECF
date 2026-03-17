<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DbConnection;
use App\Models\Avis;
use App\Models\Utilisateur;
use PDO;

class AvisController extends Controller
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = DbConnection::getPDO();
    }


    public function create(): void
    {
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
        $id   = $data['avis_id'] ?? null;

        if (!$id) {
            $this->error('ID manquant', 400);
            return;
        }

        $stmt = $this->pdo->prepare('SELECT * FROM avis WHERE avis_id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            $this->error('Avis introuvable', 404);
            return;
        }

        $stmt = $this->pdo->prepare('DELETE FROM avis WHERE avis_id = :id');
        $stmt->execute([':id' => $id]);

        $this->success(['message' => 'Avis supprimé avec succès']);
    }
}