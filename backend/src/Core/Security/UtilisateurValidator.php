<?php

namespace App\Core\Security;

class UtilisateurValidator
{
    public function validate(): array
    {
        $errors = [];
        if (empty($this->getPrenom())) {
            $errors['first_name'] = 'Le champ prénom ne doit pas être vide';
        }
        if (empty($this->getNom())) {
            $errors['last_name'] = 'Le champ nom ne doit pas être vide';
        }
        if (empty($this->getEmail())) {
            $errors['email'] = 'Le champ email ne doit pas être vide';
        } else if (!filter_var($this->getEmail(), FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'L\'email n\'est pas valide';
        }
        if (empty($this->getPassword())) {
            $errors['password'] = 'Le champ mot de passe ne doit pas être vide';
        }
        return $errors;
    }

}