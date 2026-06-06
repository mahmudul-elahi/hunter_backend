<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RevenueCatService
{
    public function __construct(private readonly NotificationService $notificationService) {}

    private function v2Http(): PendingRequest
    {
        $apiKey = config('revenuecat.secret_api_key');

        if (! $apiKey) {
            throw new \RuntimeException('RevenueCat secret API key is not configured.');
        }

        return Http::baseUrl(config('revenuecat.api_base_url'))
            ->withToken($apiKey)
            ->acceptJson()
            ->timeout(15)
            ->retry(3, 500, throw: false);
    }

    private function projectId(): string
    {
        $id = config('revenuecat.project_id');

        if (! $id) {
            throw new \RuntimeException('RevenueCat project_id is not configured.');
        }

        return $id;
    }

    /**
     * @return array<string, mixed>
     */
    public function getSubscriber(string $appUserId): array
    {
        $projectId = $this->projectId();

        $customerResponse = $this->v2Http()->get('/projects/'.$projectId.'/customers/'.rawurlencode($appUserId));

        if ($customerResponse->failed()) {
            Log::error('RevenueCat V2: customer lookup failed.', [
                'app_user_id' => $appUserId,
                'status' => $customerResponse->status(),
                'body' => $customerResponse->body(),
            ]);

            throw new \RuntimeException("RevenueCat customer lookup failed (HTTP {$customerResponse->status()}).");
        }

        $customerInfo = $customerResponse->json();

        $subsResponse = $this->v2Http()->get('/projects/'.$projectId.'/customers/'.rawurlencode($appUserId).'/subscriptions');

        if ($subsResponse->successful()) {
            $customerInfo['subscriptions'] = $subsResponse->json()['items'] ?? [];
        }

        $entsResponse = $this->v2Http()->get('/projects/'.$projectId.'/customers/'.rawurlencode($appUserId).'/active-entitlements');

        if ($entsResponse->successful()) {
            $customerInfo['active_entitlements'] = $entsResponse->json()['items'] ?? [];
        }

        return $customerInfo;
    }

    /**
     * @param  array<string, mixed>  $customerInfo
     */
    public function syncUser(User $user, array $customerInfo, ?array $event = null): Subscription
    {
        return DB::transaction(function () use ($user, $customerInfo, $event): Subscription {
            $entitlementId = config('revenuecat.premium_entitlement_id');
            $subscriptions = $customerInfo['subscriptions'] ?? [];
            $eventProductId = $event['product_id'] ?? null;

            $activeSub = $this->resolveActiveSubscription($subscriptions, $eventProductId);

            $productId = $activeSub['product_id'] ?? $eventProductId;
            $store = $activeSub['store'] ?? $event['store'] ?? null;
            $environment = $activeSub['environment'] ?? $event['environment'] ?? null;
            $givesAccess = $activeSub['gives_access'] ?? false;

            $expiresAt = $this->parseTimestampMs($activeSub['current_period_ends_at'] ?? null);
            $startsAt = $this->parseTimestampMs($activeSub['starts_at'] ?? null);

            $status = $this->resolveStatusFromV2($activeSub, $event);
            $wasPremium = $user->is_premium;
            $isPremium = $status === 'active' || $givesAccess;

            $user->update([
                'is_premium' => $isPremium,
                'revenuecat_app_user_id' => $user->revenueCatAppUserId(),
            ]);

            $subscription = Subscription::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'revenuecat_app_user_id' => $user->revenueCatAppUserId(),
                    'revenuecat_original_app_user_id' => $customerInfo['id'] ?? null,
                    'revenuecat_product_id' => $productId,
                    'revenuecat_entitlement_id' => $entitlementId,
                    'store' => $store,
                    'environment' => $environment,
                    'status' => $status,
                    'price' => $event['price'] ?? $event['price_in_purchased_currency'] ?? null,
                    'currency' => $event['currency'] ?? null,
                    'purchased_at' => $startsAt
                        ?? $this->parseMilliseconds($event['purchased_at_ms'] ?? null),
                    'expires_at' => $expiresAt,
                    'cancelled_at' => $this->parseTimestampMs($activeSub['pending_changes']['cancelled_at'] ?? null)
                        ?? $this->parseDate($event['unsubscribe_detected_at'] ?? null),
                    'billing_issue_at' => ($activeSub['status'] ?? null) === 'in_billing_retry'
                        ? now()
                        : $this->parseDate($event['billing_issues_detected_at'] ?? null),
                    'raw_customer_info' => $customerInfo,
                    'last_event_id' => $event['id'] ?? null,
                ]
            );

            Log::info('RevenueCat V2: user synced.', [
                'user_id' => $user->id,
                'status' => $status,
                'product_id' => $productId,
                'was_premium' => $wasPremium,
                'is_premium' => $isPremium,
            ]);

            $this->sendSubscriptionNotifications($user, $status, $wasPremium, $event);

            return $subscription;
        });
    }

    /**
     * @param  array<int, mixed>  $subscriptions
     * @return array<string, mixed>
     */
    private function resolveActiveSubscription(array $subscriptions, ?string $eventProductId): array
    {
        foreach ($subscriptions as $sub) {
            if ($sub['gives_access'] ?? false) {
                return $sub;
            }
        }

        foreach ($subscriptions as $sub) {
            if (in_array($sub['status'] ?? '', ['active', 'trialing', 'in_grace_period'], true)) {
                return $sub;
            }
        }

        if ($eventProductId) {
            foreach ($subscriptions as $sub) {
                if (($sub['product_id'] ?? null) === $eventProductId) {
                    return $sub;
                }
            }
        }

        if (! empty($subscriptions)) {
            return $subscriptions[0];
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $subscriptionData
     * @param  array<string, mixed>|null  $event
     */
    private function resolveStatusFromV2(array $subscriptionData, ?array $event): string
    {
        $eventType = $event['type'] ?? null;

        if (in_array($eventType, ['CANCELLATION', 'EXPIRATION', 'PRODUCT_CHANGE', 'REFUND', 'UNCANCELLATION'], true)) {
            $status = $subscriptionData['status'] ?? null;

            if ($status === null || in_array($status, ['expired', 'none'], true)) {
                return match ($eventType) {
                    'REFUND' => 'refunded',
                    'CANCELLATION' => 'cancelled',
                    default => 'expired',
                };
            }
        }

        $v2Status = $subscriptionData['status'] ?? null;

        return match ($v2Status) {
            'trialing', 'active', 'in_grace_period' => 'active',
            'expired' => 'expired',
            'in_billing_retry' => 'billing_issue',
            'paused' => 'cancelled',
            default => 'none',
        };
    }

    private function sendSubscriptionNotifications(User $user, string $status, bool $wasPremium, ?array $event): void
    {
        $eventType = $event['type'] ?? null;

        if (! $wasPremium && $status === 'active') {
            $this->notificationService->sendPaymentSucceeded($user);
            $this->notificationService->sendAdminNewSubscription($user);
        }

        if ($wasPremium && $status !== 'active') {
            $eventType === 'BILLING_ISSUE'
                ? $this->notificationService->sendPaymentFailed($user)
                : $this->notificationService->sendSubscriptionCancelled($user);
        }
    }

    private function parseDate(?string $value): ?Carbon
    {
        return $value ? Carbon::parse($value) : null;
    }

    private function parseMilliseconds(mixed $value): ?Carbon
    {
        return $value ? Carbon::createFromTimestampMs((int) $value) : null;
    }

    private function parseTimestampMs(mixed $value): ?Carbon
    {
        return $value ? Carbon::createFromTimestampMs((int) $value) : null;
    }
}
