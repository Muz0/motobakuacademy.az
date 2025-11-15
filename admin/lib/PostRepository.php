<?php

declare(strict_types=1);

namespace MotoBaku\Admin;

use PDO;

class PostRepository
{
    private const LANGUAGES = ['az', 'ru', 'en'];

    public function __construct(private PDO $db)
    {
    }

    public function countAll(): int
    {
        return (int)$this->db->query('SELECT COUNT(*) FROM posts')->fetchColumn();
    }

    public function countPublished(): int
    {
        return $this->countByStatus('published');
    }

    public function countDrafts(): int
    {
        return $this->countByStatus('draft');
    }

    public function latest(int $limit = 5): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM posts ORDER BY COALESCE(published_at, created_at) DESC LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $posts = $stmt->fetchAll();
        $this->attachCategoriesToPosts($posts);

        return $posts;
    }

    public function paginate(int $page = 1, int $perPage = 10, array $filters = []): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;

        [$whereSql, $params] = $this->buildWhereClause($filters);

        $countStmt = $this->db->prepare('SELECT COUNT(*) FROM posts ' . $whereSql);
        $this->bindAll($countStmt, $params);
        $countStmt->execute();
        $total = (int)$countStmt->fetchColumn();

        $stmt = $this->db->prepare(
            'SELECT * FROM posts ' .
            $whereSql .
            ' ORDER BY COALESCE(published_at, created_at) DESC LIMIT :limit OFFSET :offset'
        );
        $this->bindAll($stmt, $params);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $items = $stmt->fetchAll();
        $this->attachCategoriesToPosts($items);

        $lastPage = (int)ceil($total / $perPage);

        return [
            'data' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => $lastPage,
        ];
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM posts WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);

        $post = $stmt->fetch();

        if (!$post) {
            return null;
        }

        $post['categories'] = $this->fetchCategoriesForPosts([$id])[$id] ?? [];

        return $post;
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM posts WHERE slug = :slug LIMIT 1');
        $stmt->execute([':slug' => $slug]);

        $post = $stmt->fetch();

        if (!$post) {
            return null;
        }

        $post['categories'] = $this->fetchCategoriesForPosts([(int)$post['id']])[(int)$post['id']] ?? [];

        return $post;
    }

    public function create(array $data): int
    {
        $this->db->beginTransaction();

        $publishedAt = null;
        if (($data['status'] ?? 'draft') === 'published') {
            $publishedAt = $data['published_at'] ?? date('Y-m-d H:i:s');
        }

        $stmt = $this->db->prepare(
            'INSERT INTO posts (
                title_az, title_ru, title_en,
                slug,
                summary_az, summary_ru, summary_en,
                content_az, content_ru, content_en,
                cover_image_az, cover_image_ru, cover_image_en,
                graphic_content_az, graphic_content_ru, graphic_content_en,
                accepts_comments, status, published_at, author_name
            ) VALUES (
                :title_az, :title_ru, :title_en,
                :slug,
                :summary_az, :summary_ru, :summary_en,
                :content_az, :content_ru, :content_en,
                :cover_image_az, :cover_image_ru, :cover_image_en,
                :graphic_content_az, :graphic_content_ru, :graphic_content_en,
                :accepts_comments, :status, :published_at, :author_name
            )'
        );

        try {
            $params = [
                ':slug' => $data['slug'],
                ':accepts_comments' => !empty($data['accepts_comments']) ? 1 : 0,
                ':status' => $data['status'] ?? 'draft',
                ':published_at' => $publishedAt,
                ':author_name' => $data['author_name'] ?? null,
            ];

            foreach (self::LANGUAGES as $language) {
                $params[":title_{$language}"] = $data["title_{$language}"];
                $params[":summary_{$language}"] = $data["summary_{$language}"] ?? null;
                $params[":content_{$language}"] = $data["content_{$language}"] ?? null;
                $params[":cover_image_{$language}"] = $data["cover_image_{$language}"] ?? null;
                $params[":graphic_content_{$language}"] = $data["graphic_content_{$language}"] ?? null;
            }

            $stmt->execute($params);

            $postId = (int)$this->db->lastInsertId();

            if (!empty($data['categories']) && is_array($data['categories'])) {
                $this->syncCategories($postId, $data['categories']);
            }

            $this->db->commit();
        } catch (\Throwable $throwable) {
            $this->db->rollBack();
            throw $throwable;
        }

        return $postId;
    }

    public function update(int $id, array $data): bool
    {
        $this->db->beginTransaction();

        $publishedAt = null;
        if (($data['status'] ?? 'draft') === 'published') {
            $publishedAt = $data['published_at'] ?? date('Y-m-d H:i:s');
        }

        $stmt = $this->db->prepare(
            'UPDATE posts
             SET title_az = :title_az,
                 title_ru = :title_ru,
                 title_en = :title_en,
                 slug = :slug,
                 summary_az = :summary_az,
                 summary_ru = :summary_ru,
                 summary_en = :summary_en,
                 content_az = :content_az,
                 content_ru = :content_ru,
                 content_en = :content_en,
                 cover_image_az = :cover_image_az,
                 cover_image_ru = :cover_image_ru,
                 cover_image_en = :cover_image_en,
                 graphic_content_az = :graphic_content_az,
                 graphic_content_ru = :graphic_content_ru,
                 graphic_content_en = :graphic_content_en,
                 accepts_comments = :accepts_comments,
                 status = :status,
                 published_at = :published_at,
                 author_name = :author_name,
                 updated_at = NOW()
             WHERE id = :id'
        );

        try {
            $params = [
                ':slug' => $data['slug'],
                ':accepts_comments' => !empty($data['accepts_comments']) ? 1 : 0,
                ':status' => $data['status'] ?? 'draft',
                ':published_at' => $publishedAt,
                ':author_name' => $data['author_name'] ?? null,
                ':id' => $id,
            ];

            foreach (self::LANGUAGES as $language) {
                $params[":title_{$language}"] = $data["title_{$language}"];
                $params[":summary_{$language}"] = $data["summary_{$language}"] ?? null;
                $params[":content_{$language}"] = $data["content_{$language}"] ?? null;
                $params[":cover_image_{$language}"] = $data["cover_image_{$language}"] ?? null;
                $params[":graphic_content_{$language}"] = $data["graphic_content_{$language}"] ?? null;
            }

            $updated = $stmt->execute($params);

            if ($updated && array_key_exists('categories', $data)) {
                $this->syncCategories($id, $data['categories'] ?? []);
            }

            $this->db->commit();

            return $updated;
        } catch (\Throwable $throwable) {
            $this->db->rollBack();
            throw $throwable;
        }
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM posts WHERE id = :id');

        return $stmt->execute([':id' => $id]);
    }

    public function existsBySlug(string $slug, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM posts WHERE slug = :slug';
        $params = [':slug' => $slug];

        if ($ignoreId !== null) {
            $sql .= ' AND id != :id';
            $params[':id'] = $ignoreId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn() > 0;
    }

    private function countByStatus(string $status): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM posts WHERE status = :status');
        $stmt->execute([':status' => $status]);

        return (int)$stmt->fetchColumn();
    }

    private function buildWhereClause(array $filters): array
    {
        $clauses = [];
        $params = [];

        if (!empty($filters['status']) && in_array($filters['status'], ['draft', 'published'], true)) {
            $clauses[] = 'status = :status';
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $clauses[] = '(' .
                'title_az LIKE :search OR ' .
                'title_ru LIKE :search OR ' .
                'title_en LIKE :search OR ' .
                'slug LIKE :search' .
            ')';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['category_id'])) {
            $clauses[] = 'EXISTS (
                SELECT 1 FROM post_category pc
                WHERE pc.post_id = posts.id AND pc.category_id = :category_id
            )';
            $params[':category_id'] = (int)$filters['category_id'];
        }

        $whereSql = $clauses ? 'WHERE ' . implode(' AND ', $clauses) : '';

        return [$whereSql, $params];
    }

    private function bindAll(\PDOStatement $stmt, array $params): void
    {
        foreach ($params as $key => $value) {
            $paramType = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($key, $value, $paramType);
        }
    }

    private function syncCategories(int $postId, array $categoryIds): void
    {
        $this->db->prepare('DELETE FROM post_category WHERE post_id = :post_id')
            ->execute([':post_id' => $postId]);

        if (empty($categoryIds)) {
            return;
        }

        $stmt = $this->db->prepare(
            'INSERT INTO post_category (post_id, category_id) VALUES (:post_id, :category_id)'
        );

        foreach ($categoryIds as $categoryId) {
            $stmt->execute([
                ':post_id' => $postId,
                ':category_id' => (int)$categoryId,
            ]);
        }
    }

    private function attachCategoriesToPosts(array &$posts): void
    {
        if (empty($posts)) {
            return;
        }

        $ids = array_map(static fn ($post) => (int)$post['id'], $posts);
        $map = $this->fetchCategoriesForPosts($ids);

        foreach ($posts as &$post) {
            $postId = (int)$post['id'];
            $post['categories'] = $map[$postId] ?? [];
        }
    }

    private function fetchCategoriesForPosts(array $postIds): array
    {
        if (empty($postIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($postIds), '?'));

        $stmt = $this->db->prepare(
            "SELECT pc.post_id, c.id, c.name, c.slug
             FROM post_category pc
             INNER JOIN categories c ON c.id = pc.category_id
             WHERE pc.post_id IN ($placeholders)"
        );

        $stmt->execute($postIds);

        $map = [];

        while ($row = $stmt->fetch()) {
            $postId = (int)$row['post_id'];
            unset($row['post_id']);
            $map[$postId][] = $row;
        }

        return $map;
    }
}
