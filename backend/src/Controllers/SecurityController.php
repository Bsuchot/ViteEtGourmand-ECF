<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Security\Security;
use App\Core\Security\UtilisateurValidator;
use App\Models\Utilisateur;
use App\Repository\UtilisateurRepository;
use App\Models\Role;

class SecurityController extends Controller
{

    public function registration(): void
    {

    }

    public function login(): void
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
        $id   = $data['utilisateur_id'] ?? null;

        if (!$id) {
            $this->error('ID manquant', 400);
            return;
        }

        $stmt = $this->pdo->prepare('SELECT * FROM utilisateur WHERE utilisateur_id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            $this->error('Utilisateur introuvable', 404);
            return;
        }

        $stmt = $this->pdo->prepare('DELETE FROM utilisateur WHERE utilisateur_id = :id');
        $stmt->execute(['id' => $id]);

        $this->success(['message' => 'Utilisateur supprimé avec succès']);
    }
}