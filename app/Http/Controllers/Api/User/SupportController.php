<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Support\ContactRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class SupportController extends Controller
{
    public function contact(ContactRequest $request): JsonResponse
    {
        $user = Auth::user();

        Mail::send(
            'emails.support-contact',
            ['user' => $user, 'subject' => $request->subject, 'message' => $request->message],
            fn ($m) => $m->to(config('mail.from.address'))
                ->replyTo($user->email)
                ->subject('Support: '.$request->subject)
        );

        return $this->successResponse('Support message sent successfully.');
    }
}
