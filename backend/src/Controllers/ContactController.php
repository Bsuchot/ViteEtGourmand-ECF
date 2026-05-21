<?php

namespace App\Controllers;


use App\Core\AbstractController;
use App\Core\MailService;
use App\Repository\UtilisateurRepository;

class ContactController extends AbstractController
{
    private MailService $mailer;
    function __construct(){
        $this->mailer = new MailService(new UtilisateurRepository());
    }
    public function send(): void
    {
        $this->tryCatch(function () {
            $data    = json_decode(file_get_contents("php://input"), true);
            $email   = $data['email']   ?? null;
            $nom      = $data['nom']     ?? null;
            $prenom   = $data['prenom']  ?? null;
            $titre     = $data['titre']     ?? null;
            $message = $data['message'] ?? null;

            if (!$email || !$titre || !$message) { $this->error('Données invalides', 400); return; }

            $this->mailer->sendContact($email, $nom, $prenom, $titre, $message);
            $this->success(['message' => 'Message envoyé.']);
        });
    }
}