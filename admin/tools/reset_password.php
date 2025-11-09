<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI !== 'cli') {
    echo "This tool must be run from the command line.\n";
    exit(1);
}

$db = app('db');

if (!$db) {
    echo "Database connection is not available.\n";
    exit(1);
}

/**
 * Prompt the operator for a value.
 */
function prompt(string $label): string
{
    echo "{$label}: ";
    return trim((string)fgets(STDIN));
}

$identifier = $argv[1] ?? null;
$newPassword = $argv[2] ?? null;

while ($identifier === null || $identifier === '') {
    $identifier = prompt('Username or ID');
}
$identifier = trim($identifier);

while ($newPassword === null || $newPassword === '') {
    $newPassword = prompt('New password');
    if ($newPassword === '') {
        echo "Password cannot be empty.\n";
    }
}

$lookupById = ctype_digit($identifier);

try {
    if ($lookupById) {
        $stmt = $db->prepare('SELECT id, username FROM users WHERE id = :value LIMIT 1');
        $stmt->execute([':value' => (int)$identifier]);
    } else {
        $stmt = $db->prepare('SELECT id, username FROM users WHERE username = :value LIMIT 1');
        $stmt->execute([':value' => $identifier]);
    }

    $user = $stmt->fetch();

    if (!$user) {
        echo "User not found.\n";
        exit(1);
    }

    $hash = password_hash($newPassword, PASSWORD_BCRYPT);
    $update = $db->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
    $update->execute([
        ':hash' => $hash,
        ':id' => (int)$user['id'],
    ]);

    echo "Password updated for user {$user['username']} (ID {$user['id']}).\n";
} catch (Throwable $exception) {
    echo "Failed to update password: " . $exception->getMessage() . "\n";
    exit(1);
}
