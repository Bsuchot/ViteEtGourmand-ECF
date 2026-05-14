<?php

namespace App\Core\Security\Validator;

use App\Repository\UtilisateurRepository;

class EmployeValidator extends AbstractValidator
{
    private UtilisateurRepository $repository;

    public function __construct(UtilisateurRepository $repository)
    {
        $this->repository = $repository;
    }

    // Création : uniquement email + unicité
    public function validate(array $data): array
    {
        $this->reset();
        $this->validateEmail($data);
        $this->validateEmailUnique($data['email'] ?? null);

        return $this->errors;
    }

    // Mise à jour en masse : email optionnel
    public function validateUpdate(array $data): array
    {
        $this->reset();

        if (isset($data['email'])) {
            $this->validateEmail($data);
        }

        return $this->errors;
    }

    // Réinitialisation du mot de passe par l'admin
    public function validatePassword(array $data): array
    {
        $this->reset();

        if (empty($data['newPassword'])) {
            $this->errors['newPassword'] = 'Le nouveau mot de passe est requis';
        }

        return $this->errors;
    }

    // --- Règles métier ---

    private function validateEmailUnique(?string $email): void
    {
        if (empty($email) || isset($this->errors['email'])) {
            return;
        }

        $existing = $this->repository->findByEmail($email);
        if ($existing) {
            $this->errors['email'] = 'Un compte est déjà associé à cet email';
        }
    }
}