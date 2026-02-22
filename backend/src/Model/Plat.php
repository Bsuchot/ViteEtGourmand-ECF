<?php
namespace Plat;

class Plat
{
    protected int $platId;
    protected string $titrePlat;
    protected string $category;
    protected string $photo;

    public function __construct(
        int $platId,
        string $titrePlat,
        string $category,
        string $photo
    ){
        $this->platId = $platId;
        $this->titrePlat = $titrePlat;
        $this->category = $category;
        $this->photo = $photo;
    }

    public function getPlatId(): int{
        return $this->platId;
    }
    public function setPlatId(int $platId): void{
        $this->platId = $platId;
    }

    public function getTitrePlat(): string{
        return $this->titrePlat;
    }
    public function setTitrePlat(string $titrePlat): void{
        $this->titrePlat = $titrePlat;
    }

    public function getCategory(): string{
        return $this->category;
    }
    public function setCategory(string $category): void{
        $this->category = $category;
    }

    public function getPhoto(): string{
        return $this->photo;
    }
    public function setPhoto(string $photo): void{
        $this->photo = $photo;
    }
}

