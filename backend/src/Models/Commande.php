<?php

namespace App\Models;

use App\Core\Model;

class Commande extends Model
{
    private ?int $id = null;
    private string $numeroDeCommande;
    private \DateTimeImmutable $dateCommande;
    private \DateTimeImmutable $datePrestation;
    private string $heureLivraison;
    private float $prixMenu;
    private int $nombrePers;
    private float $prixLivraison;
    private string $status;
    private bool $pretMateriel;
    private bool $restitutionMateriel;
    private Utilisateur $utilisateur;
    private Menu $menu;

    public function getId(): ?int
    {
        return $this->id;
    }
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getNumeroDeCommande(): string
    {
        return $this->numeroDeCommande;
    }

    public function setNumeroDeCommande(string $numeroDeCommande): void
    {
        $this->numeroDeCommande = $numeroDeCommande;
    }

    public function getDateCommande(): \DateTimeImmutable
    {
        return $this->dateCommande;
    }

    public function setDateCommande(\DateTimeImmutable $dateCommande): void
    {
        $this->dateCommande = $dateCommande;
    }

    public function getDatePrestation(): \DateTimeImmutable
    {
        return $this->datePrestation;
    }

    public function setDatePrestation(\DateTimeImmutable $datePrestation): void
    {
        $this->datePrestation = $datePrestation;
    }

    public function getHeureLivraison(): string
    {
        return $this->heureLivraison;
    }

    public function setHeureLivraison(string $heureLivraison): void
    {
        $this->heureLivraison = $heureLivraison;
    }

    public function getPrixMenu(): float
    {
        return $this->prixMenu;
    }

    public function setPrixMenu(float $prixMenu): void
    {
        $this->prixMenu = $prixMenu;
    }

    public function getNombrePers(): int
    {
        return $this->nombrePers;
    }

    public function setNombrePers(int $nombrePers): void
    {
        $this->nombrePers = $nombrePers;
    }

    public function getPrixLivraison(): float
    {
        return $this->prixLivraison;
    }

    public function setPrixLivraison(float $prixLivraison): void
    {
        $this->prixLivraison = $prixLivraison;
    }

    public function isPretMateriel(): bool
    {
        return $this->pretMateriel;
    }

    public function setPretMateriel(bool $pretMateriel): void
    {
        $this->pretMateriel = $pretMateriel;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function isRestitutionMateriel(): bool
    {
        return $this->restitutionMateriel;
    }

    public function setRestitutionMateriel(bool $restitutionMateriel): void
    {
        $this->restitutionMateriel = $restitutionMateriel;
    }

    public function getUtilisateur(): Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(Utilisateur $utilisateur): void
    {
        $this->utilisateur = $utilisateur;
    }

    public function getMenu(): Menu
    {
        return $this->menu;
    }

    public function setMenu(Menu $menu): void
    {
        $this->menu = $menu;
    }


}