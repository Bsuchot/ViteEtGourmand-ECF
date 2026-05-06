<?php

namespace App\Core\Security;
use App\Models\Utilisateur;

class Security
{
    public static function hashPassword(Utilisateur $utilisateur, string $password): void
    {
        $utilisateur->setPassword(password_hash($password, PASSWORD_BCRYPT));
    }
    public static function verifyPassword(string $password, string $hashedPassword): bool
    {
        return password_verify($password, $hashedPassword);
    }

    public static function isLogged(): bool
    {
        return isset($_SESSION['user']);
    }
    private static function hasRole(string $role): bool
    {
        return isset($_SESSION['user']) && $_SESSION['user']['role'] === $role;
    }

    public static function getCurrentUserId(): ?int
    {
        return $_SESSION['user']['id'] ??null;
    }

    public static function canAccessUser (int $id): bool
    {
        return self::isLogged() && self::getCurrentUserId() === $id;
    }

    public static function isUser(): bool    { return self::hasRole('ROLE_USER'); }
    public static function isEmploye(): bool { return self::hasRole('ROLE_EMPLOYE'); }
    public static function isAdmin(): bool   { return self::hasRole('ROLE_ADMIN'); }

    public static function generatePassword(): string
    {
        $upper   = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lower   = 'abcdefghijklmnopqrstuvwxyz';
        $digits  = '0123456789';
        $special = '!@#$%^&*()_+-=[]{}';
        $all     = $upper . $lower . $digits . $special;

        $password  = $upper[random_int(0, strlen($upper) - 1)];
        $password .= $lower[random_int(0, strlen($lower) - 1)];
        $password .= $digits[random_int(0, strlen($digits) - 1)];
        $password .= $special[random_int(0, strlen($special) - 1)];

        for ($i = 4; $i < 10; $i++) {
            $password .= $all[random_int(0, strlen($all) - 1)];
        }

        return str_shuffle($password);
    }

}