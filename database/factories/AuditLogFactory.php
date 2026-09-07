<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Al azar elegimos un tipo de tabla afectada
        $entidades = ['App\Models\Tenant', 'App\Models\Subscription', 'App\Models\User', 'App\Models\Plan'];
        $entidadSeleccionada = fake()->randomElement($entidades);

        // Simulamos acciones típicas del sistema
        $acciones = ['created', 'updated', 'deleted', 'login', 'logout'];
        $accionSeleccionada = fake()->randomElement($acciones);

        // Generamos un historial de cambios falso si la acción es "updated"
        $oldValues = null;
        $newValues = null;

        if ($accionSeleccionada === 'updated') {
            $oldValues = json_encode(['status' => 'trial', 'updated_at' => now()->subDays(5)->toDateTimeString()]);
            $newValues = json_encode(['status' => 'active', 'updated_at' => now()->toDateTimeString()]);
        } elseif ($accionSeleccionada === 'created') {
            $newValues = json_encode(['name' => fake()->word(), 'created_at' => now()->toDateTimeString()]);
        }

        return [
            // Jalamos un Tenant y un Usuario aleatorio de los que YA creamos a mano
            'tenant_id' => Tenant::inRandomOrder()->first()?->id, 
            'user_id' => User::inRandomOrder()->first()?->id, 
            
            'action' => $accionSeleccionada,
            'entity_type' => $entidadSeleccionada,
            'entity_id' => fake()->numberBetween(1, 20), // IDs simulados de las filas afectadas
            
            'old_values' => $oldValues,
            'new_values' => $newValues,
            
            'ip_address' => fake()->ipv4(), // Genera IPs reales como "192.168.1.1"
            'user_agent' => fake()->userAgent(), // Genera navegadores reales como Chrome, Firefox, etc.
            
            // Registros de tiempo distribuidos en los últimos 3 meses
            'created_at' => fake()->dateTimeBetween('-3 months', 'now'),
        ];
    }
}
