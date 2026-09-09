<?php

// Ubicación: lang/es/fields.php
// Uso: ->label(__('fields.check_in_time'))

return [

    // ===== Identidad / usuario general =====
    'name' => 'Nombre',
    'first_name' => 'Nombres',
    'last_name' => 'Apellidos',
    'full_name' => 'Nombre completo',
    'email' => 'Correo electrónico',
    'email_verified_at' => 'Correo verificado el',
    'phone' => 'Teléfono',
    'mobile' => 'Celular',
    'password' => 'Contraseña',
    'password_confirmation' => 'Confirmar contraseña',
    'username' => 'Usuario',
    'avatar' => 'Foto de perfil',
    'last_login_at' => 'Último acceso',
    'user'=>'Usuario',

    // ===== Ubicación =====
    'country' => 'País',
    'state' => 'Departamento',
    'province' => 'Provincia',
    'city' => 'Ciudad',
    'district' => 'Distrito',
    'address' => 'Dirección',
    'zip_code' => 'Código postal',
    'postal_code' => 'Código postal',
    'timezone' => 'Zona horaria',
    'latitude' => 'Latitud',
    'longitude' => 'Longitud',
    'geofence_radius' => 'Radio de geocerca',

    // ===== Identificación / negocio (Perú) =====
    'ruc' => 'RUC',
    'dni' => 'DNI',
    'document_number' => 'Número de documento',
    'document_type' => 'Tipo de documento',
    'company' => 'Empresa',
    'company_name' => 'Razón social',
    'business_name' => 'Razón social',
    'trade_name' => 'Nombre comercial',
    'tax_id' => 'RUC / Identificación tributaria',
    'legal_representative' => 'Representante legal',

    // ===== Estado / control genérico =====
    'status' => 'Estado',
    'active' => 'Activo',
    'inactive' => 'Inactivo',
    'enabled' => 'Habilitado',
    'disabled' => 'Deshabilitado',
    'is_active' => 'Activo',
    'visible' => 'Visible',
    'featured' => 'Destacado',
    'verified' => 'Verificado',
    'pending' => 'Pendiente',
    'approved' => 'Aprobado',
    'rejected' => 'Rechazado',
    'archived' => 'Archivado',
    'resolved' => 'Resuelto',
    'justified' => 'Justificado',
    'unjustified' => 'Injustificado',

    // ===== Fechas / tiempo genérico =====
    'created_at' => 'Fecha de creación',
    'updated_at' => 'Fecha de actualización',
    'deleted_at' => 'Fecha de eliminación',
    'date' => 'Fecha',
    'start_date' => 'Fecha de inicio',
    'end_date' => 'Fecha de fin',
    'due_date' => 'Fecha de vencimiento',
    'birth_date' => 'Fecha de nacimiento',
    'hire_date' => 'Fecha de contratación',
    'termination_date' => 'Fecha de cese',
    'expires_at' => 'Fecha de expiración',
    'renewed_at' => 'Fecha de renovación',

    // ===== Finanzas / SaaS =====
    'price' => 'Precio',
    'amount' => 'Monto',
    'total' => 'Total',
    'subtotal' => 'Subtotal',
    'tax' => 'Impuesto',
    'discount' => 'Descuento',
    'currency' => 'Moneda',
    'payment_method' => 'Método de pago',
    'invoice' => 'Factura',
    'invoice_number' => 'Número de factura',
    'plan' => 'Plan',
    'plans' => 'Planes',
    'plan_name' => 'Nombre del plan',
    'plan_status' => 'Estado del plan',
    'subscription' => 'Suscripción',
    'trial_ends_at' => 'Fin de periodo de prueba',
    'billing_cycle' => 'Ciclo de facturación',
    'next_billing_date' => 'Próxima fecha de cobro',
    'balance' => 'Saldo',
    'starts_at' => 'Fecha de inicio',
    'ends_at' => 'Fecha de fin',
    'cancelled_at' => 'Fecha de cancelación',

    // ===== Multitenancy =====
    'tenant' => 'Tenant/Empresa',
    'tenant_id' => 'ID de empresa',
    'tenants' => 'Tenants/Empresas',
    'max_users' => 'Máximo de usuarios',
    'max_employees' => 'Máximo de empleados',
    'max_branches' => 'Máximo de sucursales',
    'max_storage' => 'Almacenamiento máximo',

    // ===== Roles y permisos =====
    'role' => 'Rol',
    'roles' => 'Roles',
    'permission' => 'Permiso',
    'permissions' => 'Permisos',
    'guard_name' => 'Tipo de acceso',

    // ===== Employee (Empleado) =====
    'employee_code' => 'Código de empleado',
    'department' => 'Departamento',
    'branch' => 'Sucursal',
    'position' => 'Cargo',
    'supervisor' => 'Supervisor',
    'salary' => 'Salario',
    'contract_type' => 'Tipo de contrato',
    'gender' => 'Género',
    'marital_status' => 'Estado civil',
    'emergency_contact' => 'Contacto de emergencia',
    'emergency_phone' => 'Teléfono de emergencia',

    // ===== Branch (Sucursal) =====
    'branch_name' => 'Nombre de sucursal',
    'branch_code' => 'Código de sucursal',
    'manager' => 'Encargado',

    // ===== Department (Departamento) =====
    'department_name' => 'Nombre del departamento',
    'department_code' => 'Código del departamento',
    'parent_department' => 'Departamento superior',

    // ===== Position (Cargo) =====
    'position_name' => 'Nombre del cargo',
    'position_level' => 'Nivel del cargo',
    'base_salary' => 'Salario base',

    // ===== WorkSchedule / WorkScheduleDay (Turnos) =====
    'schedule_name' => 'Nombre del turno',
    'shift_start' => 'Hora de inicio',
    'shift_end' => 'Hora de fin',
    'break_start' => 'Inicio de descanso',
    'break_end' => 'Fin de descanso',
    'tolerance_minutes' => 'Minutos de tolerancia',
    'day_of_week' => 'Día de la semana',
    'is_working_day' => 'Es día laborable',
    'monday' => 'Lunes',
    'tuesday' => 'Martes',
    'wednesday' => 'Miércoles',
    'thursday' => 'Jueves',
    'friday' => 'Viernes',
    'saturday' => 'Sábado',
    'sunday' => 'Domingo',

    // ===== EmployeeSchedule (asignación de turno a empleado) =====
    'assigned_schedule' => 'Turno asignado',
    'effective_from' => 'Vigente desde',
    'effective_to' => 'Vigente hasta',

    // ===== AttendanceSession / AttendanceRecord (Asistencia) =====
    'check_in_time' => 'Hora de entrada',
    'check_out_time' => 'Hora de salida',
    'worked_hours' => 'Horas trabajadas',
    'overtime_hours' => 'Horas extra',
    'late_minutes' => 'Minutos de tardanza',
    'early_leave_minutes' => 'Minutos de salida anticipada',
    'session_type' => 'Tipo de sesión',
    'device' => 'Dispositivo',
    'ip_address' => 'Dirección IP',
    'photo' => 'Fotografía',
    'location' => 'Ubicación',

    // ===== AttendanceIncident (Incidencias) =====
    'incident_type' => 'Tipo de incidencia',
    'incident_date' => 'Fecha de incidencia',
    'reason' => 'Motivo',
    'evidence' => 'Evidencia',
    'reviewed_by' => 'Revisado por',
    'reviewed_at' => 'Fecha de revisión',

    // ===== Holiday (Feriados) =====
    'holiday_name' => 'Nombre del feriado',
    'holiday_date' => 'Fecha del feriado',
    'is_recurring' => 'Es recurrente',
    'applies_to_all_branches' => 'Aplica a todas las sucursales',

    // ===== AuditLog (Auditoría) =====
    'audit_log' => 'Registro de auditoría',
    'audit_logs' => 'Registros de auditoría',
    'action' => 'Acción',
    'performed_by' => 'Realizado por',
    'ip' => 'Dirección IP',
    'user_agent' => 'Navegador / Dispositivo',
    'old_values' => 'Valores anteriores',
    'new_values' => 'Valores nuevos',
    'model_affected' => 'Registro afectado',
    'entity_type' => 'Tipo de entidad',
    'entity_id' => 'ID de entidad',

    // ===== Configuration (Configuraciones) =====
    'config_key' => 'Clave de configuración',
    'config_value' => 'Valor',

    // ===== Logs (Registros) =====
    'log_level' => 'Nivel de registro',


    // ===== Descripciones / contenido genérico =====
    'description' => 'Descripción',
    'notes' => 'Notas',
    'observations' => 'Observaciones',
    'title' => 'Título',
    'content' => 'Contenido',
    'image' => 'Imagen',
    'logo' => 'Logo',
    'file' => 'Archivo',
    'attachment' => 'Adjunto',
    'category' => 'Categoría',
    'tags' => 'Etiquetas',
    'slug' => 'SLUG',

    // ===== Cantidades / inventario =====
    'quantity' => 'Cantidad',
    'stock' => 'Stock',
    'unit' => 'Unidad',
    'sku' => 'Código (SKU)',
    'weight' => 'Peso',

    // ===== Interfaz general =====
    'actions' => 'Acciones',
    'options' => 'Opciones',
    'summary' => 'Resumen',
    'details' => 'Detalles',
    'settings' => 'Configuración',
    'preferences' => 'Preferencias',
    'language' => 'Idioma',
    'notifications' => 'Notificaciones',

    // ===== Otros campos genéricos =====
    'code' => 'Código',
    'reference' => 'Referencia',
    'all' => 'Todos',
    'search' => 'Buscar',
    'none' => 'Ninguno',
    'select' => 'Seleccionar',
    'select_all' => 'Seleccionar todo',
    'deselect_all' => 'Deseleccionar todo',
    'choose' => 'Elegir',
    'upload' => 'Subir',
    'download' => 'Descargar',
    'load' => 'Cargar',
    'load_more' => 'Cargar más',
    'reset' => 'Restablecer',
    'confirm' => 'Confirmar',
    'cancel' => 'Cancelar',
    'yes' => 'Sí',
    'no' => 'No',

];
