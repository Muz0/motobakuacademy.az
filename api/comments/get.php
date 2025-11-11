<?php

require __DIR__ . '/../../admin/bootstrap.php';

use MotoBaku\Admin\CommentRepository;
use MotoBaku\Admin\PostRepository;

header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$slug = trim((string)($_GET['slug'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = (int)($_GET['per_page'] ?? 20);
$perPage = max(1, min(50, $perPage));

/** @var PostRepository|null $postRepo */
$postRepo = app('posts');
/** @var CommentRepository|null $commentRepo */
$commentRepo = app('comments');

if (!$postRepo instanceof PostRepository || !$commentRepo instanceof CommentRepository) {
    http_response_code(500);
    echo json_encode(['error' => 'Service unavailable']);
    exit;
}

if ($slug === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Missing slug parameter']);
    exit;
}

try {
    $post = $postRepo->findBySlug($slug);
} catch (Throwable $throwable) {
    http_response_code(500);
    echo json_encode(['error' => 'Unable to load post comments']);
    exit;
}

if (!$post || ($post['status'] ?? '') !== 'published') {
    http_response_code(404);
    echo json_encode(['error' => 'Post not found']);
    exit;
}

try {
    $comments = $commentRepo->listByPost((int)$post['id'], $page, $perPage);
} catch (Throwable $throwable) {
    http_response_code(500);
    echo json_encode(['error' => 'Unable to load comments']);
    exit;
}

$response = [
    'post' => [
        'id' => (int)$post['id'],
        'slug' => $post['slug'],
        'accepts_comments' => (int)($post['accepts_comments'] ?? 1) === 1,
    ],
    'data' => array_map(static function (array $comment): array {
        return [
            'id' => (int)$comment['id'],
            'author_name' => $comment['author_name'],
            'message' => $comment['message'],
            'created_at' => $comment['created_at'],
            'parent_comment_id' => $comment['parent_comment_id'] !== null
                ? (int)$comment['parent_comment_id']
                : null,
        ];
    }, $comments['data']),
    'pagination' => [
        'page' => $comments['page'],
        'per_page' => $comments['per_page'],
        'last_page' => $comments['last_page'],
        'total' => $comments['total'],
    ],
];

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
