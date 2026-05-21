<?php

namespace App\Models;

use App\Core\Model;

class Menu extends Model
{
    private ?int $id = null;
    private ?string $titre  = null;
    private ?int $nombrePersonneMinimum  = null;
    private ?float $prixParPersonne  = null;
    private ?string $description = null;
    private ?int $quantiteRestante = null;
    private ?int $regimeId = null;
    private ?int $themeId = null;
    private ?string $image = null;
    private ?string $statut = null;
    private ?int $delai = null;
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

    public function getNombrePersonneMinimum(): ?int
    {
        return $this->nombrePersonneMinimum;
    }

    public function setNombrePersonneMinimum(?int $nombrePersonneMinimum): void
    {
        $this->nombrePersonneMinimum = $nombrePersonneMinimum;
    }

    public function getPrixParPersonne(): ?float
    {
        return $this->prixParPersonne;
    }

    public function setPrixParPersonne(?float $prixParPersonne): void
    {
        $this->prixParPersonne = $prixParPersonne;
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

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): void
    {
        $this->statut = $statut;
    }


    public function getDelai(): ?int
    {
        return $this->delai;
    }

    public function setDelai(?int $delai): void
    {
        $this->delai = $delai;
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
        $this->plats[] = $plat;
    }
    public function addPlatId(int $id): void
    {
        $this->plats[] = ['id' => $id];
    }


}