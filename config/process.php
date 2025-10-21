<?php

return [
    'levels' => ['L1', 'L2', 'L3', 'L4'],
    'default_level' => 'L3',
    'autosave_interval_seconds' => 5,
    'export' => [
        'pdf_font' => 'dejavusans',
        'pdf_temp_path' => storage_path('app/tmp/pdf'),
    ],
];
