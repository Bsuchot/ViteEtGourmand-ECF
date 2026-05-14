<?php

namespace App\Core\Security\Validator;


use App\Repository\LibelleRepository;

class LibelleValidator extends AbstractValidator
{
    private LibelleRepository $repository;
    private string $entityLabel;

    public function __construct(LibelleRepository $repository, string $entityLabel)
    {
        $this->repository  = $repository;
        $this->entityLabel = $entityLabel;
    }

    public function validate(array $data): array
    {
        $this->reset();
        $this->validateLibelle($data);
        $this->validateUnique($data['libelle'] ?? null);

        return $this->errors;
    }

    public function validateUpdate(array $data, int $id): array
    {
        $this->reset();
        $this->validateLibelle($data);
        $this->validateUnique($data['libelle'] ?? null, $id);

        return $this->errors;
    }

    // --- Règles métier ---

    private function validateLibelle(array $data): void
    {
        if (empty($data['libelle'])) {
            $this->errors['libelle'] = "Le champ libellé ne doit pas être vide";
        }
    }

    private function validateUnique(?string $libelle, ?int $excludeId = null): void
    {
        if (empty($libelle) || isset($this->errors['libelle'])) {
            return;
        }

        $existing = $this->repository->findByLibelle($libelle);

        if ($existing && $existing['id'] !== $excludeId) {
            $this->errors['libelle'] = "{$this->entityLabel} existe déjà";
        }
    }
}