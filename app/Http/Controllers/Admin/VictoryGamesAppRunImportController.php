<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\VictoryGamesAppRunImportJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VictoryGamesAppRunImportController extends Controller
{
    /**
     * Accept a single-session export from AIUXTester and queue it for import.
     *
     * Expected JSON payload:
     * {
     *   "external_session_id": "...",
     *   "external_user_id": "...",
     *   "user_email": "...",
     *   "app_name": "My App",          // optional — defaults to hostname of start_url
     *   "app_description": "...",      // optional
     *   "exported_at": "...",
     *   "session": { goal, start_url, mode, provider, model, status, external_id },
     *   "postmortem": { run_analysis, html_analysis, recommendations },
     *   "steps": [ { step_number, action_type, action_params, intent, reasoning,
     *                action_result, success, error_message, page_url,
     *                screenshot_b64, timestamp } ]
     * }
     */
    public function store(Request $request)
    {
        $this->authorizeRequest($request);

        $json = $request->getContent();
        if (!$json) {
            return response()->json(['error' => 'Empty request body'], 422);
        }

        $payload = json_decode($json, true);
        if (!$payload || !isset($payload['session'])) {
            return response()->json(['error' => 'Invalid payload — missing session key'], 422);
        }

        $filename = 'victory-games-app-runs/' . now()->format('Y-m-d_His') . '_' . uniqid() . '.json';
        Storage::disk('local')->put($filename, $json);

        VictoryGamesAppRunImportJob::dispatch($filename);

        return response()->json([
            'ok'      => true,
            'queued'  => true,
            'session' => $payload['session']['external_id'] ?? null,
            'user'    => $payload['user_email'] ?? $payload['external_user_id'] ?? null,
        ]);
    }

    private function authorizeRequest(Request $request): void
    {
        $token = config('app.admin_import_token');
        if ($token) {
            $bearer = $request->bearerToken();

            if (!$bearer && function_exists('apache_request_headers')) {
                $authHeader = apache_request_headers()['Authorization'] ?? '';
                if (str_starts_with($authHeader, 'Bearer ')) {
                    $bearer = substr($authHeader, 7);
                }
            }

            if ($bearer && hash_equals($token, $bearer)) {
                return;
            }
        }

        $user = $request->user();
        if ($user && $user->is_admin) {
            return;
        }

        abort(401, 'Unauthorized.');
    }
}
