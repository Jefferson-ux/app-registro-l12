<?php

namespace App\Http\Controllers;

use App\Http\Requests\FaceMarkRequest;
use App\Services\AttendanceService;
use App\Services\FaceMatcher;
use Illuminate\Support\Facades\Log;

class FaceAttendanceController extends Controller
{
    public function markByFace(
        FaceMarkRequest $request,
        FaceMatcher $matcher,
        AttendanceService $service
    ) {
        $match = $matcher->match($request->input('face_descriptor'));

        if (!$match) {
            Log::warning('Marcación facial: rostro no reconocido', ['ip' => $request->ip()]);

            return response()->json([
                'status'  => 404,
                'success' => false,
                'message' => 'Rostro no reconocido',
            ], 404);
        }

        // Log para auditar falsos positivos y calibrar el umbral
        Log::info('Marcación facial', [
            'employee_id' => $match['employee']->id,
            'distance'    => round($match['distance'], 4),
        ]);

        return $service->mark(
            $match['employee'],
            null,                              // el tipo lo decide el servidor (next_action)
            $request->input('request_id')
        );
    }
}