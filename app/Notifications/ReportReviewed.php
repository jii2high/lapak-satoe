<?php

namespace App\Notifications;

use App\Enums\ReportStatus;
use App\Models\Report;
use Illuminate\Notifications\Notification;

class ReportReviewed extends Notification
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
        $label = $this->report->targetLabel();

        return [
            'type' => 'report_result',
            'status' => $this->report->status->value,
            'message' => $this->report->status === ReportStatus::Processed
                ? "Laporanmu atas {$label} sedang diproses admin."
                : "Laporanmu atas {$label} ditolak: {$this->report->reporter_note}",
        ];
    }
}
