<?php

return [
    'trash' => [
        'heading'     => '¿Mover registro a la papelera?',
        'description' => 'El registro dejará de ser visible pero podrá ser recuperado posteriormente.',
    ],
    'force_delete' => [
        'heading'     => '¿Eliminar permanentemente?',
        'description' => 'Esta acción NO se puede deshacer. Se borrará directamente de la base de datos.',
    ],
    'restore' => [
        'heading'     => '¿Restaurar registro?',
        'description' => 'El registro volverá a estar activo y visible en el sistema.',
    ],
    'bulk' => [
        'trash' => [
            'heading'     => '¿Mover registros seleccionados a la papelera?',
            'description' => 'Los registros seleccionados dejarán de ser visibles pero podrán ser recuperados.',
        ],
        'force_delete' => [
            'heading'     => '¿Eliminar registros seleccionados permanentemente?',
            'description' => 'Esta acción NO se puede deshacer. Los registros seleccionados se borrarán de la base de datos.',
        ],
        'restore' => [
            'heading'     => '¿Restaurar registros seleccionados?',
            'description' => 'Los registros seleccionados volverán a estar activos en el sistema.',
        ],
    ],
];