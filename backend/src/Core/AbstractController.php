<?php

namespace App\Core;

use App\Core\Security\Security;
use App\Repository\UtilisateurRepository;

abstract class AbstractController
{

    protected function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        echo json_encode($data);
    }
    protected function tryCatch(callable $action): void
    {
        try {
            $action();
        } catch (\InvalidArgumentException $e) {
            $this->error($e->getMessage(), 422);
            return;
        } catch (\RuntimeException $e) {
            $this->error($e->getMessage(), 500);
            return;
        } catch (\Throwable $e) {
            error_log('ERREUR: ' . $e->getMessage() . ' in ' . $e->getFile() . ' line ' . $e->getLine());
            $this->error($e->getMessage() . ' in ' . $e->getFile() . ' line ' . $e->getLine(), 500);
            return;
        }
    }

    protected function success(mixed $data = null, int $status = 200): void
    {
        $this->json(['success' => true, 'data' => $data], $status);
    }

    protected function error(string|array $message, int $status = 400): void
    {
        $this->json(['success' => false, 'error' => $message], $status);
    }
    protected function requireCsrf(): bool
    {
        $headers = getallheaders();
        $token = $headers['X-CSRF-TOKEN'] ?? null;

        if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
            $this->error('CSRF invalide', 403);
            return false;
        }

        return true;
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
        if (!Security::isAdmin() && !Security::isEmploye()) {
            $this->error('Accès interdit', 403);
            return false;
        }

        if (Security::isEmploye() && ($_SESSION['user']['statut'] ?? null) !== 'actif') {
            $this->error('Accès interdit — compte inactif', 403);
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
    protected function checkAccess(callable $userChecks): bool
    {
        if (Security::isAdmin() || Security::isEmploye()) {
            return true;
        }

        return $userChecks();
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
    protected function requireOwner(array $data, string $ownerId = 'utilisateurId'): bool
    {
        if ((int)$data[$ownerId] !== (int)$_SESSION['user']['id']) {
            $this->error('Accès interdit', 403);
            return false;
        }
        error_log('ownerId: ' . $data[$ownerId] . ' | sessionId: ' . $_SESSION['user']['id']);
        return true;
    }
    protected function checkOrderStatut(array $commande): bool
    {
        if ($commande['statut'] !== 'en attente') {
            $this->error('Cette commande ne peut plus être modifiée', 403);
            return false;
        }
        return true;
    }
    protected function generateNumeroCommande(): string
    {
        return 'CMD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    }
    protected function checkCommandeTerminable(array $commande): bool
    {
        if (!$commande['pretMateriel'] || $commande['restitutionMateriel']) {
            return true;
        }

        $this->error('La commande ne peut pas être terminée : le matériel n\'a pas été restitué', 403);
        return false;
    }


    protected function httpPost(string $url, array $payload, array $headers = []): string|false
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => array_merge(['Content-Type: application/json'], $headers),
        ]);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }

}