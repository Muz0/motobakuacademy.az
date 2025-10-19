<?php

require __DIR__ . '/../admin/bootstrap.php';

use MotoBaku\Admin\PostRepository;

// header('Content-Type: application/json; charset=utf-8');
// declare(strict_types=1);

// Allow any origin (development only)
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

// For preflight requests (if you plan to use POST/PUT/DELETE)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Accept');
    exit(0);
}

$slug = trim((string)($_GET['slug'] ?? ''));
if ($slug === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Missing slug parameter']);
    exit;
}

/** @var PostRepository|null $posts */
$posts = app('posts');

if (!$posts instanceof PostRepository) {
    http_response_code(500);
    echo json_encode(['error' => 'Post repository unavailable']);
    exit;
}

try {
    $post = $posts->findBySlug($slug);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load post']);
    exit;
}

if (!$post || ($post['status'] ?? '') !== 'published') {
    http_response_code(404);
    echo json_encode(['error' => 'Post not found']);
    exit;
}

$publishedAt = $post['published_at'] ?? $post['created_at'] ?? null;
if ($publishedAt) {
    try {
        $publishedAt = (new \DateTime($publishedAt))->format(DATE_ATOM);
    } catch (\Throwable $e) {
        $publishedAt = null;
    }
}

$response = [
    'slug' => $post['slug'],
    'title' => $post['title'],
    'excerpt' => $post['excerpt'] ?? '',
    'cover_image' => $post['cover_image'] ?? null,
    'graphic_content' => $post['graphic_content'] ?? null,
    'content' => $post['content'],
    'published_at' => $publishedAt,
];

echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
