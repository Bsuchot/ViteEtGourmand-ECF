<?php

namespace App\Models;

use App\Core\Model;

class Theme extends Model
{
    private ?int $id = null;
    private  string $libelle;


    public function getId(): ?int
    {
        return $this->id;
    }
    public function setThemeId(int $id): void
    {
        $this->id = $id;
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