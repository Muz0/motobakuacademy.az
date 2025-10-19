<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use MotoBaku\Admin\CSRF;
use MotoBaku\Admin\MediaService;

$auth = app('auth');

if ($auth) {
    $auth->requireAuth();
}

header('Content-Type: application/json; charset=utf-8');

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

try {
    if ($method === 'GET') {
        $media = MediaService::listMedia();
        echo json_encode([
            'data' => $media,
            'token' => CSRF::getToken(),
        ]);
        exit;
    }

    if ($method === 'POST') {
        $token = $_POST['_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if (!CSRF::validate($token)) {
            http_response_code(419);
            echo json_encode(['error' => 'Invalid session token.', 'token' => CSRF::getToken()]);
            exit;
        }

        $result = MediaService::storeUploadedFile($_FILES['media'] ?? []);

        echo json_encode([
            'data' => $result,
            'token' => CSRF::getToken(),
        ]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed.']);
} catch (RuntimeException $exception) {
    http_response_code(400);
    echo json_encode([
        'error' => $exception->getMessage(),
        'token' => CSRF::getToken(),
    ]);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Unexpected error occurred.',
        'token' => CSRF::getToken(),
    ]);
}
