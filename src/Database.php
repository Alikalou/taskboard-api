<?php
declare(strict_types=1);

namespace Taskboard;

use PDO;
use PDOException;
use RuntimeException;
// These are buil
//RuntimeException extends the base Exception class, what is interesting that it is used to detect exception dynamically.

//Don't extend this class.
final class Database
{
    private static ?PDO $pdo = null;


    public static function conn(): PDO
    {
        //We expect a pdo object to be returned.
        if (self::$pdo) return self::$pdo;
        //If we already have a pdo object, return it; Don't create multiple PDOs for the same app.

        $path = __DIR__ . '/../storage/database.sqlite';
        $dsn  = 'sqlite:' . $path;
        //A PDO need two things, the path to the database and the driver type.


        try {
            $pdo = new PDO($dsn);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            //Set some attributes for the PDO object.
            //The second line here is setting the way we fitch the data, here we want it to be similar to php arrays.
            //The first line is checking the ERRMODE attribute, if true then throw the exception.
        } catch (PDOException $e) {
            throw new RuntimeException('Database connection failed: ' . $e->getMessage(), 0, $e);
        }

        return self::$pdo = $pdo;
    }

    public static function runSchema(string $file): void
    {
        $sql = @file_get_contents($file);
        if ($sql === false) {
            throw new RuntimeException('Cannot read schema file: ' . $file);
        }
        self::conn()->exec($sql);
    }
}
