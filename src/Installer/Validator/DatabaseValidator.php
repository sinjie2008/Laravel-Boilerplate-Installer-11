<?php

namespace Installer\Validator;

use PDO;
use PDOException;

class DatabaseValidator
{
    public function validate(array $data): array
    {
        if (empty($data['db_host']) || empty($data['db_name']) || empty($data['db_user'])) {
            return [
                'success' => false,
                'message' => 'Please fill in all required database fields.'
            ];
        }

        $dbHost = $data['db_host'];
        $dbPort = !empty($data['db_port']) ? $data['db_port'] : '3306';
        $dbName = $data['db_name'];
        $dbUser = $data['db_user'];
        $dbPassword = $data['db_password'] ?? ''; // Ensure password is a string

        $_SESSION['database'] = [
            'host' => $dbHost,
            'port' => $dbPort,
            'name' => $dbName,
            'user' => $dbUser,
            'password' => $dbPassword,
        ];

        try {
            $dsn = "mysql:host={$dbHost};port={$dbPort}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 5
            ];
            $pdo = new PDO($dsn, $dbUser, $dbPassword, $options);

            // Check if database exists, if not try to create it
            $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = " . $pdo->quote($dbName));
            if ($stmt->rowCount() == 0) {
                $pdo->exec("CREATE DATABASE `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            }
            return ['success' => true];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database connection or creation failed: ' . $e->getMessage()
            ];
        }
    }
}
