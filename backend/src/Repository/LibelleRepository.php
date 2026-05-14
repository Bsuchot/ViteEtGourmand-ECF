<?php

namespace App\Repository;

interface LibelleRepository
{
    public function findByLibelle(string $libelle): ?array;
}