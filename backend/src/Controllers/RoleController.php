<?php

namespace App\Controllers;

use App\Core\AbstractController;
use App\Core\Security\Validator\LibelleValidator;
use App\Models\Role;
use App\Repository\RoleRepository;

class RoleController extends AbstractController
{
    private LibelleValidator $validator;
    private RoleRepository $repository;

    public function __construct()
    {
        $this->repository = new RoleRepository();
        $this->validator  = new LibelleValidator($this->repository, 'Ce rôle');
    }

    public function create(): void
    {
        if (!$this->requireAdmin()) return;

        $this->tryCatch(function () {
            $data = json_decode(file_get_contents("php://input"), true);
            if (!$data) { $this->error('Données invalides', 400); return; }

            $errors = $this->validator->validate($data);
            if ($errors) { $this->error($errors, 422); return; }

            $role = new Role();
            $role->setLibelle($data['libelle']);
            $this->repository->create($role);

            $this->success(['message' => 'Rôle créé avec succès'], 201);
        });
    }

    public function delete(int $id): void
    {
        if (!$this->requireAdmin()) return;

        $this->tryCatch(function () use ($id) {
            $role = $this->repository->findById($id);
            if (!$role) { $this->error('Rôle introuvable', 404); return; }

            $this->repository->delete($id);
            $this->success(['message' => 'Rôle supprimé avec succès']);
        });
    }
}