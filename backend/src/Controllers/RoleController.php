<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Role;
use App\Repository\RoleRepository;



class RoleController extends Controller
{

    public function create(): void
    {
        if (!$this->requireAdmin()) return;

        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data) {
            $this->error('Données invalides', 400);
            return;
        }

        $repository = new RoleRepository();
        $existing = $repository->findByLibelle($data['libelle']);
        if ($existing) {
            $this->error('Ce rôle existe déjà', 409);
            return;
        }

        $role = new Role();
        $role->setLibelle($data['libelle']);
        $repository->create($role);

        $this->success(['message' => 'Rôle créé avec succès'], 201);
    }

    public function delete(int $id): void
    {
        if (!$this->requireAdmin()) return;

        $repository = new RoleRepository();
        $role = $repository->findById($id);

        if (!$role) {
            $this->error('Rôle introuvable', 404);
            return;
        }

        $repository->delete($id);
        $this->success(['message' => 'Rôle supprimé avec succès']);
    }
}