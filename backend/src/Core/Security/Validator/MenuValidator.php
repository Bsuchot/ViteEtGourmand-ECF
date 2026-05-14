<?php

namespace App\Core\Security\Validator;

class MenuValidator extends AbstractValidator
{
    private const REQUIRED_FIELDS = [
        'titre'       => 'titre',
        'nombrePersonneMinimum'   => 'nombre de personnes minimum',
        'prixParPersonne' => 'prix par personne',
        'description' => 'description',
        'regimeId'    => 'régime',
        'themeId'     => 'thème',
        'plats'       => 'plats',
    ];

    public function validate(array $data): array
    {
        $this->reset();
        $this->validateRequired($data, self::REQUIRED_FIELDS);
        $this->validateNombrePersonneMinimum($data);
        $this->validatePrixParPersonne($data);
        $this->validateQuantiteRestante($data);
        $this->validatePlats($data);

        return $this->errors;
    }

    public function validateUpdate(array $data): array
    {
        $this->reset();
        $this->validateChamps($data);

        return $this->errors;
    }

    // --- Règles métier ---

    private function validateChamps(array $data): void
    {
        if (isset($data['nombrePersonneMinimum']))        $this->validateNombrePersonneMinimum($data);
        if (isset($data['prixParPersonne']))      $this->validatePrixParPersonne($data);
        if (isset($data['quantiteRestante'])) $this->validateQuantiteRestante($data);
        if (isset($data['plats']))            $this->validatePlats($data);
    }

    private function validateNombrePersonneMinimum(array $data): void
    {
        if (empty($data['nombrePersonneMinimum']) || isset($this->errors['nombrePersonneMinimum'])) {
            return;
        }
        if (!is_int($data['nombrePersonneMinimum']) || $data['nombrePersonneMinimum'] <= 0) {
            $this->errors['nombrePersonneMinimum'] = 'Le nombre de personnes minimum doit être un entier positif';
        }
    }

    private function validatePrixParPersonne(array $data): void
    {
        if (empty($data['prixParPersonne']) || isset($this->errors['prixParPersonne'])) {
            return;
        }
        if (!is_numeric($data['prixParPersonne']) || $data['prixParPersonne'] <= 0) {
            $this->errors['prixParPersonne'] = 'Le prix par personne doit être un nombre positif';
        }
    }

    private function validateQuantiteRestante(array $data): void
    {
        if (!isset($data['quantiteRestante'])) {
            return;
        }
        if (!is_int($data['quantiteRestante']) || $data['quantiteRestante'] < 0) {
            $this->errors['quantiteRestante'] = 'La quantité restante doit être un entier positif ou nul';
        }
    }

    private function validatePlats(array $data): void
    {
        if (empty($data['plats'])) {
            $this->errors['plats'] = 'Le menu doit contenir au moins un plat';
            return;
        }
        if (!is_array($data['plats'])) {
            $this->errors['plats'] = 'Le champ plats doit être un tableau';
            return;
        }
        foreach ($data['plats'] as $i => $platId) {
            if (!is_int($platId) || $platId <= 0) {
                $this->errors["plats.{$i}"] = 'Chaque plat doit être un identifiant entier valide';
            }
        }
    }
}