<?php
namespace Taskboard;

final class TasksController
{
    // For Day 1: hard-coded data to prove the plumbing
    public function index(): void
    {
        // Normally you would fetch from DB; we'll stub it out
        $tasks = [
            [
                'id' => 1,
                'title' => 'Write REST demo',
                'status' => 'open',
                'created_at' => '2025-08-25T15:30:51Z',
                'updated_at' => '2025-08-25T15:30:51Z'
            ],
            [
                'id' => 2,
                'title' => 'Read docs',
                'status' => 'in_progress',
                'created_at' => '2025-08-26T10:00:00Z',
                'updated_at' => '2025-08-26T10:00:00Z'
            ],
        ];

        // Pagination headers (stubbed)
        header('X-Total-Count: 2');

        Http::json($tasks, 200);
    }
}
