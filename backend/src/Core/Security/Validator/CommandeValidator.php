<?php

namespace App\Core\Security\Validator;

use App\Repository\MenuRepository;

class CommandeValidator extends AbstractValidator
{
    private MenuRepository $menuRepository;

    public function __construct(MenuRepository $menuRepository)
    {
        $this->menuRepository = $menuRepository;
    }

    private const REQUIRED_FIELDS_CREATE = [
        'datePrestation'   => 'date de prestation',
        'heureLivraison'   => 'heure de livraison',
        'adresseLivraison' => 'adresse de livraison',
        'prixMenu'         => 'prix du menu',
        'nombrePersonne'       => 'nombre de personnes',
        'prixLivraison'    => 'prix de livraison',
        'menuId'           => 'menu',
    ];

    private const REQUIRED_FIELDS_UPDATE = [
        'datePrestation'  => 'date de prestation',
        'heureLivraison'  => 'heure de livraison',
        'prixMenu'        => 'prix du menu',
        'nombrePersonne'      => 'nombre de personnes',
        'prixLivraison'   => 'prix de livraison',
    ];

    private const STATUTS_AUTORISES = [
        'en attente',
        'accepté',
        'en préparation',
        'livré',
        'en attente du retour de matériel',
        'terminée',
    ];

    public function validate(array $data): array
    {
        $this->reset();
        $this->validateRequired($data, self::REQUIRED_FIELDS_CREATE);
        $this->validateChamps($data);

        return $this->errors;
    }

    public function validateUpdate(array $data): array
    {
        $this->reset();
        $this->validateRequired($data, self::REQUIRED_FIELDS_UPDATE);
        $this->validateChamps($data);

        return $this->errors;
    }

    private function validateChamps(array $data): void
    {
        $this->validateDatePrestation($data);
        $this->validateNombrePersonne($data);
        $this->validateNombrePersonsVsMenu($data);
        $this->validatePrix($data, 'prixMenu', 'prix du menu');
        $this->validatePrix($data, 'prixLivraison', 'prix de livraison');
    }

    public function validateStatut(array $data): array
    {
        $this->reset();

        if (empty($data['statut'])) {
            $this->errors['statut'] = 'Le champ statut ne doit pas être vide';
        } elseif (!in_array($data['statut'], self::STATUTS_AUTORISES, true)) {
            $this->errors['statut'] = sprintf(
                'Le statut "%s" est invalide. Valeurs autorisées : %s',
                $data['statut'],
                implode(', ', self::STATUTS_AUTORISES)
            );
        }

        return $this->errors;
    }

    // --- Règles métier ---

    private function validateDatePrestation(array $data): void
    {
        if (empty($data['datePrestation'])) {
            return;
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $data['datePrestation']);
        if (!$date) {
            $this->errors['datePrestation'] = 'La date de prestation doit être au format AAAA-MM-DD';
            return;
        }

        if ($date <= new \DateTimeImmutable('today')) {
            $this->errors['datePrestation'] = 'La date de prestation doit être dans le futur';
        }
    }

    private function validateNombrePersonne(array $data): void
    {
        if (empty($data['nombrePersonne'])) {
            return;
        }

        if (!is_int($data['nombrePersonne']) || $data['nombrePersonne'] < 1) {
            $this->errors['nombrePersonne'] = 'Le nombre de personnes doit être un entier supérieur à 0';
        }
    }

    private function validateNombrePersonsVsMenu(array $data): void
    {
        if (
            empty($data['menuId'])
            || empty($data['nombrePersonne'])
            || isset($this->errors['nombrePersonne'])
        ) {
            return;
        }

        $menu = $this->menuRepository->findById((int) $data['menuId']);

        if (!$menu) {
            $this->errors['menuId'] = 'Le menu sélectionné est introuvable';
            return;
        }

        $min = $menu['nombrePersonneMinimum'] ?? null;

        if ($min !== null && $data['nombrePersonne'] < $min) {
            $this->errors['nombrePersonneMinimum'] = sprintf(
                'Le menu sélectionné requiert un minimum de %d personne%s',
                $min,
                $min > 1 ? 's' : ''
            );
        }
    }

    private function validatePrix(array $data, string $field, string $label): void
    {
        if (!isset($data[$field]) || $data[$field] === '') {
            return;
        }

        if (!is_numeric($data[$field]) || $data[$field] < 0) {
            $this->errors[$field] = "Le {$label} doit être un nombre positif";
        }
    }
}