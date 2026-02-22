<?php
namespace Theme;

class Theme
{
    protected int $themeId;
    protected string $libelle;

    public function __construct(int $themeId, string $libelle){
        $this->themeId = $themeId;
        $this->libelle = $libelle;
    }
    public function getThemeId(): int
    {
        return $this->themeId;
    }
    public function setThemeId(int $themeId): void{
        $this->themeId = $themeId;
    }

    public function getLibelle(): string{
        return $this->libelle;
    }
    public function setLibelle(string $libelle): void{
        $this->libelle = $libelle;
    }
}