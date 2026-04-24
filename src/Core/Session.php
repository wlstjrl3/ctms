<?php
declare(strict_types=1);

namespace App\Core;

class Session
{
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Set a session variable
     */
    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Get a session variable
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Remove a session variable
     */
    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * Destroy the session
     */
    public function destroy(): void
    {
        session_destroy();
        $_SESSION = [];
    }

    /**
     * Authentication methods specifically for CTMS
     */
    
    public function login(array $userData): void
    {
        $this->set('is_logged_in', true);
        $this->set('user_id', $userData['strLoginID']);
        $this->set('user_name', $userData['strLoginName']);
        $this->set('role', $userData['ctms_admin']); // Legacy: office, daerigoo, bondang
    }

    public function isLoggedIn(): bool
    {
        return (bool)$this->get('is_logged_in', false);
    }

    public function getRole(): ?string
    {
        return $this->get('role');
    }

    /**
     * Check if user has required role (office > daerigoo > bondang)
     */
    public function hasPermission(string $requiredRole): bool
    {
        $roles = ['bondang' => 1, 'daerigoo' => 2, 'office' => 3];
        $currentRole = $this->getRole();

        if (!$currentRole || !isset($roles[$currentRole])) {
            return false;
        }

        return $roles[$currentRole] >= ($roles[$requiredRole] ?? 99);
    }
}
