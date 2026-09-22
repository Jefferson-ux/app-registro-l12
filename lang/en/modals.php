<?php

return [
    'trash' => [
        'heading'     => 'Move record to trash?',
        'description' => 'The record will no longer be visible, but it can be restored later.',
    ],
    'force_delete' => [
        'heading'     => 'Delete permanently?',
        'description' => 'This action CANNOT be undone. It will be permanently removed from the database.',
    ],
    'restore' => [
        'heading'     => 'Restore record?',
        'description' => 'The record will become active and visible in the system again.',
    ],
    'bulk' => [
        'trash' => [
            'heading'     => 'Move selected records to trash?',
            'description' => 'The selected records will no longer be visible, but they can be restored later.',
        ],
        'force_delete' => [
            'heading'     => 'Delete selected records permanently?',
            'description' => 'This action CANNOT be undone. The selected records will be permanently removed from the database.',
        ],
        'restore' => [
            'heading'     => 'Restore selected records?',
            'description' => 'The selected records will become active and visible in the system again.',
        ],
    ],
];