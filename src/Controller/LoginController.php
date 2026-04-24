<?php
declare(strict_types=1);

namespace App\Controller;

use App\Core\App;
use App\Service\AuthService;

class LoginController
{
    private AuthService $auth;

    public function __construct()
    {
        $this->auth = new AuthService();
    }

    public function show(): void
    {
        $base = App::getInstance()->getBasePath();
        if (App::getInstance()->session()->isLoggedIn()) {
            header("Location: {$base}index.php?page=dashboard");
            exit;
        }

        require __DIR__ . '/../../views/pages/login.php';
    }

    public function login(): void
    {
        $userId = $_POST['userId'] ?? '';
        $password = $_POST['password'] ?? '';

        $base = App::getInstance()->getBasePath();
        if ($this->auth->attempt($userId, $password)) {
            header("Location: {$base}index.php?page=dashboard");
        } else {
            header("Location: {$base}index.php?page=login&error=invalid");
        }
        exit;
    }

    public function logout(): void
    {
        $this->auth->logout();
        $base = App::getInstance()->getBasePath();
        header("Location: {$base}index.php?page=login");
        exit;
    }
}
