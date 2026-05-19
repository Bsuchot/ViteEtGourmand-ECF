<?php

namespace App\Core\Security\Validator;

class UtilisateurValidator extends AbstractValidator
{
    private const REQUIRED_FIELDS = [
        'prenom'    => 'prénom',
        'nom'       => 'nom',
        'password'  => 'mot de passe',
        'telephone' => 'téléphone',
        'adresse'   => 'adresse',
        'ville'     => 'ville',
        'pays'      => 'pays',
    ];

    private const REQUIRED_FIELDS_UPDATE = [
        'prenom'    => 'prénom',
        'nom'       => 'nom',
        'telephone' => 'téléphone',
        'adresse'   => 'adresse',
        'ville'     => 'ville',
        'pays'      => 'pays',
    ];

    // Inscription complète
    public function validate(array $data): array
    {
        $this->reset();
        $this->validateRequired($data, self::REQUIRED_FIELDS);
        $this->validateEmail($data);
        $this->validatePassword($data);

        return $this->errors;
    }

    // Mise à jour du profil (email et password non requis)
    public function validateUpdate(array $data): array
    {
        $this->reset();

        foreach (self::REQUIRED_FIELDS_UPDATE as $field => $label) {
            if (isset($data[$field]) && empty($data[$field])) {
                $this->errors[$field] = "Le champ $label ne doit pas être vide";
            }
        }

        if (isset($data['email'])) {
            $this->validateEmail($data);
        }

        return $this->errors;
    }

    // Login
    public function validateLogin(array $data): array
    {
        $this->reset();

        if (empty($data['email'])) {
            $this->errors['email'] = 'Le champ email ne doit pas être vide';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->errors['email'] = "L'email n'est pas valide";
        }
        if (empty($data['password'])) {
            $this->errors['password'] = 'Le champ mot de passe ne doit pas être vide';
        }

        return $this->errors;
    }

    // Changement de mot de passe (currentPassword + newPassword)
    public function validatePasswordChange(array $data): array
    {
        $this->reset();

        if (empty($data['currentPassword'])) {
            $this->errors['currentPassword'] = 'Le mot de passe actuel est requis';
        }
        if (empty($data['newPassword'])) {
            $this->errors['newPassword'] = 'Le nouveau mot de passe est requis';
        } else {
            $this->validatePassword(['password' => $data['newPassword']]);
        }

        return $this->errors;
    }

    // --- Règles métier ---

    private function validatePassword(array $data): void
    {
        $password = $data['password'] ?? null;

        if (empty($password)) {
            return; // déjà capturé par validateRequired
        }

        $errors = [];

        if (strlen($password) < 10)              $errors[] = '10 caractères minimum';
        if (!preg_match('/[A-Z]/', $password))   $errors[] = 'une majuscule';
        if (!preg_match('/[a-z]/', $password))   $errors[] = 'une minuscule';
        if (!preg_match('/[0-9]/', $password))   $errors[] = 'un chiffre';
        if (!preg_match('/[\W_]/', $password))   $errors[] = 'un caractère spécial';

        if ($errors) {
            $this->errors['password'] = 'Le mot de passe doit contenir : ' . implode(', ', $errors);
        }
    }
}
