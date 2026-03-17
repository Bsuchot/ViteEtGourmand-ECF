<?php

namespace App\Models;

use App\Core\Model;

class Role extends Model
{
    private ?int $roleId= null;
    private string $libelle;


    public function getRoleId(): ?int
    {
        return $this->roleId;
    }
    public function setRoleId(?int $roleId): void
    {
        $this->roleId = $roleId;
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
        $role->setRoleId($id);
        return $role;
    }


}