<?php

namespace App\Actions\Employee;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProcessFaceCaptureAction
{
    public function execute(array $data, ?Model $record = null): Model
    {
        return DB::transaction(function () use ($data, $record) {

            // 1. Procesar el payload de la cara si existe
            if (!empty($data['face_payload'])) {
                $payload = is_string($data['face_payload']) ? json_decode($data['face_payload'], true) : $data['face_payload'];

                abort_if(
                    !is_array($payload) ||
                        empty($payload['photo']) ||
                        !is_array($payload['descriptor'] ?? null) ||
                        count($payload['descriptor']) !== 128,
                    422,
                    'Datos faciales inválidos'
                );

                // Decodificar y guardar la foto física en el storage
                abort_if(!is_string($payload['photo']), 422, 'Imagen inválida');
                $imageData = preg_replace('#^data:image/\w+;base64,#', '', $payload['photo']);
                $binary = base64_decode($imageData, true);
                abort_if($binary === false, 422, 'Imagen inválida');

                $tenantId = filament()->getTenant()?->id ?? request()->user()?->tenant_id ?? 1;
                $path = "faces/{$tenantId}/" . Str::uuid() . '.jpg';

                abort_unless(Storage::disk('local')->put($path, $binary), 500, 'No se pudo guardar la imagen');

                // Asignar los campos reales de la tabla
                $data['face_photo_path'] = $path;
                $data['face_descriptor'] = array_map('floatval', $payload['descriptor']);
            }

            // Limpiar el campo temporal de la interfaz
            unset($data['face_payload']);

            // 2. Persistir directamente en la base de datos dentro de la transacción
            if ($record) {
                $record->update($data);
                return $record;
            }

            return Employee::create($data);
        });
    }
}
