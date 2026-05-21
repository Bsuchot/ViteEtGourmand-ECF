<?php

namespace App\Core;

use App\Repository\UtilisateurRepository;
use PHPMailer\PHPMailer\PHPMailer;

class MailService
{
    private PHPMailer $mailer;


    private readonly string $adminEmail;

    public function __construct(UtilisateurRepository $repository)
    {
        $admin = $repository->findByRole('ROLE_ADMIN');
        $this->adminEmail = $admin['email'] ?? $_ENV['MAIL_FROM'];

        $this->mailer = new PHPMailer(true);
        $this->mailer->isSMTP();
        $this->mailer->Host       = $_ENV['MAIL_HOST'];
        $this->mailer->SMTPAuth   = true;
        $this->mailer->Username   = $_ENV['MAIL_USERNAME'];
        $this->mailer->Password   = $_ENV['MAIL_PASSWORD'];
        $this->mailer->SMTPSecure = $_ENV['MAIL_ENCRYPTION'];
        $this->mailer->Port       = $_ENV['MAIL_PORT'];
        $this->mailer->CharSet    = 'UTF-8';
        $this->mailer->setFrom($_ENV['MAIL_FROM'], $_ENV['MAIL_FROM_NAME']);
    }

    public function send(string $to, string $subject, string $body): void
    {
        error_log('MAIL TO: ' . $to . ' | SUBJECT: ' . $subject);
        $this->mailer->clearAddresses();
        $this->mailer->addAddress($to);
        $this->mailer->isHTML(true);
        $this->mailer->Subject = $subject;
        $this->mailer->Body    = $body;
        $this->mailer->send();
    }

    public function sendConfirmationCommande(string $to, array $commande): void
    {
        $numero = $commande['numeroDeCommande'];
        $date   = $commande['datePrestation'];
        $heure  = $commande['heureLivraison'];
        $prix   = number_format((float)$commande['prixMenu'] + (float)$commande['prixLivraison'], 2, ',', ' ');

        $body = "
            <h2>Confirmation de votre commande</h2>
            <p>Votre commande <strong>{$numero}</strong> a bien été enregistrée.</p>
            <ul>
                <li>Date de livraison : {$date}</li>
                <li>Heure : {$heure}</li>
                <li>Total : {$prix} €</li>
            </ul>
            <p>Merci pour votre confiance — Vite & Gourmand</p>
        ";

        $this->send($to,'Confirmation de commande — ' . $numero, $body);
    }

    public function sendBienvenue(string $to, string $prenom): void
    {
        $body = "
            <h2>Bienvenue chez Vite & Gourmand !</h2>
            <p>Bonjour <strong>{$prenom}</strong>,</p>
            <p>Votre compte a bien été créé. Vous pouvez dès maintenant passer vos commandes en ligne.</p>
            <p>À bientôt — Vite & Gourmand</p>
        ";

        $this->send($to,'Bienvenue chez Vite & Gourmand', $body);
    }

    public function sendBienvenueEmploye(string $to): void
    {
        $body = "
        <h2>Bienvenue chez Vite & Gourmand !</h2>
        <p>Un compte employé vient d'être créé pour vous.</p>
        <p>Pour votre mot de passe, rapprochez-vous de votre employeur.</p>
        <p>— Vite & Gourmand</p>
    ";

        $this->send($to, 'Bienvenue chez Vite & Gourmand', $body);
    }

    public function sendChangementStatutCommande(string $to, string $prenom, string $numero, string $statut): void
    {
        $body = "
            <h2>Mise à jour de votre commande</h2>
            <p>Bonjour <strong>{$prenom}</strong>,</p>
            <p>Le statut de votre commande <strong>{$numero}</strong> a été mis à jour :</p>
            <p><strong>{$statut}</strong></p>
            <p>— Vite & Gourmand</p>
        ";

        $this->send($to,'Mise à jour commande — ' . $numero, $body);
    }

    public function sendReinitialisationPassword(string $to, string $prenom, string $token): void
    {
        $lien = $_ENV['FRONT_URL'] . '/reset-password?token=' . $token;

        $body = "
            <h2>Réinitialisation de votre mot de passe</h2>
            <p>Bonjour <strong>{$prenom}</strong>,</p>
            <p>Cliquez sur le lien ci-dessous pour créer un nouveau mot de passe :</p>
            <p><a href='{$lien}'>Réinitialiser mon mot de passe</a></p>
            <p>Ce lien est valable 1 heure.</p>
            <p>Si vous n'êtes pas à l'origine de cette demande, ignorez cet email.</p>
            <p>— Vite & Gourmand</p>
        ";

        $this->send($to,'Réinitialisation de mot de passe — Vite & Gourmand', $body);
    }

    public function sendContact(string $from, string $titre, string $nom, string $prenom, string $message): void
    {
        $body = "
            <h2>Nouveau message de contact</h2>
            <p><strong>De :</strong>$prenom $nom ({$from})</p>
            <p><strong>Titre :</strong>$titre </p>
            <p><strong>Message :</strong></p>
            <p>{$message}</p>
        ";

        $this->send($this->adminEmail,'Message de contact — ' . $prenom . ' ' . $nom, $body);
    }
}