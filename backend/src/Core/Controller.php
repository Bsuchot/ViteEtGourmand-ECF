<?php

namespace App\Core;

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
}