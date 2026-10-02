<?php

namespace App\Services;

use App\Models\NotificationModel;
use App\Models\UserModel;

/**
 * In-app notifications. Links are stored as site-relative paths.
 */
class Notifier
{
    public static function notify(int $userId, string $type, string $title, string $message, ?string $link = null): void
    {
        model(NotificationModel::class)->insert([
            'user_id'    => $userId,
            'type'       => $type,
            'title'      => mb_substr($title, 0, 150),
            'message'    => mb_substr($message, 0, 500),
            'link'       => $link,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function notifyAdmins(string $type, string $title, string $message, ?string $link = null): void
    {
        $admins = model(UserModel::class)->where('role', 'admin')->where('is_active', 1)->findColumn('id') ?? [];
        foreach ($admins as $adminId) {
            self::notify((int) $adminId, $type, $title, $message, $link);
        }
    }
}
