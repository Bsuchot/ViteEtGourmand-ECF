<?php

namespace App\Core\Security;

class EmployeValidator
{
    public function validate(array $data): array
    {
        $errors = [];

        if (empty($data['email'])) {
            $errors['email'] = 'Le champ email ne doit pas être vide';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'L\'email n\'est pas valide';
        }

        return $errors;
    }
}