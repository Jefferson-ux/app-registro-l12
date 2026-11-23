<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PDO;
use Symfony\Component\Process\Process;
use Throwable;

class TestMysqlCommand extends Command
{
    protected $signature = 'test:mysql
        {--database=app_test : Base de datos dedicada para las pruebas}
        {--filter= : Filtro de PHPUnit/Pest}
        {--testsuite= : Suite de pruebas a ejecutar}
        {--group= : Grupo de pruebas}
        {--exclude-group= : Grupo de pruebas a excluir}
        {--drop : Eliminar la base de datos de pruebas al finalizar}
        {--force : Permitir un nombre de base de datos sin el sufijo _test}';

    protected $description = 'Ejecuta la suite de pruebas contra MySQL en una base de datos dedicada.';

    public function handle(): int
    {
        $target = (string) $this->option('database');
        $development = (string) config('database.connections.mysql.database');

        if (! preg_match('/^[A-Za-z0-9_]+$/', $target)) {
            $this->error('El nombre de la base de datos solo puede contener letras, números y guiones bajos.');

            return self::FAILURE;
        }

        if ($target === $development) {
            $this->error("La base de datos '{$target}' es la de desarrollo. Usa una base dedicada, por ejemplo app_test.");

            return self::FAILURE;
        }

        if (! str_ends_with($target, '_test') && ! $this->option('force')) {
            $this->error('Por seguridad, el nombre debe terminar en _test. Usa --force si estás seguro.');

            return self::FAILURE;
        }

        $connection = config('database.connections.mysql');
        $host = (string) ($connection['host'] ?? '127.0.0.1');
        $port = (string) ($connection['port'] ?? '3306');
        $username = (string) ($connection['username'] ?? 'root');
        $password = (string) ($connection['password'] ?? '');
        $charset = (string) ($connection['charset'] ?? 'utf8mb4');
        $collation = (string) ($connection['collation'] ?? 'utf8mb4_unicode_ci');

        $dsn = ! empty($connection['unix_socket'])
            ? "mysql:unix_socket={$connection['unix_socket']}"
            : "mysql:host={$host};port={$port}";

        try {
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
        } catch (Throwable $exception) {
            $this->error('No se pudo conectar a MySQL: ' . $exception->getMessage());
            $this->line('Verifica que MySQL/MariaDB esté iniciado.');

            return self::FAILURE;
        }

        try {
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$target}` CHARACTER SET {$charset} COLLATE {$collation}");
        } catch (Throwable $exception) {
            $this->error("No se pudo crear la base de datos '{$target}': " . $exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Ejecutando pruebas contra MySQL en la base de datos '{$target}'...");

        $command = [
            PHP_BINARY,
            base_path('artisan'),
            'test',
        ];

        foreach (['filter', 'testsuite', 'group', 'exclude-group'] as $option) {
            $value = $this->option($option);

            if ($value !== null && $value !== '') {
                $command[] = "--{$option}={$value}";
            }
        }

        $environment = [
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $host,
            'DB_PORT' => $port,
            'DB_DATABASE' => $target,
            'DB_USERNAME' => $username,
            'DB_PASSWORD' => $password,
        ];

        $process = new Process($command, base_path(), $environment, null, null);
        $process->run(function (string $type, string $buffer): void {
            $this->output->write($buffer);
        });

        if ($this->option('drop')) {
            try {
                $pdo->exec("DROP DATABASE IF EXISTS `{$target}`");
                $this->info("Base de datos '{$target}' eliminada.");
            } catch (Throwable $exception) {
                $this->warn("No se pudo eliminar la base de datos '{$target}': " . $exception->getMessage());
            }
        }

        return $process->isSuccessful() ? self::SUCCESS : self::FAILURE;
    }
}
