<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use ReflectionClass;
use ReflectionMethod;
use SplFileInfo;

class GeneratePermissionsJson extends Command
{
    protected $signature = 'permissions:generate-json';
    protected $description = 'Escanea app/Models y genera un permissions.json plano basado en los Policies';

    public function handle(): void
    {
        $modelsPath = app_path('Models');
        $flatPermissions = [];

        // Mapeo de método del Policy -> Prefijo del Permiso
        $methodToPrefix = [
            // --- CRUD Básico ---
                'viewAny'             => 'ViewAny',
                'view'                => 'View',
                'create'              => 'Create',
                'update'              => 'Update',
                'delete'              => 'Delete',

                // --- Borrado Masivo (Bulk Actions) ---
                'deleteAny'           => 'DeleteAny',

                // --- Soft Deletes (Papelera) ---
                'restore'             => 'Restore',
                'restoreAny'          => 'RestoreAny',
                'forceDelete'         => 'ForceDelete',
                'forceDeleteAny'      => 'ForceDeleteAny',

                // --- Acciones de Filament Adicionales / Relaciones ---
                'replicate'           => 'Replicate',
                'reorder'             => 'Reorder',
                'attach'              => 'Attach',
                'attachAny'           => 'AttachAny',
                'detach'              => 'Detach',
                'detachAny'           => 'DetachAny',

                // --- Acciones de Negocio / Auditoría Comunes ---
                'import'              => 'Import',
                'export'              => 'Export',
                'audit'               => 'Audit',
                'approve'             => 'Approve',
        ];

        // 1. Escanear automáticamente todos los archivos en app/Models
        $files = File::allFiles($modelsPath);

        foreach ($files as $file) {
            // Resolver el FQCN (Fully Qualified Class Name) del modelo
            $relativeClass = $file->getRelativePathname();
            $modelClass = 'App\\Models\\' . str_replace(['/', '.php'], ['\\', ''], $relativeClass);

            // Verificar si la clase existe y es un modelo válido
            if (!class_exists($modelClass)) {
                continue;
            }

            $permissionSuffix = class_basename($modelClass);

            // 2. Obtener el Policy asociado al modelo
            $policyClass = Gate::getPolicyFor($modelClass);

            if (!$policyClass) {
                continue; // Si el modelo no tiene Policy, lo ignoramos
            }

            // 3. Inspeccionar métodos públicos del Policy
            $reflection = new ReflectionClass($policyClass);
            $methods = array_map(
                fn (ReflectionMethod $m) => $m->getName(),
                $reflection->getMethods(ReflectionMethod::IS_PUBLIC)
            );

            // 4. Generar los permisos basados en los métodos existentes
            foreach ($methodToPrefix as $method => $prefix) {
                if (in_array($method, $methods)) {
                    $flatPermissions[] = [
                        'name'       => "{$prefix}:{$permissionSuffix}",
                        'guard_name' => 'web',
                    ];
                }
            }
        }

        // 5. Ordenar alfabéticamente por 'name'
        usort($flatPermissions, fn ($a, $b) => strcmp($a['name'], $b['name']));

        // 6. Guardar en el archivo JSON
        $directory = database_path('data');
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $filePath = database_path('seeders/json/permissions.json');
        File::put($filePath, json_encode($flatPermissions, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->info("¡Archivo permissions.json generado automáticamente con " . count($flatPermissions) . " permisos!");
    }
}