<?php

namespace App\Core;

use App\Core\Security\Security;
use App\Repository\UtilisateurRepository;

class Controller
{
    protected function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        echo json_encode($data);
    }

    protected function success(mixed $data = null, int $status = 200): void
    {
        $this->json(['success' => true, 'data' => $data], $status);
    }

    protected function error(string $message, int $status = 400): void
    {
        $this->json(['success' => false, 'error' => $message], $status);
    }

    protected function requireLogin(): bool
    {
        if (!Security::isLogged()) {
            $this->error('Non autorisé', 401);
            return false;
        }
        return true;
    }

    protected function requireSelf(int $id): bool
    {
        if (!Security::canAccessUser($id)) {
            $this->error('Accès interdit', 403);
            return false;
        }
        return true;
    }

    protected function requireAdmin(): bool
    {
        if (!Security::isAdmin()) {
            $this->error('Accès interdit', 403);
            return false;
        }
        return true;
    }
    protected function requireAdminOrEmploye(): bool
    {
        if (!Security::isAdmin() || !Security::isEmploye()) {
            $this->error('Accès interdit', 403);
            return false;
        }
        return true;
    }
    protected function requireUser(): bool
    {
        if (!Security::isUser()) {
            $this->error('Accès interdit', 403);
            return false;
        }
        return true;
    }
    protected function getUtilisateurOrFail(int $id): ?array
    {
        if (!$this->requireLogin()) {
            return null;
        }

        $repository = new UtilisateurRepository();
        $utilisateur = $repository->findById($id);

        if (!$utilisateur) {
            $this->error('Utilisateur introuvable', 404);
            return null;
        }

        return $utilisateur;
    }
    protected function requireAvisOwner(array $avis): bool
    {
        if ($avis['utilisateurId'] !== $_SESSION['user']['id']) {
            $this->error('Accès interdit', 403);
            return false;
        }
        return true;
    }

}