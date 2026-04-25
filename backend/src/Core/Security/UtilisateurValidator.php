<?php

namespace App\Core\Security;

class UtilisateurValidator
{
    public function validate(array $data): array
    {
        $errors = [];

        if (empty($data['prenom'])) {
            $errors['prenom'] = 'Le champ prénom ne doit pas être vide';
        }
        if (empty($data['nom'])) {
            $errors['nom'] = 'Le champ nom ne doit pas être vide';
        }
        if (empty($data['email'])) {
            $errors['email'] = 'Le champ email ne doit pas être vide';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'L\'email n\'est pas valide';
        }
        if (empty($data['password'])) {
            $errors['password'] = 'Le champ mot de passe ne doit pas être vide';
        }
        if (empty($data['telephone'])) {
            $errors['telephone'] = 'Le champ téléphone ne doit pas être vide';
        }
        if (empty($data['adresse'])) {
            $errors['adresse'] = 'Le champ adresse ne doit pas être vide';
        }
        if (empty($data['ville'])) {
            $errors['ville'] = 'Le champ ville ne doit pas être vide';
        }
        if (empty($data['pays'])) {
            $errors['pays'] = 'Le champ pays ne doit pas être vide';
        }

        return $errors;
    }
}
