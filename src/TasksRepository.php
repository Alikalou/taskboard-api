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
}

/* This task repository class defines the basic CRUD operations, using prepared statements to prevent SQL injection.
What is hard about this code is not really the idea, but the php syntax. A lot of OPP cencepts used here.
And it's clear more than ever that I need a text book to guide me through the basics of php.
*/


