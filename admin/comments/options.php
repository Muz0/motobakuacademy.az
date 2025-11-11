<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

use MotoBaku\Admin\CommentRepository;

$auth = app('auth');

if ($auth) {
    $auth->requireAuth();
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$postId = (int)($_GET['post_id'] ?? 0);

/** @var CommentRepository|null $commentsRepo */
$commentsRepo = app('comments');

if (!$commentsRepo) {
    http_response_code(500);
    echo json_encode(['error' => 'Comment service unavailable']);
    exit;
}

if ($postId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid post id']);
    exit;
}

try {
    $listing = $commentsRepo->listByPost($postId, 1, 500, true);
} catch (Throwable $exception) {
    http_response_code(500);
    echo json_encode(['error' => 'Unable to load comments']);
    exit;
}

$data = array_map(static function (array $comment): array {
    $plain = trim(strip_tags((string)$comment['message']));
    $preview = mb_strlen($plain) > 80 ? mb_substr($plain, 0, 77) . '…' : ($plain !== '' ? $plain : '(no text)');

    return [
        'id' => (int)$comment['id'],
        'author_name' => $comment['author_name'],
        'message_plain' => $preview,
        'is_deleted' => (int)($comment['is_deleted'] ?? 0) === 1,
    ];
}, $listing['data'] ?? []);

echo json_encode(['data' => $data], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
