<?php

require __DIR__ . '/../admin/bootstrap.php';

use MotoBaku\Admin\PostRepository;

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

/** @var PostRepository|null $posts */
$posts = app('posts');

if (!$posts instanceof PostRepository) {
    http_response_code(500);
    echo json_encode(['error' => 'Post repository unavailable']);
    exit;
}

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = (int)($_GET['per_page'] ?? 10);
$perPage = max(1, min(50, $perPage));

try {
    $paginated = $posts->paginate($page, $perPage, ['status' => 'published']);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load posts']);
    exit;
}

$data = array_map(static function (array $item): array {
    $publishedAt = $item['published_at'] ?? $item['created_at'] ?? null;
    if ($publishedAt) {
        try {
            $publishedAt = (new \DateTime($publishedAt))->format(DATE_ATOM);
        } catch (\Throwable $e) {
            $publishedAt = null;
        }
    }

    return [
        'slug' => $item['slug'],
        'title' => $item['title'],
        'excerpt' => $item['excerpt'] ?? '',
        'cover_image' => $item['cover_image'] ?? null,
        'published_at' => $publishedAt,
    ];
}, $paginated['data']);

$response = [
    'data' => $data,
    'pagination' => [
        'page' => $paginated['page'],
        'per_page' => $paginated['per_page'],
        'last_page' => $paginated['last_page'],
        'total' => $paginated['total'],
    ],
];

echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
