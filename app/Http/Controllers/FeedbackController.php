<?php

namespace App\Http\Controllers;

use App\Exceptions\TicketingClientException;
use App\Exceptions\TicketingNotConfiguredException;
use App\Exceptions\TicketingUnavailableException;
use App\Http\Requests\Feedback\StoreFeedbackRequest;
use App\Services\TicketingClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class FeedbackController extends Controller
{
    private const IDEMPOTENCY_KEY_PATTERN = '/^[A-Za-z0-9_\-]{8,128}$/';

    public function __construct(private readonly TicketingClient $ticketing) {}

    public function store(StoreFeedbackRequest $request): JsonResponse
    {
        $idempotencyKey = $request->header('Idempotency-Key');

        if (! is_string($idempotencyKey) || preg_match(self::IDEMPOTENCY_KEY_PATTERN, $idempotencyKey) !== 1) {
            return response()->json([
                'message' => 'Invalid idempotency key.',
                'errors' => ['idempotency_key' => ['The Idempotency-Key header must be 8–128 alphanumerics, underscores, or hyphens.']],
            ], 422);
        }

        $data = $request->validated();
        $user = $request->user()->loadMissing('organisation');

        $payload = [
            'subject' => $data['subject'],
            'description' => $data['description'],
            'submitter_email' => $user->email,
            'submitter_name' => $user->name,
            'metadata' => [
                'category' => $data['category'],
                'app_version' => config('app.version'),
                'organisation_id' => $user->organisation_id,
                'organisation_name' => $user->organisation?->name,
                'page' => $data['page'],
                'user_agent' => substr((string) $request->userAgent(), 0, 500),
            ],
        ];

        $logContext = [
            'user_id' => $user->id,
            'organisation_id' => $user->organisation_id,
            'idempotency_key' => $idempotencyKey,
            'category' => $data['category'],
        ];

        try {
            $this->ticketing->createTicket($payload, $idempotencyKey);
        } catch (TicketingNotConfiguredException $e) {
            Log::channel('feedback')->error('not_configured', $logContext + ['exception' => $e->getMessage()]);

            return response()->json([
                'message' => 'Feedback service is temporarily unavailable. Please try again.',
            ], 503);
        } catch (TicketingUnavailableException $e) {
            Log::channel('feedback')->error('upstream_error', $logContext + ['exception' => $e->getMessage()]);

            return response()->json([
                'message' => 'Feedback service is temporarily unavailable. Please try again.',
            ], 503);
        } catch (TicketingClientException $e) {
            Log::channel('feedback')->error('upstream_rejected', $logContext + [
                'upstream_status' => $e->upstreamStatus,
                'upstream_body' => $e->upstreamBody,
            ]);

            return response()->json([
                'message' => "Feedback couldn't be submitted.",
            ], 502);
        }

        Log::channel('feedback')->info('submitted', $logContext);

        return response()->json(null, 201);
    }
}
