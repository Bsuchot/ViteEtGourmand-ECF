<?php

namespace App\Models\Commande;

use DateTimeImmutable;
use DateTime;
use Menu\Menu;
use Utilisateur\Utilisateur;

class Commande
{
    protected string $numeroDeCommande;
    protected DateTimeImmutable $dateCommande;
    protected DateTime $datePrestation;
    protected string $heureLivraison;
    protected float $prixMenu;
    protected int $nombrePersonne;
    protected float $prixLivraison;
    protected string $statutCommande;
    protected int $pretMateriel;
    protected int $restitutionMateriel;
    protected Utilisateur $utilisateur;
    protected Menu $menu;

    public function __construct(
        string $numeroDeCommande,
        DateTimeImmutable $dateCommande,
        DateTime $datePrestation,
        string $heureLivraison,
        float $prixMenu,
        int $nombrePersonne,
        float $prixLivraison,
        string $statutCommande,
        int $pretMateriel,
        int $restitutionMateriel,
        Utilisateur $utilisateur,
        Menu $menu
    ){
        $this->numeroDeCommande = $numeroDeCommande;
        $this->dateCommande = $dateCommande;
        $this->datePrestation = $datePrestation;
        $this->heureLivraison = $heureLivraison;
        $this->prixMenu = $prixMenu;
        $this->nombrePersonne = $nombrePersonne;
        $this->prixLivraison = $prixLivraison;
        $this->statutCommande = $statutCommande;
        $this->pretMateriel = $pretMateriel;
        $this->restitutionMateriel = $restitutionMateriel;
        $this->utilisateur = $utilisateur;
        $this->menu = $menu;
    }
    public function getNumeroDeCommande(): string
    {
        return $this->numeroDeCommande;
    }
    public function setNumeroDeCommande(string $numeroDeCommande): void{
        $this->numeroDeCommande = $numeroDeCommande;
    }

    public function getDateDeCommande(): DateTimeImmutable{
        return $this->dateCommande;
    }
    public function setDateDeCommande(DateTimeImmutable $dateCommande): void{
        $this->dateCommande = $dateCommande;
    }

    public function getDatePrestation(): DateTime{
        return $this->datePrestation;
    }
    public function setDatePrestation(DateTime $datePrestation): void{
        $this->datePrestation = $datePrestation;
    }

    public function getHeureLivraison(): string{
        return $this->heureLivraison;
    }
    public function setHeureLivraison(string $heureLivraison): void{
        $this->heureLivraison = $heureLivraison;
    }

    public function getPrixMenu(): float{
        return $this->prixMenu;
    }
    public function setPrixMenu(float $prixMenu): void{
        $this->prixMenu = $prixMenu;
    }

    public function getNombrePersonne(): int{
        return $this->nombrePersonne;
    }
    public function setNombrePersonne(int $nombrePersonne): void{
        $this->nombrePersonne = $nombrePersonne;
    }

    public function getPrixLivraison(): float{
        return $this->prixLivraison;
    }
    public function setPrixLivraison(float $prixLivraison): void{
        $this->prixLivraison = $prixLivraison;
    }

    public function getStatutCommande(): string{
        return $this->statutCommande;
    }
    public function setStatutCommande(string $statutCommande): void{
        $this->statutCommande = $statutCommande;
    }

    public function getPretMateriel(): int{
        return $this->pretMateriel;
    }
    public function setPretMateriel(int $pretMateriel): void{
        $this->pretMateriel = $pretMateriel;
    }

    public function getRestitutionMateriel(): int{
        return $this->restitutionMateriel;
    }
    public function setRestitutionMateriel(int $restitutionMateriel): void{
        $this->restitutionMateriel = $restitutionMateriel;
    }

    public function getUtilisateur(): Utilisateur{
        return $this->utilisateur;
    }
    public function setUtilisateur(Utilisateur $utilisateur): void{
        $this->utilisateur = $utilisateur;
    }

    public function getMenu(): Menu{
        return $this->menu;
    }
    public function setMenu(Menu $menu): void{
        $this->menu = $menu;
    }
}