<?php
declare(strict_types=1);

namespace Taskboard;



final class TasksController
{
    public function __construct(private readonly TasksRepository $repo) {}

    // GET /v1/tasks
    public function index(): void
    {
        // 1) Read query params (strings by default)
        $status  = $_GET['status']  ?? null; // e.g., "open", "done"
        $q       = $_GET['q']       ?? null; // text search
        $sortBy  = $_GET['sort_by'] ?? 'created_at';
        $sortDir = $_GET['sort_dir'] ?? 'desc';

        // 2) Pagination with defaults + clamping
        $limit  = filter_var($_GET['limit']  ?? null, FILTER_VALIDATE_INT);
        $offset = filter_var($_GET['offset'] ?? null, FILTER_VALIDATE_INT);
        $limit  = $limit  !== false ? max(1, min($limit, 100)) : 20; // 1..100
        $offset = $offset !== false ? max(0, $offset) : 0;

        // 3) Optional: validate status against known values
        $allowedStatuses = ['open','in_progress','done','archived'];
        if ($status !== null && $status !== '' && !in_array($status, $allowedStatuses, true)) {
            Http::error('invalid_status', 'Invalid status value', 400);
            return;
        }

        // 4) Fetch total (with the same filters)
        $total = $this->repo->countByCriteria($status, $q);

        // 5) Fetch the current page
        $rows = $this->repo->findByCriteria($status, $q, $sortBy, $sortDir, $limit, $offset);

        // 6) Pagination headers
        $hasMore = ($offset + $limit) < $total ? 'true' : 'false';

        header('X-Total-Count: ' . $total);
        header('X-Limit: ' . $limit);
        header('X-Offset: ' . $offset);
        header('X-Has-More: ' . $hasMore);

        // (Optional) RFC5988 Link header for prev/next
        $base = $this->baseUrl('/v1/tasks');
        $links = [];
        if ($offset > 0) {
            $prevOffset = max(0, $offset - $limit);
            $links[] = '<' . $this->buildUrl($base, $status, $q, $sortBy, $sortDir, $limit, $prevOffset) . '>; rel="prev"';
        }
        if (($offset + $limit) < $total) {
            $nextOffset = $offset + $limit;
            $links[] = '<' . $this->buildUrl($base, $status, $q, $sortBy, $sortDir, $limit, $nextOffset) . '>; rel="next"';
        }
        if ($links) {
            header('Link: ' . implode(', ', $links));
        }

        // 7) Return JSON list
        Http::json($rows, 200);
    }

    private function baseUrl(string $path): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host . $path;
    }

    private function buildUrl(
        string $base,
        ?string $status,
        ?string $q,
        string $sortBy,
        string $sortDir,
        int $limit,
        int $offset
    ): string {
        $query = [
            'limit'    => $limit,
            'offset'   => $offset,
            'sort_by'  => $sortBy,
            'sort_dir' => $sortDir,
        ];
        if ($status !== null && $status !== '') { $query['status'] = $status; }
        if ($q !== null && $q !== '')           { $query['q'] = $q; }
        return $base . '?' . http_build_query($query);
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
