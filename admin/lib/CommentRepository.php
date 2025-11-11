<?php

declare(strict_types=1);

namespace MotoBaku\Admin;

use PDO;

class CommentRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function paginate(int $page = 1, int $perPage = 15, array $filters = []): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $offset = ($page - 1) * $perPage;

        [$whereSql, $params] = $this->buildWhereClause($filters);

        $countStmt = $this->db->prepare(
            'SELECT COUNT(*) FROM comments c
             LEFT JOIN posts p ON p.id = c.post_id ' . $whereSql
        );
        $this->bindAll($countStmt, $params);
        $countStmt->execute();
        $total = (int)$countStmt->fetchColumn();

        $stmt = $this->db->prepare(
            'SELECT c.*, p.title_az AS post_title, p.slug AS post_slug
             FROM comments c
             LEFT JOIN posts p ON p.id = c.post_id ' .
            $whereSql .
            ' ORDER BY c.created_at DESC
              LIMIT :limit OFFSET :offset'
        );
        $this->bindAll($stmt, $params);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'data' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => (int)max(1, ceil($total / $perPage)),
        ];
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT c.*, p.title_az AS post_title, p.slug AS post_slug
             FROM comments c
             LEFT JOIN posts p ON p.id = c.post_id
             WHERE c.id = :id
             LIMIT 1'
        );
        $stmt->execute([':id' => $id]);

        $comment = $stmt->fetch();

        return $comment ?: null;
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = [':id' => $id];

        if (array_key_exists('author_name', $data)) {
            $fields[] = 'author_name = :author_name';
            $params[':author_name'] = $data['author_name'];
        }

        if (array_key_exists('message', $data)) {
            $fields[] = 'message = :message';
            $params[':message'] = $data['message'];
        }

        if (array_key_exists('is_deleted', $data)) {
            $fields[] = 'is_deleted = :is_deleted';
            $params[':is_deleted'] = (int)((bool)$data['is_deleted']);
        }

        if (empty($fields)) {
            return false;
        }

        $sql = 'UPDATE comments SET ' . implode(', ', $fields) . ' WHERE id = :id';

        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);
    }

    public function setDeleted(int $id, bool $deleted = true): bool
    {
        $stmt = $this->db->prepare('UPDATE comments SET is_deleted = :deleted WHERE id = :id');
        $stmt->bindValue(':deleted', $deleted ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public function listByPost(int $postId, int $page = 1, int $perPage = 20, bool $includeHidden = false): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(50, $perPage));
        $offset = ($page - 1) * $perPage;

        $visibilityClause = $includeHidden ? '' : ' AND is_deleted = 0';

        $countStmt = $this->db->prepare(
            'SELECT COUNT(*) FROM comments WHERE post_id = :post_id' . $visibilityClause
        );
        $countStmt->execute([':post_id' => $postId]);
        $total = (int)$countStmt->fetchColumn();

        $stmt = $this->db->prepare(
            'SELECT id, post_id, user_id, parent_comment_id, author_name, message, created_at, is_deleted
             FROM comments
             WHERE post_id = :post_id' . $visibilityClause . '
             ORDER BY created_at ASC
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':post_id', $postId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'data' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => (int)max(1, ceil($total / $perPage)),
        ];
    }

    public function create(array $payload): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO comments (post_id, user_id, parent_comment_id, author_name, message, is_deleted)
             VALUES (:post_id, :user_id, :parent_comment_id, :author_name, :message, :is_deleted)'
        );

        $stmt->execute([
            ':post_id' => $payload['post_id'],
            ':user_id' => $payload['user_id'] ?? null,
            ':parent_comment_id' => $payload['parent_comment_id'] ?? null,
            ':author_name' => $payload['author_name'],
            ':message' => $payload['message'],
            ':is_deleted' => $payload['is_deleted'] ?? 0,
        ]);

        return (int)$this->db->lastInsertId();
    }

    private function buildWhereClause(array $filters): array
    {
        $clauses = [];
        $params = [];

        $status = $filters['status'] ?? null;
        if ($status === 'deleted') {
            $clauses[] = 'c.is_deleted = 1';
        } elseif ($status === 'active') {
            $clauses[] = 'c.is_deleted = 0';
        }

        if (!empty($filters['search'])) {
            $clauses[] = '(c.author_name LIKE :search OR c.message LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['post'])) {
            $clauses[] = '(p.slug LIKE :post OR p.title LIKE :post)';
            $params[':post'] = '%' . $filters['post'] . '%';
        }

        if (!empty($filters['post_id'])) {
            $clauses[] = 'c.post_id = :post_id';
            $params[':post_id'] = (int)$filters['post_id'];
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
}
