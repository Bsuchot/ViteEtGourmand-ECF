<?php

namespace App\Models;

use App\Core\Model;

class Commande extends Model
{
    private ?int $id = null;
    private ?string $numeroDeCommande = null;
    private ?\DateTimeImmutable $dateCommande  = null;
    private ?\DateTimeImmutable $datePrestation  = null;
    private ?string $heureLivraison = null;
    private ?string $adresseLivraison = null;
    private ?float $prixMenu = null;
    private ?int $nombrePersonne = null;
    private ?float $prixLivraison = null;
    private ?string $statut  = null;
    private ?bool $pretMateriel = null;
    private ?bool $restitutionMateriel = null;
    private ?int $utilisateurId = null;
    private ?int $menuId = null;

    public function getId(): ?int
    {
        return $this->id;
    }
    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getNumeroDeCommande(): ?string
    {
        return $this->numeroDeCommande;
    }

    public function setNumeroDeCommande(?string $numeroDeCommande): void
    {
        $this->numeroDeCommande = $numeroDeCommande;
    }

    public function getDateCommande(): ?\DateTimeImmutable
    {
        return $this->dateCommande;
    }

    public function setDateCommande(string|\DateTimeImmutable|null $dateCommande): void
    {
        if (is_string($dateCommande)) {
            $dateCommande = new \DateTimeImmutable($dateCommande);
        }
        $this->dateCommande = $dateCommande;
    }

    public function getDatePrestation(): ?\DateTimeImmutable
    {
        return $this->datePrestation;
    }

    public function setDatePrestation(string|\DateTimeImmutable|null $datePrestation): void
    {
        if (is_string($datePrestation)) {
            $datePrestation = new \DateTimeImmutable($datePrestation);
        }
        $this->datePrestation = $datePrestation;
    }

    public function getHeureLivraison(): ?string
    {
        return $this->heureLivraison;
    }

    public function setHeureLivraison(?string $heureLivraison): void
    {
        $this->heureLivraison = $heureLivraison;
    }

    public function getAdresseLivraison(): ?string
    {
        return $this->adresseLivraison;
    }

    public function setAdresseLivraison(?string $adresseLivraison): void
    {
        $this->adresseLivraison = $adresseLivraison;
    }


    public function getPrixMenu(): ?float
    {
        return $this->prixMenu;
    }

    public function setPrixMenu(?float $prixMenu): void
    {
        $this->prixMenu = $prixMenu;
    }

    public function getNombrePersonne(): ?int
    {
        return $this->nombrePersonne;
    }

    public function setNombrePersonne(?int $nombrePersonne): void
    {
        $this->nombrePersonne = $nombrePersonne;
    }

    public function getPrixLivraison(): ?float
    {
        return $this->prixLivraison;
    }

    public function setPrixLivraison(?float $prixLivraison): void
    {
        $this->prixLivraison = $prixLivraison;
    }

    public function getPretMateriel(): ?bool
    {
        return $this->pretMateriel;
    }

    public function setPretMateriel(?bool $pretMateriel): void
    {
        $this->pretMateriel = $pretMateriel;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(?string $statut): void
    {
        $this->statut = $statut;
    }


    public function getRestitutionMateriel(): ?bool
    {
        return $this->restitutionMateriel;
    }

    public function setRestitutionMateriel(?bool $restitutionMateriel): void
    {
        $this->restitutionMateriel = $restitutionMateriel;
    }

    public function getUtilisateurId(): ?int
    {
        return $this->utilisateurId;
    }

    public function setUtilisateurId(?int $utilisateurId): void
    {
        $this->utilisateurId = $utilisateurId;
    }

    public function getMenuId(): ?int
    {
        return $this->menuId;
    }

    public function setMenuId(?int $menuId): void
    {
        $this->menuId = $menuId;
    }


}