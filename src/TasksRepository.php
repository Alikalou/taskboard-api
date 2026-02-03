<?php
declare(strict_types=1);

namespace Taskboard;

use PDO;

final class TasksRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function all(): array
    {
        $stmt = $this->pdo->query(
            'SELECT id, title, status, created_at, updated_at
             FROM tasks ORDER BY id DESC'
        );
        return $stmt->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, title, status, created_at, updated_at
             FROM tasks WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
    
    public function create(string $title, string $status = 'open'): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO tasks (title, status) VALUES (:title, :status)'
        );
        $stmt->execute([':title' => $title, ':status' => $status]);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, ?string $title = null, ?string $status = null): bool
    {
        $fields = [];
        $params = [':id' => $id];

        if ($title !== null) { $fields[] = 'title = :title'; $params[':title'] = $title; }
        if ($status !== null) { $fields[] = 'status = :status'; $params[':status'] = $status; }

        if (!$fields) return false;

        $sql = 'UPDATE tasks SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM tasks WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function countByCriteria(?string $status, ?string $q): int
    {
    $where  = [];
    $params = [];

    if ($status !== null && $status !== '') {
        $where[] = 'status = :status';
        $params[':status'] = $status;
    }
    if ($q !== null && $q !== '') {
        $where[] = '(title LIKE :q OR description LIKE :q)';
        $params[':q'] = '%' . $q . '%';
    }

    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $sql = "SELECT COUNT(*) FROM tasks $whereSql";

    $stmt = $this->pdo->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->execute();
    return (int)$stmt->fetchColumn();
    }

    
    public function findByCriteria(
        ?string $status,
        ?string $q,
        string $sortBy,
        string $sortDir,
        int $limit,
        int $offset
    ): array {
        $where  = [];
        $params = [];

        if ($status !== null && $status !== '') {
            $where[] = 'status = :status';
            $params[':status'] = $status;
        }
        if ($q !== null && $q !== '') {
            $where[] = '(title LIKE :q OR description LIKE :q)';
            $params[':q'] = '%' . $q . '%';
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // Whitelist sortable columns (never interpolate arbitrary input)
        $sortable = ['id','title','status','created_at','updated_at'];
        if (!in_array($sortBy, $sortable, true)) {
            $sortBy = 'created_at';
        }
        $sortDir = strtolower($sortDir) === 'asc' ? 'ASC' : 'DESC';

        $sql = "SELECT id, title, status, created_at, updated_at
                FROM tasks
                $whereSql
                ORDER BY $sortBy $sortDir
                LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit',  $limit,  \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}

/* This task repository class defines the basic CRUD operations, using prepared statements to prevent SQL injection.
What is hard about this code is not really the idea, but the php syntax. A lot of OPP cencepts used here.
And it's clear more than ever that I need a text book to guide me through the basics of php.
*/


