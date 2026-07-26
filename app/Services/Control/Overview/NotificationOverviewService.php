<?php

namespace App\Services\Control\Overview;

use App\Models\SystemNotification;

class NotificationOverviewService extends BaseOverviewService
{
    public function getNotifications(): array
    {
        return $this->execute('overview:notifications', CacheTiers::NEAR_REAL_TIME, function () {
            $notifications = SystemNotification::latest()
                ->take(5)
                ->get()
                ->map(function ($notif) {
                    return [
                        'id' => $notif->id,
                        'title' => $notif->title,
                        'time' => $notif->created_at->diffForHumans(),
                        'unread' => !$notif->is_read,
                    ];
                })->toArray();
                
            return $notifications;
        });
    }
}
