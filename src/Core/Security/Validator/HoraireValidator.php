<?php

namespace App\Core\Security\Validator;

use App\Repository\HoraireRepository;

class HoraireValidator extends AbstractValidator
{
    private HoraireRepository $horaireRepository;

    private const JOURS_AUTORISES = [
        'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche',
    ];

    private const REQUIRED_FIELDS = [
        'jour'           => 'jour',
        'heureOuverture' => 'heure d\'ouverture',
        'heureFermeture' => 'heure de fermeture',
        'statut'         => 'statut',
    ];

    public function __construct(HoraireRepository $horaireRepository)
    {
        $this->horaireRepository = $horaireRepository;
    }

    public function validate(array $data): array
    {
        $this->reset();
        $this->validateRequired($data, self::REQUIRED_FIELDS);
        $this->validateJour($data);
        $this->validateUniqueJour($data['jour'] ?? null);
        $this->validateHeures($data);

        return $this->errors;
    }

    public function validateUpdate(array $data): array
    {
        $this->reset();
        $this->validateJour($data);
        $this->validateHeures($data);

        return $this->errors;
    }

    // --- Règles métier ---

    private function validateJour(array $data): void
    {
        if (empty($data['jour']) || isset($this->errors['jour'])) {
            return;
        }

        if (!in_array(strtolower($data['jour']), self::JOURS_AUTORISES, true)) {
            $this->errors['jour'] = sprintf(
                'Le jour "%s" est invalide. Valeurs autorisées : %s',
                $data['jour'],
                implode(', ', self::JOURS_AUTORISES)
            );
        }
    }

    private function validateUniqueJour(?string $jour): void
    {
        if (empty($jour) || isset($this->errors['jour'])) {
            return;
        }

        $existing = $this->horaireRepository->findByJour($jour);
        if ($existing) {
            $this->errors['jour'] = "Un horaire existe déjà pour ce jour";
        }
    }

    private function validateHeures(array $data): void
    {
        $ouverture  = $data['heureOuverture']  ?? null;
        $fermeture  = $data['heureFermeture']  ?? null;

        if ($ouverture && !$this->isHeureValide($ouverture)) {
            $this->errors['heureOuverture'] = "L'heure d'ouverture doit être au format HH:MM";
        }
        if ($fermeture && !$this->isHeureValide($fermeture)) {
            $this->errors['heureFermeture'] = "L'heure de fermeture doit être au format HH:MM";
        }

        if (!isset($this->errors['heureOuverture']) && !isset($this->errors['heureFermeture'])
            && $ouverture && $fermeture && $ouverture >= $fermeture
        ) {
            $this->errors['heureFermeture'] = "L'heure de fermeture doit être après l'heure d'ouverture";
        }
    }

    private function isHeureValide(string $heure): bool
    {
        return (bool) preg_match('/^\d{2}:\d{2}$/', $heure)
            && \DateTimeImmutable::createFromFormat('H:i', $heure) !== false;
    }
}