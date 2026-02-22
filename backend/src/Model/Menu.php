<?php
namespace Menu;

use Theme\Theme;
use Regime\Regime;

class Menu
{
    protected int $menuId;
    protected string $titre;
    protected int $nombrePersonneMin;
    protected string $description;
    protected float $prixParPersonne;
    protected int $quantiteRestante;
    protected string $image;
    protected Regime $regime;
    protected Theme $theme;


    public function __construct(
        int $menuId,
        string $titre,
        int $nombrePersonneMin,
        string $description,
        float $prixParPersonne,
        int $quantiteRestante,
        string $image,
        Regime $regime,
        Theme $theme
    )
    {
        $this->menuId = $menuId;
        $this->titre = $titre;
        $this->nombrePersonneMin = $nombrePersonneMin;
        $this->description = $description;
        $this->prixParPersonne = $prixParPersonne;
        $this->quantiteRestante = $quantiteRestante;
        $this->image = $image;
        $this->regime = $regime;
        $this->theme = $theme;
    }

    public function getMenuId(): int
    {
        return $this->menuId;
    }
    public function setMenuId(int $menuId): void{
        $this->menuId = $menuId;
    }

    public function getTitre(): string{
        return $this->titre;
    }
    public function setTitre(string $titre): void{
        $this->titre = $titre;
    }

    public function getNombrePersonneMin(): int{
        return $this->nombrePersonneMin;
    }
    public function setNombrePersonneMin(int $nombrePersonneMin): void{
        $this->nombrePersonneMin = $nombrePersonneMin;
    }

    public function getDescription(): string{
        return $this->description;
    }
    public function setDescription(string $description): void{
        $this->description = $description;
    }

    public function getPrixParPersonne(): float{
        return $this->prixParPersonne;
    }
    public function setPrixParPersonne(float $prixParPersonne): void{
        $this->prixParPersonne = $prixParPersonne;
    }

    public function getQuantiteRestante(): int{
        return $this->quantiteRestante;
    }
    public function setQuantiteRestante(int $quantiteRestante): void{
        $this->quantiteRestante = $quantiteRestante;
    }

    public function getImage(): string{
        return $this->image;
    }
    public function setImage(string $image): void{
        $this->image = $image;
    }

    public function getRegime(): Regime{
        return $this->regime;
    }
    public function setRegime(Regime $regime): void{
        $this->regime = $regime;
    }

    public function getTheme(): Theme{
        return $this->theme;
    }
    public function setTheme(Theme $theme): void{
        $this->theme = $theme;
    }
}