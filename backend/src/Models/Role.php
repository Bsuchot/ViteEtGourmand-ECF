<?php

namespace App\Models;

use App\Core\Model;

class Role extends Model
{
    private ?int $id= null;
    private string $libelle;


    public function getId(): ?int
    {
        return $this->id;
    }
    public function setId(?int $id): void
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
    public static function from(int $id): self
    {
        $role = new self();
        $role->setId($id);
        return $role;
    }


}