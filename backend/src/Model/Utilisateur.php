<?php
namespace Utilisateur;

use Role\Role;
class Utilisateur{
    protected int $utilisateurId;
    protected string $nom;
    protected string $prenom;
    protected string $email;
    protected string $password;
    protected string $telephone;
    protected string $ville;
    protected string $adresse;
    protected string $pays;
    protected Role $role;

    public function __construct(
        int $utilisateurId,
        string $nom,
        string $prenom,
        string $email,
        string $password,
        string $telephone,
        string $ville,
        string $adresse,
        string $pays,
        Role $role
    ){
        $this->utilisateurId = $utilisateurId;
        $this->nom = $nom;
        $this->prenom = $prenom;
        $this->email = $email;
        $this->setPassword($password);
        $this->telephone = $telephone;
        $this->ville = $ville;
        $this->adresse = $adresse;
        $this->pays = $pays;
        $this->role = $role;

    }

    /**
     * @return int
     */
    public function getUtilisateurId(): int
    {
        return $this->utilisateurId;
    }
    public function setUtilisateurId(int $utilisateurId): void
    {
        $this->utilisateurId = $utilisateurId;
    }

    public function getNom(): string{
        return $this->nom;
    }
    public function setNom(string $nom): void{
        $this->nom = $nom;
    }

    public function getPrenom(): string{
        return $this->prenom;
    }
    public function setPrenom(string $prenom): void{
        $this->prenom = $prenom;
    }

    public function getEmail(): string{
        return $this->email;
    }
    public function setEmail(string $email): void{
        $this->email = $email;
    }

    public function getPassword(): string{
        return $this->password;
    }
    public function setPassword(string $password): void{
        $this->password = password_hash($password, PASSWORD_DEFAULT);
    }
    public function verifyPassword(string $password): bool{
        return password_verify($password, $this->password);
    }

    public function getTelephone(): string{
        return $this->telephone;
    }
    public function setTelephone(string $telephone): void{
        $this->telephone = $telephone;
    }

    public function getVille(): string{
        return $this->ville;
    }
    public function setVille(string $ville): void{
        $this->ville = $ville;
    }

    public function getAdresse(): string{
        return $this->adresse;
    }
    public function setAdresse(string $adresse): void{
        $this->adresse = $adresse;
    }

    public function getPays(): string{
        return $this->pays;
    }
    public function setPays(string $pays): void{
        $this->pays = $pays;
    }

    public function getRole(): Role{
        return $this->role;
    }
    public function setRole(Role $role): void{
        $this->role = $role;
    }

}
