<?php

require __DIR__ . '/../admin/bootstrap.php';

use MotoBaku\Admin\TeamRepository;

handle_preflight(['GET']);
send_json_headers(['GET']);

/** @var TeamRepository|null $teamRepo */
$teamRepo = app('team');

if (!$teamRepo instanceof TeamRepository) {
    http_response_code(500);
    echo json_encode(['error' => 'Team repository unavailable']);
    exit;
}

function sanitizePlainText(?string $value): string
{
    return trim(strip_tags($value ?? ''));
}

function sanitizeRichText(?string $value): string
{
    if ($value === null) {
        return '';
    }

    $clean = preg_replace('#<(script|style)\b[^>]*>(.*?)</\1>#is', '', $value);
    $clean = preg_replace('#on\w+\s*=\s*["\']?.*?["\']?#is', '', $clean);
    $clean = preg_replace('#javascript:#i', '', $clean);

    return trim($clean ?? '');
}

function sanitizeOptionalUrl(?string $value): ?string
{
    if ($value === null || $value === '') {
        return null;
    }

    $safe = filter_var($value, FILTER_SANITIZE_URL);

    return $safe ?: null;
}

$members = $teamRepo->all();
$description = sanitizePlainText($teamRepo->getTeamDescription());
$aboutContent = $teamRepo->getAboutContent();

$data = array_map(static function (array $member): array {
    return [
        'id' => (int)$member['id'],
        'name' => sanitizePlainText($member['name'] ?? ''),
        'role' => sanitizePlainText($member['role'] ?? ''),
        'description' => sanitizePlainText($member['description'] ?? ''),
        'photo_url' => sanitizeOptionalUrl($member['photo_url'] ?? '') ?? '',
        'position' => (int)($member['position'] ?? 0),
    ];
}, $members);

$aboutPayload = [
    'title_az' => sanitizePlainText($aboutContent['title_az'] ?? ''),
    'title_ru' => sanitizePlainText($aboutContent['title_ru'] ?? ''),
    'title_en' => sanitizePlainText($aboutContent['title_en'] ?? ''),
    'content_az' => sanitizeRichText($aboutContent['content_az'] ?? ''),
    'content_ru' => sanitizeRichText($aboutContent['content_ru'] ?? ''),
    'content_en' => sanitizeRichText($aboutContent['content_en'] ?? ''),
];

echo json_encode([
    'data' => $data,
    'description' => $description,
    'about' => $aboutPayload,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
