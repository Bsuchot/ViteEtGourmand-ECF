<?php

namespace App\Models;

use App\Core\Model;

class Avis extends Model
{

    private ?int $id = null;
    private string $titre;
    private string $description;
    private int $note;
    private string $statut;
    private \DateTimeImmutable $date;
    private Utilisateur $utilisateur;


    public function getId(): ?int
    {
    return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }
    public function getTitre(): string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): void
    {
        $this->titre = $titre;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getNote(): int
    {
        return $this->note;
    }

    public function setNote(int $note): void
    {
        $this->note = $note;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatus(string $statut): void
    {
        $this->statut = $statut;
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): void
    {
        $this->date = $date;
    }

    public function getUtilisateur(): Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(Utilisateur $utilisateur): void
    {
        $this->utilisateur = $utilisateur;
    }

}