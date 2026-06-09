<?php

namespace App\Listeners;

use App\Models\UserDeviceToken;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Support\Arr;
use Kreait\Firebase\Messaging\SendReport;
use NotificationChannels\Fcm\FcmChannel;

class DeleteExpiredFcmToken
{
    public function handle(NotificationFailed $event): void
    {
        if ($event->channel !== FcmChannel::class) {
            return;
        }

        $report = Arr::get($event->data, 'report');

        if (! $report instanceof SendReport) {
            return;
        }

        if (! $report->messageTargetWasInvalid() && ! $report->messageWasSentToUnknownToken()) {
            return;
        }

        UserDeviceToken::where('token', $report->target()->value())->delete();
    }
}
