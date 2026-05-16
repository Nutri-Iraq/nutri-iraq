<?php
// app/Services/NotificationService.php
namespace App\Services;

use App\Models\Notification;
use App\Models\Member;
use App\Models\Subscription;

class NotificationService
{
    public function create(
        string  $type,
        string  $priority,
        string  $title,
        string  $body,
        ?string $userId   = null,
        ?string $memberId = null,
    ): Notification {
        return Notification::create(compact('type', 'priority', 'title', 'body', 'userId', 'memberId'));
    }

    public function checkExpiringSubscriptions(): void
    {
        Subscription::expiringSoon(3)
            ->with('member')
            ->get()
            ->each(function (Subscription $sub) {
                $days    = $sub->days_remaining;
                $name    = $sub->member->full_name;
                $already = Notification::where('member_id', $sub->member_id)
                    ->where('type', 'subscription')
                    ->whereDate('created_at', today())
                    ->exists();

                if (!$already) {
                    $this->create(
                        type:     'subscription',
                        priority: $days === 0 ? 'urgent' : 'normal',
                        title:    "اشتراك ينتهي — {$name}",
                        body:     $days === 0
                            ? "انتهى اشتراك {$name} اليوم."
                            : "اشتراك {$name} ينتهي خلال {$days} " . ($days === 1 ? 'يوم' : 'أيام') . '.',
                        memberId: $sub->member_id,
                    );
                }
            });
    }

    public function checkMissingWeights(): void
    {
        Member::where('is_active', true)
            ->whereDoesntHave('measurements', fn($q) =>
                $q->where('measured_at', '>=', now()->subDays(7))
            )
            ->each(function (Member $member) {
                $this->create(
                    type:     'weight',
                    priority: 'normal',
                    title:    "لم يُسجَّل الوزن — {$member->full_name}",
                    body:     "مرت 7 أيام دون تسجيل وزن {$member->full_name}.",
                    memberId: $member->id,
                );
            });
    }

    public function checkNonCompliance(): void
    {
        Member::where('is_active', true)
            ->whereHas('dailyTracking', fn($q) =>
                $q->where('diet_followed', false)
                  ->where('tracking_date', '>=', now()->subDays(2))
            )
            ->each(function (Member $member) {
                $this->create(
                    type:     'compliance',
                    priority: 'normal',
                    title:    "عدم التزام — {$member->full_name}",
                    body:     "لم يلتزم {$member->full_name} بالنظام الغذائي خلال آخر يومين.",
                    memberId: $member->id,
                );
            });
    }
}
