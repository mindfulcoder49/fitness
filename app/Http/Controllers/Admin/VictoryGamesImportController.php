<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\VictoryGamesImportJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VictoryGamesImportController extends Controller
{
    /**
     * Accept a competition export JSON file and import it asynchronously.
     *
     * Supports both:
     *   - multipart file upload:  POST with file field "export_file"
     *   - raw JSON body:          POST with Content-Type: application/json
     *
     * Returns immediately after queuing — actual import (including screenshot
     * decoding) runs in the background queue worker, avoiding nginx timeouts
     * on large payloads.
     *
     * Idempotent: re-importing the same competition updates existing records.
     */
    public function store(Request $request)
    {
        $this->authorize($request);

        // Parse payload — either uploaded file or raw JSON body
        if ($request->hasFile('export_file')) {
            $json = $request->file('export_file')->get();
        } else {
            $json = $request->getContent();
        }

        if (!$json) {
            return response()->json(['error' => 'Empty request body'], 422);
        }

        // Quick structural validation before queuing
        $payload = json_decode($json, true);
        if (!$payload || !isset($payload['competition'])) {
            return response()->json(['error' => 'Invalid export payload'], 422);
        }

        // Write to disk and queue — avoids nginx fastcgi_read_timeout for large payloads
        $filename = 'victory-games-imports/' . now()->format('Y-m-d_His') . '_' . uniqid() . '.json';
        Storage::disk('local')->put($filename, $json);

        VictoryGamesImportJob::dispatch($filename);

        return response()->json([
            'ok'          => true,
            'queued'      => true,
            'competition' => $payload['competition']['name'] ?? null,
            'entries'     => count($payload['entries'] ?? []),
        ]);
    }

    private function authorize(Request $request): void
    {
        // Bearer token path (for server-to-server exports)
        $token = config('app.admin_import_token');
        if ($token) {
            $bearer = $request->bearerToken();
            if ($bearer && hash_equals($token, $bearer)) {
                return;
            }
        }

        // Session path (logged-in admin via browser/admin UI)
        $user = $request->user();
        if ($user && $user->is_admin) {
            return;
        }

        abort(401, 'Unauthorized.');
    }
}
