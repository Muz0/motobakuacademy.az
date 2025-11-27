<?php

declare(strict_types=1);

namespace MotoBaku\Admin;

use PDO;

class TeamRepository
{
    public function __construct(private PDO $db)
    {
    }

    private function ensureSettingsRow(): void
    {
        $stmt = $this->db->prepare(
            'INSERT IGNORE INTO team_settings (
                id,
                description,
                about_title_az,
                about_title_ru,
                about_title_en,
                about_content_az,
                about_content_ru,
                about_content_en
            ) VALUES (1, NULL, NULL, NULL, NULL, NULL, NULL, NULL)'
        );
        $stmt->execute();
    }

    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM team_members ORDER BY position ASC, id ASC');
        return $stmt->fetchAll() ?: [];
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM team_members WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO team_members (name, role, description, photo_url, position)
             VALUES (:name, :role, :description, :photo_url, :position)'
        );

        $stmt->execute([
            ':name' => $data['name'],
            ':role' => $data['role'],
            ':description' => $data['description'] ?? null,
            ':photo_url' => $data['photo_url'],
            ':position' => $data['position'] ?? 0,
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE team_members
             SET name = :name,
                 role = :role,
                 description = :description,
                 photo_url = :photo_url,
                 position = :position,
                 updated_at = NOW()
             WHERE id = :id'
        );

        return $stmt->execute([
            ':name' => $data['name'],
            ':role' => $data['role'],
            ':description' => $data['description'] ?? null,
            ':photo_url' => $data['photo_url'],
            ':position' => $data['position'] ?? 0,
            ':id' => $id,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM team_members WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    public function getTeamDescription(): string
    {
        $this->ensureSettingsRow();
        $stmt = $this->db->query('SELECT description FROM team_settings WHERE id = 1 LIMIT 1');
        $row = $stmt->fetch();

        return $row && isset($row['description']) ? (string)$row['description'] : '';
    }

    public function saveTeamDescription(?string $description): void
    {
        $this->ensureSettingsRow();
        $stmt = $this->db->prepare('UPDATE team_settings SET description = :description, updated_at = NOW() WHERE id = 1');
        $stmt->execute([':description' => $description]);
    }

    public function getAboutContent(): array
    {
        $this->ensureSettingsRow();
        $stmt = $this->db->query('SELECT about_title_az, about_title_ru, about_title_en, about_content_az, about_content_ru, about_content_en FROM team_settings WHERE id = 1 LIMIT 1');
        $row = $stmt->fetch() ?: [];

        return [
            'title_az' => $row['about_title_az'] ?? '',
            'title_ru' => $row['about_title_ru'] ?? '',
            'title_en' => $row['about_title_en'] ?? '',
            'content_az' => $row['about_content_az'] ?? '',
            'content_ru' => $row['about_content_ru'] ?? '',
            'content_en' => $row['about_content_en'] ?? '',
        ];
    }

    public function saveAboutContent(array $data): void
    {
        $this->ensureSettingsRow();
        $stmt = $this->db->prepare(
            'UPDATE team_settings
             SET about_title_az = :title_az,
                 about_title_ru = :title_ru,
                 about_title_en = :title_en,
                 about_content_az = :content_az,
                 about_content_ru = :content_ru,
                 about_content_en = :content_en,
                 updated_at = NOW()
             WHERE id = 1'
        );

        $stmt->execute([
            ':title_az' => $data['title_az'] ?? null,
            ':title_ru' => $data['title_ru'] ?? null,
            ':title_en' => $data['title_en'] ?? null,
            ':content_az' => $data['content_az'] ?? null,
            ':content_ru' => $data['content_ru'] ?? null,
            ':content_en' => $data['content_en'] ?? null,
        ]);
    }

    public function nextPosition(): int
    {
        $stmt = $this->db->query('SELECT COALESCE(MAX(position), 0) AS max_pos FROM team_members');
        $max = (int)$stmt->fetchColumn();
        return $max + 1;
    }
}
