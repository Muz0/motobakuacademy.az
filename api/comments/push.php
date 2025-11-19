<?php

require __DIR__ . '/../../admin/bootstrap.php';
require_once __DIR__ . '/helpers.php';

use MotoBaku\Admin\CommentRepository;
use MotoBaku\Admin\PostRepository;

handle_preflight(['POST']);
send_json_headers(['POST']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$rawBody = file_get_contents('php://input');
$payload = json_decode($rawBody, true);
if (!is_array($payload)) {
    $payload = $_POST;
}

$slug = trim((string)($payload['slug'] ?? ''));
$authorName = sanitize_comment_author($payload['author_name'] ?? '');
$message = sanitize_comment_message($payload['message'] ?? '');
$parentCommentId = $payload['parent_comment_id'] ?? null;
$parentCommentId = $parentCommentId !== null ? (int)$parentCommentId : null;

$errors = [];

if ($slug === '') {
    $errors['slug'][] = 'Slug is required.';
}
if ($authorName === '' || mb_strlen($authorName) < 2) {
    $errors['author_name'][] = 'Author name must be at least 2 characters.';
} elseif (mb_strlen($authorName) > 191) {
    $errors['author_name'][] = 'Author name is too long.';
}
if ($message === '' || mb_strlen($message) < 2) {
    $errors['message'][] = 'Message must be at least 2 characters.';
} elseif (mb_strlen($message) > 2000) {
    $errors['message'][] = 'Message is too long.';
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['errors' => $errors]);
    exit;
}

/** @var PostRepository|null $postRepo */
$postRepo = app('posts');
/** @var CommentRepository|null $commentRepo */
$commentRepo = app('comments');

if (!$postRepo instanceof PostRepository || !$commentRepo instanceof CommentRepository) {
    http_response_code(500);
    echo json_encode(['error' => 'Service unavailable']);
    exit;
}

try {
    $post = $postRepo->findBySlug($slug);
} catch (Throwable $throwable) {
    http_response_code(500);
    echo json_encode(['error' => 'Unable to save comment']);
    exit;
}

if (!$post || ($post['status'] ?? '') !== 'published') {
    http_response_code(404);
    echo json_encode(['error' => 'Post not found']);
    exit;
}

if ((int)($post['accepts_comments'] ?? 1) !== 1) {
    http_response_code(409);
    echo json_encode(['error' => 'Comments are closed for this post']);
    exit;
}

$parentComment = null;
if ($parentCommentId !== null) {
    try {
        $parentComment = $commentRepo->find($parentCommentId);
    } catch (Throwable $throwable) {
        $parentComment = null;
    }

    if (!$parentComment || (int)$parentComment['post_id'] !== (int)$post['id'] || (int)$parentComment['is_deleted'] === 1) {
        http_response_code(422);
        echo json_encode(['errors' => ['parent_comment_id' => ['Parent comment not found.']]]);
        exit;
    }
}

try {
    $commentId = $commentRepo->create([
        'post_id' => (int)$post['id'],
        'user_id' => null,
        'parent_comment_id' => $parentCommentId,
        'author_name' => $authorName,
        'message' => $message,
        'is_deleted' => 0,
    ]);

    $created = $commentRepo->find($commentId);
} catch (Throwable $throwable) {
    http_response_code(500);
    echo json_encode(['error' => 'Unable to save comment']);
    exit;
}

$response = [
    'id' => (int)$created['id'],
    'author_name' => sanitize_comment_author($created['author_name'] ?? ''),
    'message' => sanitize_comment_message($created['message'] ?? ''),
    'created_at' => $created['created_at'],
    'parent_comment_id' => $created['parent_comment_id'] !== null
        ? (int)$created['parent_comment_id']
        : null,
];

http_response_code(201);
echo json_encode(['status' => 'ok', 'comment' => $response], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
