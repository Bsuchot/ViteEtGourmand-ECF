<?php

namespace App\Core\Security\Validator;

abstract class AbstractValidator
{
    protected array $errors = [];

    abstract public function validate(array $data): array;

    protected function validateRequired(array $data, array $fields): void
    {
        foreach ($fields as $field => $label) {
            if (empty($data[$field])) {
                $this->errors[$field] = "Le champ {$label} ne doit pas être vide";
            }
        }
    }

    protected function validateEmail(array $data): void
    {
        if (empty($data['email'])) {
            $this->errors['email'] = 'Le champ email ne doit pas être vide';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->errors['email'] = "L'email n'est pas valide";
        }
    }

    protected function reset(): void
    {
        $this->errors = [];
    }
}