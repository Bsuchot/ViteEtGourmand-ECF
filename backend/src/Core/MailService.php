<?php

namespace App\Core;

use PHPMailer\PHPMailer\PHPMailer;

class MailService
{
    private PHPMailer $mailer;

    public function __construct()
    {
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

        $this->send('Confirmation de commande — ' . $numero, $body);
    }

    public function sendBienvenue(string $to, string $prenom): void
    {
        $body = "
            <h2>Bienvenue chez Vite & Gourmand !</h2>
            <p>Bonjour <strong>{$prenom}</strong>,</p>
            <p>Votre compte a bien été créé. Vous pouvez dès maintenant passer vos commandes en ligne.</p>
            <p>À bientôt — Vite & Gourmand</p>
        ";

        $this->send('Bienvenue chez Vite & Gourmand', $body);
    }

    public function sendBienvenueEmploye(string $adminEmail, string $emailEmploye): void
    {
        $body = "
        <h2>Nouveau compte employé créé</h2>
        <p>Un nouveau compte employé a été créé avec l'email suivant :</p>
        <ul>
            <li>Email : <strong>{$emailEmploye}</strong></li>
            <li>Pour votre mot de passe, rapprochez-vous de votre employeur.</li>
        </ul>
        <p>— Vite & Gourmand</p>
    ";

        $this->send($adminEmail, 'Nouveau compte employé créé', $body);
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

        $this->send('Mise à jour commande — ' . $numero, $body);
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

        $this->send('Réinitialisation de mot de passe — Vite & Gourmand', $body);
    }

    public function sendContact(string $from, string $nom, string $message): void
    {
        $body = "
            <h2>Nouveau message de contact</h2>
            <p><strong>De :</strong> {$nom} ({$from})</p>
            <p><strong>Message :</strong></p>
            <p>{$message}</p>
        ";

        $this->send('Message de contact — ' . $nom, $body);
    }
}