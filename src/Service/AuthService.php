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

        // 1. Check office/daerigoo (from MPLUS_MEMBER_LIST with strAdmin check)
        // Legacy logic: if it's office/daerigoo, they might be in MPLUS_MEMBER_LIST
        $user = $db->fetch(
            "SELECT strLoginID, strLoginPwd, strLoginName, strAdmin, bcode 
             FROM MPLUS_MEMBER_LIST 
             WHERE strLoginID = ? AND bitDelete = 0", 
            [$userId]
        );

        if ($user && $user['strLoginPwd'] === $password) {
            $roleMap = ['1' => 'office', '2' => 'daerigoo']; // Simple mapping for now
            $role = $roleMap[$user['strAdmin']] ?? 'bondang';
            
            $session->login([
                'strLoginID'   => $user['strLoginID'],
                'strLoginName' => $user['strLoginName'],
                'ctms_admin'   => $role,
                'bcode'        => $user['bcode'] ?? ''
            ]);
            return true;
        }

        // 2. Check bondang (from ctms_user_info)
        $bondang = $db->fetch(
            "SELECT ctms_uid, ctms_upwd, ctms_uname, ctms_ucode 
             FROM ctms_user_info 
             WHERE ctms_uid = ?", 
            [$userId]
        );

        if ($bondang && $bondang['ctms_upwd'] === $password) {
            $session->login([
                'strLoginID'   => $bondang['ctms_uid'],
                'strLoginName' => $bondang['ctms_uname'],
                'ctms_admin'   => 'bondang',
                'bcode'        => $bondang['ctms_ucode'] ?? ''
            ]);
            return true;
        }

        return false;
    }

    public function logout(): void
    {
        App::getInstance()->session()->destroy();
    }
}
