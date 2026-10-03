<?php

return [
    'readiness' => [
        'tls_evidence' => env('DESATARA_TLS_EVIDENCE'),
        'backup_evidence' => env('DESATARA_BACKUP_EVIDENCE'),
        'restore_evidence' => env('DESATARA_RESTORE_EVIDENCE'),
        'queue_evidence' => env('DESATARA_QUEUE_EVIDENCE'),
        'scheduler_evidence' => env('DESATARA_SCHEDULER_EVIDENCE'),
    ],
];
