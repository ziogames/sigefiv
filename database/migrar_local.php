<?php

use PDO;
use PDOException;

echo "========================================\n";
echo " MIGRACION SIGEFIV: SQLite -> PostgreSQL LOCAL\n";
echo "========================================\n\n";

$pgHost = 'host.docker.internal';
$pgPort = '5432';
$pgDatabase = 'sigefiv';
$pgUsername = 'postgres';
$pgPassword = getenv('LOCAL_DB_PASSWORD');

if (!$pgPassword) {
    die("ERROR: Falta la variable LOCAL_DB_PASSWORD\n");
}

$sqliteFile = __DIR__ . '/database.sqlite';

if (!file_exists($sqliteFile)) {
    die("ERROR: No existe database/database.sqlite\n");
}

try {

    /*
    |--------------------------------------------------------------------------
    | CONEXION SQLITE
    |--------------------------------------------------------------------------
    */

    $sqlite = new PDO(
        'sqlite:' . $sqliteFile,
        null,
        null,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    /*
    |--------------------------------------------------------------------------
    | CONEXION POSTGRESQL LOCAL
    |--------------------------------------------------------------------------
    */

    $pgsql = new PDO(
        "pgsql:host={$pgHost};port={$pgPort};dbname={$pgDatabase}",
        $pgUsername,
        $pgPassword,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    echo "SQLite conectado correctamente.\n";
    echo "PostgreSQL local conectado correctamente.\n\n";

    /*
    |--------------------------------------------------------------------------
    | TABLAS A MIGRAR
    |--------------------------------------------------------------------------
    */

    $tables = [
        'users',
        'permissions',
        'roles',
        'model_has_permissions',
        'model_has_roles',
        'role_has_permissions',
        'configuracions',
        'categorias',
        'periodos',
        'movimientos',
        'bitacoras',
        'tareas',
        'password_reset_tokens',
        'cache',
        'cache_locks',
        'jobs',
        'job_batches',
        'failed_jobs',
        'sessions',
    ];

    /*
    |--------------------------------------------------------------------------
    | MIGRACION
    |--------------------------------------------------------------------------
    */

    foreach ($tables as $table) {

        $check = $sqlite->prepare("
            SELECT name
            FROM sqlite_master
            WHERE type = 'table'
            AND name = ?
        ");

        $check->execute([$table]);

        if (!$check->fetch()) {
            echo "Omitiendo {$table}: no existe en SQLite.\n";
            continue;
        }

        $rows = $sqlite
            ->query("SELECT * FROM \"{$table}\"")
            ->fetchAll();

        echo "Procesando: {$table} ... " .
            count($rows) .
            " registros\n";

        /*
        |--------------------------------------------------------------------------
        | LIMPIAR TABLA DESTINO
        |--------------------------------------------------------------------------
        */

        $pgsql->exec(
            "TRUNCATE TABLE \"{$table}\" RESTART IDENTITY CASCADE"
        );

        if (count($rows) === 0) {
            continue;
        }

        /*
        |--------------------------------------------------------------------------
        | COLUMNAS
        |--------------------------------------------------------------------------
        */

        $columns = array_keys($rows[0]);

        $columnList = implode(
            ', ',
            array_map(
                fn($column) =>
                    '"' . $column . '"',
                $columns
            )
        );

        $placeholders = implode(
            ', ',
            array_fill(
                0,
                count($columns),
                '?'
            )
        );

        $sql = "
            INSERT INTO \"{$table}\"
            ({$columnList})
            VALUES ({$placeholders})
        ";

        $insert = $pgsql->prepare($sql);

        /*
        |--------------------------------------------------------------------------
        | INSERTAR
        |--------------------------------------------------------------------------
        */

        foreach ($rows as $row) {

            $values = [];

            foreach ($columns as $column) {
                $values[] = $row[$column];
            }

            $insert->execute($values);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | ACTUALIZAR SECUENCIAS
    |--------------------------------------------------------------------------
    */

    echo "\nActualizando secuencias...\n";

    $sequenceTables = [
        'users',
        'permissions',
        'roles',
        'configuracions',
        'categorias',
        'periodos',
        'movimientos',
        'bitacoras',
        'tareas',
    ];

    foreach ($sequenceTables as $table) {

        $exists = $pgsql->prepare("
            SELECT EXISTS (
                SELECT 1
                FROM information_schema.columns
                WHERE table_name = ?
                AND column_name = 'id'
            )
        ");

        $exists->execute([$table]);

        if (!$exists->fetchColumn()) {
            continue;
        }

        $max = $pgsql
            ->query(
                "SELECT COALESCE(MAX(id), 0)
                 FROM \"{$table}\""
            )
            ->fetchColumn();

        $sequence = "{$table}_id_seq";

        try {

            $pgsql->exec(
                "SELECT setval(
                    '{$sequence}',
                    {$max},
                    " .
                ($max > 0 ? 'true' : 'false') .
                "
                )"
            );

            echo
                "Secuencia {$table}.id -> {$max}\n";

        } catch (PDOException $e) {

            echo
                "Aviso: no se pudo actualizar {$sequence}\n";
        }
    }

    echo "\n========================================\n";
    echo " MIGRACION COMPLETADA CORRECTAMENTE\n";
    echo "========================================\n";

} catch (PDOException $e) {

    echo "\n========================================\n";
    echo " ERROR EN LA MIGRACION\n";
    echo "========================================\n";

    echo $e->getMessage() . "\n";

    exit(1);
}