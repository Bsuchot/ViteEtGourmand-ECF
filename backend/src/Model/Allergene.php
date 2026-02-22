<?php
namespace Allergene;

class Allergene{
    protected int $allergeneId;
    protected string $labelle;

    /**
     * @return int
     */
    public function getAllergeneId(): int{
        return $this->allergeneId;
    }
    public function setAllergeneId(int $allergeneId): void{
        $this->allergeneId = $allergeneId;
    }

    public function getLabelle(): string{
        return $this->labelle;
    }
    public function setLabelle(string $labelle): void{
        $this->labelle = $labelle;
    }
}


