<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
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
            $trialEndsAt = ($activeSub['status'] ?? null) === 'trialing' ? $expiresAt : null;

            $status = $this->resolveStatusFromV2($activeSub, $event);
            $plan = $productId ? SubscriptionPlan::where('revenuecat_product_id', $productId)->first() : null;
            $wasPremium = $user->is_premium;
            $isPremium = in_array($status, ['active', 'trial'], true) || $givesAccess;

            $user->update([
                'is_premium' => $isPremium,
                'revenuecat_app_user_id' => $user->revenueCatAppUserId(),
                'trial_ends_at' => $trialEndsAt,
            ]);

            $subscription = Subscription::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'subscription_plan_id' => $plan?->id,
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
                    'trial_ends_at' => $trialEndsAt,
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

        // Handle event-based statuses for terminated states
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
            'trialing' => 'trial',
            'active', 'in_grace_period' => 'active',
            'expired' => 'expired',
            'in_billing_retry' => 'billing_issue',
            'paused' => 'cancelled',
            default => 'none',
        };
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createEntitlement(array $data): array
    {
        $projectId = $this->projectId();

        $response = $this->v2Http()->post('/projects/'.$projectId.'/entitlements', $data);

        if ($response->failed()) {
            Log::error('RevenueCat V2: create entitlement failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException("Failed to create entitlement (HTTP {$response->status()}).");
        }

        return $response->json();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createProduct(array $data): array
    {
        $projectId = $this->projectId();

        $response = $this->v2Http()->post('/projects/'.$projectId.'/products', $data);

        if ($response->failed()) {
            Log::error('RevenueCat V2: create product failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException("Failed to create product (HTTP {$response->status()}).");
        }

        return $response->json();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createOffering(array $data): array
    {
        $projectId = $this->projectId();

        $response = $this->v2Http()->post('/projects/'.$projectId.'/offerings', $data);

        if ($response->failed()) {
            Log::error('RevenueCat V2: create offering failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException("Failed to create offering (HTTP {$response->status()}).");
        }

        return $response->json();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function createPackage(string $offeringId, array $data): array
    {
        $projectId = $this->projectId();

        $response = $this->v2Http()->post('/projects/'.$projectId.'/offerings/'.$offeringId.'/packages', $data);

        if ($response->failed()) {
            Log::error('RevenueCat V2: create package failed.', [
                'offering_id' => $offeringId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException("Failed to create package (HTTP {$response->status()}).");
        }

        return $response->json();
    }

    /**
     * @param  array<string>  $productIds
     * @return array<string, mixed>
     */
    public function attachProductsToEntitlement(string $entitlementId, array $productIds): array
    {
        $projectId = $this->projectId();

        $response = $this->v2Http()->post('/projects/'.$projectId.'/entitlements/'.$entitlementId.'/actions/attach_products', [
            'product_ids' => $productIds,
        ]);

        if ($response->failed()) {
            Log::error('RevenueCat V2: attach products to entitlement failed.', [
                'entitlement_id' => $entitlementId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException("Failed to attach products to entitlement (HTTP {$response->status()}).");
        }

        return $response->json();
    }

    /**
     * @param  array<string>  $productIds
     * @return array<string, mixed>
     */
    public function detachProductsFromEntitlement(string $entitlementId, array $productIds): array
    {
        $projectId = $this->projectId();

        $response = $this->v2Http()->post('/projects/'.$projectId.'/entitlements/'.$entitlementId.'/actions/detach_products', [
            'product_ids' => $productIds,
        ]);

        if ($response->failed()) {
            Log::error('RevenueCat V2: detach products from entitlement failed.', [
                'entitlement_id' => $entitlementId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException("Failed to detach products from entitlement (HTTP {$response->status()}).");
        }

        return $response->json();
    }

    /**
     * @return array<string, mixed>
     */
    public function listEntitlements(): array
    {
        $projectId = $this->projectId();

        $response = $this->v2Http()->get('/projects/'.$projectId.'/entitlements');

        if ($response->failed()) {
            Log::error('RevenueCat V2: list entitlements failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException("Failed to list entitlements (HTTP {$response->status()}).");
        }

        return $response->json()['items'] ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function listProducts(): array
    {
        $projectId = $this->projectId();

        $response = $this->v2Http()->get('/projects/'.$projectId.'/products');

        if ($response->failed()) {
            Log::error('RevenueCat V2: list products failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException("Failed to list products (HTTP {$response->status()}).");
        }

        return $response->json()['items'] ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public function listOfferings(): array
    {
        $projectId = $this->projectId();

        $response = $this->v2Http()->get('/projects/'.$projectId.'/offerings');

        if ($response->failed()) {
            Log::error('RevenueCat V2: list offerings failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException("Failed to list offerings (HTTP {$response->status()}).");
        }

        return $response->json()['items'] ?? [];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function updateEntitlement(string $rcEntitlementId, array $data): array
    {
        $projectId = $this->projectId();

        $response = $this->v2Http()->post('/projects/'.$projectId.'/entitlements/'.$rcEntitlementId, $data);

        if ($response->failed()) {
            Log::error('RevenueCat V2: update entitlement failed.', [
                'entitlement_id' => $rcEntitlementId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException("Failed to update entitlement (HTTP {$response->status()}).");
        }

        return $response->json();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function updateProduct(string $rcProductId, array $data): array
    {
        $projectId = $this->projectId();

        $response = $this->v2Http()->post('/projects/'.$projectId.'/products/'.$rcProductId, $data);

        if ($response->failed()) {
            Log::error('RevenueCat V2: update product failed.', [
                'product_id' => $rcProductId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException("Failed to update product (HTTP {$response->status()}).");
        }

        return $response->json();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function updateOffering(string $rcOfferingId, array $data): array
    {
        $projectId = $this->projectId();

        $response = $this->v2Http()->post('/projects/'.$projectId.'/offerings/'.$rcOfferingId, $data);

        if ($response->failed()) {
            Log::error('RevenueCat V2: update offering failed.', [
                'offering_id' => $rcOfferingId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException("Failed to update offering (HTTP {$response->status()}).");
        }

        return $response->json();
    }

    /**
     * @return array<string, mixed>
     */
    public function archiveEntitlement(string $rcEntitlementId): array
    {
        $projectId = $this->projectId();

        $response = $this->v2Http()->post('/projects/'.$projectId.'/entitlements/'.$rcEntitlementId.'/actions/archive');

        if ($response->failed()) {
            Log::error('RevenueCat V2: archive entitlement failed.', [
                'entitlement_id' => $rcEntitlementId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException("Failed to archive entitlement (HTTP {$response->status()}).");
        }

        return $response->json();
    }

    /**
     * @return array<string, mixed>
     */
    public function unarchiveEntitlement(string $rcEntitlementId): array
    {
        $projectId = $this->projectId();

        $response = $this->v2Http()->post('/projects/'.$projectId.'/entitlements/'.$rcEntitlementId.'/actions/unarchive');

        if ($response->failed()) {
            Log::error('RevenueCat V2: unarchive entitlement failed.', [
                'entitlement_id' => $rcEntitlementId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException("Failed to unarchive entitlement (HTTP {$response->status()}).");
        }

        return $response->json();
    }

    /**
     * @return array<string, mixed>
     */
    public function archiveProduct(string $rcProductId): array
    {
        $projectId = $this->projectId();

        $response = $this->v2Http()->post('/projects/'.$projectId.'/products/'.$rcProductId.'/actions/archive');

        if ($response->failed()) {
            Log::error('RevenueCat V2: archive product failed.', [
                'product_id' => $rcProductId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException("Failed to archive product (HTTP {$response->status()}).");
        }

        return $response->json();
    }

    /**
     * @return array<string, mixed>
     */
    public function unarchiveProduct(string $rcProductId): array
    {
        $projectId = $this->projectId();

        $response = $this->v2Http()->post('/projects/'.$projectId.'/products/'.$rcProductId.'/actions/unarchive');

        if ($response->failed()) {
            Log::error('RevenueCat V2: unarchive product failed.', [
                'product_id' => $rcProductId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException("Failed to unarchive product (HTTP {$response->status()}).");
        }

        return $response->json();
    }

    /**
     * @return array<string, mixed>
     */
    public function archiveOffering(string $rcOfferingId): array
    {
        $projectId = $this->projectId();

        $response = $this->v2Http()->post('/projects/'.$projectId.'/offerings/'.$rcOfferingId.'/actions/archive');

        if ($response->failed()) {
            Log::error('RevenueCat V2: archive offering failed.', [
                'offering_id' => $rcOfferingId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException("Failed to archive offering (HTTP {$response->status()}).");
        }

        return $response->json();
    }

    /**
     * @return array<string, mixed>
     */
    public function unarchiveOffering(string $rcOfferingId): array
    {
        $projectId = $this->projectId();

        $response = $this->v2Http()->post('/projects/'.$projectId.'/offerings/'.$rcOfferingId.'/actions/unarchive');

        if ($response->failed()) {
            Log::error('RevenueCat V2: unarchive offering failed.', [
                'offering_id' => $rcOfferingId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException("Failed to unarchive offering (HTTP {$response->status()}).");
        }

        return $response->json();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findEntitlementByLookupKey(string $lookupKey): ?array
    {
        $entitlements = $this->listEntitlements();

        foreach ($entitlements as $entitlement) {
            if (($entitlement['lookup_key'] ?? null) === $lookupKey) {
                return $entitlement;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findProductByStoreIdentifier(string $storeIdentifier): ?array
    {
        $products = $this->listProducts();

        foreach ($products as $product) {
            if (($product['store_identifier'] ?? null) === $storeIdentifier) {
                return $product;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findOfferingByLookupKey(string $lookupKey): ?array
    {
        $offerings = $this->listOfferings();

        foreach ($offerings as $offering) {
            if (($offering['lookup_key'] ?? null) === $lookupKey) {
                return $offering;
            }
        }

        return null;
    }

    private function sendSubscriptionNotifications(User $user, string $status, bool $wasPremium, ?array $event): void
    {
        $eventType = $event['type'] ?? null;

        if (! $wasPremium && in_array($status, ['active', 'trial'], true)) {
            $status === 'trial'
                ? $this->notificationService->sendTrialStarted($user)
                : $this->notificationService->sendPaymentSucceeded($user);

            $this->notificationService->sendAdminNewSubscription($user);
        }

        if ($wasPremium && ! in_array($status, ['active', 'trial'], true)) {
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
