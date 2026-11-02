<?php
declare(strict_types=1);

function get_db(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $host    = '';
        $port    = '';
        $dbname  = '';
        $user    = '';
        $pass    = '';

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 5,
        ];

        try {
            $pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            die("SYSTEM ERROR: Unable to load authentication provider. " . $e->getMessage() . "\n");
        }
    }

    return $pdo;
}
?>