<?php
namespace Regime;

class Regime
{
    protected int $regimeId;
    protected string $libelle;

    public function __construct(int $regimeId, string $libelle){
        $this->regimeId = $regimeId;
        $this->libelle = $libelle;
    }

    public function getRegimeId(): int
    {
        return $this->regimeId;
    }
    public function setRegimeId(int $regimeId): void{
        $this->regimeId = $regimeId;
    }

    public function getLibelle(): string{
        return $this->libelle;
    }
    public function setLibelle(string $libelle): void{
        $this->libelle = $libelle;
    }
}
