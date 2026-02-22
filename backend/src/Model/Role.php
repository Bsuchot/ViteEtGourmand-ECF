<?php
namespace Role;

class Role
{
    protected int $roleId;
    protected string $libelle;

    public function __construct(int $roleId, string $libelle){
        $this->roleId = $roleId;
        $this->libelle = $libelle;
    }
    public function getRoleId(): int
    {
        return $this->roleId;
    }
    public function setRoleId(int $roleId): void{
        $this->roleId = $roleId;
    }

    public function getLibelle(): string{
        return $this->libelle;
    }
    public function setLibelle(string $libelle): void{
        $this->libelle = $libelle;
    }
}