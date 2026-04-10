<?php

namespace App\Http\Controllers\VictoryGames;

use App\Http\Controllers\Controller;
use App\Models\VictoryGamesCertificate;
use App\Models\VictoryGamesVictor;
use Barryvdh\DomPDF\Facade\Pdf;
use Inertia\Inertia;

class CertificateController extends Controller
{
    /** Authenticated page: see all your certificates. */
    public function index()
    {
        $victorIds = VictoryGamesVictor::where('user_id', auth()->id())->pluck('id');

        $certificates = $victorIds->isNotEmpty()
            ? VictoryGamesCertificate::whereIn('victor_id', $victorIds)
                ->with(['victor', 'competition'])
                ->orderByDesc('issued_at')
                ->get()
                ->map(fn ($cert) => [
                    'id'               => $cert->id,
                    'certificate_type' => $cert->certificate_type,
                    'type_label'       => $cert->typeLabel(),
                    'download_token'   => $cert->download_token,
                    'issued_at'        => $cert->issued_at->toISOString(),
                    'victor'           => [
                        'slug'         => $cert->victor->slug,
                        'display_name' => $cert->victor->display_name,
                    ],
                    'competition'      => [
                        'id'      => $cert->competition->id,
                        'slug'    => $cert->competition->slug,
                        'name'    => $cert->competition->name,
                        'held_at' => $cert->competition->held_at?->toISOString(),
                    ],
                ])
            : collect();

        return Inertia::render('VictoryGames/Certificates', [
            'has_profile'  => $victorIds->isNotEmpty(),
            'certificates' => $certificates,
        ]);
    }

    /** Tokenized PDF download — no login required (for sharing). */
    public function download(string $token)
    {
        $cert = VictoryGamesCertificate::where('download_token', $token)
            ->with(['victor', 'competition'])
            ->firstOrFail();

        $pdf = Pdf::loadView('certificates.victory_games', [
            'cert'        => $cert,
            'victor'      => $cert->victor,
            'competition' => $cert->competition,
            'type_label'  => $cert->typeLabel(),
        ])->setPaper('a4', 'landscape');

        $filename = sprintf(
            'vibecode-victory-games-%s-%s.pdf',
            str($cert->competition->slug)->limit(30, ''),
            str($cert->certificate_type)->slug()
        );

        return $pdf->download($filename);
    }
}
