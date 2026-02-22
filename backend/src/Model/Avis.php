<?php

namespace Avis;

class Avis{
    protected int $avisId;
    protected string $titre;
    protected string $description;
    protected int $note;
    protected string $statut;
    protected \DateTimeImmutable $date;
    protected int $idUser;

    /**
     * @return int
     */
    public function getAvisId(): int
    {
        return $this->avisId;
    }
    public function setAvisId(int $avisId): void{
        $this->avisId = $avisId;
    }

    public function getTitre(): string{
        return $this->titre;
    }
    public function setTitre(string $titre): void{
        $this->titre = $titre;
    }

    public function getDescription(): string{
        return $this->description;
    }
    public function setDescription(string $description): void{
        $this->description = $description;
    }

    public function getNote(): int{
        return $this->note;
    }
    public function setNote(int $note): void{
        $this->note = $note;
    }

    public function getStatut(): string{
        return $this->statut;
    }
    public function setStatut(string $statut): void{
        $this->statut = $statut;
    }

    public function getDate(): \DateTimeImmutable{
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): void{
        $this->date = $date;
    }

    public function getIdUser(): int{
        return $this->idUser;
    }
    public function setIdUser(int $idUser): void{
        $this->idUser = $idUser;
    }

}