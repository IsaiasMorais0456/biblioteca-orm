<?php

declare(strict_types=1);

namespace BibliotecaORM\Config;

use PDO;

class Conexao
{
    public static function criar(): PDO
    {
        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $porta = getenv('DB_PORT') ?: '3306';
        $banco = getenv('DB_NAME') ?: 'biblioteca_orm';
        $usuario = getenv('DB_USER') ?: 'root';
        $senha = getenv('DB_PASS') !== false ? (string) getenv('DB_PASS') : 'root';

        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $porta, $banco);

        return new PDO($dsn, $usuario, $senha, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
}