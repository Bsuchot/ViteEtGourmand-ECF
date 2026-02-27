<?php

namespace App\Models;

class Theme
{
    public function __construct(
        private  string $libelle
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