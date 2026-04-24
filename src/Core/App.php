<?php
declare(strict_types=1);

namespace App\Core;

class App
{
    private static ?self $instance = null;
    private array $services = [];
    private array $config = [];

    private function __construct()
    {
        $this->config = require __DIR__ . '/../../config/config.php';
        
        // Register core services
        $this->services['session'] = new Session();
        
        $dbConfig = $this->config['db'];
        $this->services['db'] = new Database(
            $dbConfig['host'],
            $dbConfig['name'],
            $dbConfig['user'],
            $dbConfig['pass'],
            $dbConfig['port'],
            $dbConfig['charset']
        );
    }

    public function getBasePath(): string
    {
        return rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/';
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function get(string $name): mixed
    {
        return $this->services[$name] ?? null;
    }

    public function db(): Database
    {
        return $this->get('db');
    }

    public function session(): Session
    {
        return $this->get('session');
    }

    public function config(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $value = $this->config;
        
        foreach ($keys as $k) {
            if (!isset($value[$k])) return $default;
            $value = $value[$k];
        }
        
        return $value;
    }
}
