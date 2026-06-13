<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Support\ContactRequest;
use App\Notifications\SupportContactNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Notification;

class SupportController extends Controller
{
    public function contact(ContactRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        Notification::route('mail', config('mail.from.address'))
            ->notify(new SupportContactNotification(
                user: $user,
                subject: $validated['subject'],
                supportMessage: $validated['message'],
            ));

        return $this->successResponse('Support message sent successfully.');
    }
}
