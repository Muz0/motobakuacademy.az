<?php

declare(strict_types=1);

namespace MotoBaku\Admin;

use PDO;

class Auth
{
    private PDO $db;
    private string $sessionKey = 'auth';
    private ?array $cachedUser = null;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function attempt(string $username, string $password): bool
    {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();

        if (!$user) {
            return false;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

        $this->login((int)$user['id']);
        $this->cachedUser = $user;

        return true;
    }

    public function login(int $userId): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        $_SESSION[$this->sessionKey] = [
            'user_id' => $userId,
            'last_active' => time(),
        ];
    }

    public function logout(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        unset($_SESSION[$this->sessionKey]);
        $this->cachedUser = null;
    }

    public function check(): bool
    {
        return isset($_SESSION[$this->sessionKey]['user_id']);
    }

    public function user(): ?array
    {
        if (!$this->check()) {
            return null;
        }

        if ($this->cachedUser) {
            return $this->cachedUser;
        }

        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $_SESSION[$this->sessionKey]['user_id']]);
        $user = $stmt->fetch() ?: null;

        $this->cachedUser = $user ?: null;

        return $this->cachedUser;
    }

    public function requireAuth(): void
    {
        if ($this->check()) {
            return;
        }

        flash('error', 'Please sign in to continue.');
        redirect(base_url('login.php'));
    }

    public function requireRole(string|array $roles): void
    {
        $roles = (array)$roles;
        $user = $this->user();

        if (!$user || !in_array($user['role'], $roles, true)) {
            flash('error', 'You do not have permission to access that area.');
            redirect(base_url());
        }
    }
}
