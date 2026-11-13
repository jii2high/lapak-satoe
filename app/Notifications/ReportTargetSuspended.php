<?php

namespace App\Notifications;

use App\Models\Report;
use Illuminate\Notifications\Notification;

class ReportTargetSuspended extends Notification
{
    public function __construct(private readonly Report $report) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'report_suspended',
            'message' => "{$this->report->targetLabel()} dinonaktifkan admin: {$this->report->reported_note}",
            'url' => $this->report->product_id !== null ? '/seller/products' : '/seller/store',
        ];
    }
}
