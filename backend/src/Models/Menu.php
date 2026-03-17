<?php

namespace App\Models;

use App\Core\Model;

class Menu extends Model
{
    private ?int $id = null;
    private string $titre;
    private int $nbPersMin;
    private float $prixParPers;
    private string $description;
    private int $quantiteRestante;
    private Regime $regime;
    private Theme $theme;
    private string $image;
    private array $plats;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): void
    {
        $this->titre = $titre;
    }

    public function getNbPersMin(): int
    {
        return $this->nbPersMin;
    }

    public function setNbPersMin(int $nbPersMin): void
    {
        $this->nbPersMin = $nbPersMin;
    }

    public function getPrixParPers(): float
    {
        return $this->prixParPers;
    }

    public function setPrixParPers(float $prixParPers): void
    {
        $this->prixParPers = $prixParPers;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getQuantiteRestante(): int
    {
        return $this->quantiteRestante;
    }

    public function setQuantiteRestante(int $quantiteRestante): void
    {
        $this->quantiteRestante = $quantiteRestante;
    }

    public function getRegime(): Regime
    {
        return $this->regime;
    }

    public function setRegime(Regime $regime): void
    {
        $this->regime = $regime;
    }

    public function getTheme(): Theme
    {
        return $this->theme;
    }

    public function setTheme(Theme $theme): void
    {
        $this->theme = $theme;
    }

    public function getImage(): string
    {
        return $this->image;
    }

    public function setImage(string $image): void
    {
        $this->image = $image;
    }
    public function getPlats(): array
    {
        return $this->plats;
    }

    public function addPlat(Plat $plat): void
    {
        $this->plats[] = $plat;
    }



}