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


    public static function isUser(): bool    { return self::hasRole('user'); }
    public static function isEmploye(): bool { return self::hasRole('employe'); }
    public static function isAdmin(): bool   { return self::hasRole('admin'); }


    public static function getCurrentUserId(): ?int
    {
        return $_SESSION['user']['id'] ??null;
    }

}