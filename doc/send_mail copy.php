<?php

function send_mail_with_attachment(string $to, string $subject, string $body, string $filePath, string $fileName): bool
{
    $separator = md5((string) microtime(true));
    $fileContent = chunk_split(base64_encode((string) file_get_contents($filePath)));

    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: multipart/mixed; boundary="' . $separator . '"';

    $message = "--{$separator}\r\n";
    $message .= "Content-Type: text/plain; charset=utf-8\r\n";
    $message .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $message .= $body . "\r\n";
    $message .= "--{$separator}\r\n";
    $message .= "Content-Type: text/plain; name=\"{$fileName}\"\r\n";
    $message .= "Content-Transfer-Encoding: base64\r\n";
    $message .= "Content-Disposition: attachment; filename=\"{$fileName}\"\r\n\r\n";
    $message .= $fileContent . "\r\n";
    $message .= "--{$separator}--";

    return mail($to, $subject, $message, implode("\r\n", $headers));
}

function respond(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);

    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }

    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function my_function()
{
    $data = [
        'customer' => $_POST['customer'] ?? null,
        'project' => $_POST['project'] ?? null,
        'revision' => $_POST['revision'] ?? null,
    ];
    $recipient = $_POST['email'] ?? '';

    $data = array_filter(
        $data,
        static function ($value) {
            return $value !== null;
        }
    );

    if ($recipient === '') {
        respond(['success' => false, 'message' => 'Adresse email manquante.'], 400);
        return;
    }

    if (empty($data)) {
        respond(['success' => false, 'message' => 'Aucune donnée à envoyer à WordPress.'], 400);
        return;
    }

    $payload = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($payload === false) {
        respond(['success' => false, 'message' => 'Impossible de sérialiser les données.'], 500);
        return;
    }

    $tempFile = tempnam(sys_get_temp_dir(), 'wse_payload_');

    if ($tempFile === false) {
        respond(['success' => false, 'message' => 'Impossible de créer le fichier temporaire.'], 500);
        return;
    }

    $filePath = $tempFile . '.json';
    rename($tempFile, $filePath);
    $fileName = 'wordpress-data.json';

    try {
        $written = file_put_contents($filePath, $payload);
        if ($written === false) {
            respond(['success' => false, 'message' => 'Impossible d\'écrire le fichier temporaire.'], 500);
            return;
        }

        $sent = send_mail_with_attachment(
            $recipient,
            'Vos données pour WordPress',
            "Bonjour,\n\nVeuillez trouver en pièce jointe le fichier de données à envoyer dans WordPress.\n",
            $filePath,
            $fileName
        );

        if (!$sent) {
            respond(['success' => false, 'message' => 'Échec de l\'envoi de l\'email.'], 500);
            return;
        }

        respond([
            'success' => true,
            'message' => 'Fichier créé, envoyé par email, puis supprimé.',
            'data' => $data,
        ]);
    } finally {
        if (is_file($filePath)) {
            unlink($filePath);
        }
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    my_function();
}


