<?php

namespace App\Models;

use App\Core\Model;

class Menu extends Model
{
    private ?int $id = null;
    private ?string $titre  = null;
    private ?int $nbPersMin  = null;
    private ?float $prixParPers  = null;
    private ?string $description = null;
    private ?int $quantiteRestante = null;
    private ?int $regimeId = null;
    private ?int $themeId = null;
    private ?string $image = null;
    private array $plats =[];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }


    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(?string $titre): void
    {
        $this->titre = $titre;
    }

    public function getNbPersMin(): ?int
    {
        return $this->nbPersMin;
    }

    public function setNbPersMin(?int $nbPersMin): void
    {
        $this->nbPersMin = $nbPersMin;
    }

    public function getPrixParPers(): ?float
    {
        return $this->prixParPers;
    }

    public function setPrixParPers(?float $prixParPers): void
    {
        $this->prixParPers = $prixParPers;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getQuantiteRestante(): ?int
    {
        return $this->quantiteRestante;
    }

    public function setQuantiteRestante(?int $quantiteRestante): void
    {
        $this->quantiteRestante = $quantiteRestante;
    }

    public function getRegimeId(): ?int
    {
        return $this->regimeId;
    }

    public function setRegimeId(?int $regimeId): void
    {
        $this->regimeId = $regimeId;
    }

    public function getThemeId(): ?int
    {
        return $this->themeId;
    }

    public function setThemeId(?int $themeId): void
    {
        $this->themeId = $themeId;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): void
    {
        $this->image = $image;
    }
    public function getPlats(): array
    {
        return $this->plats;
    }

    public function setPlats(array $plats): void
    {
        $this->plats = $plats;
    }

    public function addPlat(Plat $plat): void
    {
        $this->plats[] = $plat->toArray();
    }

}