<?php

namespace App\Models;

class Role
{
    public function __construct(
        public string $libelle
    )
    {
    }

    public function getLibelle(): string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): void
    {
        $this->libelle = $libelle;
    }

}