<?php

namespace App\Core;

use App\Repository\UtilisateurRepository;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MailService
{
    private PHPMailer $mailer;
    private readonly string $adminEmail;

    public function __construct(UtilisateurRepository $repository)
    {
        // Récupération de l'admin ou repli sur le mail par défaut
        $admin = $repository->findByRole('ROLE_ADMIN');
        $this->adminEmail = $admin['email'] ?? $_ENV['MAIL_FROM'];

        $this->mailer = new PHPMailer(true);
        $this->mailer->isSMTP();
        $this->mailer->Host       = $_ENV['MAIL_HOST'];
        $this->mailer->SMTPAuth   = true;
        $this->mailer->Username   = $_ENV['MAIL_USERNAME'];
        $this->mailer->Password   = $_ENV['MAIL_PASSWORD'];
        
        // Sécurisation de l'encryption (gère 'tls' du .env ou les constantes PHPMailer)
        $encryption = strtolower($_ENV['MAIL_ENCRYPTION'] ?? '');
        if ($encryption === 'tls' || $encryption === 'starttls') {
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } elseif ($encryption === 'ssl') {
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $this->mailer->SMTPSecure = '';
        }

        $this->mailer->Port       = (int)$_ENV['MAIL_PORT'];
        $this->mailer->CharSet    = 'UTF-8';
        $this->mailer->setFrom($_ENV['MAIL_FROM'], $_ENV['MAIL_FROM_NAME']);
    }

    /**
     * Envoi générique d'un email
     */
    public function send(string $to, string $subject, string $body, string $replyTo = null): void
    {
        try {
            error_log('MAIL TO: ' . $to . ' | SUBJECT: ' . $subject);
            
            // Nettoyage systématique avant chaque envoi (très important si l'instance est réutilisée)
            $this->mailer->clearAddresses();
            $this->mailer->clearReplyTos();
            
            $this->mailer->addAddress($to);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = $subject;
            $this->mailer->Body    = $body;

            // Ajout du Reply-To si spécifié (ex: formulaire de contact)
            if ($replyTo) {
                $this->mailer->addReplyTo($replyTo);
            }

            $this->mailer->send();
        } catch (Exception $e) {
            error_log("Erreur lors de l'envoi de l'email : " . $this->mailer->ErrorInfo);
            throw $e; // Permet à votre contrôleur de capturer l'erreur et d'afficher un message à l'utilisateur
        }
    }

    public function sendConfirmationCommande(string $to, array $commande): void
    {
        $numero = htmlspecialchars($commande['numeroDeCommande']);
        $date   = htmlspecialchars($commande['datePrestation']);
        $heure  = htmlspecialchars($commande['heureLivraison']);
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

        $this->send($to, 'Confirmation de commande — ' . $numero, $body);
    }

    public function sendBienvenue(string $to, string $prenom): void
    {
        $prenomSafe = htmlspecialchars($prenom);
        $body = "
            <h2>Bienvenue chez Vite & Gourmand !</h2>
            <p>Bonjour <strong>{$prenomSafe}</strong>,</p>
            <p>Votre compte a bien été créé. Vous pouvez dès maintenant passer vos commandes en ligne.</p>
            <p>À bientôt — Vite & Gourmand</p>
        ";

        $this->send($to, 'Bienvenue chez Vite & Gourmand', $body);
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
        $prenomSafe = htmlspecialchars($prenom);
        $numeroSafe = htmlspecialchars($numero);
        $statutSafe = htmlspecialchars($statut);

        $body = "
            <h2>Mise à jour de votre commande</h2>
            <p>Bonjour <strong>{$prenomSafe}</strong>,</p>
            <p>Le statut de votre commande <strong>{$numeroSafe}</strong> a été mis à jour :</p>
            <p><strong>{$statutSafe}</strong></p>
            <p>— Vite & Gourmand</p>
        ";

        $this->send($to, 'Mise à jour commande — ' . $numeroSafe, $body);
    }

    public function sendReinitialisationPassword(string $to, string $prenom, string $token): void
    {
        $prenomSafe = htmlspecialchars($prenom);
        $lien = $_ENV['FRONT_URL'] . '/reset-password?token=' . urlencode($token);

        $body = "
            <h2>Réinitialisation de votre mot de passe</h2>
            <p>Bonjour <strong>{$prenomSafe}</strong>,</p>
            <p>Cliquez sur le lien ci-dessous pour créer un nouveau mot de passe :</p>
            <p><a href='{$lien}'>Réinitialiser mon mot de passe</a></p>
            <p>Ce lien est valable 1 heure.</p>
            <p>Si vous n'êtes pas à l'origine de cette demande, ignorez cet email.</p>
            <p>— Vite & Gourmand</p>
        ";

        $this->send($to, 'Réinitialisation de mot de passe — Vite & Gourmand', $body);
    }

    public function sendContact(string $from, string $titre, string $nom, string $prenom, string $message): void
    {
        $fromSafe    = htmlspecialchars($from);
        $titreSafe   = htmlspecialchars($titre);
        $nomSafe     = htmlspecialchars($nom);
        $prenomSafe  = htmlspecialchars($prenom);
        $messageSafe = nl2br(htmlspecialchars($message)); // Conserve les retours à la ligne du client

        $body = "
            <h2>Nouveau message de contact</h2>
            <p><strong>De :</strong> {$prenomSafe} {$nomSafe} ({$fromSafe})</p>
            <p><strong>Titre :</strong> {$titreSafe}</p>
            <p><strong>Message :</strong></p>
            <p>{$messageSafe}</p>
        ";

        // Envoi à l'admin avec l'adresse du visiteur en Reply-To
        $this->send($this->adminEmail, 'Message de contact — ' . $prenomSafe . ' ' . $nomSafe, $body, $from);
    }
}