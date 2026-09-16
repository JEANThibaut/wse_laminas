<?php
/*
Plugin Name: Mail Endpoint
Description: Endpoint REST pour créer un JSON, l'envoyer par mail et le supprimer.
Version: 1.0.0
*/
defined('ABSPATH') || exit;

add_action('rest_api_init', 'register_mail_endpoint');

function register_mail_endpoint(): void
{
    register_rest_route(
        'mail-endpoint/v1',
        '/send-mail',
        [
            'methods' => 'POST',
            'callback' => 'handle_mail_request',
            'permission_callback' => '__return_true',
        ]
    );
}

function handle_mail_request(WP_REST_Request $request): WP_REST_Response
{
    $data = [
        'customer' => sanitize_text_field((string) $request->get_param('customer')),
        'project' => sanitize_text_field((string) $request->get_param('project')),
        'revision' => sanitize_text_field((string) $request->get_param('revision')),
    ];
    // $recipient = sanitize_email((string) $request->get_param('email'));
    $recipient = "monmail@mail.mail";

    $data = array_filter(
        $data,
        static function ($value) {
            return $value !== '';
        }
    );

    if ($recipient === '') {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Adresse email manquante.',
        ], 400);
    }

    if (empty($data)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Aucune donnée à envoyer à WordPress.',
        ], 400);
    }

    $payload = wp_json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($payload === false) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Impossible de sérialiser les données.',
        ], 500);
    }

    $uploadDir = wp_upload_dir();
    if (!empty($uploadDir['error'])) {
        return new WP_REST_Response([
            'success' => false,
            'message' => $uploadDir['error'],
        ], 500);
    }

    $directory = trailingslashit($uploadDir['basedir']) . 'temp';
    if (!wp_mkdir_p($directory)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Impossible de créer le répertoire temporaire.',
        ], 500);
    }

    $fileName = 'wordpress-data-' . wp_generate_password(8, false, false) . '.json';
    $filePath = trailingslashit($directory) . $fileName;

    try {
        $written = file_put_contents($filePath, $payload);
        if ($written === false) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Impossible d\'écrire le fichier temporaire.',
            ], 500);
        }

        $sent = wp_mail(
            $recipient,
            'Données depuis Wordpress',
            "Bonjour,\n\nVeuillez trouver en pièce jointe le fichier de données à envoyer dans WordPress.\n",
            ['Content-Type: text/plain; charset=UTF-8'],
            [$filePath]
        );

        if (!$sent) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Échec de l\'envoi de l\'email.',
            ], 500);
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Fichier créé, envoyé par email, puis supprimé.',
            'data' => $data,
        ], 200);
    } finally {
        if (is_file($filePath)) {
            unlink($filePath);
        }
    }
}


