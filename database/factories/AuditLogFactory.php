<?php

namespace Database\Factories;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        $entidades = ['App\Models\Tenant', 'App\Models\Subscription', 'App\Models\User', 'App\Models\Plan'];
        $acciones  = ['created', 'updated', 'deleted', 'login', 'logout'];

        $accion = $acciones[array_rand($acciones)];
        $entidad = $entidades[array_rand($entidades)];

        $oldValues = null;
        $newValues = null;

        if ($accion === 'updated') {
            $oldValues = json_encode(['status' => 'trial', 'updated_at' => now()->subDays(5)->toDateTimeString()]);
            $newValues = json_encode(['status' => 'active', 'updated_at' => now()->toDateTimeString()]);
        } elseif ($accion === 'created') {
            $newValues = json_encode(['name' => fake()->word(), 'created_at' => now()->toDateTimeString()]);
        }

        return [
            'tenant_id'   => null, // Se inyecta desde el Seeder
            'user_id'     => null, // Se inyecta desde el Seeder
            'action'      => $accion,
            'entity_type' => $entidad,
            'entity_id'   => rand(1, 20),
            'old_values'  => $oldValues,
            'new_values'  => $newValues,
            'ip_address'  => fake()->ipv4(),
            'user_agent'  => fake()->userAgent(),
            'created_at'  => fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d H:i:s'),
        ];
    }
}
