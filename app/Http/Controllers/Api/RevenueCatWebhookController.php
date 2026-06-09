<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\RevenueCatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RevenueCatWebhookController extends Controller
{
    public function __construct(private readonly RevenueCatService $revenueCatService) {}

    public function handle(Request $request): JsonResponse
    {
        $expectedAuthorization = config('revenuecat.webhook_authorization');

        if ($expectedAuthorization && $request->header('Authorization') !== $expectedAuthorization) {
            Log::warning('RevenueCat webhook: unauthorized request.', [
                'ip' => $request->ip(),
            ]);

            return response()->json(null, 401);
        }

        $event = $request->input('event', []);
        $eventId = $event['id'] ?? null;
        $appUserId = $event['app_user_id'] ?? null;

        if (! $eventId || ! $appUserId) {
            return response()->json(null, 422);
        }

        $alreadyProcessed = DB::table('revenuecat_webhook_events')
            ->where('event_id', $eventId)
            ->whereNotNull('processed_at')
            ->exists();

        if ($alreadyProcessed) {
            return response()->json(null);
        }

        DB::table('revenuecat_webhook_events')->updateOrInsert(
            ['event_id' => $eventId],
            [
                'event_type' => $event['type'] ?? null,
                'app_user_id' => $appUserId,
                'payload' => json_encode($request->all()),
                'updated_at' => now(),
            ]
        );

        DB::table('revenuecat_webhook_events')
            ->where('event_id', $eventId)
            ->whereNull('created_at')
            ->update(['created_at' => now()]);

        // Match webhook by users.id only. We no longer support a separate
        // revenuecat_app_user_id column; clients should pass the numeric
        // user id (string) as app_user_id from the SDK.
        $user = User::where('id', $appUserId)->first();

        if (! $user) {
            Log::info('RevenueCat webhook: user not found, skipping.', [
                'event_id' => $eventId,
                'event_type' => $event['type'] ?? null,
                'app_user_id' => $appUserId,
            ]);

            return response()->json(null);
        }

        try {
            $customerInfo = $this->revenueCatService->getSubscriber($appUserId);
            $this->revenueCatService->syncUser($user, $customerInfo, $event);

            DB::table('revenuecat_webhook_events')
                ->where('event_id', $eventId)
                ->update(['processed_at' => now(), 'updated_at' => now()]);

            Log::info('RevenueCat webhook: processed successfully.', [
                'event_id' => $eventId,
                'event_type' => $event['type'] ?? null,
                'user_id' => $user->id,
            ]);

            return response()->json(null);
        } catch (\Throwable $e) {
            Log::error('RevenueCat webhook: processing failed.', [
                'event_id' => $eventId,
                'event_type' => $event['type'] ?? null,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            // Return a 5xx so RevenueCat retries the delivery. The event is not
            // marked processed, and the idempotency guard above prevents
            // duplicate processing on the retry.
            return response()->json(null, 500);
        }
    }
}
