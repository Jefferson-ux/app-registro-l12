<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConnectKioskRequest;
use App\Http\Resources\KioskResource;
use App\Models\Device;

class AuthKioskController extends Controller
{
    public function connect(ConnectKioskRequest $request)
    {
        /** @var Device $device */
        $device = $request->user();
        $identifier = $request->validated('device_identifier');

        if ($device->is_active
            && $device->device_identifier !== null
            && $device->device_identifier !== $identifier) {
            return response()->json([
                'success' => false,
                'message' => 'El kiosco ya está conectado con otro dispositivo.',
            ], 409);
        }

        $device->latitude = $request->validated('latitude');
        $device->longitude = $request->validated('longitude');
        $device->device_identifier = $identifier;
        $device->is_active = true;
        $device->save();

        return response()->json([
            'success' => true,
            'data' => new KioskResource($device),
        ], 200);
    }

    public function disconnect(): \Illuminate\Http\JsonResponse
    {
        /** @var Device $device */
        $device = request()->user();
        $device->disconnect();

        return response()->json([
            'success' => true,
            'message' => 'Kiosco desconectado.',
        ], 200);
    }
}
