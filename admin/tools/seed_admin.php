<?php

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';

if (PHP_SAPI !== 'cli') {
    echo "This script must be run from the command line.\n";
    exit(1);
}

$db = app('db');

if (!$db) {
    echo "Database connection is not configured.\n";
    exit(1);
}

function prompt(string $label, ?string $default = null): string
{
    $suffix = $default !== null ? " [{$default}]" : '';
    echo "{$label}{$suffix}: ";
    $input = trim((string)fgets(STDIN));

    if ($input === '' && $default !== null) {
        return $default;
    }

    return $input;
}

$username = '';
while ($username === '') {
    $username = prompt('Username', 'admin');
}

$password = '';
while ($password === '') {
    echo "Password: ";
    $password = trim((string)fgets(STDIN));
    if ($password === '') {
        echo "Password cannot be empty.\n";
    }
}

$role = prompt('Role (admin/editor)', 'admin');
if (!in_array($role, ['admin', 'editor'], true)) {
    $role = 'admin';
}

try {
    $existsStmt = $db->prepare('SELECT COUNT(*) FROM users WHERE username = :username');
    $existsStmt->execute([':username' => $username]);
    if ((int)$existsStmt->fetchColumn() > 0) {
        echo "User already exists.\n";
        exit(1);
    }

    $stmt = $db->prepare(
        'INSERT INTO users (username, password_hash, role) VALUES (:username, :password_hash, :role)'
    );
    $stmt->execute([
        ':username' => $username,
        ':password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ':role' => $role,
    ]);

    echo "Admin user created.\n";
} catch (Throwable $exception) {
    echo "Failed to create user: " . $exception->getMessage() . "\n";
    exit(1);
}
