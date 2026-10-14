<?php

return [
    'enabled' => filter_var(env('DESATARA_DEMO_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
    'admin_password' => env('DESATARA_DEMO_ADMIN_PASSWORD'),
];
