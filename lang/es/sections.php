<?php

return [
    "basic_information" =>[
        "title" => "Información Básica",
        "description" => "Datos generales de la oferta del plan",
    ],

    "pricing_and_billing" => [
        "title" => "Precios y Facturación",
        "description" => "Detalles de precios y facturación del plan",
    ],

    'subscription_info' => [
        'title' => 'Información de la Suscripción',
        'description' => 'Empresa, plan asignado y estado de la suscripción',
        ],
    
    'validity_cancellation' => [
        'title' => 'Vigencia y Cancelación',
        'description' => 'Fechas de inicio, vencimiento y cancelación de la suscripción',
    ],

    'MRR' => [
        'title' => 'MRR (Ingresos Mensuales Recurrentes)',
        'description' => 'Recurrencia estimada en Soles',
    ],

    'active_tenants' => [
        'title' => 'Tenants Activos',
        'description' => 'Empresas registradas en la plataforma',
    ],

    'upcoming_expirations' => [
        'title' => 'Suscripciones por Vencer',
        'description' => 'Vencen en los próximos 7 días',
    ],

    'trial_conversions' => [
        'title' => 'Conversión de Trial',
        'description' => 'Clientes que pasaron a planes de pago',
    ],

    'cancellation_rate' => [
        'title' => 'Tasa de Cancelación',
        'description' => 'Pérdida de clientes vs activos',
    ],

    'lifetime_value' => [
        'title' => 'Valor de Vida del Cliente (LTV)',
        'description' => 'Ingreso proyectado por cada empresa',
    ],

    "helpers" => [
        "status_plan_helper"=>"Los planes inactivos no se mostrarán como opción de compra para nuevos Tenants"
    ],

    "placeholder" => [
        "not_assigned" => "No asignado"
    ]

];