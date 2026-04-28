<?php
declare(strict_types=1);

namespace App\Service;

use App\Core\App;

class AuthService
{
    /**
     * Attempt to login a user
     */
    public function attempt(string $userId, string $password): bool
    {
        $db = App::getInstance()->db();
        $session = App::getInstance()->session();

        // Join with parishes to get the org_cd
        $user = $db->fetch(
            "SELECT u.*, p.org_cd 
             FROM users u
             LEFT JOIN parishes p ON u.parish_id = p.id
             WHERE u.login_id = ?", 
            [$userId]
        );

        // Support both hashed passwords and legacy plain-text
        $isMatch = false;
        if ($user) {
            if (password_verify($password, $user['password_hash'])) {
                $isMatch = true;
            } elseif ($user['password_hash'] === $password) {
                $isMatch = true;
            }
        }

        if ($isMatch) {
            $session->login([
                'strLoginID'   => $user['login_id'],
                'strLoginName' => $user['name'],
                'ctms_admin'   => $user['role'],
                'org_cd'       => $user['org_cd'] ?? ''
            ]);

            // Update last login
            $db->query("UPDATE users SET last_login_at = NOW() WHERE id = ?", [$user['id']]);
            
            return true;
        }

        return false;
    }

    public function logout(): void
    {
        App::getInstance()->session()->destroy();
    }
}
