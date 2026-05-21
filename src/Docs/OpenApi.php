<?php

namespace App\Docs;

use OpenApi\Attributes as OA;

#[OA\OpenApi]
#[OA\Info(
    title: "API Authentication",
    version: "1.0.0",
    description: "API de gestion des utilisateurs"
)]
#[OA\Server(
    url: "http://localhost:8000",
    description: "Serveur local"
)]
#[OA\SecurityScheme(
    securityScheme: "cookieAuth",
    type: "apiKey",
    in: "cookie",
    name: "PHPSESSID"
)]
class OpenApi {}