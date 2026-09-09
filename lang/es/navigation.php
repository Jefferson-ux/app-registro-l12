<?php

// Ubicación: lang/es/navigation.php
// Uso: return __('navigation.employees'); dentro de getNavigationLabel()
// o ->navigationGroup(__('navigation.group_hr'))

return [

    // Grupos de navegación
    'hr' => 'Recursos Humanos',
    'attendance' => 'Asistencia',
    'business' => 'Negocio',
    'finance' => 'Finanzas',
    'settings' => 'Configuración',
    'security' => 'Seguridad',
    'reports' => 'Reportes',

    'multitenancy' => 'Multitenencia',
    'roles and_permissions' => 'Roles y permisos',
    'plans and_subscriptions' => 'Planes y suscripciones',
    'administration'=>'Administración',
    'access_control'=>'Control de acceso',

    // Labels de Resources — RRHH / estructura
    'dashboard' => 'Dashboard',
    'employees' => 'Empleados',
    'branches' => 'Sucursales',
    'departments' => 'Departamentos',
    'positions' => 'Cargos',

    // Labels de Resources — Asistencia
    'attendance_records' => 'Registros de asistencia',
    'attendance_sessions' => 'Sesiones de asistencia',
    'attendance_incidents' => 'Incidencias de asistencia',
    'work_schedules' => 'Turnos de trabajo',
    'work_schedule_days' => 'Días de turno',
    'employee_schedules' => 'Horarios de empleados',
    'holidays' => 'Feriados',

    // Labels de Resources — SaaS / negocio
    'tenants' => 'Empresas',
    'plans' => 'Planes',
    'subscriptions' => 'Suscripciones',

    // Labels de Resources — Seguridad / sistema
    'users' => 'Usuarios',
    'roles' => 'Roles',
    'permissions' => 'Permisos',
    'audit_logs' => 'Registros de auditoría',
    'configurations' => 'Configuraciones',

    // Reportes
    'attendance_report' => 'Reporte de asistencia',
    'payroll_report' => 'Reporte de planilla',

];
