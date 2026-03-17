<?php

namespace App\Repository;

use App\Core\Repository;
use App\Models\Horaire;
use PDO;

class HoraireRepository extends Repository
{
    public function findAll(): array
    {
        $query = $this->pdo->prepare("SELECT * FROM `horaire`");
        $query->execute();

        $horaires = $query->fetchAll($this->pdo::FETCH_ASSOC);

        $horairesArray = [];
        if ($horaires) {
            foreach ($horaires as $horaireArray) {
                $horairesArray[] = Horaire::createAndHydrate($horaireArray);
            }
        }

        return $horairesArray;
    }

    public function findById(int $id): Horaire
    {
        $query = $this->pdo->prepare("SELECT * FROM `horaire` WHERE `id` = :id");
        $query->bindValue(':id', $id, PDO::PARAM_INT);
        $query->execute();
        $horaireArray = $query->fetch($this->pdo::FETCH_ASSOC);

        $horaire = Horaire::createAndHydrate($horaireArray);


        return $horaire;

    }
}