<?php

namespace App\Models;

class Horaire
{

    public function __construct(
        private string $jour,
        private string $heureOuverture,
        private string $heureFermeture,
        private string $statut
    ){
    }


    public function getJour(): string{
        return $this->jour;
    }
    public function setJour(string $jour): void
    {
        $this->jour = $jour;
    }

    public function getHeureOuverture(): string{
        return $this->heureOuverture;
    }
    public function setHeureOuverture(string $heureOuverture): void{
        $this->heureOuverture = $heureOuverture;
    }
    public function getHeureFermeture(): string{
        return $this->heureFermeture;
    }
    public function setHeureFermeture(string $heureFermeture): void{
        $this->heureFermeture = $heureFermeture;
    }

    public function getStatut(): string{
        return $this->statut;
    }
    public function setStatut(string $statut): void{
        $this->statut = $statut;
    }
}