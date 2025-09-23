<?php
declare(strict_types=1);

namespace Taskboard;

final class TasksController
{
    public function __construct(private readonly TasksRepository $repo) {}

    // GET /v1/tasks
    public function index(): void
    {
        $tasks = $this->repo->all();
        header('X-Total-Count: ' . count($tasks));
        Http::json($tasks, 200);
    }

    // GET /v1/tasks/{id}
    public function show(string $id): void
    {
        $task = $this->repo->find((int)$id);
        if (!$task) {
            Http::error('not_found', 'Task not found', 404);
            return;
        }
        Http::json($task, 200);
    }

    // POST /v1/tasks
    // Body: { "title": "Buy milk", "status": "open|in_progress|done" }
    public function store(): void
    {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];

        $title  = trim((string)($input['title'] ?? ''));
        $status = (string)($input['status'] ?? 'open');

        $allowed = ['open','in_progress','done'];
        if ($title === '') {
            Http::error('validation_error', 'Title is required', 422, ['title' => ['Title cannot be empty']]);
            return;
        }
        if (!in_array($status, $allowed, true)) {
            Http::error('validation_error', 'Invalid status', 422, ['status' => ['Must be open|in_progress|done']]);
            return;
        }

        $id = $this->repo->create($title, $status);
        $created = $this->repo->find($id);

        Http::json($created, 201, ['Location' => '/v1/tasks/' . $id]);
    }

    // PUT /v1/tasks/{id}
    // Body can include any subset of: { "title": "...", "status": "..." }
    public function update(string $id): void
    {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $title  = array_key_exists('title', $input) ? trim((string)$input['title']) : null;
        $status = array_key_exists('status', $input) ? (string)$input['status'] : null;

        if ($status !== null && !in_array($status, ['open','in_progress','done'], true)) {
            Http::error('validation_error', 'Invalid status', 422, ['status' => ['Must be open|in_progress|done']]);
            return;
        }

        $ok = $this->repo->update((int)$id, $title, $status);
        if (!$ok) {
            $exists = $this->repo->find((int)$id);
            if (!$exists) {
                Http::error('not_found', 'Task not found', 404);
                return;
            }
            Http::error('validation_error', 'No valid fields to update', 422);
            return;
        }

        $fresh = $this->repo->find((int)$id);
        Http::json($fresh, 200);
    }

    // DELETE /v1/tasks/{id}
    public function destroy(string $id): void
    {
        $ok = $this->repo->delete((int)$id);
        if (!$ok) {
            Http::error('not_found', 'Task not found', 404);
            return;
        }
        Http::json(['deleted' => (int)$id], 200);
    }
}
