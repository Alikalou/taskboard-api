<?php
namespace Taskboard;

require __DIR__ . '/src/Database.php';

use Taskboard\Database;

Database::runSchema(__DIR__ . '/storage/schema.sql');

echo "Schema applied.\n";
