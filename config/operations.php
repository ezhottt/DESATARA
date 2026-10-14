<?php

return [
    'readiness' => [
        'tls_evidence' => env('DESATARA_TLS_EVIDENCE'),
        'backup_evidence' => env('DESATARA_BACKUP_EVIDENCE'),
        'restore_evidence' => env('DESATARA_RESTORE_EVIDENCE'),
        'queue_evidence' => env('DESATARA_QUEUE_EVIDENCE'),
        'scheduler_evidence' => env('DESATARA_SCHEDULER_EVIDENCE'),
        'malware_evidence' => env('DESATARA_MALWARE_EVIDENCE'),
        'proxy_evidence' => env('DESATARA_PROXY_EVIDENCE'),
        'monitoring_evidence' => env('DESATARA_MONITORING_EVIDENCE'),
        'alerting_evidence' => env('DESATARA_ALERTING_EVIDENCE'),
    ],

    'malware_scanner' => [
        'driver' => env('MALWARE_SCANNER_DRIVER', 'none'),
        'clamav_binary' => env('CLAMAV_BINARY', 'clamscan'),
        'windows_defender_binary' => env('WINDOWS_DEFENDER_BINARY'),
        'timeout_seconds' => (int) env('MALWARE_SCANNER_TIMEOUT', 120),
    ],
];
