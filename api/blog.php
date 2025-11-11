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

$languages = ['az', 'ru', 'en'];

$response = [
    'slug' => $post['slug'],
    'published_at' => $publishedAt,
    'accepts_comments' => (int)($post['accepts_comments'] ?? 1) === 1,
];

foreach ($languages as $lang) {
    $response["title_{$lang}"] = $post["title_{$lang}"] ?? '';
    $response["summary_{$lang}"] = $post["summary_{$lang}"] ?? '';
    $response["content_{$lang}"] = $post["content_{$lang}"] ?? '';
    $response["cover_image_{$lang}"] = $post["cover_image_{$lang}"] ?? null;
    $response["graphic_content_{$lang}"] = $post["graphic_content_{$lang}"] ?? null;
}

echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
