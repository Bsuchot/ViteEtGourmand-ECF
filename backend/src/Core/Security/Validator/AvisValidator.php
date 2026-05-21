<?php

namespace App\Core\Security\Validator;

class AvisValidator extends AbstractValidator
{
    private const REQUIRED_FIELDS = [
        'titre'       => 'titre',
        'description' => 'description',
        'note'        => 'note',
    ];

    private const REQUIRED_FIELDS_UPDATE = [
        'titre'       => 'titre',
        'description' => 'description',
        'note'        => 'note',
    ];

    private const STATUTS_AUTORISES = [
        'En attente', 'Publié', 'Rejeté',
    ];

    public function validate(array $data): array
    {
        $this->reset();
        $this->validateRequired($data, self::REQUIRED_FIELDS);
        $this->validateNote($data);

        return $this->errors;
    }

    public function validateUpdate(array $data): array
    {
        $this->reset();
        $this->validateRequired($data, self::REQUIRED_FIELDS_UPDATE);
        $this->validateNote($data);

        return $this->errors;
    }

    public function validateStatut(array $data): array
    {
        $this->reset();

        if (empty($data['statut'])) {
            $this->errors['statut'] = 'Le champ statut ne doit pas être vide';
        } elseif (!in_array($data['statut'], self::STATUTS_AUTORISES, true)) {
            $this->errors['statut'] = sprintf(
                'Le statut "%s" est invalide. Valeurs autorisées : %s',
                $data['statut'],
                implode(', ', self::STATUTS_AUTORISES)
            );
        }

        return $this->errors;
    }

    // --- Règles métier ---

    private function validateNote(array $data): void
    {
        if (empty($data['note'])) {
            return;
        }

        if (!is_int($data['note']) || $data['note'] < 1 || $data['note'] > 5) {
            $this->errors['note'] = 'La note doit être un entier entre 1 et 5';
        }
    }
}