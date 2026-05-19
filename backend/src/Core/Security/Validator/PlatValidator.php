<?php

namespace App\Core\Security\Validator;

class PlatValidator extends AbstractValidator
{
    private const REQUIRED_FIELDS = [
        'titre'    => 'titre',
        'category' => 'catégorie',
        'photo'    => 'photo',
    ];

    private const CATEGORIES_AUTORISEES = [
        'entree', 'plat', 'dessert',
    ];

    public function validate(array $data): array
    {
        $this->reset();
        $this->validateRequired($data, self::REQUIRED_FIELDS);
        $this->validateCategory($data);
        $this->validateAllergenes($data);

        return $this->errors;
    }

    public function validateUpdate(array $data): array
    {
        return $this->validate($data);
    }

    // --- Règles métier ---

    private function validateCategory(array $data): void
    {
        if (empty($data['category']) || isset($this->errors['category'])) {
            return;
        }

        if (!in_array(strtolower($data['category']), self::CATEGORIES_AUTORISEES, true)) {
            $this->errors['category'] = sprintf(
                'La catégorie "%s" est invalide. Valeurs autorisées : %s',
                $data['category'],
                implode(', ', self::CATEGORIES_AUTORISEES)
            );
        }
    }
    private function validatePhoto(array $data): void
    {}

    private function validateAllergenes(array $data): void
    {
        if (!isset($data['allergenes'])) {
            return; // champ optionnel
        }

        if (!is_array($data['allergenes'])) {
            $this->errors['allergenes'] = 'Le champ allergènes doit être un tableau';
            return;
        }

        foreach ($data['allergenes'] as $i => $allergeneId) {
            if (!is_int($allergeneId) || $allergeneId <= 0) {
                $errors["allergenes.$i"] = "Chaque allergène doit être un identifiant valide";
            }
        }
    }
}