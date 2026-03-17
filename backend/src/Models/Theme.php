<?php

namespace App\Models;

use App\Core\Model;

class Theme extends Model
{
    private ?int $themeId = null;
    private  string $libelle;


    public function getThemeId(): ?int
    {
        return $this->themeId;
    }
    public function setThemeId(int $themeId): void
    {
        $this->themeId = $themeId;
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