<?php

namespace App\Models;

use App\Core\Model;

class Plat extends Model
{
    private ?int $id = null;
    private ?string $titre = null;
    private ?string $category = null;
    private ?string $photo  = null;
    private array $allergenes = [];

    public function getId(): ?int
    {
        return $this->id;
    }
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getPhoto(): string
    {
        return $this->photo;
    }

    public function setPhoto(string $photo): void
    {
        $this->photo = $photo;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): void
    {
        $this->category = $category;
    }

    public function getTitre(): string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): void
    {
        $this->titre = $titre;
    }

    public function getAllergenes(): array
    {
        return $this->allergenes;
    }

    public function setAllergenes(array $allergenes): void
    {
        $this->allergenes = $allergenes;
    }
    public function addAllergene(Allergene $allergene): void
    {
        $this->allergenes[] = $allergene->toArray();
    }

}