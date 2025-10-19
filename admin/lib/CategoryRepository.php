<?php

declare(strict_types=1);

namespace MotoBaku\Admin;

use PDO;

class CategoryRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function all(): array
    {
        $stmt = $this->db->query('SELECT * FROM categories ORDER BY name ASC');

        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM categories WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);

        $category = $stmt->fetch();

        return $category ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO categories (name, slug) VALUES (:name, :slug)'
        );
        $stmt->execute([
            ':name' => $data['name'],
            ':slug' => $data['slug'],
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE categories SET name = :name, slug = :slug WHERE id = :id'
        );

        return $stmt->execute([
            ':name' => $data['name'],
            ':slug' => $data['slug'],
            ':id' => $id,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM categories WHERE id = :id');

        return $stmt->execute([':id' => $id]);
    }

    public function existsBySlug(string $slug, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM categories WHERE slug = :slug';
        $params = [':slug' => $slug];

        if ($ignoreId !== null) {
            $sql .= ' AND id != :id';
            $params[':id'] = $ignoreId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn() > 0;
    }
}
