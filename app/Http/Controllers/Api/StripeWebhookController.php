<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Services\NotificationService;
use Laravel\Cashier\Http\Controllers\WebhookController;
use Symfony\Component\HttpFoundation\Response;

class StripeWebhookController extends WebhookController
{
    public function __construct(private readonly NotificationService $notificationService)
    {
        parent::__construct();
    }

    public function handleInvoicePaymentSucceeded(array $payload): Response
    {
        $user = $this->getUserByStripeId($payload['data']['object']['customer'] ?? null);

        if ($user) {
            $user->update(['is_premium' => true]);
            $this->notificationService->sendPaymentSucceeded($user);
            $this->notificationService->sendAdminNewSubscription($user);
        }

        return $this->successMethod();
    }

    public function handleInvoicePaymentFailed(array $payload): Response
    {
        $user = $this->getUserByStripeId($payload['data']['object']['customer'] ?? null);

        if ($user) {
            $user->update(['is_premium' => false]);
            $this->notificationService->sendPaymentFailed($user);
            $this->notificationService->sendAdminPaymentFailed($user);
        }

        return $this->successMethod();
    }

    public function handleCustomerSubscriptionDeleted(array $payload): Response
    {
        $user = $this->getUserByStripeId($payload['data']['object']['customer'] ?? null);

        if ($user) {
            $user->update(['is_premium' => false]);
            $this->notificationService->sendSubscriptionCancelled($user);
        }

        return parent::handleCustomerSubscriptionDeleted($payload);
    }

    public function handleCustomerSubscriptionUpdated(array $payload): Response
    {
        $user = $this->getUserByStripeId($payload['data']['object']['customer'] ?? null);

        if ($user) {
            $stripeStatus = $payload['data']['object']['status'] ?? null;
            $isPremium = in_array($stripeStatus, ['trialing', 'active']);
            $user->update(['is_premium' => $isPremium]);
        }

        return parent::handleCustomerSubscriptionUpdated($payload);
    }

    protected function getUserByStripeId($stripeId)
    {
        if (! $stripeId) {
            return null;
        }

        return User::where('stripe_id', $stripeId)->first();
    }
}
