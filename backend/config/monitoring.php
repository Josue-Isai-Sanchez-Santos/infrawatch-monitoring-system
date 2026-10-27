<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Retención del historial de monitoreo
    |--------------------------------------------------------------------------
    |
    | Número de días durante los cuales InfraWatch conserva
    | métricas de hosts y comprobaciones de servicios.
    |
    | Las alertas se conservan independientemente.
    |
    */

    'retention_days' => (int) env(
        'MONITORING_HISTORY_RETENTION_DAYS',
        30
    ),
];
