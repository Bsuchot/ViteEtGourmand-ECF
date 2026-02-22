<?php
namespace Horaire;

class Horaire
{
    protected int $horaireId;
    protected string $jour;
    protected string $heureOuverture;
    protected string $heureFermeture;
    protected string $statut;

    public function __construct(
        int $horaireId,
        string $jour,
        string $heureOuverture,
        string $heureFermeture,
        string $statut)
    {
        $this->horaireId = $horaireId;
        $this->jour = $jour;
        $this->heureOuverture = $heureOuverture;
        $this->heureFermeture = $heureFermeture;
        $this->statut = $statut;
    }

    public function getHoraireId(): int{
        return $this->horaireId;
    }
    public function setHoraireId(int $horaireId): void{
        $this->horaireId = $horaireId;
    }

    public function getJour(): string{
        return $this->jour;
    }
    public function setJour(string $jour): void{
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
