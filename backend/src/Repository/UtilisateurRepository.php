<?php

namespace App\Repository;

use App\Core\Repository;
use App\Models\Utilisateur;

class UtilisateurRepository extends Repository
{
    public function hashPassword($password): void
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
    }
}